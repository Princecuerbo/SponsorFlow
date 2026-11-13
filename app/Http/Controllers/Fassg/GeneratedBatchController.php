<?php

namespace App\Http\Controllers\Fassg;

use App\Enums\GeneratedBatchStatus;
use App\Http\Controllers\Concerns\ResolvesModuleContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Fassg\VerifyApplicationRequest;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\BatchCandidate;
use App\Models\GeneratedBatch;
use App\Models\SponsorshipProgram;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class GeneratedBatchController extends Controller
{
    use ResolvesModuleContext;

    public function index(Request $request): View
    {
        $programId = $request->integer('sponsorship_program_id', 0);

        $lists = GeneratedBatch::query()
            ->with('sponsorshipProgram')
            ->withCount('items')
            ->when($programId > 0, fn($query) => $query->where('sponsorship_program_id', $programId))
            ->latest()
            ->get();

        return view('fassg.generated_batches.index', [
            'user' => $this->actor($request),
            'lists' => $lists,
            'fixedLists' => $lists,
            'batches' => $lists,
            'programs' => SponsorshipProgram::query()->orderBy('program_name')->get(),
            'selectedProgramId' => $programId,
        ]);
    }

    public function show(Request $request, GeneratedBatch $generatedBatch): View
    {
        $generatedBatch->load(['sponsorshipProgram', 'latestApproval', 'sponsorApprovals.decidedBy', 'sponsorApprovals.recordedBy', 'sponsorApprovals.forwardedBy', 'items.application.studentProfile.user']);

        $generatedBatch->setRelation(
            'items',
            $generatedBatch->items->sortBy(
                static fn($item) => $item->student_id_number,
            ),
        );

        return view('fassg.generated_batches.show', [
            'user' => $this->actor($request),
            'list' => $generatedBatch,
            'fixedList' => $generatedBatch,
            'currentApproval' => $generatedBatch->currentApproval(),
        ]);
    }

    public function destroy(Request $request, GeneratedBatch $generatedBatch): RedirectResponse
    {
        abort_unless($request->user()?->isFassg(), 403);
        DB::transaction(function () use ($generatedBatch): void {
            SponsorshipProgram::query()->lockForUpdate()->findOrFail($generatedBatch->sponsorship_program_id);
            $batch = GeneratedBatch::query()->lockForUpdate()->findOrFail($generatedBatch->id);
            abort_unless($batch->status === GeneratedBatchStatus::Saved && $batch->submission_version === 0
                && $batch->submitted_roster === null && $batch->fassg_assigned_at === null
                && $batch->fassg_assigned_by_id === null && ! $batch->sponsorApprovals()->exists(),
                403, 'Submitted roster or approval/forwarding history cannot be deleted.');
            $applicationIds = $batch->items()->pluck('application_id');
            $batch->items()->delete();
            $batch->delete();
            Application::query()->whereKey($applicationIds)->where('batch_id', $batch->id)
                ->update(['is_batched' => false, 'batch_id' => null]);
        }, 3);
        $this->audit($request, 'fassg.generated_batch.deleted', 'generated_batches');

        return redirect()->route('fassg.generated-batches.index')->with('status', 'Unsubmitted draft deleted.');
    }

    public function reviewApplication(Request $request, GeneratedBatch $generatedBatch, Application $application): View
    {
        abort_unless($request->user()?->isFassg(), 403);
        abort_unless((int) $application->sponsorship_program_id === (int) $generatedBatch->sponsorship_program_id
            && $generatedBatch->items()->where('application_id', $application->id)->exists(), 404);
        $application->load(['studentProfile.user', 'sponsorshipProgram.sponsor', 'documents']);

        return view('fassg.applications.show', [
            'user' => $this->actor($request),
            'application' => $application,
            'generatedBatch' => $generatedBatch,
            'eligibilityErrors' => $application->sponsorshipProgram->beneficiaryEligibilityErrors($application),
        ]);
    }

    public function removeCandidate(Request $request, GeneratedBatch $generatedBatch, BatchCandidate $batchCandidate): RedirectResponse
    {
        abort_unless($request->user()?->isFassg(), 403);
        DB::transaction(function () use ($request, $generatedBatch, $batchCandidate): void {
            SponsorshipProgram::query()->lockForUpdate()->findOrFail($generatedBatch->sponsorship_program_id);
            $batch = GeneratedBatch::query()->lockForUpdate()->findOrFail($generatedBatch->id);
            abort_unless($batch->status === GeneratedBatchStatus::Saved && $batch->submission_version === 0
                && $batch->submitted_roster === null && $batch->fassg_assigned_at === null
                && $batch->fassg_assigned_by_id === null && ! $batch->sponsorApprovals()->exists(),
                403, 'Only an unsubmitted draft without approval or forwarding history can be edited.');
            $item = $batch->items()->whereKey($batchCandidate->id)->lockForUpdate()->firstOrFail();
            $application = Application::query()->lockForUpdate()->findOrFail($item->application_id);
            abort_unless((int) $application->sponsorship_program_id === (int) $batch->sponsorship_program_id
                && (int) $application->batch_id === (int) $batch->id
                && $application->approved_at === null
                && in_array($application->status, [\App\Enums\ApplicationStatus::Pending, \App\Enums\ApplicationStatus::Verified], true),
                403, 'The member has a conflicting status or historical approval. Review the original records.');
            // The source list and processing application remain intact; audit the removed membership.
            AuditLog::record('fassg.generated_batch.candidate_removed', 'generated_batch_items', $request->user(), $request->ip(),
                json_encode(['batch_id' => $batch->id, 'before' => $item->getAttributes()]));
            $item->delete();
            if (! $batch->items()->where('application_id', $application->id)->exists()) {
                $application->update(['batch_id' => null, 'is_batched' => false]);
            }
            $batch->update(['total_slots' => $batch->items()->count()]);
        }, 3);

        return back()->with('status', 'Candidate removed from this draft only. Original source and verification evidence were retained.');
    }

    public function verifyApplication(VerifyApplicationRequest $request, GeneratedBatch $generatedBatch, Application $application): RedirectResponse
    {
        // The shared verifier locks and validates this route's batch and exact membership.
        return app(ApplicantVerificationController::class)->verify($request, $application);
    }

    public function submit(Request $request, GeneratedBatch $generatedBatch): RedirectResponse
    {
        DB::transaction(function () use ($request, $generatedBatch): void {
            $program = SponsorshipProgram::query()->lockForUpdate()->findOrFail($generatedBatch->sponsorship_program_id);
            $batch = GeneratedBatch::query()->lockForUpdate()->findOrFail($generatedBatch->id);
            abort_unless(in_array($batch->status, [GeneratedBatchStatus::Saved, GeneratedBatchStatus::Rejected], true), 403);
            if ($batch->status === GeneratedBatchStatus::Rejected) {
                $batch->assertSubmittedRoster();
                if ($batch->currentApproval()?->fassg_recorded_at === null) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['batch' => 'Record receipt of the Sponsor rejection before resubmitting the unchanged roster.']);
                }
            } elseif ($batch->submitted_roster !== null || $batch->submission_version !== 0) {
                abort(403, 'A previously submitted roster cannot be edited as a draft.');
            }
            $this->lockMembers($batch);
            $batch->assertEligibleMembers(false, true);
            $occupied = $program->applications()->approvedBeneficiaries()->distinct('student_profile_id')->count('student_profile_id');
            if ($batch->items()->count() > max(0, min($program->available_slots,
                (int) $program->getRawOriginal('available_slots'), $program->total_slots - $occupied))) {
                throw \Illuminate\Validation\ValidationException::withMessages(['batch' => 'The entire roster exceeds available capacity. No members were removed.']);
            }
            $snapshot = $batch->submitted_roster ?? [
                'program_id' => $program->id,
                'sponsor_id' => $program->sponsor_id,
                'submitted_by_id' => $request->user()->id,
                'submitted_at' => now()->toIso8601String(),
                'members' => $batch->rosterMembers(),
            ];
            $batch->update([
                'status' => GeneratedBatchStatus::Submitted,
                'submission_version' => $batch->submission_version + 1,
                'submitted_roster' => $snapshot,
            ]);
            \App\Models\AuditLog::record('fassg.generated_batch.submitted', 'generated_batches', $request->user(), $request->ip(),
                json_encode(['batch_id' => $batch->id, 'submission_version' => $batch->submission_version, 'roster' => $snapshot]));
        }, 3);

        return back()->with('status', 'Exact batch roster submitted for whole-batch Sponsor review.');
    }

    public function recordSponsorDecision(Request $request, GeneratedBatch $generatedBatch): RedirectResponse
    {
        abort_unless($request->user()?->isFassg(), 403);
        DB::transaction(function () use ($request, $generatedBatch): void {
            SponsorshipProgram::query()->lockForUpdate()->findOrFail($generatedBatch->sponsorship_program_id);
            $batch = GeneratedBatch::query()->lockForUpdate()->findOrFail($generatedBatch->id);
            $batch->assertSubmittedRoster();
            $approval = $this->decisionForAction($request, $batch);
            abort_unless(in_array($batch->status, [GeneratedBatchStatus::Approved, GeneratedBatchStatus::Rejected], true), 403);
            if ($approval->decided_by_id === null || $approval->decided_at === null
                || ($batch->status === GeneratedBatchStatus::Approved && ! $approval->hasConfirmationEvidence())
                || ($batch->status === GeneratedBatchStatus::Rejected && $approval->confirmation_status !== \App\Enums\ConfirmationStatus::Rejected)) {
                throw \Illuminate\Validation\ValidationException::withMessages(['approval' => 'A matching explicit Sponsor decision and its required evidence must be present.']);
            }
            if ($approval->fassg_recorded_at !== null) {
                return; // Repeat acknowledgement preserves the original officer and time.
            }
            $approval->update(['fassg_recorded_by_id' => $request->user()->id, 'fassg_recorded_at' => now()]);
            \App\Models\AuditLog::record('fassg.sponsor_decision.received', 'sponsor_approvals', $request->user(), $request->ip(),
                json_encode(['batch_id' => $batch->id, 'approval_id' => $approval->id, 'submission_version' => $batch->submission_version]));
        }, 3);

        return back()->with('status', 'Sponsor decision received and recorded by FASSG. No beneficiaries were forwarded.');
    }

    public function forwardToAccounting(Request $request, GeneratedBatch $generatedBatch): RedirectResponse
    {
        abort_unless($request->user()?->isFassg(), 403);
        DB::transaction(function () use ($request, $generatedBatch): void {
            $program = SponsorshipProgram::query()->lockForUpdate()->findOrFail($generatedBatch->sponsorship_program_id);
            $batch = GeneratedBatch::query()->lockForUpdate()->findOrFail($generatedBatch->id);
            abort_unless($batch->status === GeneratedBatchStatus::Approved, 403);
            $batch->assertSubmittedRoster();
            $approval = $this->decisionForAction($request, $batch);
            if (! $approval->hasConfirmationEvidence() || $approval->fassg_recorded_by_id === null || $approval->fassg_recorded_at === null
                || $approval->fassg_recorded_at->lt($approval->decided_at)) {
                throw \Illuminate\Validation\ValidationException::withMessages(['approval' => 'FASSG must record receipt of valid Sponsor confirmation first.']);
            }
            $this->lockMembers($batch);
            $batch->assertEligibleMembers(true, true);
            if ($program->applications()->approvedBeneficiaries()->distinct('student_profile_id')->count('student_profile_id') > $program->total_slots) {
                throw \Illuminate\Validation\ValidationException::withMessages(['batch' => 'Existing approvals exceed program capacity and require review.']);
            }
            if ($approval->accounting_forwarded_at !== null) {
                return;
            }
            $approval->update(['accounting_forwarded_by_id' => $request->user()->id, 'accounting_forwarded_at' => now()]);
            if ($batch->fassg_assigned_at === null && $batch->fassg_assigned_by_id === null) {
                $batch->update(['fassg_assigned_at' => $approval->accounting_forwarded_at, 'fassg_assigned_by_id' => $request->user()->id]);
            }
            \App\Models\AuditLog::record('fassg.beneficiaries.forwarded_to_accounting', 'sponsor_approvals', $request->user(), $request->ip(),
                json_encode(['batch_id' => $batch->id, 'approval_id' => $approval->id, 'members' => $batch->submitted_roster['members']]));
        }, 3);

        return back()->with('status', 'FASSG forwarded the approved, verified batch to Accounting.');
    }

    public function viewApproval(Request $request, GeneratedBatch $generatedBatch, \App\Models\SponsorApproval $sponsorApproval): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        abort_unless($request->user()?->isFassg()
            && (int) $sponsorApproval->generated_batch_id === (int) $generatedBatch->id
            && (int) $sponsorApproval->sponsorship_program_id === (int) $generatedBatch->sponsorship_program_id, 403);
        abort_if(blank($sponsorApproval->approval_document_path), 404);
        abort_unless(\Illuminate\Support\Facades\Storage::disk('local')->exists($sponsorApproval->approval_document_path), 404);

        return response()->file(\Illuminate\Support\Facades\Storage::disk('local')->path($sponsorApproval->approval_document_path));
    }

    private function lockMembers(GeneratedBatch $batch): void
    {
        $ids = $batch->items()->orderBy('id')->lockForUpdate()->pluck('application_id');
        $applications = Application::query()->whereKey($ids)->orderBy('id')->lockForUpdate()->get();
        \App\Models\StudentProfile::query()->whereKey($applications->pluck('student_profile_id'))->orderBy('id')->lockForUpdate()->get();
    }

    private function decisionForAction(Request $request, GeneratedBatch $batch): \App\Models\SponsorApproval
    {
        $request->validate(['approval_id' => ['required', 'integer'], 'submission_version' => ['required', 'integer', 'min:1']]);
        $approval = $batch->sponsorApprovals()->where('submission_version', $batch->submission_version)
            ->where('sponsorship_program_id', $batch->sponsorship_program_id)->lockForUpdate()->find($request->integer('approval_id'));
        if ($request->integer('submission_version') !== $batch->submission_version
            || $approval === null || $approval->id !== $batch->currentApproval()?->id) {
            throw \Illuminate\Validation\ValidationException::withMessages(['approval' => 'Reload and review the current Sponsor decision before this action.']);
        }

        return $approval;
    }
}
