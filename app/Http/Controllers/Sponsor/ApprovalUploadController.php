<?php

namespace App\Http\Controllers\Sponsor;

use App\Enums\ApplicationStatus;
use App\Enums\ConfirmationStatus;
use App\Enums\GeneratedBatchStatus;
use App\Http\Controllers\Concerns\ResolvesModuleContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sponsor\StoreApprovalDocumentRequest;
use App\Models\Application;
use App\Models\GeneratedBatch;
use App\Models\Sponsor;
use App\Models\SponsorApproval;
use App\Models\SponsorshipProgram;
use App\Models\StudentProfile;
use App\Notifications\ApplicationStatusUpdated;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ApprovalUploadController extends Controller
{
    use ResolvesModuleContext;

    public function store(StoreApprovalDocumentRequest $request, GeneratedBatch $generatedBatch): RedirectResponse
    {
        $sponsor = $this->sponsorOrganization($request);
        $this->assertCanActOnBatch($sponsor, $generatedBatch);

        $path = $request->file('approval_document')->store(
            "sponsor-approvals/{$generatedBatch->id}",
            'local',
        );

        $approval = SponsorApproval::query()->updateOrCreate(
            [
                'sponsorship_program_id' => $generatedBatch->sponsorship_program_id,
                'generated_batch_id' => $generatedBatch->id,
            ],
            [
                'approval_document_path' => $path,
                'confirmation_status' => ConfirmationStatus::Pending,
                'uploaded_by_sponsor_id' => $this->actor($request)->id,
            ],
        );

        $this->audit($request, 'sponsor.approval.uploaded', 'sponsor_approvals');

        return redirect()
            ->route('sponsor.lists.show', $generatedBatch)
            ->with('status', 'Signed approval document uploaded. Confirm the beneficiary list to finalize.');
    }

    public function confirm(Request $request, GeneratedBatch $generatedBatch): RedirectResponse
    {
        $sponsor = $this->sponsorOrganization($request);
        $this->assertCanActOnBatch($sponsor, $generatedBatch);

        $generatedBatch->load('latestApproval');
        $approval = $generatedBatch->latestApproval;

        if ($approval === null || blank($approval->approval_document_path)) {
            return back()->withErrors([
                'approval' => 'Upload a signed PDF or JPG approval document before confirming this batch.',
            ]);
        }

        DB::transaction(function () use ($generatedBatch, $approval, $request): void {
            $approval->update(['confirmation_status' => ConfirmationStatus::Confirmed]);

            // Automatic Accounting hand-off: stamp FASSG assignment on the batch
            // so confirmed beneficiaries appear in Accounting immediately without
            // requiring a manual post-sponsor assignment step.
            $generatedBatch->update([
                'status' => GeneratedBatchStatus::Approved,
                'fassg_assigned_at' => now(),
                'fassg_assigned_by_id' => $this->actor($request)->id,
            ]);

            $program = SponsorshipProgram::query()
                ->lockForUpdate()
                ->findOrFail($generatedBatch->sponsorship_program_id);
            $this->promoteMatchingApplications($generatedBatch, $program);
        });

        $this->audit($request, 'sponsor.approval.confirmed', 'sponsor_approvals');

        return back()->with('status', "Beneficiary batch {$generatedBatch->batch_name} confirmed.");
    }

    public function reject(Request $request, GeneratedBatch $generatedBatch): RedirectResponse
    {
        $sponsor = $this->sponsorOrganization($request);
        $this->assertCanActOnBatch($sponsor, $generatedBatch);

        DB::transaction(function () use ($request, $generatedBatch): void {
            SponsorApproval::query()->updateOrCreate(
                [
                    'sponsorship_program_id' => $generatedBatch->sponsorship_program_id,
                    'generated_batch_id' => $generatedBatch->id,
                ],
                [
                    'approval_document_path' => $generatedBatch->latestApproval?->approval_document_path ?? '',
                    'confirmation_status' => ConfirmationStatus::Rejected,
                    'uploaded_by_sponsor_id' => $this->actor($request)->id,
                ],
            );

            $generatedBatch->update(['status' => GeneratedBatchStatus::Rejected]);
        });

        $this->audit($request, 'sponsor.approval.rejected', 'sponsor_approvals');

        return back()->with('status', "Beneficiary batch {$generatedBatch->batch_name} returned to FASSG.");
    }

    public function download(Request $request, SponsorApproval $sponsorApproval): BinaryFileResponse
    {
        $sponsor = $this->sponsorOrganization($request);
        $sponsorApproval->load('sponsorshipProgram');

        abort_unless($sponsor->ownsProgram($sponsorApproval->sponsorshipProgram), 403);
        abort_if(blank($sponsorApproval->approval_document_path), 404);

        $path = Storage::disk('local')->path($sponsorApproval->approval_document_path);
        abort_unless(is_file($path), 404, 'Approval document not found.');

        return response()->file($path, [
            'Content-Disposition' => 'inline; filename="'.basename($sponsorApproval->approval_document_path).'"',
        ]);
    }

    private function assertCanActOnBatch(Sponsor $sponsor, GeneratedBatch $generatedBatch): void
    {
        $generatedBatch->loadMissing('sponsorshipProgram');

        abort_unless($sponsor->ownsProgram($generatedBatch->sponsorshipProgram), 403);
        abort_unless(
            $generatedBatch->isForwardedToSponsor(),
            403,
            'This batch is not currently forwarded for sponsor confirmation.',
        );
    }

    private function promoteMatchingApplications(GeneratedBatch $generatedBatch, SponsorshipProgram $program): void
    {
        $applicationIds = $generatedBatch->items()->pluck('application_id')->filter()->unique()->values();

        if ($applicationIds->isEmpty()) {
            return;
        }

        $applications = Application::query()
            ->where('sponsorship_program_id', $generatedBatch->sponsorship_program_id)
            ->whereIn('id', $applicationIds)
            ->whereIn('status', [ApplicationStatus::Pending, ApplicationStatus::Verified])
            ->with('studentProfile')
            ->get();

        $profiles = StudentProfile::query()
            ->whereIn('id', $applications->pluck('student_profile_id'))
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($applications as $application) {
            /** @var StudentProfile|null $profile */
            $profile = $profiles->get($application->student_profile_id);

            if ($profile === null || $profile->hasActiveSponsorship()) {
                continue;
            }

            $previousStatus = $application->status;
            $application->update([
                'status' => ApplicationStatus::Approved,
                'approved_at' => now(),
            ]);

            $profile->update(['active_sponsorship_id' => $application->id]);

            if (! $program->decrementAvailableSlot()) {
                $application->update([
                    'status' => $previousStatus,
                    'approved_at' => null,
                ]);
                $profile->update(['active_sponsorship_id' => null]);
                break;
            }

            $studentUser = $application->studentProfile->user ?? null;

            if ($studentUser !== null) {
                $studentUser->notify(new ApplicationStatusUpdated($application, ApplicationStatus::Approved));
            }
        }
    }
}
