<?php

namespace App\Http\Controllers\Fassg;

use App\Enums\FixedListItemStatus;
use App\Enums\FixedListStatus;
use App\Http\Controllers\Concerns\ResolvesModuleContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Fassg\ImportFixedListRequest;
use App\Http\Requests\Fassg\StoreFixedListItemRequest;
use App\Http\Requests\Fassg\StoreFixedListRequest;
use App\Models\AcademicProgram;
use App\Models\AuditLog;
use App\Models\BatchCandidate;
use App\Models\FixedList;
use App\Models\FixedListItem;
use App\Models\GeneratedBatch;
use App\Models\SleFheVerification;
use App\Models\SponsorshipProgram;
use App\Models\StudentProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use SplFileObject;

class FixedListController extends Controller
{
    use ResolvesModuleContext;

    public function index(Request $request): View
    {
        $lists = FixedList::query()
            ->with('sponsorshipProgram')
            ->withCount('items')
            ->latest()
            ->get();

        return view('fassg.fixed_lists.index', [
            'user' => $this->actor($request),
            'lists' => $lists,
            'fixedLists' => $lists,
            'programs' => SponsorshipProgram::query()->orderBy('program_name')->get(),
        ]);
    }

    public function show(Request $request, FixedList $fixedList): View
    {
        $fixedList->load(['sponsorshipProgram', 'items.application', 'items.studentProfile']);

        return view('fassg.fixed_lists.show', [
            'user' => $this->actor($request),
            'list' => $fixedList,
            'fixedList' => $fixedList,
            'endorsementErrors' => $fixedList->items->mapWithKeys(fn (FixedListItem $item) =>
                [$item->id => $item->endorsementErrors($fixedList->sponsorshipProgram)]),
            'academicPrograms' => AcademicProgram::query()->where('is_active', true)
                ->where('is_undergraduate', true)->orderBy('name')->get(),
            'yearLevels' => StoreFixedListItemRequest::YEAR_LEVELS,
        ]);
    }

    public function edit(Request $request, FixedList $fixedList): View
    {
        $this->assertListEditable($fixedList);

        return view('fassg.fixed_lists.edit', [
            'user' => $this->actor($request),
            'list' => $fixedList,
            'fixedList' => $fixedList,
        ]);
    }

    public function update(Request $request, FixedList $fixedList): RedirectResponse
    {
        $this->assertListEditable($fixedList);

        $validated = $request->validate([
            'batch_name' => ['required', 'string', 'max:150'],
            'redirect_to' => ['nullable', 'url'],
        ]);

        $fixedList->update(['batch_name' => $validated['batch_name']]);
        $this->audit($request, 'fassg.fixed_list.updated', 'fixed_lists');

        $targetUrl = $validated['redirect_to'] ?? route('fassg.fixed-lists.show', $fixedList->id);
        $targetHost = parse_url($targetUrl, PHP_URL_HOST);
        $applicationHost = parse_url(config('app.url'), PHP_URL_HOST);

        if ($targetHost !== null && $applicationHost !== null && $targetHost !== $applicationHost) {
            $targetUrl = route('fassg.fixed-lists.show', $fixedList->id);
        }

        return redirect($targetUrl)->with('success', 'Batch name updated successfully.');
    }

    public function destroy(Request $request, FixedList $fixedList): RedirectResponse
    {
        abort_unless($request->user()?->isFassg(), 403);
        DB::transaction(function () use ($request, $fixedList): void {
            SponsorshipProgram::query()->lockForUpdate()->findOrFail($fixedList->sponsorship_program_id);
            $list = FixedList::query()->lockForUpdate()->findOrFail($fixedList->id);
            $this->assertListEditable($list);
            $this->assertNoListReferences($list, $list->items()->pluck('id')->all());
            AuditLog::record('fassg.fixed_list.archived', 'fixed_lists', $request->user(), $request->ip(),
                json_encode(['fixed_list_id' => $list->id, 'before' => $list->getAttributes()]));
            $list->delete(); // Existing SoftDeletes retains the list and all its original members.
        }, 3);

        return redirect()
            ->route('fassg.fixed-lists.index')
            ->with('status', 'Fixed list archived. Its original members and evidence were retained.');
    }

