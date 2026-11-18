@extends('layouts.app')

@php
    $listTitle = $list->batch_name ?: ('Batch #' . $list->id . ' - ' . ($list->sponsorshipProgram->program_name ?? 'Unassigned Program'));
    $itemCount = $list->items->count();
    $itemCountText = $itemCount . ' ' . \Illuminate\Support\Str::plural('student', $itemCount);
    $listStatus = $list->status->value ?? (string) $list->status;
@endphp

@section('title', 'Fixed List Details')
@section('eyebrow', 'FASSG Office · Fixed Lists')
@section('page-title', $listTitle)
@section('subtitle', ($list->sponsorshipProgram->program_name ?? 'Unassigned Program') . ' · ' . $itemCountText)

@section('header-actions')
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('fassg.fixed-lists.index') }}"
            class="btn btn-outline-secondary btn-sm fw-semibold d-inline-flex align-items-center gap-2">
            <i class="bi bi-arrow-left"></i>Back to Fixed Lists
        </a>
        @if ($list->status === \App\Enums\FixedListStatus::Finalized)
            <a href="{{ route('fassg.applications.index', ['program_id' => $list->sponsorship_program_id, 'fixed_list_id' => $list->id, 'prepare_batch' => 1]) }}"
                class="btn btn-sf-navy btn-sm fw-semibold d-inline-flex align-items-center gap-1.5">
                <i class="bi bi-list-check"></i>Create Batch from This List
            </a>
        @endif
        <x-status-badge :status="$list->status" class="sf-fixed-list-status-badge sf-fixed-list-status-{{ strtolower($listStatus) }}" />
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2/dist/css/tom-select.bootstrap5.min.css">
    @include('fassg.partials.fixed-lists-styles')
@endpush

