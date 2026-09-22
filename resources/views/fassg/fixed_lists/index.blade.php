@extends('layouts.app')

@section('title', 'Fixed Lists')
@section('eyebrow', 'FASSG Office')
@section('page-title', 'Sponsor-Provided Fixed Lists')
@section('subtitle', 'Manage and process batch beneficiary lists forwarded by sponsors.')

@section('header-actions')
    <button type="button" class="btn btn-sf-navy fw-semibold d-inline-flex align-items-center gap-2 px-3"
        data-bs-toggle="modal" data-bs-target="#newFixedListModal">
        <i class="bi bi-plus-lg"></i>Upload / Encode List
    </button>
@endsection

@push('styles')
    <style>

        .badge-info-custom {
            background-color: #e0f2fe !important;
            color: #0369a1 !important;
            border: 1px solid #bae6fd !important;
        }
    </style>
@endpush

@section('content')
    @if ($fixedLists->isEmpty())
        <div class="card sf-card border-0 shadow-sm">
            <div class="sf-empty-state text-center p-5">
                <i class="bi bi-list-check text-secondary fs-1 d-block mb-3"></i>
                <div class="fw-semibold">No fixed lists yet</div>
                <div class="small text-secondary mb-3">Upload a sponsor-provided beneficiary list, or encode names manually.
                </div>
                <button type="button" class="btn btn-sf-navy btn-sm px-3" data-bs-toggle="modal"
                    data-bs-target="#newFixedListModal">Upload / Encode List</button>
            </div>
        </div>
    @else
        <div class="accordion" id="fixedListsAccordion">
            @foreach ($fixedLists as $list)
                <div class="card sf-card mb-3 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h3 class="h6 sf-heading mb-1 fw-bold">
                                    <a href="{{ route('fassg.fixed-lists.show', $list) }}"
                                        class="text-decoration-none text-dark">
                                        {{ $list->batch_name ?: 'Batch #' . $list->id . ' - ' . ($list->sponsorshipProgram?->program_name ?? 'Unassigned Program') }}
                                    </a>
                                </h3>
                                <div class="small text-secondary">
                                    {{ $list->sponsorshipProgram?->program_name ?? 'Unassigned Program' }} · {{ $list->total_names }}
                                    {{ Str::plural('name', $list->total_names) }}
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                @php
                                    $listStatus = $list->status->value ?? (string) $list->status;
                                @endphp
                                <x-status-badge :status="$listStatus" />

                                <a href="{{ route('fassg.fixed-lists.show', $list) }}" class="btn btn-outline-primary btn-sm"
                                    title="View &amp; Manage Fixed List" aria-label="View &amp; Manage Fixed List">
                                    <i class="bi bi-eye"></i>
                                </a>

                                @if (in_array($list->status, [\App\Enums\FixedListStatus::Draft, \App\Enums\FixedListStatus::Rejected], true))
                                    <a href="{{ route('fassg.fixed-lists.edit', $list) }}"
                                        class="btn btn-outline-secondary btn-sm" title="Rename Fixed List"
                                        aria-label="Rename Fixed List">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="POST" action="{{ route('fassg.fixed-lists.destroy', $list) }}"
                                        class="d-inline" onsubmit="return confirm('Delete this fixed list?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm"
                                            title="Delete Fixed List" aria-label="Delete Fixed List">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table sf-table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Student Name</th>
                                        <th>Student ID</th>
                                        <th>Academic Program</th>
                                        <th>Year</th>
                                        <th>SLE-FHE Check</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($list->items as $item)
                                        <tr>
                                            <td class="fw-semibold">{{ $item->student_name }}</td>
                                            <td class="sf-mono text-secondary">{{ $item->student_id_number ?: 'N/A' }}</td>
                                            <td class="text-secondary">{{ $item->course ?: '—' }}</td>
                                            <td class="text-secondary">{{ $item->year_level ?: '—' }}</td>
                                            <td>
                                                @php
                                                    $isVerified = $item->is_sle_fhe_verified || ($item->studentProfile && $item->studentProfile->isSleFheVerified());
                                                @endphp
                                                @if ($isVerified)
                                                    <span class="px-2 py-0.5 text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-md">✓ Verified</span>
                                                @else
                                                    <span class="px-2 py-0.5 text-xs font-semibold text-amber-700 bg-amber-50 border border-amber-200 rounded-md">⏳ Pending</span>
                                                @endif
                                            </td>
                                            <td><x-status-badge :status="$item->status" /></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Upload / encode modal --}}
    <div class="modal fade" id="newFixedListModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="{{ route('fassg.fixed-lists.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title sf-heading fw-bold">New Fixed List</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small text-secondary">Sponsorship Program <span
                                        class="text-danger">*</span></label>
                                <select name="sponsorship_program_id" class="form-select" required>
                                    <option value="">Select…</option>
                                    @foreach ($programs as $program)
                                        <option value="{{ $program->id }}">{{ $program->program_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-secondary">Fixed List Title <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="batch_name" class="form-control"
                                    placeholder="e.g. Governor Endorsed Candidates 2026" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label small text-secondary">Upload CSV/Excel of Names</label>
                                <input type="file" name="file" id="file" class="form-control" accept=".csv,.txt">
                                <div class="form-text">Or leave blank and encode names one by one after creating the batch.
                                </div>
                            </div>

                            <div class="col-12">
                                <hr class="my-1">
                                <label
                                    class="form-label small text-secondary fw-semibold d-flex align-items-center gap-1">
                                    <i class="bi bi-funnel"></i>Criteria Filtering
                                    <span class="text-secondary fw-normal">(optional, applied to uploaded rows)</span>
                                </label>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small text-secondary">Academic Program</label>
                                <input type="text" name="criteria_course" class="form-control"
                                    placeholder="e.g. BSIT" value="{{ old('criteria_course') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small text-secondary">Campus</label>
                                <select name="criteria_campus" class="form-select">
                                    <option value="">All Campuses</option>
                                    @foreach (['Main Campus (City of Mati)', 'Baganga Campus', 'Banaybanay Campus', 'Cateel Campus', 'San Isidro Campus', 'Tarragona Campus'] as $campusOpt)
                                        <option value="{{ $campusOpt }}" @selected(old('criteria_campus') === $campusOpt)>{{ $campusOpt }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small text-secondary">Max GPA / Requirement</label>
                                <input type="number" step="0.01" min="0" max="5" name="criteria_gpa"
                                    class="form-control" placeholder="e.g. 1.75" value="{{ old('criteria_gpa') }}">
                                <div class="form-text">Rows with a higher GPA than this are skipped.</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal"><i class="bi bi-x-lg me-1"></i> Cancel</button>
                        <button type="submit" class="btn btn-sf-navy"><i class="bi bi-upload me-1"></i>Save
                            Fixed List</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