    public function removeItem(Request $request, FixedList $fixedList, FixedListItem $fixedListItem): RedirectResponse
    {
        abort_unless($request->user()?->isFassg(), 403);
        DB::transaction(function () use ($request, $fixedList, $fixedListItem): void {
            SponsorshipProgram::query()->lockForUpdate()->findOrFail($fixedList->sponsorship_program_id);
            $list = FixedList::query()->lockForUpdate()->findOrFail($fixedList->id);
            abort_unless($list->status === FixedListStatus::Draft, 403, 'Only an editable Draft Fixed List permits student removal.');
            $item = $list->items()->whereKey($fixedListItem->id)->lockForUpdate()->firstOrFail();
            $this->assertNoListReferences($list, [$item->id]);
            if ($item->fassg_assigned_at !== null || $item->fassg_assigned_by_id !== null) {
                throw ValidationException::withMessages(['fixed_list' => 'This student has historical forwarding evidence and cannot be removed.']);
            }
            AuditLog::record('fassg.fixed_list.student_removed', 'fixed_list_items', $request->user(), $request->ip(),
                json_encode(['fixed_list_id' => $list->id, 'item_id' => $item->id, 'before' => $item->getAttributes()]));
            $item->delete();
            $list->update(['total_names' => $list->items()->count()]);
        }, 3);

        return back()->with('status', 'Student removed from this draft list. Student and verification records were retained.');
    }

    private function assertNoListReferences(FixedList $list, array $itemIds): void
    {
        $referenced = $list->fassg_assigned_at !== null || $list->fassg_assigned_by_id !== null
            || $list->sponsorApprovals()->exists()
            || GeneratedBatch::withTrashed()->where('fixed_list_id', $list->id)->exists()
            || BatchCandidate::query()->whereIn('fixed_list_item_id', $itemIds)->exists();
        // Also protect frozen source identities even if a historical membership link is missing.
        if (! $referenced && $itemIds !== []) {
            $referenced = GeneratedBatch::withTrashed()->where(function ($query) use ($itemIds): void {
                foreach ($itemIds as $id) {
                    $query->orWhereJsonContains('submitted_roster->members', ['fixed_list_item_id' => (int) $id]);
                }
            })->exists();
        }
        if ($referenced) {
            throw ValidationException::withMessages(['fixed_list' => 'This list or student is referenced by a Generated Batch or historical approval/forwarding evidence. Removal and archiving are blocked.']);
        }
    }

    public function store(StoreFixedListRequest $request): RedirectResponse
    {
        $list = DB::transaction(function () use ($request): FixedList {
            $list = FixedList::query()->create([
                ...$request->safe()->except(['file', 'list_file', 'criteria_course', 'criteria_campus', 'criteria_gpa']),
                'uploaded_by_fassg_id' => $this->actor($request)->id,
                'total_names' => 0,
                'status' => FixedListStatus::Draft,
            ]);

            $uploadedFile = $request->file('file') ?? $request->file('list_file');
            if ($uploadedFile instanceof UploadedFile && $uploadedFile->isValid()) {
                $this->processCsvImport($list, $uploadedFile, [
                    'course' => $request->string('criteria_course')->trim()->toString(),
                    'campus' => $request->string('criteria_campus')->trim()->toString(),
                    'gpa' => (float) $request->input('criteria_gpa', 0),
                ]);
            }

            return $list;
        });

        $this->audit($request, 'fassg.fixed_list.created', 'fixed_lists');

        return redirect()
            ->route('fassg.fixed-lists.show', $list)
            ->with('status', 'Fixed list batch created. Encode names or upload a CSV.');
    }

