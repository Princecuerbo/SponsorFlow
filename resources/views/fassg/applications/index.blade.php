@extends('layouts.app')

@section('title', 'Application Queue')
@section('eyebrow', 'FASSG Office')
@section('page-title', 'Application Queue')

@push('styles')
    <style>
        .btn-navy-primary,
        a.btn-navy-primary,
        button.btn-navy-primary {
            background-color: #0F2942 !important;
            border-color: #0F2942 !important;
            color: #ffffff !important;
            font-weight: 600;
            box-shadow: none !important;
            transition: all 0.2s ease-in-out;
        }

        .btn-navy-primary:hover,
        .btn-navy-primary:focus,
        a.btn-navy-primary:hover,
        a.btn-navy-primary:focus,
        button.btn-navy-primary:hover,
        button.btn-navy-primary:focus {
            background-color: #0A1E31 !important;
            border-color: #0A1E31 !important;
            color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(15, 41, 66, 0.15) !important;
        }

        .filter-card {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
        }

        .stat-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.3rem 0.75rem;
            border-radius: 2rem;
            font-size: 0.8rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.15s ease-in-out;
        }

        .stat-pill:hover {
            opacity: 0.85;
            text-decoration: none;
        }
    </style>
@endpush

@section('content')
    {{-- Page Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div>
            <p class="text-uppercase small fw-semibold text-secondary mb-2">FASSG Office · Applications</p>
            <h1 class="display-6 fw-bold mb-1">Application Queue</h1>
            <p class="text-secondary mb-0">Review submitted scholarship applications from SLE-FHE verified students.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @php
                $statusLinks = [
                    ['label' => 'Pending Applications', 'enum' => 'Pending', 'param' => 'pending', 'icon' => 'bi-hourglass-split', 'activeBg' => 'bg-warning bg-opacity-25 border-warning', 'inactiveBg' => 'bg-warning bg-opacity-10 border-warning-subtle', 'textColor' => 'text-dark'],
                    ['label' => 'Verified', 'enum' => 'Verified', 'param' => 'verified', 'icon' => 'bi-patch-check', 'activeBg' => 'bg-primary bg-opacity-25 border-primary', 'inactiveBg' => 'bg-primary bg-opacity-10 border-primary-subtle', 'textColor' => 'text-primary'],
                    ['label' => 'Approved', 'enum' => 'Approved', 'param' => 'approved', 'icon' => 'bi-award', 'activeBg' => 'bg-success bg-opacity-25 border-success', 'inactiveBg' => 'bg-success bg-opacity-10 border-success-subtle', 'textColor' => 'text-success'],
                    ['label' => 'Resubmission Requested', 'enum' => 'Resubmission Requested', 'param' => 'resubmission requested', 'icon' => 'bi-arrow-counterclockwise', 'activeBg' => 'bg-info bg-opacity-25 border-info', 'inactiveBg' => 'bg-info bg-opacity-10 border-info-subtle', 'textColor' => 'text-info'],
                    ['label' => 'Rejected', 'enum' => 'Rejected', 'param' => 'rejected', 'icon' => 'bi-x-circle', 'activeBg' => 'bg-danger bg-opacity-25 border-danger', 'inactiveBg' => 'bg-danger bg-opacity-10 border-danger-subtle', 'textColor' => 'text-danger'],
                ];
                $counts = [
                    'Pending' => $pendingCount ?? 0,
                    'Verified' => $verifiedCount ?? 0,
                    'Approved' => $approvedCount ?? 0,
                    'Resubmission Requested' => $resubmissionCount ?? 0,
                    'Rejected' => $rejectedCount ?? 0,
                ];
            @endphp
            @foreach ($statusLinks as $link)
                @php $isActive = ($selectedStatus ?? null) === $link['enum']; @endphp
                <a href="{{ route('fassg.applications.index', array_merge(request()->query(), ['status' => $link['param']])) }}"
                    class="stat-pill {{ $isActive ? $link['activeBg'] : $link['inactiveBg'] }} border {{ $link['textColor'] }}">
                    <i class="bi {{ $link['icon'] }}"></i> {{ $counts[$link['enum']] }} {{ $link['label'] }}
                </a>
            @endforeach
            @if ($selectedProgram && $availableSlots !== null)
                <span class="stat-pill bg-cyan-50 text-cyan-700 border border-cyan-200">
                    <i class="bi bi-people"></i> Available Slots: {{ $availableSlots }}
                </span>
            @endif
        </div>
    </div>

    {{-- Flash Messages --}}
    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ $errors->first() }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Filter Bar --}}
    <div class="card filter-card mb-4 rounded-3">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('fassg.applications.index') }}" class="row g-2 align-items-end">
                {{-- Program --}}
                <div class="col-md-3">
                    <label class="form-label small text-secondary fw-semibold mb-1">Program</label>
                    <select name="program_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All programs</option>
                        @foreach ($programs as $prog)
                            <option value="{{ $prog->id }}" @selected((int) request('program_id') === $prog->id)>
                                {{ $prog->program_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Campus --}}
                <div class="col-md-2">
                    <label class="form-label small text-secondary fw-semibold mb-1">Campus</label>
                    <select name="campus" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Campuses</option>
                        @foreach (['Main Campus (City of Mati)', 'Baganga Campus', 'Banaybanay Campus', 'Cateel Campus', 'San Isidro Campus', 'Tarragona Campus'] as $campusOpt)
                            <option value="{{ $campusOpt }}" @selected(request('campus') === $campusOpt)>{{ $campusOpt }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Status --}}
                <div class="col-md-2">
                    <label class="form-label small text-secondary fw-semibold mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All statuses</option>
                        <option value="Pending" @selected(request('status') === 'Pending')>Pending</option>
                        <option value="Verified" @selected(request('status') === 'Verified')>Verified</option>
                        <option value="Approved" @selected(request('status') === 'Approved')>Approved</option>
                        <option value="Resubmission Requested" @selected(request('status') === 'Resubmission Requested')>Resubmission Requested</option>
                        <option value="Rejected" @selected(request('status') === 'Rejected')>Rejected</option>
                    </select>
                </div>

                {{-- Search --}}
                <div class="col-md-3">
                    <label class="form-label small text-secondary fw-semibold mb-1">Search</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="bi bi-search text-secondary"></i>
                        </span>
                        <input type="text" name="q" value="{{ request('q') }}"
                            class="form-control border-start-0 ps-0"
                            placeholder="Name, student ID, course…"
                            oninput="clearTimeout(window.searchTimer); window.searchTimer = setTimeout(() => this.form.submit(), 600)">
                    </div>
                </div>

                {{-- Reset --}}
                <div class="col-md-2">
                    <a href="{{ route('fassg.applications.index') }}"
                        class="btn btn-outline-secondary btn-sm w-100 d-flex align-items-center justify-content-center gap-1">
                        Reset Filters
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Program Criteria Banner (Visible when a specific program is selected) --}}
    @if ($selectedProgram)
        <div class="card sf-card mb-4 border-0 shadow-sm" style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border-left: 4px solid #0F2942 !important;">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 pb-2 border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; background: rgba(15, 41, 66, 0.1); color: #0F2942;">
                            <i class="bi bi-card-checklist"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-0 text-dark">Program Eligibility Criteria: {{ $selectedProgram->program_name }}</h6>
                            <small class="text-secondary">{{ $selectedProgram->sponsor->company_organization_name ?? 'Sponsor' }}</small>
                        </div>
                    </div>
                    @if ($availableSlots !== null)
                        <span class="badge bg-primary px-3 py-2">
                            <i class="bi bi-people-fill me-1"></i> {{ $availableSlots }} Available Slot{{ $availableSlots === 1 ? '' : 's' }}
                        </span>
                    @endif
                </div>
                <div class="row g-3">
                    <div class="col-sm-6 col-md-4 col-lg-2">
                        <div class="small text-muted fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Minimum GWA</div>
                        <div class="fw-bold fs-6 text-dark mt-1">
                            @if ($selectedProgram->min_gpa)
                                <span class="badge bg-light text-dark border">{{ number_format($selectedProgram->min_gpa, 2) }}</span>
                            @else
                                <span class="text-secondary fw-normal">No minimum</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-4 col-lg-2">
                        <div class="small text-muted fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Address Requirement</div>
                        <div class="fw-semibold text-dark mt-1">
                            {{ $selectedProgram->address_requirement ?: 'No preference' }}
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-4 col-lg-2">
                        <div class="small text-muted fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Eligible Year Levels</div>
                        <div class="fw-semibold text-dark mt-1">
                            @if (! empty($selectedProgram->eligible_year_levels))
                                {{ implode(', ', array_map(fn($y) => "Year {$y}", (array) $selectedProgram->eligible_year_levels)) }}
                            @else
                                <span class="text-secondary fw-normal">All Year Levels</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-6 col-lg-3">
                        <div class="small text-muted fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Eligible Campuses</div>
                        <div class="fw-semibold text-dark mt-1 small">
                            @if (! empty($selectedProgram->eligible_campuses))
                                {{ implode(', ', (array) $selectedProgram->eligible_campuses) }}
                            @else
                                <span class="text-secondary fw-normal">All Campuses</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-6 col-lg-3">
                        <div class="small text-muted fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Eligible Courses</div>
                        <div class="fw-semibold text-dark mt-1 small">
                            @php
                                $coursesList = collect();
                                if ($selectedProgram->academicPrograms && $selectedProgram->academicPrograms->isNotEmpty()) {
                                    $coursesList = $selectedProgram->academicPrograms->map(fn($ap) => $ap->code ?: $ap->name);
                                } elseif (filled($selectedProgram->target_course)) {
                                    $coursesList = collect(explode(',', (string) $selectedProgram->target_course))->map('trim');
                                }
                            @endphp
                            @if ($coursesList->isNotEmpty())
                                {{ $coursesList->implode(', ') }}
                            @else
                                <span class="text-secondary fw-normal">All Academic Programs</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Queue Table --}}
    @if ($pendingApplications->isEmpty())
        <div class="card sf-card">
            <div class="sf-empty-state">
                <i class="bi bi-inboxes"></i>
                <div class="fw-semibold">Queue is empty</div>
                <div class="small">No applications match the current filters.</div>
            </div>
        </div>
    @else
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
            <div class="small text-secondary">
                <i class="bi bi-stack me-1"></i>
                Showing {{ $pendingApplications->count() }} of {{ $pendingApplications->total() }} applicant(s)
                @if ($selectedProgram)
                    <span class="mx-1">·</span>
                    <span class="badge bg-cyan-50 text-cyan-700 border border-cyan-200">
                        <i class="bi bi-arrow-down-up me-1"></i>Ranked by GWA, then Submission Date
                    </span>
                @endif
            </div>
            <button type="button" class="btn btn-primary fw-semibold" data-bs-toggle="modal"
                data-bs-target="#createBatchModal">
                <i class="bi bi-list-check me-1"></i>Create Batch List from Selected
            </button>
        </div>

        <div class="card sf-card">
            <div class="table-responsive">
                <table class="table sf-table mb-0">
                    <thead>
                        <tr>
                            <th class="ps-3" style="width: 36px;">
                                <input type="checkbox" class="form-check-input" id="selectAllApps"
                                    aria-label="Select all applicants on this page">
                            </th>
                            <th class="ps-4">Student ID</th>
                            <th>Student Name</th>
                            <th>Course</th>
                            <th>Year Level</th>
                            <th>GWA</th>
                            <th>Program Applied</th>
                            <th>Date Submitted</th>
                            <th>Documents</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pendingApplications as $application)
                            @php
                                $profile = $application->studentProfile;
                                $slots = $availableSlots ?? ($selectedProgram->available_slots ?? 0);
                                $rank = ($pendingApplications instanceof \Illuminate\Pagination\LengthAwarePaginator)
                                    ? ($pendingApplications->firstItem() + $loop->index)
                                    : $loop->iteration;
                                $isTopCandidate = $selectedProgram && $slots > 0 && $rank <= $slots;
                            @endphp
                            <tr class="{{ $isTopCandidate ? 'table-success bg-opacity-10' : '' }}"
                                style="{{ $isTopCandidate ? 'border-left: 4px solid #16a34a !important; background-color: rgba(22, 163, 74, 0.04);' : '' }}">
                                {{-- Selection --}}
                                <td class="ps-3">
                                    <input type="checkbox" class="form-check-input app-checkbox"
                                        name="selected_applications[]" value="{{ $application->id }}"
                                        data-status="{{ $application->status->value }}"
                                        aria-label="Select {{ $profile->user->name ?? $profile->student_id_number }}">
                                </td>

                                {{-- Student ID --}}
                                <td class="ps-4">
                                    <span class="small text-secondary sf-mono fw-semibold">{{ $profile->student_id_number ?: '—' }}</span>
                                </td>

                                {{-- Student Name --}}
                                <td>
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <div class="fw-semibold">{{ $profile->user->name ?? trim($profile->first_name . ' ' . ($profile->middle_name ?? '') . ' ' . $profile->last_name . ($profile->extension_name ? ' ' . $profile->extension_name : '')) }}</div>
                                        @if ($isTopCandidate)
                                            <span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle d-inline-flex align-items-center gap-1" style="font-size: 0.72rem;">
                                                <i class="bi bi-star-fill text-warning"></i> Top {{ $slots }} Candidate
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                {{-- Course --}}
                                <td>
                                    <div>{{ $profile->course ?: '—' }}</div>
                                </td>

                                {{-- Year Level --}}
                                <td>
                                    @if ($profile->year_level)
                                        <span>Year {{ $profile->year_level }}</span>
                                    @else
                                        <span class="text-muted">Year N/A</span>
                                    @endif
                                </td>

                                {{-- GWA --}}
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="fw-semibold sf-mono">{{ number_format($application->gpa_submitted, 2) }}</span>
                                        @if ($isTopCandidate)
                                            <span class="badge bg-success bg-opacity-25 text-success-emphasis border border-success-subtle" style="font-size: 0.68rem;" title="Rank in program-filtered queue">
                                                #{{ $rank }}
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                {{-- Program Applied --}}
                                <td>
                                    <div class="small fw-semibold text-break" style="max-width:180px;">
                                        {{ $application->sponsorshipProgram->program_name }}
                                    </div>
                                    <div class="small text-secondary">
                                        {{ $application->sponsorshipProgram->sponsor->company_organization_name }}
                                    </div>
                                </td>

                                {{-- Date Submitted --}}
                                <td>
                                    <div class="small">{{ optional($application->submitted_at ?? $application->created_at)->format('M d, Y') }}</div>
                                </td>

                                {{-- Documents --}}
                                <td>
                                    @php $docCounts = $application->documentStatusCounts(); @endphp
                                    <span class="badge {{ $docCounts['uploaded'] >= $docCounts['required'] ? 'bg-success-subtle text-success-emphasis' : 'bg-warning-subtle text-warning-emphasis' }}"
                                        title="{{ $docCounts['uploaded'] }} of {{ $docCounts['required'] }} required docs">
                                        <i class="bi bi-paperclip me-1"></i>{{ $docCounts['uploaded'] }}
                                    </span>
                                </td>

                                {{-- Status --}}
                                <td>
                                    <x-status-badge :status="$application->status" />
                                </td>

                                {{-- Actions --}}
                                <td class="text-end pe-4">
                                    <a href="{{ route('fassg.applications.show', $application) }}"
                                        class="btn btn-sm btn-navy-primary">
                                        Review Application <i class="bi bi-chevron-right"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        @if ($pendingApplications->hasPages())
            <div class="mt-3 d-flex justify-content-end">
                {{ $pendingApplications->withQueryString()->links() }}
            </div>
        @endif
    @endif

    {{-- Create Batch List Modal --}}
    <div class="modal fade" id="createBatchModal" tabindex="-1" aria-labelledby="createBatchModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('fassg.applications.create-batch') }}" class="modal-content"
                id="createBatchForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="createBatchModalLabel">
                        <i class="bi bi-list-check me-1"></i>Create Batch List from Selected
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="selectedCountNote" class="alert alert-info py-2 small mb-3">
                        <i class="bi bi-info-circle me-1"></i>0 applicant(s) selected.
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark mb-2">Save As</label>
                        <div class="form-check mb-1">
                            <input class="form-check-input" type="radio" name="batch_mode" id="batchModeNew"
                                value="new" @checked(old('batch_mode', 'new') === 'new')>
                            <label class="form-check-label small" for="batchModeNew">Create New Batch List</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="batch_mode" id="batchModeAppend"
                                value="append" @checked(old('batch_mode') === 'append')>
                            <label class="form-check-label small" for="batchModeAppend">Append to Existing Saved Batch</label>
                        </div>
                    </div>
                    <div id="batchNameGroup" class="mb-3">
                        <label class="form-label small fw-semibold text-dark" for="batch_name">Batch Name</label>
                        <input type="text" id="batch_name" name="batch_name" class="form-control"
                            placeholder="e.g., CHED Batch 1 - 2026" required maxlength="150"
                            value="{{ old('batch_name') }}">
                    </div>
                    <div id="appendListGroup" class="mb-3 d-none">
                        <label class="form-label small fw-semibold text-dark" for="existing_fixed_list_id">Existing Saved Batch</label>
                        <select id="existing_fixed_list_id" name="existing_fixed_list_id" class="form-select">
                            <option value="">Select batch…</option>
                            @foreach ($savedFixedLists as $savedList)
                                <option value="{{ $savedList->id }}" data-program-id="{{ $savedList->sponsorship_program_id }}"
                                    @selected((int) old('existing_fixed_list_id') === $savedList->id)>
                                    {{ $savedList->batch_name }} ({{ $savedList->total_names }} {{ Str::plural('name', $savedList->total_names) }})
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text small text-secondary">
                            Only Saved or Draft batches from the target program can be appended to.
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold text-dark" for="batch_program">Target Program</label>
                        <select id="batch_program" name="sponsorship_program_id" class="form-select" required>
                            <option value="">Select program…</option>
                            @foreach ($programs as $prog)
                                <option value="{{ $prog->id }}" @selected((int) request('program_id') === $prog->id)>
                                    {{ $prog->program_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-text small text-secondary">
                        Only applicants applied to the target program will be included in the batch.
                    </div>
                    <div id="selectedApplicantsContainer"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary fw-semibold"
                        data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-semibold">
                        <i class="bi bi-list-check me-1"></i>Create Batch List
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            (function () {
                const selectAll = document.getElementById('selectAllApps');
                const checkboxes = Array.from(document.querySelectorAll('.app-checkbox'));
                const modalForm = document.getElementById('createBatchForm');
                const container = document.getElementById('selectedApplicantsContainer');
                const countNote = document.getElementById('selectedCountNote');
                const availableSlots = {{ $availableSlots ?? 0 }};

                function updateState() {
                    if (!selectAll) {
                        return;
                    }
                    const checked = checkboxes.filter(chk => chk.checked);
                    selectAll.checked = checked.length > 0 && checked.length === checkboxes.length;
                }

                function syncSelected() {
                    const checked = checkboxes.filter(chk => chk.checked);
                    if (container) {
                        container.innerHTML = '';
                        checked.forEach(chk => {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'selected_applications[]';
                            input.value = chk.value;
                            container.appendChild(input);
                        });
                    }
                    if (countNote) {
                        countNote.className = 'alert alert-info py-2 small mb-3';
                        countNote.innerHTML = '<i class="bi bi-info-circle me-1"></i>' + checked.length +
                            ' applicant(s) selected.';
                    }
                    return checked.length;
                }

                if (selectAll) {
                    selectAll.addEventListener('change', function () {
                        checkboxes.forEach(chk => { chk.checked = selectAll.checked; });
                        updateState();
                    });
                }

                checkboxes.forEach(chk => chk.addEventListener('change', updateState));

                if (modalForm) {
                    modalForm.addEventListener('submit', function (e) {
                        if (syncSelected() === 0) {
                            e.preventDefault();
                            if (countNote) {
                                countNote.className = 'alert alert-danger py-2 small mb-3';
                                countNote.textContent =
                                    'Select at least one applicant to include in this batch list.';
                            }
                            return;
                        }
                        const appendChecked = batchModeAppend && batchModeAppend.checked;
                        if (appendChecked && existingListSelect && existingListSelect.value === '') {
                            e.preventDefault();
                            if (countNote) {
                                countNote.className = 'alert alert-danger py-2 small mb-3';
                                countNote.textContent =
                                    'Select the existing saved batch you want to append to.';
                            }
                        }
                    });
                }

                const modalEl = document.getElementById('createBatchModal');
                if (modalEl) {
                    modalEl.addEventListener('show.bs.modal', function () {
                        syncSelected();
                    });
                }

                // Auto-check the top available non-rejected applicants matching the
                // program's available slots (only meaningful when a program is
                // selected and the queue is GPA-ranked).
                if (availableSlots > 0) {
                    const eligible = checkboxes.filter(chk => chk.dataset.status !== 'Rejected');
                    const precheck = Math.min(availableSlots, eligible.length);
                    eligible.forEach((chk, index) => { chk.checked = index < precheck; });
                    updateState();
                }

                // Batch mode toggle: "Create New Batch List" vs "Append to Existing Saved Batch"
                const batchModeNew = document.getElementById('batchModeNew');
                const batchModeAppend = document.getElementById('batchModeAppend');
                const batchNameGroup = document.getElementById('batchNameGroup');
                const appendListGroup = document.getElementById('appendListGroup');
                const batchNameInput = document.getElementById('batch_name');
                const existingListSelect = document.getElementById('existing_fixed_list_id');
                const batchProgramSelect = document.getElementById('batch_program');

                function syncBatchMode() {
                    if (!batchModeNew) return;
                    const append = batchModeAppend && batchModeAppend.checked;
                    if (batchNameGroup) {
                        batchNameGroup.classList.toggle('d-none', append);
                    }
                    if (appendListGroup) {
                        appendListGroup.classList.toggle('d-none', !append);
                    }
                    if (batchNameInput) {
                        batchNameInput.required = !append;
                    }
                    if (existingListSelect) {
                        existingListSelect.required = append;
                    }
                }

                function filterAppendOptions() {
                    if (!batchProgramSelect || !existingListSelect) return;
                    const pid = batchProgramSelect.value;
                    Array.from(existingListSelect.options).forEach(opt => {
                        opt.hidden = opt.value !== '' && opt.dataset.programId !== pid;
                    });
                    if (existingListSelect.value && existingListSelect.selectedOptions.length > 0 && existingListSelect.selectedOptions[0].hidden) {
                        existingListSelect.value = '';
                    }
                }

                if (batchModeNew) batchModeNew.addEventListener('change', syncBatchMode);
                if (batchModeAppend) batchModeAppend.addEventListener('change', syncBatchMode);
                if (batchProgramSelect) batchProgramSelect.addEventListener('change', filterAppendOptions);
                syncBatchMode();
                filterAppendOptions();
            })();
        </script>
    @endpush
@endsection