@section('content')
<div class="sf-fixed-list-detail-page">
    @php
        $isEditable = in_array($list->status, [\App\Enums\FixedListStatus::Draft, \App\Enums\FixedListStatus::Rejected, \App\Enums\FixedListStatus::Saved], true);
        $canEditStudents = $isEditable && $user?->isFassg();
    @endphp

    {{-- Information Banner --}}
    <div class="sf-notice-card">
        <i class="bi bi-info-circle"></i>
        <div class="sf-notice-text">
            <strong class="sf-notice-title d-block mb-1">Fixed List Preparation</strong>
            FASSG prepares and endorses sponsor-provided candidates. Finalizing this list does not verify program qualifications or constitute Sponsor approval. Program-required GWA and documents must still be explicitly verified in Generated Batch review before Sponsor submission.
        </div>
    </div>

    {{-- Finalized Status Banner (for finalized lists) --}}
    @if ($list->status === \App\Enums\FixedListStatus::Finalized)
        <div class="sf-finalized-notice-card">
            <div class="sf-finalized-notice-icon">
                <i class="bi bi-lock"></i>
            </div>
            <div>
                <div class="sf-finalized-notice-title">Fixed List Finalized</div>
                <p class="sf-finalized-notice-desc">This list is read-only and can be included in a Generated Batch for individual eligibility review. Finalization does not constitute Sponsor approval.</p>
            </div>
        </div>
    @endif

    {{-- Encoded and Imported Students Table --}}
    <div class="card sf-table-card border-0 shadow-sm mb-4">
        <div class="sf-table-card-header">
            <div>
                <h3 class="sf-table-title" id="fixed-list-students-heading">
                    <i class="bi bi-people-fill text-secondary me-2"></i>Encoded &amp; Imported Students
                </h3>
                <p class="sf-table-subtitle">{{ $itemCountText }} · Original list details and current review status</p>
            </div>
            @if ($canEditStudents)
                <button type="submit" form="bulkEndorseForm" class="btn btn-sm btn-outline-primary fw-semibold d-inline-flex align-items-center gap-1.5" id="bulkEndorseBtn" disabled>
                    <i class="bi bi-check2-circle" aria-hidden="true"></i>Endorse Selected
                </button>
            @else
                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2.5 py-1.5 fw-semibold">
                    <i class="bi bi-lock me-1"></i>Read-only list
                </span>
            @endif
        </div>

        <div class="table-responsive" role="region" tabindex="0" aria-labelledby="fixed-list-students-heading">
            <table class="table sf-table sf-fixed-list-table align-middle mb-0" id="candidateListTable">
                <caption class="visually-hidden">Fixed List students, academic details, verification, endorsement, and available actions.</caption>
                <thead>
                    <tr>
                        @if ($canEditStudents)
                            <th scope="col" style="width: 38px;" class="ps-3 text-center">
                                <input type="checkbox" class="form-check-input" id="selectAllCheckbox" aria-label="Select all eligible candidates for endorsement">
                            </th>
                        @endif
                        <th scope="col" style="width: 20%;" class="{{ $canEditStudents ? '' : 'ps-3' }}">Student</th>
                        <th scope="col" style="width: 23%;">Academic Details</th>
                        <th scope="col" style="width: 13%;">Candidate Source</th>
                        <th scope="col" style="width: 15%;">SLE-FHE Verification</th>
                        <th scope="col" style="width: 15%;">Endorsement Status</th>
                        <th scope="col" class="text-end pe-3" style="width: 14%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($list->items as $item)
                        @php
                            $profile = $item->studentProfile;
                            $hasVerificationEvidence = $profile?->hasDocumentedSleFheVerification() ?? false;
                            $isVerified = $item->is_sle_fhe_verified && $hasVerificationEvidence;
                            $itemErrors = collect($endorsementErrors[$item->id] ?? []);
                            [$verificationMessages, $endorsementMessages] = $itemErrors->partition(fn ($message) =>
                                str_contains($message, 'SLE-FHE') || str_contains($message, 'registered student profile'));
                            $endorsementStatus = $item->is_manually_endorsed ? 'Endorsed'
                                : ($item->status === \App\Enums\FixedListItemStatus::Ineligible ? 'Ineligible' : 'Pending');
                            $canEndorse = $canEditStudents && $isVerified && ! $item->is_manually_endorsed && $itemErrors->isEmpty();
                            $canVerify = $canEditStudents && ! $isVerified && $hasVerificationEvidence
                                && filled($item->student_id_number) && $item->status !== \App\Enums\FixedListItemStatus::Endorsed;
                            $canRemove = $canEditStudents && $list->status === \App\Enums\FixedListStatus::Draft;
                        @endphp
                        <tr>
                            @if ($canEditStudents)
                                <td class="ps-3 text-center">
                                    <input type="checkbox" class="form-check-input item-checkbox"
                                        name="selected_items[]" form="bulkEndorseForm" value="{{ $item->id }}"
                                        aria-label="Select {{ $item->student_name }} for endorsement" @disabled(! $canEndorse)>
                                </td>
                            @endif
                            <td class="{{ $canEditStudents ? '' : 'ps-3' }}">
                                @if (filled($item->student_name))
                                    <div class="sf-student-name">{{ $item->student_name }}</div>
                                @else
                                    <div class="sf-student-name text-secondary fst-italic">Name not recorded</div>
                                @endif
                                <span class="sf-student-id">{{ $item->student_id_number ?: 'Student ID not recorded' }}</span>
                            </td>
                            <td>
                                <div class="sf-course-name">{{ $item->course ?: 'Academic program not recorded' }}</div>
                                <div class="sf-academic-meta">
                                    {{ $item->year_level ? "Year {$item->year_level}" : 'Year level not recorded' }} · {{ $item->campus ?: 'Campus not recorded' }}
                                </div>
                            </td>
                            <td>
                                <span class="sf-source-badge-fixed" title="Sponsor-proposed Fixed List">
                                    <i class="bi bi-pin-angle-fill me-1"></i>Fixed List
                                </span>
                            </td>
                            <td>
                                @if ($isVerified)
                                    <span class="sf-status-badge-inline sf-status-verified">
                                        <i class="bi bi-shield-check"></i>Verified
                                    </span>
                                @else
                                    <span class="sf-status-badge-inline sf-status-pending">
                                        <i class="bi bi-clock"></i>Pending
                                    </span>
                                @endif
                                @foreach ($verificationMessages as $message)
                                    <p class="small text-warning-emphasis mb-0 mt-1" style="font-size: 0.71875rem;">{{ $message }}</p>
                                @endforeach
                                @if (! $isVerified && $profile !== null && ! $hasVerificationEvidence)
                                    <p class="small text-secondary mb-0 mt-1" style="font-size: 0.71875rem;">Complete FASSG SLE-FHE review first.</p>
                                @endif
                            </td>
                            <td>
                                @if ($item->is_manually_endorsed)
                                    <span class="sf-status-badge-inline sf-status-endorsed">
                                        <i class="bi bi-check2-circle"></i>Endorsed
                                    </span>
                                @elseif ($item->status === \App\Enums\FixedListItemStatus::Ineligible)
                                    <span class="sf-status-badge-inline sf-status-ineligible">
                                        <i class="bi bi-x-circle"></i>Ineligible
                                    </span>
                                @else
                                    <span class="sf-status-badge-inline sf-status-pending">
                                        <i class="bi bi-clock"></i>Pending
                                    </span>
                                @endif

                                @if ($item->status->value !== $endorsementStatus)
                                    <p class="small text-secondary mb-0 mt-1" style="font-size: 0.71875rem;">List status: {{ $item->status->value }}</p>
                                @endif
                                @if ($item->is_manually_endorsed && ! $item->hasRecordedEndorsement())
                                    <p class="small text-danger mb-0 mt-1" style="font-size: 0.71875rem;">Original endorsement evidence incomplete.</p>
                                @endif
                                @if ($endorsementMessages->isNotEmpty())
                                    <p class="small text-danger mb-0 mt-1" style="font-size: 0.71875rem;">{{ $endorsementMessages->first() }}</p>
                                    @if ($endorsementMessages->count() > 1)
                                        <details class="small mt-1" style="font-size: 0.71875rem;">
                                            <summary class="text-secondary">{{ $endorsementMessages->count() - 1 }} more checks</summary>
                                            <ul class="ps-3 mb-0 mt-1">
                                                @foreach ($endorsementMessages->skip(1) as $message)
                                                    <li>{{ $message }}</li>
                                                @endforeach
                                            </ul>
                                        </details>
                                    @endif
                                @elseif (! $item->is_manually_endorsed && $isVerified)
                                    <p class="small text-secondary mb-0 mt-1" style="font-size: 0.71875rem;">Awaiting FASSG endorsement.</p>
                                @endif
                            </td>
                            <td class="text-end pe-3">
                                <div class="sf-row-actions">
                                    @if ($canVerify)
                                        <form method="POST" action="{{ route('fassg.fixed-lists.items.verify', [$list, $item]) }}" class="d-inline">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="sf-btn-action-verify">
                                                <i class="bi bi-shield-check"></i>Verify
                                            </button>
                                        </form>
                                    @endif
                                    @if ($canEndorse)
                                        <form method="POST" id="endorse-{{ $item->id }}" action="{{ route('fassg.fixed-lists.items.endorse', [$list, $item]) }}" class="d-inline">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="endorse" value="1">
                                            <button type="submit" class="sf-btn-action-endorse">
                                                <i class="bi bi-check2-circle"></i>Endorse
                                            </button>
                                        </form>
                                    @endif
                                    @if ($canRemove)
                                        <form method="POST" action="{{ route('fassg.fixed-lists.items.destroy', [$list, $item]) }}"
                                            class="d-inline"
                                            data-remove-student-form data-student-name="{{ $item->student_name ?: 'Name not recorded' }}"
                                            data-student-id="{{ $item->student_id_number ?: 'ID not recorded' }}">
                                            @csrf @method('DELETE')
                                            <button type="button" class="sf-btn-action-remove"
                                                data-bs-toggle="modal" data-bs-target="#removeStudentModal">
                                                <i class="bi bi-trash3"></i>Remove
                                            </button>
                                        </form>
                                    @endif
                                    @if (! $canVerify && ! $canEndorse && ! $canRemove)
                                        <span class="small text-secondary">{{ $canEditStudents ? 'No actions' : 'Read-only' }}</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canEditStudents ? 7 : 6 }}" class="text-center py-4 text-secondary small">
                                No students encoded in this list yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($canEditStudents)
            <form method="POST" id="bulkEndorseForm" class="d-none" action="{{ route('fassg.fixed-lists.items.endorse-selected', $list) }}">
                @csrf @method('PATCH')
            </form>
        @endif
    </div>

    {{-- Manual Encoding, Import, and Finalization Workflow --}}
    @if ($list->status !== \App\Enums\FixedListStatus::Finalized)
        <div class="row g-4 mb-4">
            <div class="col-12 col-lg-7">
                {{-- Manual Encoding Form --}}
                @if (in_array($list->status, [\App\Enums\FixedListStatus::Draft, \App\Enums\FixedListStatus::Rejected, \App\Enums\FixedListStatus::Saved], true))
                    <div class="card sf-panel-card border-0 shadow-sm h-100">
                        <div class="card-body sf-panel-card-body">
                            <h3 class="sf-panel-title">
                                <i class="bi bi-person-plus text-primary"></i>Encode Student Manually
                            </h3>
                            <p class="sf-panel-desc">Add a sponsor-provided student directly to this fixed list roster.</p>
                            <form method="POST" action="{{ route('fassg.fixed-lists.items.store', $list) }}">
                                @csrf
                                <div class="row g-3">
                                    <div class="col-md-7">
                                        <label class="form-label" for="manual_student_name">Full Name <span class="text-danger">*</span></label>
                                        <input type="text" id="manual_student_name" name="student_name" class="form-control form-control-sm"
                                            placeholder="e.g. Juan Dela Cruz" required value="{{ old('student_name') }}">
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label" for="manual_student_id">Student ID <span class="text-danger">*</span></label>
                                        <input type="text" id="manual_student_id" name="student_id_number" class="form-control form-control-sm"
                                            placeholder="e.g. 2022-00101" required value="{{ old('student_id_number') }}">
                                    </div>
                                    <div class="col-md-5">
                                        <label for="fixed-list-course" class="form-label">Academic Program <span class="text-danger">*</span></label>
                                        <select id="fixed-list-course" name="course"
                                            class="form-select form-select-sm @error('course') is-invalid @enderror"
                                            required aria-describedby="fixed-list-course-error" aria-invalid="{{ $errors->has('course') ? 'true' : 'false' }}">
                                            <option value="" disabled @selected(old('course') === null || old('course') === '')>Select Academic Program</option>
                                            @foreach ($academicPrograms as $academicProgram)
                                                <option value="{{ $academicProgram->name }}" @selected(old('course') === $academicProgram->name)>
                                                    {{ $academicProgram->code }} — {{ $academicProgram->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div id="fixed-list-course-error" class="small text-danger mt-1">@error('course') {{ $message }} @enderror</div>
                                    </div>
                                    <div class="col-md-2">
                                        <label for="fixed-list-year-level" class="form-label">Year Level <span class="text-danger">*</span></label>
                                        <select id="fixed-list-year-level" name="year_level"
                                            class="form-select form-select-sm @error('year_level') is-invalid @enderror"
                                            required aria-describedby="fixed-list-year-error" aria-invalid="{{ $errors->has('year_level') ? 'true' : 'false' }}">
                                            <option value="">Year</option>
                                            @foreach ($yearLevels as $yearLevel)
                                                <option value="{{ $yearLevel }}" @selected((string) old('year_level') === (string) $yearLevel)>Year {{ $yearLevel }}</option>
                                            @endforeach
                                        </select>
                                        <div id="fixed-list-year-error" class="small text-danger mt-1">@error('year_level') {{ $message }} @enderror</div>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="manual_campus" class="form-label">Campus <span class="text-danger">*</span></label>
                                        <select id="manual_campus" name="campus" class="form-select form-select-sm" required>
                                            @foreach (['Main Campus (City of Mati)', 'Baganga Campus', 'Banaybanay Campus', 'Cateel Campus', 'San Isidro Campus', 'Tarragona Campus'] as $campusOption)
                                                <option value="{{ $campusOption }}"
                                                    {{ old('campus', 'Main Campus (City of Mati)') === $campusOption ? 'selected' : '' }}>
                                                    {{ $campusOption }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end">
                                        <button type="submit" class="btn btn-sf-navy btn-sm w-100 fw-semibold d-inline-flex align-items-center justify-content-center gap-1">
                                            <i class="bi bi-plus-lg"></i>Add
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif
            </div>

            <div class="col-12 col-lg-5">
                <div class="d-flex flex-column gap-4">
                    {{-- CSV / Excel Import Card --}}
                    @if (in_array($list->status, [\App\Enums\FixedListStatus::Draft, \App\Enums\FixedListStatus::Rejected, \App\Enums\FixedListStatus::Saved], true))
                        <div class="card sf-panel-card border-0 shadow-sm">
                            <div class="card-body sf-panel-card-body">
                                <h3 class="sf-panel-title">
                                    <i class="bi bi-file-earmark-arrow-up text-primary"></i>Import CSV / Excel
                                </h3>
                                <p class="sf-panel-desc mb-2">
                                    Expected columns:
                                    <span class="sf-code-pill">student_name</span>,
                                    <span class="sf-code-pill">student_id_number</span>,
                                    <span class="sf-code-pill">course</span>,
                                    <span class="sf-code-pill">year_level</span>,
                                    <span class="sf-code-pill">campus</span>
                                </p>
                                <form method="POST" action="{{ route('fassg.fixed-lists.import', $list) }}" enctype="multipart/form-data">
                                    @csrf
                                    <div class="mb-3">
                                        <input type="file" name="file" class="form-control form-control-sm" accept=".csv,.txt" required>
                                    </div>
                                    <button type="submit" class="btn btn-sf-navy btn-sm w-100 fw-semibold d-inline-flex align-items-center justify-content-center gap-1.5">
                                        <i class="bi bi-upload"></i>Import File
                                    </button>
                                </form>
                            </div>
                        </div>

                        {{-- Finalize Fixed List Card --}}
                        <div class="card sf-panel-card border-0 shadow-sm">
                            <div class="card-body sf-panel-card-body text-start">
                                @php
                                    $hasUnverifiedOrUnendorsed = $list->items->contains(
                                        fn ($item) => ! $item->hasRecordedEndorsement() || ($endorsementErrors[$item->id] ?? []) !== []
                                    );
                                @endphp
                                <h3 class="sf-panel-title">
                                    <i class="bi bi-lock text-primary"></i>Finalize Fixed List
                                </h3>
                                <p class="sf-panel-desc">
                                    Finalizing makes this list read-only and available for manual selection into a Generated Batch. It does not approve beneficiaries or reserve sponsorship slots.
                                </p>

                                @if ($hasUnverifiedOrUnendorsed)
                                    <div class="sf-action-required-card">
                                        <div class="fw-bold mb-1">
                                            <i class="bi bi-exclamation-triangle me-1"></i>Action Required
                                        </div>
                                        <div>
                                            Complete the required SLE-FHE checks and FASSG endorsements before finalizing this list.
                                        </div>
                                    </div>
                                @endif

                                @if ($list->items->isNotEmpty() && ! $hasUnverifiedOrUnendorsed)
                                    <form method="POST" action="{{ route('fassg.fixed-lists.finalize', $list) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sf-navy w-100 py-2 fw-semibold d-inline-flex align-items-center justify-content-center gap-1.5"
                                            onClick="return confirm('All students are verified and endorsed. Finalize this list for batch generation?');">
                                            <i class="bi bi-lock"></i>Finalize List
                                        </button>
                                    </form>
                                @else
                                    <button type="button" disabled class="btn btn-sf-navy w-100 py-2 fw-semibold d-inline-flex align-items-center justify-content-center gap-1.5">
                                        <i class="bi bi-lock"></i>Finalize List
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Remove Student Confirmation Modal --}}
    @if ($canEditStudents && $list->status === \App\Enums\FixedListStatus::Draft)
        <div class="modal fade" id="removeStudentModal" tabindex="-1" aria-hidden="true"
            aria-labelledby="removeStudentModalTitle" aria-describedby="removeStudentModalMessage removeStudentModalInfo"
            data-bs-keyboard="true" data-bs-backdrop="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header sf-modal-header-danger">
                        <h3 class="modal-title h5 fw-bold d-flex align-items-center gap-2" id="removeStudentModalTitle">
                            <i class="bi bi-trash3 text-danger"></i>Remove Student from Fixed List?
                        </h3>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <p id="removeStudentModalMessage" class="mb-3">
                            Are you sure you want to remove
                            <strong id="removeStudentIdentity" class="text-break"></strong> from
                            <strong class="text-break">{{ $list->batch_name ?: 'Batch #' . $list->id . ' - ' . ($list->sponsorshipProgram->program_name ?? 'Unassigned Program') }}</strong>?
                        </p>
                        <div id="removeStudentModalInfo" class="bg-light border rounded-3 p-3 small text-secondary">
                            Only this student's entry in the draft fixed list will be removed. Existing student profiles and verification records are not affected.
                        </div>
                        <div id="removeStudentError" class="alert alert-danger small mt-3 mb-0" role="alert" hidden></div>
                        <p id="removeStudentProgress" class="small text-secondary mt-3 mb-0" role="status" aria-live="polite"></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" id="cancelRemoveStudent" class="btn btn-outline-secondary btn-sm fw-semibold px-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" id="confirmRemoveStudent" class="btn btn-danger btn-sm fw-semibold px-3 d-inline-flex align-items-center gap-1.5" disabled>
                            <i class="bi bi-trash3"></i>Remove from List
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@push('scripts')
    <script>
        (function () {
            const selectAll = document.getElementById('selectAllCheckbox');
            const bulkEndorseBtn = document.getElementById('bulkEndorseBtn');
            const bulkForm = document.getElementById('bulkEndorseForm');
            if (!selectAll || !bulkEndorseBtn || !bulkForm) {
                return;
            }

            const checkboxes = Array.from(document.querySelectorAll('.item-checkbox'));
            const selectable = () => checkboxes.filter(chk => !chk.disabled);
            let submitting = false;

            function updateState() {
                const checked = selectable().filter(chk => chk.checked);
                bulkEndorseBtn.disabled = submitting || checked.length === 0;
                selectAll.disabled = submitting || selectable().length === 0;
                selectAll.checked = checked.length > 0 && checked.length === selectable().length;
                selectAll.indeterminate = checked.length > 0 && checked.length < selectable().length;
            }

            selectAll.addEventListener('change', function () {
                selectable().forEach(chk => { chk.checked = selectAll.checked; });
                updateState();
            });

            checkboxes.forEach(chk => chk.addEventListener('change', updateState));

            bulkForm.addEventListener('submit', function (event) {
                const checked = selectable().filter(chk => chk.checked);
                if (submitting || checked.length === 0) {
                    event.preventDefault();
                    return;
                }

                if (!confirm('Endorse ' + checked.length + ' selected student(s) for FASSG list preparation? The same validation applies to every student. Sponsor approval remains separate.')) {
                    event.preventDefault();
                    return;
                }

                submitting = true;
                updateState();
            });
            updateState();
        })();
    </script>
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2/dist/js/tom-select.complete.min.js"></script>
    <script id="fixed-list-encoding-script">
        (function () {
            const select = document.getElementById('fixed-list-course');
            if (!select || select.tomselect || typeof TomSelect === 'undefined') return;
            const dropdown = new TomSelect(select, {
                placeholder: 'Select Academic Program',
                allowEmptyOption: false,
                create: false,
                sortField: { field: 'text', direction: 'asc' },
            });
            dropdown.control_input.setAttribute('aria-describedby', 'fixed-list-course-error');
            dropdown.control_input.setAttribute('aria-invalid', select.getAttribute('aria-invalid'));
        })();
    </script>
    <script id="fixed-list-removal-script">
        (function () {
            const modal = document.getElementById('removeStudentModal');
            if (!modal || modal.dataset.removalInitialized === '1') return;
            modal.dataset.removalInitialized = '1';

            const forms = Array.from(document.querySelectorAll('form[data-remove-student-form]'));
            const confirmButton = document.getElementById('confirmRemoveStudent');
            const cancelButton = document.getElementById('cancelRemoveStudent');
            const dismissButtons = Array.from(modal.querySelectorAll('[data-bs-dismiss="modal"]'));
            const identity = document.getElementById('removeStudentIdentity');
            const progress = document.getElementById('removeStudentProgress');
            const error = document.getElementById('removeStudentError');
            let activeForm = null;
            let opener = null;
            let confirming = false;
            let submitting = false;

            function setProcessing(processing) {
                submitting = processing;
                modal.setAttribute('aria-busy', String(processing));
                confirmButton.disabled = processing || !activeForm;
                confirmButton.textContent = processing ? 'Removing...' : 'Remove from List';
                progress.textContent = processing ? 'Removing student entry. Please wait.' : '';
                dismissButtons.forEach(button => { button.disabled = processing; });
                forms.forEach(form => { form.querySelector('[data-bs-target="#removeStudentModal"]').disabled = processing; });
            }

            modal.addEventListener('show.bs.modal', event => {
                const form = event.relatedTarget?.closest('form[data-remove-student-form]');
                if (submitting || !forms.includes(form)) {
                    event.preventDefault();
                    return;
                }
                activeForm = form;
                opener = event.relatedTarget;
                identity.textContent = form.dataset.studentName + ' (' + form.dataset.studentId + ')';
                error.hidden = true;
                error.textContent = '';
                setProcessing(false);
            });
            modal.addEventListener('shown.bs.modal', () => { cancelButton.focus(); });
            modal.addEventListener('hide.bs.modal', event => {
                if (submitting) event.preventDefault();
            });
            modal.addEventListener('hidden.bs.modal', () => {
                activeForm = null;
                identity.textContent = '';
                setProcessing(false);
                if (opener?.isConnected) opener.focus();
                opener = null;
            });

            forms.forEach(form => form.addEventListener('submit', event => {
                if (submitting || !confirming || form !== activeForm) {
                    event.preventDefault();
                    return;
                }
                setProcessing(true);
            }));
            confirmButton.addEventListener('click', () => {
                if (!activeForm || submitting) return;
                error.hidden = true;
                confirming = true;
                try {
                    activeForm.requestSubmit();
                } catch (failure) {
                    setProcessing(false);
                    error.textContent = 'The removal request could not be submitted. Please try again.';
                    error.hidden = false;
                } finally {
                    confirming = false;
                }
            });
            window.addEventListener('pageshow', event => {
                if (event.persisted) setProcessing(false);
            });
        })();
    </script>
@endpush
@endsection