    public function upload(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sponsorship_program_id' => ['required', 'integer', 'exists:sponsorship_programs,id'],
            'batch_name' => ['required', 'string', 'max:150'],
            'list_file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $fixedList = FixedList::query()->create([
            'sponsorship_program_id' => $validated['sponsorship_program_id'],
            'batch_name' => $validated['batch_name'],
            'uploaded_by_fassg_id' => $request->user()->id,
            'total_names' => 0,
            'status' => FixedListStatus::Draft,
        ]);

        $this->processCsvImport($fixedList, $request->file('file') ?? $request->file('list_file'));
        $this->audit($request, 'fassg.fixed_list.imported', 'fixed_lists');

        return redirect()->route('fassg.fixed-lists.show', $fixedList)->with('status', 'Fixed list uploaded and ready for verification.');
    }

    public function encode(StoreFixedListItemRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $fixedList = FixedList::query()->findOrFail($validated['fixed_list_id']);
        $this->assertListEditable($fixedList);
        $isSleFheVerified = StudentProfile::query()
            ->where('student_id_number', $validated['student_id_number'])
            ->sleFheVerified()
            ->exists();

        $fixedList->items()->updateOrCreate(
            ['student_id_number' => $validated['student_id_number']],
            [...$validated, 'is_sle_fhe_verified' => $isSleFheVerified, 'is_fixed_list' => true, 'status' => FixedListItemStatus::Pending],
        );
        $this->refreshTotalNames($fixedList);
        $this->audit($request, 'fassg.fixed_list.item_encoded', 'fixed_list_items');

        return redirect()->route('fassg.fixed-lists.show', $fixedList)->with('status', 'Student added to the fixed list.');
    }

    public function storeItem(StoreFixedListItemRequest $request, FixedList $fixedList): RedirectResponse
    {
        $this->assertListEditable($fixedList);

        $studentId = $request->string('student_id_number')->toString();

        $isSleFheVerified = StudentProfile::query()
            ->where('student_id_number', $studentId)
            ->sleFheVerified()
            ->exists();

        $item = $fixedList->items()->updateOrCreate(
            ['student_id_number' => $studentId],
            [
                ...$request->validated(),
                'is_sle_fhe_verified' => $isSleFheVerified,
                'is_fixed_list' => true,
                'status' => FixedListItemStatus::Pending,
            ],
        );

        $this->refreshTotalNames($fixedList);
        $this->audit($request, 'fassg.fixed_list.item_encoded', 'fixed_list_items');

        return back()->with('status', "Encoded {$item->student_name} ({$item->student_id_number}).");
    }

    public function import(ImportFixedListRequest $request, FixedList $fixedList): RedirectResponse
    {
        $this->assertListEditable($fixedList);

        $this->processCsvImport($fixedList, $request->file('file') ?? $request->file('list_file'));
        $this->audit($request, 'fassg.fixed_list.imported', 'fixed_lists');

        return back()->with('status', 'Student list imported successfully.');
    }

    public function finalize(Request $request, FixedList $fixedList): RedirectResponse
    {
        abort_unless($request->user()?->isFassg(), 403);
        DB::transaction(function () use ($request, $fixedList): void {
            $program = SponsorshipProgram::query()->lockForUpdate()->findOrFail($fixedList->sponsorship_program_id);
            $list = FixedList::query()->lockForUpdate()->findOrFail($fixedList->id);
            $this->assertListEditable($list);
            $items = $this->lockListItems($list);
            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['list' => 'Encode or upload at least one student before finalizing.']);
            }
            foreach ($items as $item) {
                if (! $item->hasRecordedEndorsement() || $item->endorsementErrors($program) !== []) {
                    throw ValidationException::withMessages(['list' => 'Cannot finalize list: every candidate must have documented SLE-FHE verification, a recorded FASSG endorsement, and satisfy the list preparation checks. Review entry '.$item->id.'.']);
                }
            }
            $list->update([
                'status' => FixedListStatus::Finalized,
                'fassg_assigned_at' => now(),
                'fassg_assigned_by_id' => $request->user()->id,
            ]);
            AuditLog::record('fassg.fixed_list.finalized', 'fixed_lists', $request->user(), $request->ip(),
                json_encode(['fixed_list_id' => $list->id, 'item_ids' => $items->modelKeys()]));
        }, 3);

        return back()->with('status', 'Fixed List has been finalized and is ready for batch generation.');
    }

    public function verifyItem(Request $request, FixedList $fixedList, FixedListItem $fixedListItem): RedirectResponse
    {
        $this->assertListEditable($fixedList);
        abort_unless($fixedListItem->fixed_list_id === $fixedList->id, 404);

        if (blank($fixedListItem->student_id_number)) {
            return back()->withErrors([
                'verify' => "Cannot verify {$fixedListItem->student_name}: No Student ID number provided.",
            ]);
        }

        $profile = $fixedListItem->matchingStudentProfile();

        if ($profile === null) {
            $fixedListItem->update([
                'is_sle_fhe_verified' => false,
                'status' => FixedListItemStatus::Pending,
            ]);

            return back()->withErrors([
                'verify' => "No registered student account found with ID {$fixedListItem->student_id_number}.",
            ]);
        }

        if (! $profile->hasDocumentedSleFheVerification()) {
            return back()->withErrors([
                'verify' => "Student account {$fixedListItem->student_id_number} exists, but their SLE-FHE profile has not been verified yet.",
            ]);
        }

        $fassg = $this->actor($request);

        DB::transaction(function () use ($fixedListItem, $profile, $fassg): void {
            $fixedListItem->update([
                'is_sle_fhe_verified' => true,
                'status' => FixedListItemStatus::Verified,
            ]);

            // A list check references existing SLE-FHE evidence; it never rewrites that evidence.
        });

        $this->audit($request, 'fassg.fixed_list.sle_fhe_verified', 'fixed_list_items');

        return back()->with('status', "SLE-FHE verified for {$fixedListItem->student_id_number}.");
    }

    public function endorseItem(Request $request, FixedList $fixedList, FixedListItem $fixedListItem): RedirectResponse
    {
        abort_unless($request->user()?->isFassg(), 403);
        abort_unless((int) $fixedListItem->fixed_list_id === (int) $fixedList->id, 404);
        $request->validate([
            'endorse' => ['sometimes', 'boolean'],
            ...$this->noCriteriaOverrideRules(),
        ]);
        // Repeating Endorse is idempotent. Revocation must be explicitly requested.
        $endorse = $request->boolean('endorse', true);
        $this->updateEndorsements($request, $fixedList, [$fixedListItem->id], $endorse);

        return back()->with('status', $endorse
            ? "{$fixedListItem->student_name} has been endorsed by FASSG for list preparation. Sponsor approval is separate."
            : "Endorsement removed for {$fixedListItem->student_name}.");
    }

    public function endorseSelected(Request $request, FixedList $fixedList): RedirectResponse
    {
        abort_unless($request->user()?->isFassg(), 403);
        $validated = $request->validate([
            'selected_items' => ['required', 'array', 'min:1'],
            'selected_items.*' => ['required', 'integer', 'min:1'],
            ...$this->noCriteriaOverrideRules(),
        ]);
        $ids = array_values(array_unique(array_map('intval', $validated['selected_items'])));
        $this->updateEndorsements($request, $fixedList, $ids, true);

        return back()->with('status', count($ids).' selected student(s) endorsed by FASSG for list preparation. Sponsor approval is separate.');
    }

    private function noCriteriaOverrideRules(): array
    {
        return array_fill_keys(['outside_criteria', 'is_outside_criteria', 'override_criteria', 'exception_reason'], ['prohibited']);
    }

    private function updateEndorsements(Request $request, FixedList $fixedList, array $ids, bool $endorse): void
    {
        DB::transaction(function () use ($request, $fixedList, $ids, $endorse): void {
            $program = SponsorshipProgram::query()->lockForUpdate()->findOrFail($fixedList->sponsorship_program_id);
            $list = FixedList::query()->lockForUpdate()->findOrFail($fixedList->id);
            $this->assertListEditable($list);
            $items = $this->lockListItems($list, $ids);
            if ($items->count() !== count($ids)) {
                throw ValidationException::withMessages(['selected_items' => 'Every selected entry must belong to this fixed list.']);
            }
            // Validate the complete selection before changing any endorsement.
            if ($endorse) {
                foreach ($items as $item) {
                    $errors = $item->endorsementErrors($program);
                    if ($errors !== []) {
                        throw ValidationException::withMessages(['endorsement' => 'Entry '.$item->id.': '.implode(' ', $errors)]);
                    }
                }
            }
            foreach ($items as $item) {
                if ($item->is_manually_endorsed === $endorse) {
                    continue; // Preserve the original officer and timestamp on retries.
                }
                $before = $item->only(['status', 'is_manually_endorsed', 'endorsed_by_id', 'endorsed_at']);
                $item->update([
                    'is_manually_endorsed' => $endorse,
                    'endorsed_by_id' => $endorse ? $request->user()->id : null,
                    'endorsed_at' => $endorse ? now() : null,
                    'status' => $endorse ? FixedListItemStatus::Endorsed : FixedListItemStatus::Pending,
                ]);
                AuditLog::record($endorse ? 'fassg.fixed_list.item_endorsed' : 'fassg.fixed_list.item_endorsement_revoked',
                    'fixed_list_items', $request->user(), $request->ip(), json_encode([
                        'fixed_list_id' => $list->id, 'item_id' => $item->id, 'action_route' => $request->route()?->getName(),
                        'before' => $before, 'after' => $item->only(array_keys($before)),
                    ]));
            }
        }, 3);
    }

    private function lockListItems(FixedList $list, ?array $ids = null): \Illuminate\Database\Eloquent\Collection
    {
        $items = $list->items()->when($ids !== null, fn ($query) => $query->whereIn('id', $ids))
            ->orderBy('id')->lockForUpdate()->get();
        $profiles = StudentProfile::query()->whereIn('student_id_number', $items->pluck('student_id_number'))
            ->orderBy('id')->lockForUpdate()->get()->keyBy('student_id_number');
        SleFheVerification::query()->whereIn('student_profile_id', $profiles->pluck('id'))
            ->orderBy('id')->lockForUpdate()->get();
        foreach ($items as $item) {
            $item->setRelation('studentProfile', $profiles->get($item->student_id_number));
        }

        return $items;
    }

    private function assertListEditable(FixedList $fixedList): void
    {
        abort_unless(
            in_array($fixedList->status, [FixedListStatus::Draft, FixedListStatus::Rejected, FixedListStatus::Saved], true),
            403,
            'This fixed list can no longer be edited.',
        );
    }

    private function refreshTotalNames(FixedList $fixedList): void
    {
        $fixedList->update([
            'total_names' => $fixedList->items()
                ->where('status', '!=', FixedListItemStatus::Ineligible)
                ->count(),
        ]);
    }

    private function processCsvImport(FixedList $fixedList, UploadedFile $uploadedFile, array $criteria = []): void
    {
        $criteriaCourse = trim((string) ($criteria['course'] ?? ''));
        $criteriaCampus = trim((string) ($criteria['campus'] ?? ''));
        $criteriaGpa = (float) ($criteria['gpa'] ?? 0);

        $file = new SplFileObject($uploadedFile->getRealPath());
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);
        $header = null;

        $verifiedByStudentId = [];

        $isSleFheVerified = static function (string $studentId) use (&$verifiedByStudentId): bool {
            return $verifiedByStudentId[$studentId] ??= StudentProfile::query()
                ->where('student_id_number', $studentId)
                ->sleFheVerified()
                ->exists();
        };

        foreach ($file as $row) {
            if (! is_array($row) || $row === [null] || $row === false) {
                continue;
            }

            $row = array_map(static fn ($value) => is_string($value) ? trim($value) : $value, $row);

            if ($header === null) {
                $header = array_map(
                    static fn ($value) => strtolower(str_replace(' ', '_', (string) $value)),
                    $row,
                );

                continue;
            }

            $record = array_combine($header, array_pad($row, count($header), null));

            if (! is_array($record) || blank($record['student_id_number'] ?? $record['student_id'] ?? null)) {
                continue;
            }

            $studentId = trim((string) ($record['student_id_number'] ?? $record['student_id']));
            $rowCourse = trim((string) ($record['course'] ?? ''));
            $rowCampus = blank($record['campus'] ?? null) ? '' : trim((string) $record['campus']);
            $gpaValue = $record['gpa'] ?? $record['gwa'] ?? null;
            $rowGpa = blank($gpaValue) ? 0.0 : (float) $gpaValue;

            if ($criteriaCourse !== '' && stripos($rowCourse, $criteriaCourse) === false) {
                continue;
            }

            if ($criteriaCampus !== '' && strcasecmp($rowCampus, $criteriaCampus) !== 0) {
                continue;
            }

            if ($criteriaGpa > 0 && $rowGpa > 0 && $rowGpa > $criteriaGpa) {
                continue;
            }

            $fixedList->items()->updateOrCreate(
                ['student_id_number' => $studentId],
                [
                    'student_name' => trim((string) ($record['student_name'] ?? $record['name'] ?? 'Unknown')),
                    'course' => $rowCourse !== '' ? $rowCourse : 'Unspecified',
                    'year_level' => (int) ($record['year_level'] ?? $record['year'] ?? 1),
                    'campus' => $rowCampus !== '' ? $rowCampus : null,
                    'is_sle_fhe_verified' => $isSleFheVerified($studentId),
                    'is_fixed_list' => true,
                    'status' => FixedListItemStatus::Pending,
                ],
            );
        }

        $this->refreshTotalNames($fixedList);
    }
}
