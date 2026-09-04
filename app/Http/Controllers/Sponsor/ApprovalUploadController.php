<?php

namespace App\Http\Controllers\Sponsor;

use App\Enums\ApplicationStatus;
use App\Enums\ConfirmationStatus;
use App\Enums\FixedListStatus;
use App\Http\Controllers\Concerns\ResolvesModuleContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sponsor\StoreApprovalDocumentRequest;
use App\Models\Application;
use App\Models\FixedList;
use App\Models\Sponsor;
use App\Models\SponsorApproval;
use App\Models\StudentProfile;
use App\Models\SponsorshipProgram;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ApprovalUploadController extends Controller
{
    use ResolvesModuleContext;

    public function store(StoreApprovalDocumentRequest $request, FixedList $fixedList): RedirectResponse
    {
        $sponsor = $this->sponsorOrganization($request);
        $this->assertCanActOnList($sponsor, $fixedList);

        $path = $request->file('approval_document')->store(
            "sponsor-approvals/{$fixedList->id}",
            'local',
        );

        $approval = SponsorApproval::query()->updateOrCreate(
            [
                'sponsorship_program_id' => $fixedList->sponsorship_program_id,
                'fixed_list_id' => $fixedList->id,
            ],
            [
                'approval_document_path' => $path,
                'confirmation_status' => ConfirmationStatus::Pending,
                'uploaded_by_sponsor_id' => $this->actor($request)->id,
            ],
        );

        $this->audit($request, 'sponsor.approval.uploaded', 'sponsor_approvals');

        return redirect()
            ->route('sponsor.lists.show', $fixedList)
            ->with('status', 'Signed approval document uploaded. Confirm the beneficiary list to finalize.');
    }

    public function confirm(Request $request, FixedList $fixedList): RedirectResponse
    {
        $sponsor = $this->sponsorOrganization($request);
        $this->assertCanActOnList($sponsor, $fixedList);

        $fixedList->load('latestApproval');
        $approval = $fixedList->latestApproval;

        if ($approval === null || blank($approval->approval_document_path)) {
            return back()->withErrors([
                'approval' => 'Upload a signed PDF or JPG approval document before confirming this list.',
            ]);
        }

        DB::transaction(function () use ($fixedList, $approval): void {
            $approval->update(['confirmation_status' => ConfirmationStatus::Confirmed]);
            $fixedList->update(['status' => FixedListStatus::Approved]);
            $program = SponsorshipProgram::query()
                ->lockForUpdate()
                ->findOrFail($fixedList->sponsorship_program_id);
            $this->promoteMatchingApplications($fixedList, $program);
        });

        $this->audit($request, 'sponsor.approval.confirmed', 'sponsor_approvals');

        return back()->with('status', "Beneficiary list {$fixedList->batch_name} confirmed.");
    }

    public function reject(Request $request, FixedList $fixedList): RedirectResponse
    {
        $sponsor = $this->sponsorOrganization($request);
        $this->assertCanActOnList($sponsor, $fixedList);

        DB::transaction(function () use ($request, $fixedList): void {
            SponsorApproval::query()->updateOrCreate(
                [
                    'sponsorship_program_id' => $fixedList->sponsorship_program_id,
                    'fixed_list_id' => $fixedList->id,
                ],
                [
                    'approval_document_path' => $fixedList->latestApproval?->approval_document_path ?? '',
                    'confirmation_status' => ConfirmationStatus::Rejected,
                    'uploaded_by_sponsor_id' => $this->actor($request)->id,
                ],
            );

            $fixedList->update(['status' => FixedListStatus::Rejected]);
        });

        $this->audit($request, 'sponsor.approval.rejected', 'sponsor_approvals');

        return back()->with('status', "Beneficiary list {$fixedList->batch_name} returned to FASSG.");
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
            'Content-Disposition' => 'inline; filename="' . basename($sponsorApproval->approval_document_path) . '"',
        ]);
    }

    private function assertCanActOnList(Sponsor $sponsor, FixedList $fixedList): void
    {
        $fixedList->loadMissing('sponsorshipProgram');

        abort_unless($sponsor->ownsProgram($fixedList->sponsorshipProgram), 403);
        abort_unless(
            $fixedList->isForwardedToSponsor(),
            403,
            'This list is not currently forwarded for sponsor confirmation.',
        );
    }

    private function promoteMatchingApplications(FixedList $fixedList, SponsorshipProgram $program): void
    {
        $studentIds = $fixedList->items()
            ->where('is_sle_fhe_verified', true)
            ->pluck('student_id_number')
            ->filter();

        if ($studentIds->isEmpty()) {
            return;
        }

        $applications = Application::query()
            ->where('sponsorship_program_id', $fixedList->sponsorship_program_id)
            ->whereHas('studentProfile', fn($query) => $query->whereIn('student_id_number', $studentIds))
            ->where('status', ApplicationStatus::Pending)
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

            $application->update([
                'status' => ApplicationStatus::Approved,
                'approved_at' => now(),
            ]);

            $profile->update(['active_sponsorship_id' => $application->id]);

            if (! $program->decrementAvailableSlot()) {
                $application->update([
                    'status' => ApplicationStatus::Pending,
                    'approved_at' => null,
                ]);
                $profile->update(['active_sponsorship_id' => null]);
                break;
            }
        }
    }
}
