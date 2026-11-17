@extends('layouts.app')

@section('title', 'Encode Beneficiary List')
@section('eyebrow', 'FASSG Office · Fixed Lists')
@section('page-title')
    {{ $list->batch_name ?: 'Batch #' . $list->id . ' - ' . ($list->sponsorshipProgram->program_name ?? 'Unassigned Program') }}
    <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full bg-blue-50 text-blue-700 border border-blue-200">Fixed List Registry</span>
@endsection
@section('subtitle', $list->sponsorshipProgram->program_name . ' · Total Names: ' . $list->items->count())

@section('header-actions')
    <a href="{{ route('fassg.fixed-lists.index') }}"
        class="btn btn-outline-secondary btn-sm fw-semibold d-inline-flex align-items-center gap-2">
        <i class="bi bi-arrow-left"></i>Back to Fixed Lists
    </a>
    @if ($list->status === \App\Enums\FixedListStatus::Finalized)
        <a href="{{ route('fassg.applications.index', ['program_id' => $list->sponsorship_program_id, 'fixed_list_id' => $list->id, 'prepare_batch' => 1]) }}"
            class="btn btn-sf-navy btn-sm fw-semibold">
            <i class="bi bi-list-check me-1"></i>Create Batch from This List
        </a>
    @endif
    <x-status-badge :status="$list->status" />
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2/dist/css/tom-select.bootstrap5.min.css">
    <style>

        .btn-outline-navy {
            color: #0F2942 !important;
            border-color: #0F2942 !important;
            background-color: transparent !important;
            font-weight: 600;
            transition: all 0.2s ease-in-out;
        }

        .btn-outline-navy:hover,
        .btn-outline-navy:focus {
            color: #ffffff !important;
            background-color: #0F2942 !important;
            border-color: #0F2942 !important;
        }
        /* Scoped to this table: SponsorFlow typography and Tailwind slate/status palette. */
        .fixed-list-panel { border: 1px solid var(--sf-border, #e2e8f0); border-radius: 12px; background: #fff; overflow: hidden; margin-bottom: 1.5rem; }
        .fixed-list-panel-header { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .75rem; padding: 1rem 1.25rem; }
        .fixed-list-panel-header h3 { margin: 0; font-size: .95rem; font-weight: 700; }
        .fixed-list-panel-header p { margin: .25rem 0 0; font-size: .75rem; color: var(--sf-muted, #64748b); }
        .fixed-list-table-scroll { width: 100%; height: auto; max-height: none; overflow-x: auto; overflow-y: hidden; }
        .fixed-list-table-scroll:focus-visible { outline: 2px solid var(--sf-info, #0284c7); outline-offset: -2px; }
        #candidateListTable { width: 100%; min-width: 70rem; table-layout: fixed; border-collapse: collapse; font-size: .8125rem; }
        #candidateListTable th { padding: .75rem 1rem; background: #f8fafc; color: #475569; font-size: .6875rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; border-block: 1px solid #e2e8f0; }
        #candidateListTable td { padding: 1rem; vertical-align: top; border-bottom: 1px solid #e2e8f0; color: #334155; }
        #candidateListTable tbody tr:last-child td { border-bottom: 0; }
        #candidateListTable tbody tr:hover { background: #f8fafc; }
        #candidateListTable .fixed-list-select { width: 3rem; padding-inline: .875rem; text-align: center; }
        #candidateListTable .form-check-input { margin: .15rem 0 0; float: none; }
        #candidateListTable .fixed-list-student { width: 19%; }
        #candidateListTable .fixed-list-academic { width: 23%; }
        #candidateListTable .fixed-list-source { width: 12%; }
        #candidateListTable .fixed-list-actions-column { width: 10rem; }
        .fixed-list-name, .fixed-list-course { font-weight: 600; line-height: 1.5; overflow-wrap: anywhere; color: #0f172a; }
        .fixed-list-student-id { display: block; white-space: nowrap; margin-top: .25rem; font-family: var(--font-mono, monospace); font-size: .75rem; color: #64748b; }
        .fixed-list-helper { margin: .375rem 0 0; font-size: .75rem; line-height: 1.5; color: #64748b; overflow-wrap: anywhere; }
        .fixed-list-helper-warning { color: #92400e; }
        .fixed-list-status { display: inline-flex; align-items: center; gap: .3rem; padding: .2rem .5rem; border: 1px solid #cbd5e1; border-radius: .375rem; font-size: .6875rem; line-height: 1.4; font-weight: 600; white-space: nowrap; background: #f8fafc; color: #475569; }
        .fixed-list-status[data-status="Pending"] { background: #fffbeb; color: #92400e; border-color: #fde68a; }
        .fixed-list-status[data-status="Verified"] { background: #ecfeff; color: #0e7490; border-color: #a5f3fc; }
        .fixed-list-status[data-status="Endorsed"], .fixed-list-status[data-status="Fixed List"] { background: #eef2ff; color: #4338ca; border-color: #c7d2fe; }
        .fixed-list-status[data-status="Ineligible"] { background: #fef2f2; color: #b91c1c; border-color: #fecaca; }
        .fixed-list-checks { margin-top: .375rem; font-size: .75rem; color: #92400e; }
        .fixed-list-checks summary { cursor: pointer; font-weight: 600; }
        .fixed-list-checks ul { padding-left: 1rem; margin: .375rem 0 0; }
        .fixed-list-checks li + li { margin-top: .25rem; }
        .fixed-list-actions { display: flex; flex-direction: column; align-items: stretch; gap: .5rem; }
        .fixed-list-actions form { margin: 0; }
        .fixed-list-action { display: inline-flex; align-items: center; justify-content: center; gap: .375rem; min-height: 2rem; padding: .375rem .625rem; border: 1px solid #cbd5e1; border-radius: .375rem; background: #fff; color: var(--sf-navy, #0f2537); font-size: .75rem; font-weight: 600; line-height: 1.25; white-space: nowrap; transition: background-color .15s, border-color .15s; }
        .fixed-list-actions .fixed-list-action { width: 100%; }
        .fixed-list-action:hover:not(:disabled) { background: #f1f5f9; border-color: #94a3b8; }
        .fixed-list-action:focus-visible, .fixed-list-checks summary:focus-visible { outline: 2px solid var(--sf-info, #0284c7); outline-offset: 2px; }
        .fixed-list-action:disabled { opacity: .5; cursor: not-allowed; }
        .fixed-list-action-endorse { color: #047857; border-color: #a7f3d0; }
        .fixed-list-action-remove { color: #b91c1c; border-color: #fecaca; }
        .fixed-list-empty { text-align: center; padding: 2rem !important; color: #64748b !important; }
    </style>
@endpush

@section('content')
    @php
        $isEditable = in_array($list->status, [\App\Enums\FixedListStatus::Draft, \App\Enums\FixedListStatus::Rejected, \App\Enums\FixedListStatus::Saved], true);
        $canEditStudents = $isEditable && $user?->isFassg();
    @endphp
    <div class="alert alert-info small">
        Endorsement records FASSG list preparation, not Sponsor approval or a criteria exception.
        Program-required GWA and documents must still be explicitly verified in Generated Batch review before Sponsor submission.
    </div>
    <section class="fixed-list-panel" aria-labelledby="fixed-list-students-heading">
        <div class="fixed-list-panel-header">
            <div>
                <h3 id="fixed-list-students-heading">Encoded &amp; Imported Students</h3>
                <p>{{ $list->items->count() }} {{ Str::plural('student', $list->items->count()) }} &middot; Original list details and current review status</p>
            </div>
            @if ($canEditStudents)
                <button type="submit" form="bulkEndorseForm" class="fixed-list-action fixed-list-action-endorse" id="bulkEndorseBtn" disabled>
                    <i class="bi bi-check2-square" aria-hidden="true"></i>Endorse Selected
                </button>
            @else
                <span class="fixed-list-status"><i class="bi bi-lock" aria-hidden="true"></i>Read-only list</span>
            @endif
        </div>
        <div class="fixed-list-table-scroll" role="region" tabindex="0" aria-labelledby="fixed-list-students-heading">
            <table id="candidateListTable">
                <caption class="visually-hidden">Fixed List students, academic details, verification, endorsement, and available actions. Scroll horizontally on smaller screens.</caption>
                <thead>
                    <tr>
                        @if ($canEditStudents)
                            <th scope="col" class="fixed-list-select">
                                <input type="checkbox" class="form-check-input" id="selectAllCheckbox" aria-label="Select all eligible candidates for endorsement">
                            </th>
                        @endif
                        <th scope="col" class="fixed-list-student">Student</th>
                        <th scope="col" class="fixed-list-academic">Academic Details</th>
                        <th scope="col" class="fixed-list-source">Candidate Source</th>
                        <th scope="col">SLE-FHE Verification</th>
                        <th scope="col">Endorsement Status</th>
                        <th scope="col" class="fixed-list-actions-column">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($list->items as $item)
                        @php
                            $profile = $item->studentProfile;
                            $hasVerificationEvidence = $profile?->hasDocumentedSleFheVerification() ?? false;
                            $isVerified = $item->is_sle_fhe_verified && $hasVerificationEvidence;
                            $itemErrors = collect($endorsementErrors[$item->id]);
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
                                <td class="fixed-list-select">
                                    <input type="checkbox" class="form-check-input item-checkbox"
                                        name="selected_items[]" form="bulkEndorseForm" value="{{ $item->id }}"
                                        aria-label="Select {{ $item->student_name }} for endorsement" @disabled(! $canEndorse)>
                                </td>
                            @endif
                            <td>
                                <div class="fixed-list-name">{{ $item->student_name ?: 'Name not recorded' }}</div>
                                <span class="fixed-list-student-id">{{ $item->student_id_number ?: 'Student ID not recorded' }}</span>
                            </td>
                            <td>
                                <div class="fixed-list-course">{{ $item->course ?: 'Academic program not recorded' }}</div>
                                <p class="fixed-list-helper">{{ $item->year_level ? "Year {$item->year_level}" : 'Year level not recorded' }}<br>{{ $item->campus ?: 'Campus not recorded' }}</p>
                            </td>
                            <td>
                                <span class="fixed-list-status" data-status="{{ $item->is_fixed_list ? 'Fixed List' : 'Ordinary Applicant' }}"
                                    title="{{ $item->is_fixed_list ? 'Sponsor-proposed Fixed List' : 'Ordinary Applicant' }}">
                                    {{ $item->is_fixed_list ? 'Fixed List' : 'Ordinary Applicant' }}
                                </span>
                                @if ($item->is_fixed_list)
                                    <p class="fixed-list-helper">Sponsor-proposed candidate</p>
                                @endif
                            </td>
                            <td>
                                <span class="fixed-list-status" data-status="{{ $isVerified ? 'Verified' : 'Pending' }}">
                                    <i class="bi {{ $isVerified ? 'bi-patch-check' : 'bi-hourglass-split' }}" aria-hidden="true"></i>{{ $isVerified ? 'Verified' : 'Pending' }}
                                </span>
                                @foreach ($verificationMessages as $message)
                                    <p class="fixed-list-helper fixed-list-helper-warning">{{ $message }}</p>
                                @endforeach
                                @if (! $isVerified && $profile !== null && ! $hasVerificationEvidence)
                                    <p class="fixed-list-helper">Complete the FASSG SLE-FHE review first.</p>
                                @endif
                            </td>
                            <td>
                                <span class="fixed-list-status" data-status="{{ $endorsementStatus }}">{{ $endorsementStatus }}</span>
                                @if ($item->status->value !== $endorsementStatus)
                                    <p class="fixed-list-helper">List status: {{ $item->status->value }}</p>
                                @endif
                                @if ($item->is_manually_endorsed && ! $item->hasRecordedEndorsement())
                                    <p class="fixed-list-helper fixed-list-helper-warning">Original endorsement evidence is incomplete.</p>
                                @endif
                                @if ($endorsementMessages->isNotEmpty())
                                    <p class="fixed-list-helper fixed-list-helper-warning">{{ $endorsementMessages->first() }}</p>
                                    @if ($endorsementMessages->count() > 1)
                                        <details class="fixed-list-checks">
                                            <summary>{{ $endorsementMessages->count() - 1 }} more {{ Str::plural('check', $endorsementMessages->count() - 1) }}</summary>
                                            <ul>
                                                @foreach ($endorsementMessages->skip(1) as $message)
                                                    <li>{{ $message }}</li>
                                                @endforeach
                                            </ul>
                                        </details>
                                    @endif
                                @elseif (! $item->is_manually_endorsed && $isVerified)
                                    <p class="fixed-list-helper">Awaiting FASSG endorsement.</p>
                                @endif
                            </td>
                            <td>
                                <div class="fixed-list-actions">
                                    @if ($canVerify)
                                        <form method="POST" action="{{ route('fassg.fixed-lists.items.verify', [$list, $item]) }}">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="fixed-list-action"><i class="bi bi-patch-check" aria-hidden="true"></i>Verify</button>
                                        </form>
                                    @endif
                                    @if ($canEndorse)
                                        <form method="POST" id="endorse-{{ $item->id }}" action="{{ route('fassg.fixed-lists.items.endorse', [$list, $item]) }}">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="endorse" value="1">
                                            <button type="submit" class="fixed-list-action fixed-list-action-endorse"><i class="bi bi-check-lg" aria-hidden="true"></i>Endorse</button>
                                        </form>
                                    @endif
                                    @if ($canRemove)
                                        <form method="POST" action="{{ route('fassg.fixed-lists.items.destroy', [$list, $item]) }}"
                                            data-remove-student-form data-student-name="{{ $item->student_name ?: 'Name not recorded' }}"
                                            data-student-id="{{ $item->student_id_number ?: 'ID not recorded' }}">
                                            @csrf @method('DELETE')
                                            <button type="button" class="fixed-list-action fixed-list-action-remove"
                                                data-bs-toggle="modal" data-bs-target="#removeStudentModal">
                                                <i class="bi bi-person-dash" aria-hidden="true"></i>Remove Student
                                            </button>
                                        </form>
                                    @endif
                                    @if (! $canVerify && ! $canEndorse && ! $canRemove)
                                        <span class="fixed-list-helper">{{ $canEditStudents ? 'No available actions' : 'Read-only' }}</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $canEditStudents ? 7 : 6 }}" class="fixed-list-empty">No students encoded in this list yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($canEditStudents)
            <form method="POST" id="bulkEndorseForm" class="d-none" action="{{ route('fassg.fixed-lists.items.endorse-selected', $list) }}">
                @csrf @method('PATCH')
            </form>
        @endif
    </section>
    <div class="row g-4 mb-4">
        @if ($list->status !== \App\Enums\FixedListStatus::Finalized)
        <div class="col-md-8">
            <div class="card sf-card mb-4 border-0 shadow-sm">
                <div class="card-body p-4">
                    @if (in_array($list->status, [\App\Enums\FixedListStatus::Draft, \App\Enums\FixedListStatus::Rejected, \App\Enums\FixedListStatus::Saved], true))
                        <div class="border-top pt-3 mt-3">
                            <h3 class="h6 fw-bold mb-3"><i class="bi bi-person-plus me-1"></i>Encode Student Manually</h3>
                            <form method="POST" action="{{ route('fassg.fixed-lists.items.store', $list) }}"
                                class="row g-2">
                                @csrf
                                <div class="col-md-8">
                                    <input type="text" name="student_name" class="form-control form-control-sm"
                                        placeholder="Full Name" required value="{{ old('student_name') }}">
                                </div>
                                <div class="col-md-4">
                                    <input type="text" name="student_id_number" class="form-control form-control-sm"
                                        placeholder="Student ID" required value="{{ old('student_id_number') }}">
                                </div>
                                <div class="col-md-5">
                                    <label for="fixed-list-course" class="visually-hidden">Academic Program</label>
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
                                    <label for="fixed-list-year-level" class="visually-hidden">Year Level</label>
                                    <select id="fixed-list-year-level" name="year_level"
                                        class="form-select form-select-sm @error('year_level') is-invalid @enderror"
                                        required aria-describedby="fixed-list-year-error" aria-invalid="{{ $errors->has('year_level') ? 'true' : 'false' }}">
                                        <option value="">Year Level</option>
                                        @foreach ($yearLevels as $yearLevel)
                                            <option value="{{ $yearLevel }}" @selected((string) old('year_level') === (string) $yearLevel)>Year {{ $yearLevel }}</option>
                                        @endforeach
                                    </select>
                                    <div id="fixed-list-year-error" class="small text-danger mt-1">@error('year_level') {{ $message }} @enderror</div>
                                </div>
                                <div class="col-md-3">
                                    <select name="campus" class="form-select form-select-sm" required>
                                        @foreach (['Main Campus (City of Mati)', 'Baganga Campus', 'Banaybanay Campus', 'Cateel Campus', 'San Isidro Campus', 'Tarragona Campus'] as $campusOption)
                                            <option value="{{ $campusOption }}"
                                                {{ old('campus', 'Main Campus (City of Mati)') === $campusOption ? 'selected' : '' }}>
                                                {{ $campusOption }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-sf-navy btn-sm w-100">Add</button>
                                </div>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        <div class="{{ $list->status === \App\Enums\FixedListStatus::Finalized ? 'col-12' : 'col-md-4' }}">
            @if (in_array($list->status, [\App\Enums\FixedListStatus::Draft, \App\Enums\FixedListStatus::Rejected, \App\Enums\FixedListStatus::Saved], true))
                <div class="card sf-card mb-4 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h3 class="h6 fw-bold mb-2"><i class="bi bi-file-earmark-spreadsheet me-1"></i>Import CSV / Excel
                        </h3>
                        <p class="text-secondary small mb-3">Expected columns: <code>student_name</code>,
                            <code>student_id_number</code>, <code>course</code>, <code>year_level</code>,
                            <code>campus</code>
                        </p>
                        <form method="POST" action="{{ route('fassg.fixed-lists.import', $list) }}"
                            enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <input type="file" name="file" class="form-control form-control-sm" accept=".csv,.txt"
                                    required>
                            </div>
                            <button type="submit" class="btn btn-outline-navy btn-sm w-100">
                                <i class="bi bi-upload me-1"></i>Import File
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card sf-card border-0 shadow-sm">
                    <div class="card-body p-4 text-center">
                        @php
                            $hasUnverifiedOrUnendorsed = $list->items->contains(
                                fn ($item) => ! $item->hasRecordedEndorsement() || $endorsementErrors[$item->id] !== []
                            );
                        @endphp
                        <h3 class="h6 fw-bold mb-2">Finalize Fixed List</h3>
                        <p class="text-secondary small mb-3">Finalization preserves this list for manual candidate selection into a Generated Batch. It does not reserve slots, verify qualification documents, or approve sponsorship.</p>
                        @if ($list->items->isNotEmpty() && ! $hasUnverifiedOrUnendorsed)
                            <form method="POST" action="{{ route('fassg.fixed-lists.finalize', $list) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sf-navy w-100 py-2"
                                    onClick="return confirm('All students are verified and endorsed. Finalize this list for batch generation?');">
                                    <i class="bi bi-send me-1"></i>Finalize List
                                </button>
                            </form>
                        @else
                            @if ($hasUnverifiedOrUnendorsed)
                                <div class="alert alert-warning border-0 py-2 small text-start mb-3">
                                    <i class="bi bi-exclamation-triangle me-1"></i>All candidates must pass list preparation checks and have documented SLE-FHE verification and FASSG endorsement before finalization.
                                </div>
                            @endif
                            <button type="button" disabled class="btn btn-sf-navy w-100 py-2"
                                style="cursor: not-allowed; opacity: 0.5;">
                                <i class="bi bi-send me-1"></i>{{ $hasUnverifiedOrUnendorsed ? 'Complete Verifications & Endorsements to Finalize' : 'Finalize List' }}
                            </button>
                        @endif
                    </div>
                </div>
            @elseif ($list->status === \App\Enums\FixedListStatus::Finalized)
                <div class="card sf-card border-0 shadow-sm">
                    <div class="card-body p-4 text-center">
                        <h3 class="h6 fw-bold mb-2 text-success"><i class="bi bi-lock-fill me-1"></i>Finalized / Ready for Batch Generation</h3>
                        <p class="text-secondary small mb-3">This Fixed List is finalized and read-only. FASSG can include its complete finalized roster in a Generated Batch, subject to eligibility verification and explicit Sponsor approval. The Fixed List itself does not approve students or consume sponsorship slots.</p>
                        <span class="badge bg-indigo-50 text-indigo-700 border border-indigo-200 px-3 py-2 fw-semibold">
                            <i class="bi bi-gear-wide-connected me-1"></i>Finalized
                        </span>
                    </div>
                </div>
            @elseif ($list->status === \App\Enums\FixedListStatus::Submitted)
                <div class="card sf-card border-0 shadow-sm">
                    <div class="card-body p-4 text-center">
                        <h3 class="h6 fw-bold mb-2"><i class="bi bi-hourglass-split me-1 text-info"></i>Submitted to Sponsor</h3>
                        <p class="text-secondary small mb-3">This list is awaiting sponsor review and approval.</p>
                        <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-3 py-2 fw-semibold">
                            <i class="bi bi-send me-1"></i>Submitted to Sponsor (Awaiting Sponsor Approval)
                        </span>
                    </div>
                </div>
            @elseif ($list->status === \App\Enums\FixedListStatus::Approved)
                <div class="card sf-card border-0 shadow-sm">
                    <div class="card-body p-4 text-center">
                        <h3 class="h6 fw-bold mb-2 text-success"><i class="bi bi-check-circle-fill me-1"></i>Approved by Sponsor</h3>
                        <p class="text-secondary small mb-3">This historical list records Sponsor approval. Accounting visibility requires the separate documented FASSG receipt and forwarding actions.</p>
                        <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle px-3 py-2 fw-semibold">
                            <i class="bi bi-patch-check me-1"></i>Approved by Sponsor
                        </span>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @if ($canEditStudents && $list->status === \App\Enums\FixedListStatus::Draft)
        <div class="modal fade" id="removeStudentModal" tabindex="-1" aria-hidden="true"
            aria-labelledby="removeStudentModalTitle" aria-describedby="removeStudentModalMessage removeStudentModalInfo"
            data-bs-keyboard="true" data-bs-backdrop="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow rounded-3">
                    <div class="modal-header">
                        <h3 class="modal-title h5 fw-bold" id="removeStudentModalTitle">Remove Student from Fixed List?</h3>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close removal confirmation"></button>
                    </div>
                    <div class="modal-body">
                        <p id="removeStudentModalMessage">Are you sure you want to remove
                            <strong id="removeStudentIdentity" class="text-break"></strong> from
                            <strong class="text-break">{{ $list->batch_name ?: 'Batch #' . $list->id . ' - ' . ($list->sponsorshipProgram->program_name ?? 'Unassigned Program') }}</strong>?
                        </p>
                        <div id="removeStudentModalInfo" class="bg-light border rounded-3 p-3 small text-secondary">
                            Only this student's entry in this draft Fixed List will be removed. Their registered student profile, SLE-FHE verification records, and historical records will be preserved.
                        </div>
                        <div id="removeStudentError" class="alert alert-danger small mt-3 mb-0" role="alert" hidden></div>
                        <p id="removeStudentProgress" class="small text-secondary mt-3 mb-0" role="status" aria-live="polite"></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" id="cancelRemoveStudent" class="btn btn-outline-secondary fw-semibold" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" id="confirmRemoveStudent" class="btn btn-danger fw-semibold" disabled>Remove from List</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

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

                    // One request validates the full selection and commits it atomically.
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
                // Keep the native select usable if the existing CDN asset is unavailable.
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
                    // Text only: student names and IDs must never become HTML.
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
                    // Normal form navigation preserves CSRF, DELETE spoofing and Laravel flash errors.
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
                // Browsers may restore the prior document from their back/forward cache.
                window.addEventListener('pageshow', event => {
                    if (event.persisted) setProcessing(false);
                });
            })();
        </script>
    @endpush
@endsection
