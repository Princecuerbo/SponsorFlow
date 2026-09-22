<?php

namespace App\Http\Controllers\Fassg;

use App\Enums\ApplicationStatus;
use App\Enums\GeneratedBatchStatus;
use App\Http\Controllers\Concerns\ResolvesModuleContext;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\GeneratedBatch;
use App\Models\SponsorshipProgram;
use App\Notifications\ApplicationStatusUpdated;
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
            ->when($programId > 0, fn ($query) => $query->where('sponsorship_program_id', $programId))
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
        $generatedBatch->load(['sponsorshipProgram', 'latestApproval', 'items.application.studentProfile.user']);

        $generatedBatch->setRelation(
            'items',
            $generatedBatch->items->sortBy(
                static fn ($item) => $item->rank_position ?? PHP_FLOAT_MAX,
            ),
        );

        return view('fassg.generated_batches.show', [
            'user' => $this->actor($request),
            'list' => $generatedBatch,
            'fixedList' => $generatedBatch,
        ]);
    }

    public function destroy(Request $request, GeneratedBatch $generatedBatch): RedirectResponse
    {
        $this->assertListEditable($generatedBatch);

        DB::transaction(function () use ($generatedBatch): void {
            $applicationIds = $generatedBatch->items()->pluck('application_id');

            $generatedBatch->items()->delete();
            $generatedBatch->delete();

            if ($applicationIds->isNotEmpty()) {
                Application::query()
                    ->whereKey($applicationIds)
                    ->update([
                        'is_batched' => false,
                        'batch_id' => null,
                    ]);
            }
        });

        $this->audit($request, 'fassg.generated_batch.deleted', 'generated_batches');

        return redirect()
            ->route('fassg.generated-batches.index')
            ->with('status', 'Generated batch deleted successfully. All linked applications have been unbatched and returned to the queue.');
    }

    public function submit(Request $request, GeneratedBatch $generatedBatch): RedirectResponse
    {
        $this->assertListEditable($generatedBatch);

        $blockedStatuses = [
            ApplicationStatus::Pending,
            ApplicationStatus::ResubmissionRequested,
        ];

        $hasBlocked = $generatedBatch->items()
            ->whereHas('application', fn ($query) => $query->whereIn('status', $blockedStatuses))
            ->exists();

        if ($generatedBatch->items()->count() === 0 || $hasBlocked) {
            return back()->withErrors([
                'list' => 'Cannot submit batch to sponsor. All applications in the batch must be in verified status.',
            ]);
        }

        $messages = $generatedBatch->update(['status' => GeneratedBatchStatus::Submitted]);

        $this->audit($request, 'fassg.generated_batch.submitted', 'generated_batches');

        $this->notifyShortlistedStudents($generatedBatch);

        return back()->with('status', 'Generated batch submitted for sponsor confirmation.');
    }

    private function assertListEditable(GeneratedBatch $generatedBatch): void
    {
        abort_unless(
            in_array($generatedBatch->status, [GeneratedBatchStatus::Saved, GeneratedBatchStatus::Rejected], true),
            403,
            'This generated batch can no longer be edited.',
        );
    }

    private function notifyShortlistedStudents(GeneratedBatch $generatedBatch): void
    {
        $applications = Application::query()
            ->where('sponsorship_program_id', $generatedBatch->sponsorship_program_id)
            ->whereHas('batchCandidates', fn ($query) => $query->where('generated_batch_id', $generatedBatch->id))
            ->where('status', ApplicationStatus::Pending)
            ->with('studentProfile.user')
            ->get();

        foreach ($applications as $application) {
            $studentUser = $application->studentProfile->user ?? null;

            if ($studentUser !== null) {
                $studentUser->notify(new ApplicationStatusUpdated($application, 'Sponsor Reviewed'));
            }
        }
    }
}
