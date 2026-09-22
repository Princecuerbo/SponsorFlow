<?php

namespace App\Http\Controllers\Fassg;

use App\Enums\ApplicationStatus;
use App\Enums\FixedListItemStatus;
use App\Enums\FixedListStatus;
use App\Http\Controllers\Concerns\ResolvesModuleContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Fassg\ImportFixedListRequest;
use App\Http\Requests\Fassg\StoreFixedListItemRequest;
use App\Http\Requests\Fassg\StoreFixedListRequest;
use App\Models\Application;
use App\Models\FixedList;
use App\Models\FixedListItem;
use App\Models\SleFheVerification;
use App\Models\SponsorshipProgram;
use App\Notifications\ApplicationStatusUpdated;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
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
            ->whereDoesntHave('items', fn ($query) => $query->whereNotNull('application_id'))
            ->latest()
            ->get();

        return view('fassg.fixed_lists.index', [
            'user' => $this->actor($request),
            'lists' => $lists,
            'fixedLists' => $lists,
            'programs' => SponsorshipProgram::query()->orderBy('program_name')->get(),
        ]);
    }

    public function generatedIndex(Request $request): View
    {
        $programId = $request->integer('sponsorship_program_id', 0);

        $lists = FixedList::query()
            ->with('sponsorshipProgram')
            ->withCount('items')
            ->whereHas('items', fn ($query) => $query->whereNotNull('application_id'))
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

    public function showGenerated(Request $request, FixedList $fixedList): View
    {
        $fixedList->load(['sponsorshipProgram', 'items.application']);

        $fixedList->setRelation(
            'items',
            $fixedList->items->sortBy(
                static fn (FixedListItem $item) => $item->rank_position ?? PHP_FLOAT_MAX,
            ),
        );

        return view('fassg.generated_batches.show', [
            'user' => $this->actor($request),
            'list' => $fixedList,
            'fixedList' => $fixedList,
        ]);
    }

    public function destroyGenerated(Request $request, FixedList $fixedList): RedirectResponse
    {
        $this->assertListEditable($fixedList);

        DB::transaction(function () use ($fixedList): void {
            // Hard-delete the linked items so the source applications are
            // released back into the eligible Application Queue.
            $applicationIds = $fixedList->items()
                ->whereNotNull('application_id')
                ->pluck('application_id');

            $fixedList->items()->delete();
            $fixedList->delete();

            if ($applicationIds->isNotEmpty()) {
                Application::query()
                    ->whereKey($applicationIds)
                    ->update([
                        'is_batched' => false,
                        'batch_id' => null,
                    ]);
            }
        });

        $this->audit($request, 'fassg.generated_batch.deleted', 'fixed_lists');

        return redirect()
            ->route('fassg.generated-batches.index')
            ->with('status', 'Generated batch deleted successfully. All linked applications have been unbatched and returned to the queue.');
    }

    public function show(Request $request, FixedList $fixedList): View
    {
        $fixedList->load(['sponsorshipProgram', 'items.application']);

        return view('fassg.fixed_lists.show', [
            'user' => $this->actor($request),
            'list' => $fixedList,
            'fixedList' => $fixedList,
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
        $this->assertListEditable($fixedList);

        $fixedList->delete();
        $this->audit($request, 'fassg.fixed_list.deleted', 'fixed_lists');

        return redirect()
            ->route('fassg.fixed-lists.index')
            ->with('status', 'Fixed list deleted successfully.');
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

    public function encode(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'fixed_list_id' => ['required', 'integer', 'exists:fixed_lists,id'],
            'student_name' => ['required', 'string', 'max:150'],
            'student_id_number' => ['required', 'string', 'max:50'],
            'course' => ['required', 'string', 'max:150'],
            'year_level' => ['required', 'integer', 'min:1', 'max:5'],
            'campus' => ['required', 'string', 'max:150'],
        ]);

        $fixedList = FixedList::query()->findOrFail($validated['fixed_list_id']);
        $this->assertListEditable($fixedList);
        $fixedList->items()->updateOrCreate(
            ['student_id_number' => $validated['student_id_number']],
            [...$validated, 'is_sle_fhe_verified' => false, 'is_fixed_list' => true, 'origin_type' => 'fixed_list', 'status' => FixedListItemStatus::Pending],
        );
        $this->refreshTotalNames($fixedList);
        $this->audit($request, 'fassg.fixed_list.item_encoded', 'fixed_list_items');

        return redirect()->route('fassg.fixed-lists.show', $fixedList)->with('status', 'Student added to the fixed list.');
    }

    public function storeItem(StoreFixedListItemRequest $request, FixedList $fixedList): RedirectResponse
    {
        $this->assertListEditable($fixedList);

        $item = $fixedList->items()->updateOrCreate(
            ['student_id_number' => $request->string('student_id_number')->toString()],
            [
                ...$request->validated(),
                'is_sle_fhe_verified' => false,
                'is_fixed_list' => true,
                'origin_type' => 'fixed_list',
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

    public function submit(Request $request, FixedList $fixedList): RedirectResponse
    {
        $this->assertListEditable($fixedList);

        if ($fixedList->items()->count() === 0) {
            return back()->withErrors(['list' => 'Encode or upload at least one student before submitting.']);
        }

        $items = $fixedList->items()
            ->with('application')
            ->whereNotNull('application_id')
            ->get();

        $applications = $items
            ->map(static fn (FixedListItem $item) => $item->application)
            ->filter();

        $blockedStatuses = [
            ApplicationStatus::Pending,
            ApplicationStatus::ResubmissionRequested,
        ];

        if ($applications->contains(static fn ($application) => in_array($application->status, $blockedStatuses, true))) {
            return back()->withErrors([
                'list' => 'Cannot submit batch to sponsor. All applications in the batch must be in verified status.',
            ]);
        }

        // Rejected applicants are automatically excluded from the batch forwarded to
        // the sponsor (see the batch detail filter in the sponsor lists show query),
        // so flagged as Ineligible here to keep the forwarded items consistent.
        $items
            ->filter(static fn (FixedListItem $item) => $item->application?->status === ApplicationStatus::Rejected)
            ->each(static fn (FixedListItem $item) => $item->update(['status' => FixedListItemStatus::Ineligible]));

        $fixedList->update(['status' => FixedListStatus::Submitted]);
        $this->refreshTotalNames($fixedList);
        $this->audit($request, 'fassg.fixed_list.submitted', 'fixed_lists');

        $this->notifyShortlistedStudents($fixedList);

        return back()->with('status', 'Fixed list submitted for sponsor confirmation.');
    }

    public function publish(Request $request, FixedList $fixedList): RedirectResponse
    {
        return $this->submit($request, $fixedList);
    }

    public function verifyItem(Request $request, FixedList $fixedList, FixedListItem $fixedListItem): RedirectResponse
    {
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
                'status' => FixedListItemStatus::Ineligible,
            ]);

            return back()->withErrors([
                'verify' => "No registered student account found with ID {$fixedListItem->student_id_number}.",
            ]);
        }

        if (! $profile->isSleFheVerified()) {
            return back()->withErrors([
                'verify' => "Student account {$fixedListItem->student_id_number} exists, but their SLE-FHE profile has not been verified yet.",
            ]);
        }

        DB::transaction(function () use ($fixedListItem, $profile): void {
            $fixedListItem->update([
                'is_sle_fhe_verified' => true,
                'status' => FixedListItemStatus::Verified,
            ]);

            SleFheVerification::firstOrCreate(
                ['student_profile_id' => $profile->id],
                ['verified_address' => '', 'verified_at' => now()],
            );
        });

        $this->audit($request, 'fassg.fixed_list.sle_fhe_verified', 'fixed_list_items');

        return back()->with('status', "SLE-FHE verified for {$fixedListItem->student_id_number}.");
    }

    public function endorseItem(Request $request, FixedList $fixedList, FixedListItem $fixedListItem): RedirectResponse
    {
        abort_unless($fixedListItem->fixed_list_id === $fixedList->id, 404);

        $newStatus = ! $fixedListItem->is_manually_endorsed;

        $fixedListItem->update([
            'is_manually_endorsed' => $newStatus,
            'endorsed_by_id' => $newStatus ? $this->actor($request)->id : null,
            'endorsed_at' => $newStatus ? now() : null,
            'status' => $newStatus ? FixedListItemStatus::Endorsed : FixedListItemStatus::Pending,
        ]);

        $this->audit($request, $newStatus ? 'fassg.fixed_list.item_endorsed' : 'fassg.fixed_list.item_endorsement_revoked', 'fixed_list_items');

        return back()->with('status', $newStatus
            ? "{$fixedListItem->student_name} has been manually endorsed."
            : "Endorsement removed for {$fixedListItem->student_name}.");
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
                    'is_sle_fhe_verified' => false,
                    'is_fixed_list' => true,
                    'origin_type' => 'fixed_list',
                    'status' => FixedListItemStatus::Pending,
                ],
            );
        }

        $this->refreshTotalNames($fixedList);
    }

    /**
     * Notify the students on a shortlist that their application has been
     * forwarded to the sponsor for review.
     */
    private function notifyShortlistedStudents(FixedList $fixedList): void
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
            ->whereHas('studentProfile', fn ($query) => $query->whereIn('student_id_number', $studentIds))
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
