<?php

namespace App\Http\Controllers\Fassg;

use App\Enums\FixedListItemStatus;
use App\Enums\FixedListStatus;
use App\Http\Controllers\Concerns\ResolvesModuleContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Fassg\ImportFixedListRequest;
use App\Http\Requests\Fassg\StoreFixedListItemRequest;
use App\Http\Requests\Fassg\StoreFixedListRequest;
use App\Models\FixedList;
use App\Models\FixedListItem;
use App\Models\SponsorshipProgram;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\UploadedFile;
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
        $fixedList->load(['sponsorshipProgram', 'items']);

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
                ...$request->safe()->except(['file', 'list_file']),
                'uploaded_by_fassg_id' => $this->actor($request)->id,
                'total_names' => 0,
                'status' => FixedListStatus::Draft,
            ]);

            $uploadedFile = $request->file('file') ?? $request->file('list_file');
            if ($uploadedFile instanceof UploadedFile && $uploadedFile->isValid()) {
                $this->processCsvImport($list, $uploadedFile);
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
        ]);

        $fixedList = FixedList::query()->findOrFail($validated['fixed_list_id']);
        $this->assertListEditable($fixedList);
        $fixedList->items()->updateOrCreate(
            ['student_id_number' => $validated['student_id_number']],
            [...$validated, 'is_sle_fhe_verified' => false, 'status' => FixedListItemStatus::Pending],
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

        $fixedList->update(['status' => FixedListStatus::Submitted]);
        $this->refreshTotalNames($fixedList);
        $this->audit($request, 'fassg.fixed_list.submitted', 'fixed_lists');

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

        if (! $profile->is_sle_fhe_verified) {
            return back()->withErrors([
                'verify' => "Student account {$fixedListItem->student_id_number} exists, but their SLE-FHE profile has not been verified yet.",
            ]);
        }

        DB::transaction(function () use ($fixedListItem, $profile): void {
            $fixedListItem->update([
                'is_sle_fhe_verified' => true,
                'status' => FixedListItemStatus::Verified,
            ]);

            $profile->update(['is_sle_fhe_verified' => true]);
        });

        $this->audit($request, 'fassg.fixed_list.sle_fhe_verified', 'fixed_list_items');

        return back()->with('status', "SLE-FHE verified for {$fixedListItem->student_id_number}.");
    }

    private function assertListEditable(FixedList $fixedList): void
    {
        abort_unless(
            in_array($fixedList->status, [FixedListStatus::Draft, FixedListStatus::Rejected], true),
            403,
            'This fixed list can no longer be edited.',
        );
    }

    private function refreshTotalNames(FixedList $fixedList): void
    {
        $fixedList->update(['total_names' => $fixedList->items()->count()]);
    }

    private function processCsvImport(FixedList $fixedList, UploadedFile $uploadedFile): void
    {
        $file = new SplFileObject($uploadedFile->getRealPath());
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);
        $header = null;

        foreach ($file as $row) {
            if (! is_array($row) || $row === [null] || $row === false) {
                continue;
            }

            $row = array_map(static fn($value) => is_string($value) ? trim($value) : $value, $row);

            if ($header === null) {
                $header = array_map(
                    static fn($value) => strtolower(str_replace(' ', '_', (string) $value)),
                    $row,
                );

                continue;
            }

            $record = array_combine($header, array_pad($row, count($header), null));

            if (! is_array($record) || blank($record['student_id_number'] ?? $record['student_id'] ?? null)) {
                continue;
            }

            $studentId = trim((string) ($record['student_id_number'] ?? $record['student_id']));
            $fixedList->items()->updateOrCreate(
                ['student_id_number' => $studentId],
                [
                    'student_name' => trim((string) ($record['student_name'] ?? $record['name'] ?? 'Unknown')),
                    'course' => trim((string) ($record['course'] ?? 'Unspecified')),
                    'year_level' => (int) ($record['year_level'] ?? $record['year'] ?? 1),
                    'is_sle_fhe_verified' => false,
                    'status' => FixedListItemStatus::Pending,
                ],
            );
        }

        $this->refreshTotalNames($fixedList);
    }
}
