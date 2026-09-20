@extends('layouts.app')

@section('title', 'Encode Beneficiary List')
@section('eyebrow', 'FASSG Office')
@section('page-title', 'Encode Beneficiary List')

@push('styles')
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
    </style>
@endpush

@section('content')
    <div class="row g-4 mb-4">
        <div class="col-md-8">
            <div class="card sf-card mb-4 border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3 gap-3">
                        <a href="{{ route('fassg.fixed-lists.index') }}"
                            class="btn btn-outline-secondary btn-sm fw-semibold d-inline-flex align-items-center gap-2">
                            <i class="bi bi-arrow-left"></i>Back to Fixed Lists
                        </a>
                        <x-status-badge :status="$list->status" />
                    </div>
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <span
                                class="badge bg-light text-dark mb-2 border">{{ $list->sponsorshipProgram->program_name }}</span>
                            <h2 class="h4 fw-bold mb-1">
                                {{ $list->batch_name ?: 'Batch #' . $list->id . ' - ' . ($list->sponsorshipProgram->program_name ?? 'Unassigned Program') }}
                            </h2>
                            <p class="text-secondary small mb-0">Total Names: {{ $list->items->count() }}</p>
                        </div>
                    </div>

                    @php
                        $generatedFromQueueCount = $list->items->whereNotNull('application_id')->count();
                        $generatedFromQueue = $generatedFromQueueCount > 0;
                    @endphp

                    @if ($generatedFromQueue)
                        <div class="alert alert-info border-0 mb-3 d-flex align-items-start gap-2 py-2 small">
                            <i class="bi bi-list-check text-primary mt-1"></i>
                            <div>
                                <strong>Generated from Application Queue</strong> — linked to
                                {{ $generatedFromQueueCount }} application(s). These candidates originate from the FASSG
                                applicant queue, not manual encoding. You can still encode or import additional names below.
                            </div>
                        </div>
                    @endif

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
                                <div class="col-md-3">
                                    <input type="text" name="course" class="form-control form-control-sm"
                                        placeholder="Academic Program" required value="{{ old('course') }}">
                                </div>
                                <div class="col-md-1">
                                    <input type="number" name="year_level" class="form-control form-control-sm"
                                        placeholder="Year" min="1" max="5" required value="{{ old('year_level') }}">
                                </div>
                                <div class="col-md-6">
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

            <div class="card sf-card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <h3 class="h6 fw-bold mb-0">Encoded &amp; Imported Students</h3>
                    @if (in_array($list->status, [\App\Enums\FixedListStatus::Draft, \App\Enums\FixedListStatus::Rejected, \App\Enums\FixedListStatus::Saved], true))
                        <button type="button" class="btn btn-outline-success btn-sm" id="bulkEndorseBtn" disabled>
                            <i class="bi bi-check2-square me-1"></i>Endorse Selected
                        </button>
                    @endif
                </div>
                <div class="table-responsive">
                    <table class="table sf-table mb-0 align-middle" id="candidateListTable">
                        <thead>
                            <tr>
                                <th class="ps-4" style="width: 36px;">
                                    <input type="checkbox" class="form-check-input" id="selectAllCheckbox"
                                        aria-label="Select all candidates">
                                </th>
                                <th>Student ID</th>
                                <th>Student Name</th>
                                <th>Academic Program</th>
                                <th>Year Level</th>
                                <th>Campus</th>
                                <th>SLE-FHE Status</th>
                                <th>Endorsed</th>
                                <th class="text-end pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($list->items as $item)
                                <tr class="{{ $item->is_manually_endorsed ? 'table-success' : '' }}">
                                    <td class="ps-4">
                                        <input type="checkbox" class="form-check-input item-checkbox"
                                            value="{{ $item->id }}" aria-label="Select {{ $item->student_name }}">
                                    </td>
                                    <td class="sf-mono text-secondary fw-semibold">{{ $item->student_id_number ?: 'N/A' }}</td>
                                    <td class="fw-semibold">
                                        {{ $item->student_name }}
                                        @if ($item->application_id)
                                            <a href="{{ route('fassg.applications.show', $item->application_id) }}"
                                                class="d-block small text-primary text-decoration-none fw-normal">
                                                <i class="bi bi-arrow-right-circle me-1"></i>View source application
                                            </a>
                                        @endif
                                    </td>
                                    <td>{{ $item->course ?: '—' }}</td>
                                    <td>{{ $item->year_level ? "Year {$item->year_level}" : '—' }}</td>
                                    <td>{{ $item->campus ?: 'N/A' }}</td>
                                    <td>
                                        <x-status-badge :status="$item->is_sle_fhe_verified ? 'Verified' : 'Pending'" />
                                    </td>
                                    <td>
                                        @if ($item->status === \App\Enums\FixedListItemStatus::Endorsed)
                                            <x-status-badge :status="'Endorsed'" />
                                        @else
                                            <button type="submit" form="endorse-{{ $item->id }}"
                                                class="btn btn-outline-success btn-sm">
                                                <i class="bi bi-check-lg me-1"></i>Endorse
                                            </button>
                                        @endif
                                    </td>
                                    <td class="text-end pe-4">
                                        @if (!$item->is_sle_fhe_verified && $item->status !== \App\Enums\FixedListItemStatus::Endorsed)
                                            <form method="POST"
                                                action="{{ route('fassg.fixed-lists.items.verify', [$list, $item]) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-outline-navy btn-sm">Verify</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-secondary py-4">No students encoded in this
                                        batch yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @foreach ($list->items as $item)
                    <form method="POST" id="endorse-{{ $item->id }}"
                        action="{{ route('fassg.fixed-lists.items.endorse', [$list, $item]) }}"
                        class="d-none">
                        @csrf
                        @method('PATCH')
                    </form>
                @endforeach
            </div>
        </div>

        <div class="col-md-4">
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
                        <h3 class="h6 fw-bold mb-2">Submit to Sponsor</h3>
                        <p class="text-secondary small mb-3">Once submitted, this list will be forwarded to the sponsor for
                            review.</p>
                        <form method="POST" action="{{ route('fassg.fixed-lists.submit', $list) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-sf-navy w-100 py-2" @disabled($list->items->isEmpty())
                                onClick="if ({{ $list->items->where('is_sle_fhe_verified', true)->count() }} !== {{ $list->items->count() }}) { alert('Verify all students before forwarding this list to the sponsor.'); return false; } return confirm('All students are verified. Forward this list to the sponsor?');">
                                <i class="bi bi-send me-1"></i>Submit List
                            </button>
                        </form>
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
                        <p class="text-secondary small mb-3">The sponsor has confirmed this list. Verified beneficiaries
                            have been forwarded to Accounting automatically.</p>
                        <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle px-3 py-2 fw-semibold">
                            <i class="bi bi-patch-check me-1"></i>Approved by Sponsor
                        </span>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
        <script>
            (function () {
                const selectAll = document.getElementById('selectAllCheckbox');
                const bulkEndorseBtn = document.getElementById('bulkEndorseBtn');
                if (!selectAll || !bulkEndorseBtn) {
                    return;
                }

                const checkboxes = Array.from(document.querySelectorAll('.item-checkbox'));

                function updateState() {
                    const checked = checkboxes.filter(chk => chk.checked);
                    bulkEndorseBtn.disabled = checked.length === 0;
                    selectAll.checked = checked.length > 0 && checked.length === checkboxes.length;
                }

                selectAll.addEventListener('change', function () {
                    checkboxes.forEach(chk => { chk.checked = selectAll.checked; });
                    updateState();
                });

                checkboxes.forEach(chk => chk.addEventListener('change', updateState));

                bulkEndorseBtn.addEventListener('click', function () {
                    const checked = checkboxes.filter(chk => chk.checked);
                    if (checked.length === 0) {
                        return;
                    }

                    if (!confirm('Endorse ' + checked.length + ' selected student(s) as outside-criteria? This overrides standard criteria for the batch.')) {
                        return;
                    }

                    checked.forEach((chk, index) => {
                        const form = document.getElementById('endorse-' + chk.value);
                        if (form) {
                            setTimeout(() => form.submit(), index * 150);
                        }
                    });
                });
            })();
        </script>
    @endpush
@endsection
