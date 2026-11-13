<?php

namespace App\Http\Controllers\Fassg;

use App\Enums\ApplicationStatus;
use App\Enums\DocumentType;
use App\Enums\FixedListStatus;
use App\Enums\GeneratedBatchStatus;
use App\Http\Controllers\Concerns\ResolvesModuleContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Fassg\RejectApplicationRequest;
use App\Http\Requests\Fassg\VerifyApplicationRequest;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\FixedList;
use App\Models\FixedListItem;
use App\Models\GeneratedBatch;
use App\Models\SponsorshipProgram;
use App\Models\StudentProfile;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ApplicantVerificationController extends Controller
{
    use ResolvesModuleContext;

    public function index(Request $request): View
    {
        $search = $request->string('q')->trim()->toString();
        $programId = $request->integer('program_id', 0);
        $sourceFixedListId = $request->integer('fixed_list_id', 0);
        if ($sourceFixedListId > 0) {
            $sourceList = FixedList::query()->where('status', FixedListStatus::Finalized)->findOrFail($sourceFixedListId);
            abort_if($programId > 0 && $programId !== (int) $sourceList->sponsorship_program_id, 404);
            $programId = (int) $sourceList->sponsorship_program_id;
        }
        $campus = $request->string('campus')->trim()->toString();
        $status = $request->string('status')->trim()->toString();

        $statusEnum = $status !== ''
            ? collect(ApplicationStatus::cases())->first(
                fn($case) => strcasecmp($case->value, $status) === 0,
            )
            : null;

        $applications = Application::query()
            ->with([
                'studentProfile.user',
                'sponsorshipProgram.sponsor',
                'documents',
                'batchCandidates.generatedBatch',
            ])
            ->where('is_auto_provisioned', false)
            ->whereHas('studentProfile', function ($q) use ($campus): void {
                if ($campus !== '') {
                    $q->where('campus', $campus);
                }
            })
            ->when($search !== '', function ($q) use ($search): void {
                $q->whereHas('studentProfile', function ($pq) use ($search): void {
                    $pq->where('student_id_number', 'like', "%{$search}%")
                        ->orWhere('course', 'like', "%{$search}%")
                        ->orWhereHas('academicProgram', fn($ap) => $ap->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($programId > 0, fn($q) => $q->where('sponsorship_program_id', $programId))
            ->when(
                $statusEnum !== null,
                fn($q) => $q->where('status', $statusEnum->value),
            )
            // Display order only; candidates are never selected automatically.
            ->orderByRaw('applications.submitted_at IS NULL')
            ->orderBy('applications.submitted_at')
            ->orderBy('applications.id')
            ->get();

        $endorsedKeys = $this->finalizedEndorsedKeys($applications->all());

        $programs = SponsorshipProgram::query()
            ->with([
                'sponsor',
                'academicPrograms',
                'fixedLists' => fn($q) => $q->with('items.studentProfile')->orderBy('batch_name'),
                'generatedBatches' => fn($q) => $q->with('items.application')->withExists('sponsorApprovals')->orderBy('batch_name'),
            ])
            ->orderBy('program_name')
            ->get();

        $savedFixedLists = $programs
            ->pluck('generatedBatches')
            ->flatten()
            ->filter(fn(GeneratedBatch $batch): bool => $batch->status === GeneratedBatchStatus::Saved
                && $batch->submission_version === 0 && $batch->submitted_roster === null
                && $batch->fassg_assigned_at === null && $batch->fassg_assigned_by_id === null
                && ! $batch->sponsor_approvals_exists)
            ->values();

        $finalizedFixedLists = $programs
            ->pluck('fixedLists')
            ->flatten()
            ->where('status', FixedListStatus::Finalized)
            ->values();

        $selectedProgram = $programId > 0 ? $programs->firstWhere('id', $programId) : null;

        $programCapacities = $programs->mapWithKeys(fn ($program) => [$program->id => $this->draftCapacity($program)]);
        $availableSlots = $selectedProgram !== null ? $programCapacities[$selectedProgram->id] : null;
        $selectableApplicationIds = $applications->filter(fn ($application) =>
            in_array($application->status, [ApplicationStatus::Pending, ApplicationStatus::Verified], true)
            && $application->studentProfile->hasDocumentedSleFheVerification()
            && ! $application->studentProfile->hasActiveSponsorship()
            && $application->batch_id === null && $application->batchCandidates->isEmpty())
            ->pluck('id')->all();
        $scoped = Application::query()
            ->where('is_auto_provisioned', false)->whereHas('studentProfile');

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
            'programCapacities' => $programCapacities,
            'selectableApplicationIds' => $selectableApplicationIds,
            'selectedFixedListId' => $sourceFixedListId,
            'openBatchModal' => $request->boolean('prepare_batch'),
            'endorsedKeys' => $endorsedKeys,
            'selectedProgramId' => $programId,
            'selectedStatus' => $statusEnum?->value,
        ]);
    }

    public function createBatch(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isFassg(), 403);
        $validated = $request->validate([
            'batch_name' => [
                'nullable',
                'string',
                'max:150',
                Rule::requiredIf($request->filled('existing_fixed_list_id') === false),
            ],
            'sponsorship_program_id' => ['required', 'integer', 'exists:sponsorship_programs,id'],
            'selected_applications' => ['sometimes', 'array'],
            'selected_applications.*' => ['integer'],
            'fixed_list_source' => ['nullable', 'integer', 'exists:fixed_lists,id'],
            'selected_fixed_list_items' => ['prohibited'],
            'existing_fixed_list_id' => ['nullable', 'integer', 'exists:generated_batches,id'],
            // Use the explicit source-list selector, never legacy member payloads.
            'locked_fixed_list_id' => ['prohibited'],
        ]);

        $applicationIds = array_values(array_unique(array_map('intval', $validated['selected_applications'] ?? [])));
        $sourceListId = filled($validated['fixed_list_source'] ?? null) ? (int) $validated['fixed_list_source'] : null;
        if ($applicationIds === [] && $sourceListId === null) {
            throw ValidationException::withMessages([
                'selected_applications' => 'Select at least one Pending/Verified applicant or a finalized Fixed List for the draft.',
            ]);
        }

        $programId = (int) $validated['sponsorship_program_id'];
        $appendToId = filled($validated['existing_fixed_list_id'] ?? null)
            ? (int) $validated['existing_fixed_list_id'] : null;

        [$list, $added] = DB::transaction(function () use ($request, $validated, $programId, $applicationIds, $sourceListId, $appendToId): array {
            // Serialize membership/capacity checks for this program, including appends.
            $program = SponsorshipProgram::query()->lockForUpdate()->findOrFail($programId);
            if (! $program->isOpen() || $program->effective_status === \App\Enums\ProgramStatus::Expired) {
                throw ValidationException::withMessages(['sponsorship_program_id' => 'Select an open, unexpired sponsorship program.']);
            }
            $list = $appendToId === null ? null : GeneratedBatch::query()
                ->whereKey($appendToId)
                ->where('sponsorship_program_id', $programId)
                ->where('status', GeneratedBatchStatus::Saved)
                ->where('submission_version', 0)->whereNull('submitted_roster')
                ->whereNull('fassg_assigned_at')->whereNull('fassg_assigned_by_id')->whereDoesntHave('sponsorApprovals')
                ->lockForUpdate()
                ->first();

            if ($appendToId !== null && $list === null) {
                throw ValidationException::withMessages([
                    'existing_fixed_list_id' => 'Only Saved batches from the target program can be modified.',
                ]);
            }

            $applications = Application::query()
                ->where('sponsorship_program_id', $programId)
                ->whereIn('id', $applicationIds)
                ->where('is_auto_provisioned', false)
                ->with('studentProfile')
                ->lockForUpdate()
                ->get();

            if ($applications->count() !== count($applicationIds)) {
                throw ValidationException::withMessages([
                    'selected_applications' => 'Select ordinary applications belonging to the target program.',
                ]);
            }

            $fixedItems = collect();
            if ($sourceListId !== null) {
                $sourceList = FixedList::query()->lockForUpdate()->find($sourceListId);
                if ($sourceList === null || $sourceList->status !== FixedListStatus::Finalized
                    || (int) $sourceList->sponsorship_program_id !== $programId) {
                    throw ValidationException::withMessages([
                        'fixed_list_source' => 'Select a finalized Fixed List belonging to the target program.',
                    ]);
                }
                // Include every stored member. Never filter or truncate a selected roster.
                $fixedItems = $sourceList->items()->with(['fixedList', 'studentProfile'])->orderBy('id')->lockForUpdate()->get();
                if ($fixedItems->isEmpty()) {
                    throw ValidationException::withMessages(['fixed_list_source' => 'The selected Fixed List has no candidates.']);
                }
            }

            // Key by student identity, not qualification, source priority, or submission time.
            $candidates = collect();
            foreach ($applications as $application) {
                $profile = $application->studentProfile;
                if ($profile === null || $candidates->has($profile->id)) {
                    throw ValidationException::withMessages([
                        'selected_applications' => 'Select only one application per student.',
                    ]);
                }
                $candidates->put($profile->id, ['profile' => $profile, 'application' => $application, 'fixed' => false]);
            }

            foreach ($fixedItems as $item) {
                $profile = $item->studentProfile;
                if (
                    $item->fixedList?->status !== FixedListStatus::Finalized
                    || (int) $item->fixedList->sponsorship_program_id !== $programId
                    || ! $item->is_sle_fhe_verified || ! $item->hasRecordedEndorsement() || $profile === null
                ) {
                    throw ValidationException::withMessages([
                        'fixed_list_source' => "Fixed List student {$item->student_id_number} lacks documented SLE-FHE verification or FASSG endorsement. No candidates were omitted.",
                    ]);
                }

                $entry = $candidates->get($profile->id);
                if ($entry === null) {
                    $existing = Application::query()
                        ->where('sponsorship_program_id', $programId)
                        ->where('student_profile_id', $profile->id)
                        ->where('status', '!=', ApplicationStatus::Expired)
                        ->lockForUpdate()
                        ->get();

                    if ($existing->count() > 1) {
                        throw ValidationException::withMessages([
                            'fixed_list_source' => "Multiple application records exist for {$item->student_id_number}. Review those records before batching.",
                        ]);
                    }
                    $entry = ['profile' => $profile, 'application' => $existing->first(), 'fixed' => true];
                }
                $entry['fixed'] = true;
                if (isset($entry['fixed_list_item_id']) && $entry['fixed_list_item_id'] !== $item->id) {
                    throw ValidationException::withMessages(['fixed_list_source' => 'The Fixed List contains duplicate student identities. Review the source records before batching.']);
                }
                $entry['fixed_list_item_id'] = $item->id;
                $candidates->put($profile->id, $entry);
            }

            $newCandidates = collect();
            foreach ($candidates as $studentId => $entry) {
                $profile = StudentProfile::query()->lockForUpdate()->findOrFail($studentId);
                $application = $entry['application'];
                if (isset($entry['fixed_list_item_id'])) {
                    $fixedItem = $fixedItems->firstWhere('id', $entry['fixed_list_item_id']);
                    $fixedItem->setRelation('studentProfile', $profile);
                    $sourceErrors = $fixedItem->endorsementErrors($program);
                    if ($sourceErrors !== []) {
                        throw ValidationException::withMessages(['fixed_list_source' => array_map(
                            fn ($error) => "{$fixedItem->student_id_number}: {$error}", $sourceErrors)]);
                    }
                }
                if (! $profile->hasDocumentedSleFheVerification() || $profile->hasActiveSponsorship()) {
                    throw ValidationException::withMessages([
                        'selected_applications' => "Student {$profile->student_id_number} must have documented SLE-FHE verification and no active sponsorship.",
                    ]);
                }
                $errors = [];
                // A draft records selection, not an eligibility decision. Qualifications
                // and documents are checked during explicit member review and submission.
                if ($application !== null && ! in_array($application->status,
                    [ApplicationStatus::Pending, ApplicationStatus::Verified], true)) {
                    $errors[] = 'Only Pending or Verified applications may be included in a Saved batch.';
                }
                if ($errors !== []) {
                    throw ValidationException::withMessages(['selected_applications' => $errors]);
                }
                if ($list !== null && $application !== null && $list->items()->where('application_id', $application->id)->exists()) {
                    continue; // Repeating a draft selection never recreates or verifies its processing record.
                }
                $memberships = Application::query()
                    ->where('student_profile_id', $studentId)
                    ->where('sponsorship_program_id', $programId)
                    ->whereHas('batchCandidates')
                    ->with('batchCandidates.application.sponsorshipProgram')
                    ->get()
                    ->flatMap->batchCandidates;
                if (
                    $memberships->contains(fn($item) => (int) $item->generated_batch_id !== (int) $list?->id
                        && $item->application?->status !== ApplicationStatus::Expired)
                    || ($application?->batch_id !== null && (int) $application->batch_id !== (int) $list?->id)
                ) {
                    throw ValidationException::withMessages([
                        'selected_applications' => 'A selected student is already assigned to another batch for this program.',
                    ]);
                }
                if ($memberships->contains(fn($item) => (int) $item->generated_batch_id === (int) $list?->id)) {
                    continue; // Repeated selection in the same saved batch is idempotent.
                }
                $entry['profile'] = $profile;
                $newCandidates->put($studentId, $entry);
            }

            if (($list?->items()->count() ?? 0) + $newCandidates->count() > $this->draftCapacity($program)) {
                throw ValidationException::withMessages([
                    'selected_applications' => 'The complete selection exceeds available capacity. Uncheck ordinary applicants or choose a smaller finalized list. No candidates were omitted.',
                ]);
            }

            $list ??= GeneratedBatch::query()->create([
                'sponsorship_program_id' => $programId,
                'batch_name' => $validated['batch_name'],
                'created_by_fassg_id' => $this->actor($request)->id,
                'total_slots' => 0,
                'status' => GeneratedBatchStatus::Saved,
            ]);

            foreach ($newCandidates as $entry) {
                $application = $entry['application']
                    ?? $this->createFixedListApplication($entry['profile'], $programId);
                $list->items()->create([
                    'application_id' => $application->id,
                    'rank_position' => null,
                    // Retain the legacy enum value for schema compatibility, not ranking.
                    'origin_type' => $entry['fixed'] ? 'fixed_list' : 'ranked_queue',
                    'fixed_list_item_id' => $entry['fixed_list_item_id'] ?? null,
                ]);
                $application->update(['is_batched' => true, 'batch_id' => $list->id]);
            }
            $list->update(['total_slots' => $list->items()->count()]);
            \App\Models\AuditLog::record($appendToId !== null
                ? 'fassg.generated_batch.appended_from_applications' : 'fassg.generated_batch.generated_from_applications',
                'generated_batches', $request->user(), $request->ip(), json_encode([
                    'batch_id' => $list->id, 'fixed_list_id' => $sourceListId,
                    'selected_fixed_list_item_ids' => $fixedItems->pluck('id')->all(),
                    'selected_application_ids' => $applicationIds,
                    'added_student_profile_ids' => $newCandidates->keys()->all(),
                ]));

            return [$list, $newCandidates->count()];
        });

        return redirect()->route('fassg.generated-batches.show', $list)
            ->with('status', "Saved {$added} manually selected student(s) to {$list->batch_name}. Review and submit when ready.");
    }

    private function draftCapacity(SponsorshipProgram $program): int
    {
        $occupied = $program->applications()->approvedBeneficiaries()->distinct('student_profile_id')->count('student_profile_id');

        return max(0, min($program->available_slots, (int) $program->getRawOriginal('available_slots'),
            $program->total_slots - $occupied));
    }

    private function createFixedListApplication(StudentProfile $profile, int $programId): Application
    {
        return Application::query()->create([
            'student_profile_id' => $profile->id,
            'sponsorship_program_id' => $programId,
            'gpa_submitted' => null,
            'address_submitted' => null,
            'is_rural_submitted' => null,
            'status' => ApplicationStatus::Pending,
            'verified_at' => null,
            'submitted_at' => null,
            'is_manually_endorsed' => true,
            'is_auto_provisioned' => true,
        ]);
    }

    /**
     * Build the set of "programId|studentIdNumber" keys for the applications
     * on the current queue page whose students are SLE-FHE-verified and
     * FASSG-endorsed on a Finalized Fixed List. This is not Sponsor approval.
     *
     * @param  Application[]  $applications
     * @return array<int, string>
     */
    private function finalizedEndorsedKeys(array $applications): array
    {
        $pairs = collect($applications)
            ->map(static fn(Application $application): array => [
                (int) $application->sponsorship_program_id,
                trim((string) ($application->studentProfile?->student_id_number ?? '')),
            ])
            ->filter(static fn(array $pair): bool => $pair[1] !== '');

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
            ->map(static fn(FixedListItem $item): string => (int) $item->sponsorship_program_id . '|' . trim((string) $item->student_id_number))
            ->unique()
            ->values()
            ->all();
    }

    public function show(Request $request, Application $application): View
    {
        $application->load(['studentProfile.user', 'sponsorshipProgram.sponsor', 'documents']);

        $eligibilityErrors = $application->sponsorshipProgram->beneficiaryEligibilityErrors($application);

        return view('fassg.applications.show', [
            'user' => $this->actor($request),
            'application' => $application,
            'eligibilityErrors' => $eligibilityErrors,
        ]);
    }

    public function verify(VerifyApplicationRequest $request, Application $application): RedirectResponse
    {
        $newFiles = [];
        try {
            DB::transaction(function () use ($request, $application, &$newFiles): void {
                $program = SponsorshipProgram::query()->lockForUpdate()->findOrFail($application->sponsorship_program_id);
                $requestedBatch = $request->route('generatedBatch');
                if (! $requestedBatch instanceof GeneratedBatch) {
                    throw ValidationException::withMessages(['batch' => 'Review and verify this member from a Saved Generated Batch.']);
                }
                $batch = GeneratedBatch::query()->lockForUpdate()->findOrFail($requestedBatch->id);
                abort_unless($batch->status === GeneratedBatchStatus::Saved && $batch->submission_version === 0
                    && $batch->submitted_roster === null, 403, 'Only an unsubmitted Saved batch can be verified.');
                $record = Application::query()->lockForUpdate()->findOrFail($application->id);
                abort_unless((int) $record->batch_id === (int) $batch->id
                    && (int) $batch->sponsorship_program_id === (int) $program->id
                    && $batch->items()->where('application_id', $record->id)->exists(), 404);
                StudentProfile::query()->lockForUpdate()->findOrFail($record->student_profile_id);
                abort_unless($record->status === ApplicationStatus::Pending, 403, 'Only pending processing records can be verified.');
                abort_if($record->batchCandidates()->whereHas('generatedBatch', fn ($batch) => $batch->where('submission_version', '>', 0))->exists(), 403, 'Submitted records require a new review process.');

                if ($record->is_auto_provisioned) {
                    $values = $request->safe()->only(['gpa_submitted', 'address_submitted', 'is_rural_submitted',
                        'employee_name', 'employee_id_number', 'employee_relationship']);
                    $record->fill($values);
                    foreach ($request->file('qualification_documents', []) as $type => $file) {
                        $path = $file->store('applications/documents', 'local');
                        $newFiles[] = $path;
                        $record->documents()->create([
                            'document_type' => $type, 'file_path' => $path, 'file_name' => $file->getClientOriginalName(),
                        ]);
                    }
                }
                $record->unsetRelation('documents');
                $errors = $program->beneficiaryEligibilityErrors($record);
                if ($record->studentProfile?->hasConflictingActiveSponsorship()) {
                    $errors[] = 'An active sponsorship prevents verification for another award.';
                }
                if ($errors !== []) {
                    throw ValidationException::withMessages(['application' => $errors]);
                }
                $record->fill(['status' => ApplicationStatus::Verified, 'verified_at' => now()]);
                if ($record->is_auto_provisioned) {
                    $record->fill(['endorsed_by_id' => $record->endorsed_by_id ?? $request->user()->id, 'endorsed_at' => $record->endorsed_at ?? now()]);
                }
                $record->save();
                \App\Models\AuditLog::record('fassg.application.verified', 'applications', $request->user(), $request->ip(),
                    json_encode(['application_id' => $record->id, 'generated_batch_id' => $batch->id, 'fixed_list_processing' => $record->is_auto_provisioned,
                        'verification_id' => $record->studentProfile->sleFheVerification?->id,
                        'qualifications' => $record->only(['gpa_submitted', 'address_submitted', 'is_rural_submitted'])]));
            });
        } catch (\Throwable $exception) {
            foreach ($newFiles as $path) {
                Storage::disk('local')->delete($path);
            }
            throw $exception;
        }
        NotificationService::notifyApplicationProgress($application->fresh(), ApplicationStatus::Verified);

        return back()->with('status', 'FASSG verification recorded. Sponsor approval still requires the submitted Generated Batch.');
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
            ->map(static fn(DocumentType $type): string => DocumentType::canonicalValue($type))
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
        return back()->withErrors(['batch' => 'Review and verify this member from a Saved Generated Batch.']);
    }

    public function downloadDocument(Application $application, ApplicationDocument $applicationDocument): BinaryFileResponse
    {
        abort_unless($applicationDocument->application_id === $application->id, 404);

        $disk = Storage::disk('local')->exists($applicationDocument->file_path) ? 'local' : 'public';
        $path = Storage::disk($disk)->path($applicationDocument->file_path);
        abort_unless(is_file($path), 404, 'Document file not found.');

        $fileName = addcslashes(basename($applicationDocument->file_name), '\\"');
        $mimeType = mime_content_type($path) ?: 'application/octet-stream';

        return response()->file($path, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
        ]);
    }

    public function verifySleFhe(Request $request, StudentProfile $studentProfile): RedirectResponse
    {
        return back()->withErrors(['verification' => 'Use the FASSG SLE-FHE verification screen to review and record actual eligibility evidence.']);
    }
}
