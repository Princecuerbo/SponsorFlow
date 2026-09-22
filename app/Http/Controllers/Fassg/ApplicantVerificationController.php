<?php

namespace App\Http\Controllers\Fassg;

use App\Enums\ApplicationStatus;
use App\Enums\DocumentType;
use App\Enums\FixedListItemStatus;
use App\Enums\FixedListStatus;
use App\Http\Controllers\Concerns\ResolvesModuleContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Fassg\RejectApplicationRequest;
use App\Http\Requests\Fassg\VerifyApplicationRequest;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\FixedList;
use App\Models\FixedListItem;
use App\Models\SleFheVerification;
use App\Models\SponsorshipProgram;
use App\Models\StudentProfile;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ApplicantVerificationController extends Controller
{
    use ResolvesModuleContext;

    public function index(Request $request): View
    {
        $search = $request->string('q')->trim()->toString();
        $programId = $request->integer('program_id', 0);
        $campus = $request->string('campus')->trim()->toString();
        $status = $request->string('status')->trim()->toString();

        $statusEnum = $status !== ''
            ? collect(ApplicationStatus::cases())->first(
                fn ($case) => strcasecmp($case->value, $status) === 0,
            )
            : null;

        $applications = Application::query()
            ->with([
                'studentProfile.user',
                'sponsorshipProgram.sponsor',
                'documents',
            ])
            ->where('is_auto_provisioned', false)
            ->whereHas('studentProfile', function ($q) use ($campus): void {
                $q->whereHas('sleFheVerification');
                if ($campus !== '') {
                    $q->where('campus', $campus);
                }
            })
            ->when($search !== '', function ($q) use ($search): void {
                $q->whereHas('studentProfile', function ($pq) use ($search): void {
                    $pq->where('student_id_number', 'like', "%{$search}%")
                        ->orWhere('course', 'like', "%{$search}%")
                        ->orWhereHas('academicProgram', fn ($ap) => $ap->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($programId > 0, fn ($q) => $q->where('sponsorship_program_id', $programId))
            ->when(
                $statusEnum !== null,
                fn ($q) => $q->where('status', $statusEnum->value),
            )
            ->when(
                $programId > 0,
                fn ($q) => $q->select(['applications.*'])
                    ->orderBy('applications.gpa_submitted', 'asc')
                    ->orderBy('applications.submitted_at', 'asc'),
                fn ($q) => $q
                    ->orderBy('applications.gpa_submitted', 'asc')
                    ->orderBy('applications.submitted_at', 'asc')
            )
            ->paginate(15)
            ->withQueryString();

        $endorsedKeys = $this->finalizedEndorsedKeys($applications->getCollection()->all());

        $programs = SponsorshipProgram::query()
            ->with(['sponsor', 'academicPrograms', 'fixedLists' => function ($q): void {
                $q->orderBy('batch_name');
            }])
            ->orderBy('program_name')
            ->get();

        $allFixedLists = $programs->pluck('fixedLists')->flatten()->values();

        $savedFixedLists = $allFixedLists
            ->whereIn('status', [FixedListStatus::Saved, FixedListStatus::Draft])
            ->values();

        $finalizedFixedLists = $allFixedLists
            ->where('status', FixedListStatus::Finalized)
            ->values();

        $selectedProgram = $programId > 0 ? $programs->firstWhere('id', $programId) : null;

        // Program capacity minus only Approved applications. Pending, Verified,
        // and Rejected applications never reduce the remaining slot quota, so
        // rejecting an applicant instantly frees capacity in the queue.
        $availableSlots = $selectedProgram !== null
            ? max(0, $selectedProgram->total_slots - $selectedProgram->applications()
                ->where('status', ApplicationStatus::Approved)
                ->distinct('student_profile_id')
                ->count('student_profile_id'))
            : null;

        $scoped = Application::query()
            ->where('is_auto_provisioned', false)
            ->whereHas('studentProfile', fn ($q) => $q->whereHas('sleFheVerification'));

        $pendingCount = (clone $scoped)->where('status', ApplicationStatus::Pending)->count();
        $verifiedCount = (clone $scoped)->where('status', ApplicationStatus::Verified)->count();
        $approvedCount = (clone $scoped)->where('status', ApplicationStatus::Approved)->count();
        $resubmissionCount = (clone $scoped)->where('status', ApplicationStatus::ResubmissionRequested)->count();
        $rejectedCount = (clone $scoped)->where('status', ApplicationStatus::Rejected)->count();

        return view('fassg.applications.index', [
            'user' => $this->actor($request),
            'pendingApplications' => $applications,
            'pendingCount' => $pendingCount,
            'verifiedCount' => $verifiedCount,
            'approvedCount' => $approvedCount,
            'resubmissionCount' => $resubmissionCount,
            'rejectedCount' => $rejectedCount,
            'programs' => $programs,
            'savedFixedLists' => $savedFixedLists,
            'finalizedFixedLists' => $finalizedFixedLists,
            'selectedProgram' => $selectedProgram,
            'availableSlots' => $availableSlots,
            'endorsedKeys' => $endorsedKeys,
            'selectedProgramId' => $programId,
            'selectedStatus' => $statusEnum?->value,
        ]);
    }

    public function createBatch(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'batch_name' => [
                'nullable',
                'string',
                'max:150',
                Rule::requiredIf($request->filled('existing_fixed_list_id') === false),
            ],
            'sponsorship_program_id' => ['required', 'integer', 'exists:sponsorship_programs,id'],
            'selected_applications' => ['sometimes', 'array', 'min:1'],
            'selected_applications.*' => ['integer'],
            'existing_fixed_list_id' => ['nullable', 'integer', 'exists:fixed_lists,id'],
            'locked_fixed_list_id' => ['nullable', 'integer', 'exists:fixed_lists,id'],
        ]);

        $programId = (int) $validated['sponsorship_program_id'];
        $applicationIds = array_values(array_unique(array_map('intval', $validated['selected_applications'] ?? [])));

        $appendToId = isset($validated['existing_fixed_list_id']) && $validated['existing_fixed_list_id'] !== null
            ? (int) $validated['existing_fixed_list_id']
            : null;

        $targetList = null;
        if ($appendToId !== null) {
            $targetList = FixedList::query()
                ->where('id', $appendToId)
                ->where('sponsorship_program_id', $programId)
                ->whereIn('status', [FixedListStatus::Saved, FixedListStatus::Draft])
                ->first();

            if ($targetList === null) {
                return back()->withErrors([
                    'existing_fixed_list_id' => 'The selected batch is no longer available for appending. Only Saved or Draft batches from the target program can be modified.',
                ]);
            }
        }

        // Optional finalized Fixed List whose verified candidates are locked
        // into the top slots (Stage 1). Only Finalized lists belonging to the
        // target program qualify; anything else is rejected.
        $lockedFixedListId = filled($validated['locked_fixed_list_id'] ?? null)
            ? (int) $validated['locked_fixed_list_id']
            : null;

        $lockedList = null;
        if ($lockedFixedListId !== null) {
            $lockedList = FixedList::query()
                ->where('id', $lockedFixedListId)
                ->where('sponsorship_program_id', $programId)
                ->where('status', FixedListStatus::Finalized)
                ->first();

            if ($lockedList === null) {
                return back()->withErrors([
                    'locked_fixed_list_id' => 'The selected fixed list is no longer available. Only Finalized lists from the target program can be locked into the top slots.',
                ]);
            }
        }

        // Candidates must belong to the target program and be Pending or Verified
        // with no active batch link. Approved, Rejected, or already-batched
        // applications are guarded out here.
        $baseQuery = Application::query()
            ->where('sponsorship_program_id', $programId)
            ->with('studentProfile.user')
            ->whereIn('status', [ApplicationStatus::Pending, ApplicationStatus::Verified])
            ->whereDoesntHave('fixedListItems')
            ->orderBy('gpa_submitted', 'asc')
            ->orderBy('submitted_at', 'asc');

        if ($applicationIds !== []) {
            $applications = Application::query()
                ->where('sponsorship_program_id', $programId)
                ->whereIn('id', $applicationIds)
                ->with('studentProfile.user')
                ->get();

            if ($applications->count() !== count($applicationIds)) {
                return back()->withErrors([
                    'selected_applications' => 'One or more of the selected applications no longer belongs to the target program. Please refresh the queue and reselect.',
                ])->withInput();
            }

            $eligible = $applications->filter(
                static fn (Application $application): bool => in_array(
                    $application->status,
                    [ApplicationStatus::Pending, ApplicationStatus::Verified],
                    true,
                ) && $application->fixedListItems()->doesntExist(),
            );

            if ($eligible->count() !== $applications->count()) {
                return back()->withErrors([
                    'selected_applications' => 'Some selected applications are already approved, rejected, or assigned to a batch and cannot be included. Please deselect them and try again.',
                ])->withInput();
            }
        } else {
            $eligible = $baseQuery->get();
        }

        // Stage 1 (Fixed List Lock): the SLE-FHE-verified, sponsor-endorsed
        // entries of the selected Finalized Fixed List resolve to an existing
        // Application for the target program, or are auto-provisioned a Verified
        // Application so they can be locked into the top slots. Stage 2
        // (Auto-Ranking Queue): everyone else fills the remaining slots sorted
        // by best GWA, then submission date.
        $lockedEntries = $lockedList === null
            ? collect()
            : $this->lockedCandidateEntries($lockedList, $programId);

        if ($eligible->isEmpty() && $lockedEntries->isEmpty()) {
            return back()->withErrors([
                'selected_applications' => $applicationIds !== []
                    ? 'Select at least one eligible application belonging to the target program. Only pending or verified, unbatched applications can be bundled into a batch list.'
                    : 'There are no pending or verified, unbatched applications left for this program to auto-generate a batch.',
            ]);
        }

        $reservedApplications = collect();

        foreach ($lockedEntries as $lockKey => $entry) {
            $application = $entry['application'] ?? null;

            if ($application === null) {
                $application = $this->createAutoVerifiedApplication($entry['profile'], $programId);
            }

            $reservedApplications->put($lockKey, $application);
        }

        $reservedApplicationIds = $reservedApplications
            ->map(static fn (Application $application): int => $application->id)
            ->values()
            ->all();

        $queueCandidates = $eligible->reject(
            static fn (Application $application): bool => in_array($application->id, $reservedApplicationIds, true),
        )->values();

        $sortCandidates = static fn ($collection) => $collection->sortBy([
            static fn (Application $application): float => (float) $application->gpa_submitted,
            static fn (Application $application): int => $application->submitted_at?->getTimestamp() ?? PHP_INT_MAX,
        ])->values();

        $candidates = $sortCandidates($reservedApplications->values())
            ->map(static fn (Application $application) => [$application, true])
            ->concat($sortCandidates($queueCandidates)->map(static fn (Application $application) => [$application, false]))
            ->values();

        [$list, $added] = DB::transaction(function () use ($candidates, $programId, $request, $validated, $targetList): array {
            $list = $targetList;

            if ($list === null) {
                $list = FixedList::query()->create([
                    'sponsorship_program_id' => $programId,
                    'batch_name' => $validated['batch_name'],
                    'uploaded_by_fassg_id' => $this->actor($request)->id,
                    'total_names' => 0,
                    'status' => FixedListStatus::Saved,
                ]);
            }

            $nextRank = ((int) $list->items()->max('rank_position')) + 1;
            $added = 0;

            foreach ($candidates as [$application, $isFixedList]) {
                $profile = $application->studentProfile;

                if ($profile === null) {
                    continue;
                }

                if ($list->items()->where('student_id_number', $profile->student_id_number)->exists()) {
                    continue;
                }

                $name = $profile->user?->name ?? trim(
                    ($profile->first_name ?? '').' '.($profile->middle_name ?? '').' '.($profile->last_name ?? '')
                );

                $list->items()->create([
                    'application_id' => $application->id,
                    'student_name' => $name !== '' ? $name : 'Unknown',
                    'student_id_number' => $profile->student_id_number ?: 'Unknown',
                    'course' => $profile->academicProgram?->name ?? $profile->course ?: 'Unspecified',
                    'year_level' => $profile->year_level ?? 1,
                    'campus' => $profile->campus ?: null,
                    'is_sle_fhe_verified' => $profile->isSleFheVerified(),
                    'is_fixed_list' => $isFixedList,
                    'origin_type' => $isFixedList ? 'fixed_list' : 'ranked_queue',
                    'rank_position' => $nextRank,
                    'status' => FixedListItemStatus::Pending,
                ]);

                $application->update([
                    'is_batched' => true,
                    'batch_id' => $list->id,
                ]);

                $nextRank++;
                $added++;
            }

            $list->update(['total_names' => $list->items()->count()]);

            return [$list, $added];
        });

        if ($appendToId !== null) {
            $this->audit($request, 'fassg.fixed_list.appended_from_applications', 'fixed_lists');

            return redirect()
                ->route('fassg.generated-batches.show', $list)
                ->with('status', "Successfully added {$added} student(s) to {$list->batch_name}.");
        }

        $this->audit($request, 'fassg.fixed_list.generated_from_applications', 'fixed_lists');

        return redirect()
            ->route('fassg.generated-batches.show', $list)
            ->with('status', "Batch list '{$list->batch_name}' created from {$list->total_names} applicant(s). Review and submit when ready.");
    }

    /**
     * Resolve the SLE-FHE-verified, sponsor-endorsed entries of the selected
     * Finalized Fixed List to reusable Applications for the target program.
     * This performs no writes; missing applications are provisioned later in
     * {@see createBatch} via {@see createAutoVerifiedApplication}.
     *
     * @return Collection<string, array{profile: StudentProfile, application: ?Application}>
     */
    private function lockedCandidateEntries(FixedList $lockedList, int $programId): Collection
    {
        $resolved = [];

        foreach ($lockedList->items()->with('studentProfile')->get() as $item) {
            if (! $item->is_sle_fhe_verified || ! $item->is_manually_endorsed) {
                continue;
            }

            $profile = $item->studentProfile ?? StudentProfile::query()
                ->where('student_id_number', $item->student_id_number)
                ->first();

            if ($profile === null) {
                continue;
            }

            $lockKey = trim((string) $profile->student_id_number);

            if ($lockKey === '') {
                continue;
            }

            $application = $item->application_id !== null
                ? Application::query()
                    ->whereKey((int) $item->application_id)
                    ->where('sponsorship_program_id', $programId)
                    ->first()
                : null;

            $application ??= Application::query()
                ->where('sponsorship_program_id', $programId)
                ->where('student_profile_id', $profile->id)
                ->first();

            // Candidates already claimed by another batch are left untouched.
            if ($application !== null
                && $application->fixedListItems()->where('fixed_list_id', '!=', $lockedList->id)->exists()) {
                continue;
            }

            $resolved[$lockKey] = [
                'profile' => $profile,
                'application' => $application,
            ];
        }

        return collect($resolved);
    }

    private function createAutoVerifiedApplication(StudentProfile $profile, int $programId): Application
    {
        return Application::query()->create([
            'student_profile_id' => $profile->id,
            'sponsorship_program_id' => $programId,
            'gpa_submitted' => 0.00,
            'address_submitted' => $profile->full_address,
            'is_rural_submitted' => false,
            'status' => ApplicationStatus::Verified,
            'verified_at' => now(),
            'submitted_at' => now(),
            'is_manually_endorsed' => true,
            'is_auto_provisioned' => true,
        ]);
    }

    /**
     * Build the set of "programId|studentIdNumber" keys for the applications
     * on the current queue page whose students are SLE-FHE-verified and
     * sponsor-endorsed on a Finalized Fixed List, so the queue can render the
     * "★ Endorsed by Sponsor" badge.
     *
     * @param  Application[]  $applications
     * @return array<int, string>
     */
    private function finalizedEndorsedKeys(array $applications): array
    {
        $pairs = collect($applications)
            ->map(static fn (Application $application): array => [
                (int) $application->sponsorship_program_id,
                trim((string) ($application->studentProfile?->student_id_number ?? '')),
            ])
            ->filter(static fn (array $pair): bool => $pair[1] !== '');

        if ($pairs->isEmpty()) {
            return [];
        }

        $studentIds = $pairs->pluck(1)->unique()->values()->all();
        $programIds = $pairs->pluck(0)->unique()->values()->all();

        return FixedListItem::query()
            ->join('fixed_lists', 'fixed_lists.id', '=', 'fixed_list_items.fixed_list_id')
            ->select('fixed_list_items.student_id_number', 'fixed_lists.sponsorship_program_id')
            ->where('fixed_list_items.is_sle_fhe_verified', true)
            ->where('fixed_list_items.is_manually_endorsed', true)
            ->whereIn('fixed_list_items.student_id_number', $studentIds)
            ->where('fixed_lists.status', FixedListStatus::Finalized)
            ->whereIn('fixed_lists.sponsorship_program_id', $programIds)
            ->get()
            ->map(static fn (FixedListItem $item): string => (int) $item->sponsorship_program_id.'|'.trim((string) $item->student_id_number))
            ->unique()
            ->values()
            ->all();
    }

    public function show(Request $request, Application $application): View
    {
        $application->load(['studentProfile.user', 'sponsorshipProgram.sponsor', 'documents']);

        $eligibilityErrors = $application->sponsorshipProgram->eligibilityErrors(
            $application->studentProfile,
            (float) $application->gpa_submitted,
            $application->address_submitted,
            (bool) $application->is_rural_submitted,
        );

        return view('fassg.applications.show', [
            'user' => $this->actor($request),
            'application' => $application,
            'eligibilityErrors' => $eligibilityErrors,
        ]);
    }

    public function verify(VerifyApplicationRequest $request, Application $application): RedirectResponse
    {
        if ($application->status !== ApplicationStatus::Pending) {
            return back()->withErrors([
                'application' => 'Only pending applications can be verified.',
            ]);
        }

        $application->loadMissing(['studentProfile', 'sponsorshipProgram', 'documents']);

        $requiredDocumentTypes = $application->sponsorshipProgram?->requiredDocumentCanonicalValues() ?? [];

        $submittedDocumentTypes = $application->documents
            ->pluck('document_type')
            ->map(static fn ($type): string => DocumentType::canonicalValue($type))
            ->unique()
            ->values()
            ->all();

        if (array_diff($requiredDocumentTypes, $submittedDocumentTypes) !== []) {
            return back()->withErrors([
                'application' => 'The Certificate of Grades and Proof of Residence / Barangay Certificate are required.',
            ]);
        }

        $eligibilityErrors = $application->sponsorshipProgram->eligibilityErrors(
            $application->studentProfile,
            (float) $application->gpa_submitted,
            $application->address_submitted,
            (bool) $application->is_rural_submitted,
            enforceMinimumGwa: false,
        );

        if ($eligibilityErrors !== []) {
            return back()->withErrors(['application' => $eligibilityErrors]);
        }

        $application->update([
            'status' => ApplicationStatus::Verified,
            'verified_at' => now(),
        ]);

        NotificationService::notifyApplicationProgress($application, ApplicationStatus::Verified);

        $this->audit($request, 'fassg.application.verified', 'applications');

        return back()->with('status', 'Application marked as Verified.');
    }

    public function reject(RejectApplicationRequest $request, Application $application): RedirectResponse
    {
        if (! in_array($application->status, [ApplicationStatus::Pending, ApplicationStatus::Verified], true)) {
            return back()->withErrors([
                'application' => 'This application can no longer be rejected.',
            ]);
        }

        $application->update([
            'status' => ApplicationStatus::Rejected,
        ]);

        $reason = $request->string('reason')->toString();

        NotificationService::notifyApplicationRejected($application, $reason !== '' ? $reason : null);

        $this->audit($request, 'fassg.application.rejected', 'applications');

        $message = $reason !== ''
            ? "Application rejected: {$reason}"
            : 'Application marked as Rejected.';

        return back()->with('status', $message);
    }

    public function updateStatus(Request $request, Application $application): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:Verified,Rejected'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        if ($validated['status'] === ApplicationStatus::Verified->value) {
            return $this->verifyStatus($request, $application);
        }

        $application->update(['status' => ApplicationStatus::Rejected]);
        NotificationService::notifyApplicationRejected($application, $validated['reason'] ?? null);
        $this->audit($request, 'fassg.application.rejected', 'applications');

        return back()->with('status', 'Application marked as Rejected.');
    }

    public function requestResubmission(Request $request, Application $application): RedirectResponse
    {
        $validDocumentTypes = collect(DocumentType::cases())
            ->map(static fn (DocumentType $type): string => DocumentType::canonicalValue($type))
            ->unique()
            ->values()
            ->all();

        $validated = $request->validate([
            'resubmission_notes' => ['required', 'string', 'min:5', 'max:1000'],
            'requested_documents' => ['required', 'array', 'min:1'],
            'requested_documents.*' => ['string', Rule::in($validDocumentTypes)],
        ]);

        if (! in_array($application->status, [ApplicationStatus::Pending, ApplicationStatus::Verified], true)) {
            return back()->withErrors(['application' => 'This application cannot be sent for resubmission in its current status.']);
        }

        $application->update([
            'status' => ApplicationStatus::ResubmissionRequested,
            'resubmission_notes' => trim($validated['resubmission_notes']),
            'requested_documents' => array_values(array_unique($validated['requested_documents'])),
        ]);

        NotificationService::notifyApplicationProgress($application, ApplicationStatus::ResubmissionRequested);

        $this->audit($request, 'fassg.application.resubmission_requested', 'applications');

        return back()->with('status', 'Resubmission requested. The student has been notified to upload the corrected documents.');
    }

    private function verifyStatus(Request $request, Application $application): RedirectResponse
    {
        if ($application->status !== ApplicationStatus::Pending) {
            return back()->withErrors(['application' => 'Only pending applications can be verified.']);
        }

        $application->loadMissing(['studentProfile', 'sponsorshipProgram', 'documents']);

        $required = $application->sponsorshipProgram?->requiredDocumentCanonicalValues() ?? [];

        $submitted = $application->documents
            ->pluck('document_type')
            ->map(static fn ($type): string => DocumentType::canonicalValue($type))
            ->unique()
            ->values()
            ->all();

        if (array_diff($required, $submitted) !== []) {
            return back()->withErrors(['application' => 'All required application documents must be uploaded.']);
        }

        $application->update(['status' => ApplicationStatus::Verified, 'verified_at' => now()]);
        NotificationService::notifyApplicationProgress($application, ApplicationStatus::Verified);
        $this->audit($request, 'fassg.application.verified', 'applications');

        return back()->with('status', 'Application marked as Verified.');
    }

    public function downloadDocument(Application $application, ApplicationDocument $applicationDocument): BinaryFileResponse
    {
        abort_unless($applicationDocument->application_id === $application->id, 404);

        $path = Storage::disk('public')->path($applicationDocument->file_path);
        abort_unless(is_file($path), 404, 'Document file not found.');

        $fileName = addcslashes(basename($applicationDocument->file_name), '\\"');
        $mimeType = mime_content_type($path) ?: 'application/octet-stream';

        return response()->file($path, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="'.$fileName.'"',
        ]);
    }

    public function verifySleFhe(Request $request, StudentProfile $studentProfile): RedirectResponse
    {
        SleFheVerification::firstOrCreate(
            ['student_profile_id' => $studentProfile->id],
            ['verified_address' => '', 'verified_at' => now()],
        );

        $this->audit($request, 'fassg.student.sle_fhe_verified', 'student_profiles');

        return back()->with('status', "SLE-FHE verified for {$studentProfile->student_id_number}.");
    }
}
