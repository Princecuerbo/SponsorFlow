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
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ApprovalUploadController extends Controller
{
    use ResolvesModuleContext;

    public function store(StoreApprovalDocumentRequest $request, GeneratedBatch $generatedBatch): RedirectResponse
    {
        $sponsor = $this->sponsorOrganization($request);
        $this->assertCanActOnBatch($sponsor, $generatedBatch);
        $request->validate(['submission_version' => ['required', 'integer', 'min:1']]);
        $path = $request->file('approval_document')->store("sponsor-approvals/{$generatedBatch->id}", 'local');

        try {
            DB::transaction(function () use ($request, $generatedBatch, $sponsor, $path): void {
                $program = SponsorshipProgram::query()->lockForUpdate()->findOrFail($generatedBatch->sponsorship_program_id);
                $batch = GeneratedBatch::query()->lockForUpdate()->findOrFail($generatedBatch->id);
                $this->assertCanActOnBatch($sponsor, $batch);
                $batch->assertSubmittedRoster();
                $this->assertSubmissionVersion($request, $batch);
                $approval = $batch->sponsorApprovals()->create([
                    'sponsorship_program_id' => $program->id,
                    'submission_version' => $batch->submission_version,
                    'approval_document_path' => $path,
                    'confirmation_status' => ConfirmationStatus::Pending,
                    'uploaded_by_sponsor_id' => $this->actor($request)->id,
                ]);
                \App\Models\AuditLog::record('sponsor.approval.uploaded', 'sponsor_approvals', $request->user(), $request->ip(),
                    json_encode(['batch_id' => $batch->id, 'submission_version' => $batch->submission_version, 'approval_id' => $approval->id]));
            }, 3);
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }

        return redirect()->route('sponsor.lists.show', $generatedBatch)
            ->with('status', 'Evidence saved. Explicitly approve or reject the entire displayed batch.');
    }

    public function confirm(Request $request, GeneratedBatch $generatedBatch): RedirectResponse
    {
        $sponsor = $this->sponsorOrganization($request);
        $this->assertCanActOnBatch($sponsor, $generatedBatch);
        $request->validate([
            'submission_version' => ['required', 'integer', 'min:1'],
            'approval_id' => ['required', 'integer'],
        ]);

        $approvedApplications = DB::transaction(function () use ($generatedBatch, $sponsor, $request): array {
            $program = SponsorshipProgram::query()->lockForUpdate()->findOrFail($generatedBatch->sponsorship_program_id);
            $batch = GeneratedBatch::query()->lockForUpdate()->findOrFail($generatedBatch->id);
            $this->assertCanActOnBatch($sponsor, $batch);
            $this->assertSubmissionVersion($request, $batch);
            $batch->assertSubmittedRoster();
            $approval = $batch->sponsorApprovals()->where('submission_version', $batch->submission_version)
                ->where('sponsorship_program_id', $program->id)->lockForUpdate()->find($request->integer('approval_id'));
            if ($approval === null || ! $approval->isPending() || $approval->id !== $batch->currentApproval()?->id
                || blank($approval->approval_document_path) || $approval->uploaded_by_sponsor_id === null
                || ! Storage::disk('local')->exists($approval->approval_document_path)) {
                throw ValidationException::withMessages(['approval' => 'Select the current submission evidence before approving the whole batch.']);
            }

            $approvedApplications = $this->promoteMatchingApplications($batch, $program);
            $approval->update([
                'confirmation_status' => ConfirmationStatus::Confirmed,
                'decided_by_id' => $request->user()->id,
                'decided_at' => now(),
            ]);
            // Sponsor confirmation is not FASSG receipt or Accounting forwarding.
            $batch->update(['status' => GeneratedBatchStatus::Approved]);
            \App\Models\AuditLog::record('sponsor.approval.confirmed', 'sponsor_approvals', $request->user(), $request->ip(),
                json_encode(['batch_id' => $batch->id, 'submission_version' => $batch->submission_version,
                    'approval_id' => $approval->id, 'members' => $batch->submitted_roster['members']]));

            return $approvedApplications;
        }, 3);

        foreach ($approvedApplications as $application) {
            $application->studentProfile?->user?->notify(new ApplicationStatusUpdated($application, ApplicationStatus::Approved));
        }

        return back()->with('status', 'Entire batch approved. Awaiting FASSG receipt and forwarding to Accounting.');
    }

    public function reject(Request $request, GeneratedBatch $generatedBatch): RedirectResponse
    {
        $sponsor = $this->sponsorOrganization($request);
        $this->assertCanActOnBatch($sponsor, $generatedBatch);
        $request->validate(['submission_version' => ['required', 'integer', 'min:1']]);

        DB::transaction(function () use ($request, $generatedBatch, $sponsor): void {
            $program = SponsorshipProgram::query()->lockForUpdate()->findOrFail($generatedBatch->sponsorship_program_id);
            $batch = GeneratedBatch::query()->lockForUpdate()->findOrFail($generatedBatch->id);
            $this->assertCanActOnBatch($sponsor, $batch);
            $this->assertSubmissionVersion($request, $batch);
            $batch->assertSubmittedRoster();
            // A separate decision row preserves every previously uploaded document.
            $uploaded = $batch->currentApproval();
            $approval = $batch->sponsorApprovals()->create([
                'sponsorship_program_id' => $program->id,
                'submission_version' => $batch->submission_version,
                'approval_document_path' => $uploaded?->approval_document_path,
                'uploaded_by_sponsor_id' => $uploaded?->uploaded_by_sponsor_id,
                'confirmation_status' => ConfirmationStatus::Rejected,
                'decided_by_id' => $request->user()->id,
                'decided_at' => now(),
            ]);
            $batch->update(['status' => GeneratedBatchStatus::Rejected]);
            \App\Models\AuditLog::record('sponsor.approval.rejected', 'sponsor_approvals', $request->user(), $request->ip(),
                json_encode(['batch_id' => $batch->id, 'submission_version' => $batch->submission_version,
                    'approval_id' => $approval->id, 'members' => $batch->submitted_roster['members']]));
            // Batch rejection does not rewrite application qualification/approval history.
        }, 3);

        return back()->with('status', 'Entire batch rejected and returned to FASSG. Application qualification records were preserved.');
    }

    private function assertSubmissionVersion(Request $request, GeneratedBatch $batch): void
    {
        if ($request->integer('submission_version') !== $batch->submission_version) {
            throw ValidationException::withMessages(['batch' => 'This submission has changed. Reload the complete batch before deciding.']);
        }
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

    /** @return list<Application> */
    private function promoteMatchingApplications(GeneratedBatch $generatedBatch, SponsorshipProgram $program): array
    {
        $applicationIds = $generatedBatch->items()->orderBy('id')->lockForUpdate()->pluck('application_id');
        $applications = Application::query()->whereIn('id', $applicationIds)->orderBy('id')->lockForUpdate()->get();
        StudentProfile::query()->whereIn('id', $applications->pluck('student_profile_id'))
            ->orderBy('id')->lockForUpdate()->get();

        // Validate the entire roster before computing capacity or changing any member.
        $generatedBatch->assertEligibleMembers(false, true);
        $requiredSlots = $applications->count();
        $occupiedSlots = $program->applications()->approvedBeneficiaries()
            ->distinct('student_profile_id')->count('student_profile_id');
        $remainingSlots = max(0, min(
            $program->available_slots,
            (int) $program->getRawOriginal('available_slots'),
            max(0, $program->total_slots - $occupiedSlots),
        ));
        if ($requiredSlots > $remainingSlots) {
            throw ValidationException::withMessages([
                'approval' => 'This confirmation exceeds the remaining sponsorship capacity. No approval changes were saved.',
            ]);
        }

        foreach ($applications as $application) {
            $application->update(['status' => ApplicationStatus::Approved, 'approved_at' => now()]);
            $application->studentProfile->update(['active_sponsorship_id' => $application->id]);
            if (! $program->decrementAvailableSlot()) {
                throw ValidationException::withMessages([
                    'approval' => 'The program has no remaining sponsorship slots. No approval changes were saved.',
                ]);
            }
        }

        return $applications->all();
    }
}
