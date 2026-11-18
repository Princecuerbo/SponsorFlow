@extends('layouts.app')

@section('title', 'Application Queue')
@section('eyebrow', 'FASSG Office · Applications')
@section('page-title', 'Application Queue')
@section('subtitle', 'Prepare draft batches, then verify each member in Generated Batch review.')

@section('header-actions')
    @php
        $statusLinks = [
            ['label' => 'Pending Applications', 'enum' => 'Pending', 'param' => 'pending', 'icon' => 'bi-hourglass-split', 'class' => 'sf-app-stat-pending'],
            ['label' => 'Verified', 'enum' => 'Verified', 'param' => 'verified', 'icon' => 'bi-patch-check', 'class' => 'sf-app-stat-verified'],
            ['label' => 'Approved', 'enum' => 'Approved', 'param' => 'approved', 'icon' => 'bi-award', 'class' => 'sf-app-stat-approved'],
            ['label' => 'Resubmission Requested', 'enum' => 'Resubmission Requested', 'param' => 'resubmission requested', 'icon' => 'bi-arrow-counterclockwise', 'class' => 'sf-app-stat-resubmission'],
            ['label' => 'Rejected', 'enum' => 'Rejected', 'param' => 'rejected', 'icon' => 'bi-x-circle', 'class' => 'sf-app-stat-rejected'],
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
            class="sf-app-stat-pill {{ $link['class'] }} {{ $isActive ? 'active' : '' }}">
            <i class="bi {{ $link['icon'] }}"></i>
            <span>{{ $counts[$link['enum']] }} {{ $link['label'] }}</span>
        </a>
    @endforeach
    @if ($selectedProgram && $availableSlots !== null)
        <span class="sf-app-stat-pill sf-app-stat-slots">
            <i class="bi bi-people"></i>
            <span>Available Slots: {{ $availableSlots }}</span>
        </span>
    @endif
@endsection

@push('styles')
    @include('fassg.partials.applications-styles')
@endpush

@section('content')
    <div class="sf-applications-page">
        {{-- Filter Toolbar --}}
        <div class="sf-app-filter-card">
            <form method="GET" action="{{ route('fassg.applications.index') }}" class="sf-app-filter-row">
                {{-- Program --}}
                <div class="sf-app-filter-col-program">
                    <label class="sf-app-filter-label" for="filter-program">Program</label>
                    <select id="filter-program" name="program_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All programs</option>
                        @foreach ($programs as $prog)
                            <option value="{{ $prog->id }}" @selected((int) request('program_id') === $prog->id)>
                                {{ $prog->program_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Campus --}}
                <div class="sf-app-filter-col-campus">
                    <label class="sf-app-filter-label" for="filter-campus">Campus</label>
                    <select id="filter-campus" name="campus" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Campuses</option>
                        @foreach (['Main Campus (City of Mati)', 'Baganga Campus', 'Banaybanay Campus', 'Cateel Campus', 'San Isidro Campus', 'Tarragona Campus'] as $campusOpt)
                            <option value="{{ $campusOpt }}" @selected(request('campus') === $campusOpt)>{{ $campusOpt }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Status --}}
                <div class="sf-app-filter-col-status">
                    <label class="sf-app-filter-label" for="filter-status">Status</label>
                    <select id="filter-status" name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All statuses</option>
                        <option value="Pending" @selected(request('status') === 'Pending')>Pending</option>
                        <option value="Verified" @selected(request('status') === 'Verified')>Verified</option>
                        <option value="Approved" @selected(request('status') === 'Approved')>Approved</option>
                        <option value="Resubmission Requested" @selected(request('status') === 'Resubmission Requested')>Resubmission Requested</option>
                        <option value="Rejected" @selected(request('status') === 'Rejected')>Rejected</option>
                    </select>
                </div>

                {{-- Search --}}
                <div class="sf-app-filter-col-search">
                    <label class="sf-app-filter-label" for="filter-search">Search</label>
                    <div class="sf-app-search-group">
                        <i class="bi bi-search sf-app-search-icon"></i>
                        <input type="text" id="filter-search" name="q" value="{{ request('q') }}"
                            class="form-control form-control-sm sf-app-search-input"
                            placeholder="Name, student ID, academic program…"
                            oninput="clearTimeout(window.searchTimer); window.searchTimer = setTimeout(() => this.form.submit(), 600)">
                    </div>
                </div>

                {{-- Reset --}}
                <div class="sf-app-filter-col-reset">
                    <a href="{{ route('fassg.applications.index') }}" class="sf-app-btn-reset">
                        <i class="bi bi-arrow-counterclockwise"></i>
                        <span>Reset Filters</span>
                    </a>
                </div>
            </form>
        </div>

        {{-- Program Criteria Banner (Visible when a specific program is selected) --}}
        @if ($selectedProgram)
            <div class="sf-app-criteria-card">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 pb-2 border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; background: rgba(15, 41, 66, 0.08); color: #0F2942;">
                            <i class="bi bi-card-checklist fs-6"></i>
                        </div>
                        <div>
                            <h3 class="h6 sf-heading mb-0 fw-bold" style="color: #0F2942;">Program Eligibility Criteria: {{ $selectedProgram->program_name }}</h3>
                            <small class="text-secondary">{{ $selectedProgram->sponsor->company_organization_name ?? 'Sponsor' }}</small>
                            @if ($selectedProgram->fixedLists->isNotEmpty())
                                <span class="badge bg-warning-subtle text-warning-emphasis ms-2">Fixed List Available</span>
                            @endif
                        </div>
                    </div>
                    @if ($availableSlots !== null)
                        <span class="badge bg-primary px-3 py-2 fw-semibold">
                            <i class="bi bi-people-fill me-1"></i> {{ $availableSlots }} Available Slot{{ $availableSlots === 1 ? '' : 's' }}
                        </span>
                    @endif
                </div>
                <div class="row g-3">
                    <div class="col-sm-6 col-md-4 col-lg-2">
                        <div class="sf-app-criteria-tile">
                            <div class="sf-app-filter-label" style="font-size: 11px;">Minimum GWA</div>
                            <div class="fw-bold fs-6 text-dark mt-1 sf-mono">
                                @if ($selectedProgram->min_gpa)
                                    <span class="badge bg-light text-dark border">{{ number_format($selectedProgram->min_gpa, 2) }}</span>
                                @else
                                    <span class="text-secondary fw-normal">No minimum</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-4 col-lg-2">
                        <div class="sf-app-criteria-tile">
                            <div class="sf-app-filter-label" style="font-size: 11px;">Address Requirement</div>
                            <div class="fw-semibold text-dark mt-1 small">
                                {{ $selectedProgram->address_requirement ?: 'No preference' }}
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-4 col-lg-2">
                        <div class="sf-app-criteria-tile">
                            <div class="sf-app-filter-label" style="font-size: 11px;">Eligible Year Levels</div>
                            <div class="fw-semibold text-dark mt-1 small">
                                @if (! empty($selectedProgram->eligible_year_levels))
                                    {{ implode(', ', array_map(fn($y) => "Year {$y}", (array) $selectedProgram->eligible_year_levels)) }}
                                @else
                                    <span class="text-secondary fw-normal">All Year Levels</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-6 col-lg-3">
                        <div class="sf-app-criteria-tile">
                            <div class="sf-app-filter-label" style="font-size: 11px;">Eligible Campuses</div>
                            <div class="fw-semibold text-dark mt-1 small">
                                @if (! empty($selectedProgram->eligible_campuses))
                                    {{ implode(', ', (array) $selectedProgram->eligible_campuses) }}
                                @else
                                    <span class="text-secondary fw-normal">All Campuses</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-6 col-lg-3">
                        <div class="sf-app-criteria-tile">
                            <div class="sf-app-filter-label" style="font-size: 11px;">Eligible Academic Programs</div>
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

        {{-- Selection Toolbar --}}
        <div class="sf-app-selection-bar">
            <div class="sf-app-note-text">
                <i class="bi bi-stack text-secondary"></i>
                <span>Showing all {{ $pendingApplications->count() }} applicant(s) matching the filters; capacity does not hide applicants.</span>
                @if ($selectedProgram)
                    <span class="mx-1">·</span>
                    <span class="badge bg-light text-secondary border">
                        <i class="bi bi-person-check me-1"></i>Manual FASSG selection — oldest submitted first for display
                    </span>
                @endif
            </div>

            <button type="button" id="createBatchBtn" class="sf-app-btn-batch"
                data-bs-toggle="modal" data-bs-target="#createBatchModal">
                <i class="bi bi-list-check"></i>
                <span id="createBatchBtnLabel">Select Candidates / Create Batch</span>
            </button>
        </div>

        {{-- Queue Table --}}
        @if ($pendingApplications->isEmpty())
            <div class="sf-app-table-card">
                <div class="text-center py-5">
                    <i class="bi bi-inboxes display-4 text-secondary opacity-50 mb-3 d-block"></i>
                    <h5 class="fw-bold text-dark mb-1">Queue is empty</h5>
                    <p class="text-secondary small mb-0">No applications match the current filters.</p>
                </div>
            </div>
        @else
            <div class="sf-app-table-card">
                <div class="sf-app-table-header-row">
                    <h2 class="sf-app-table-title mb-0">
                        <span>Applicants</span>
                        <span class="badge bg-light text-secondary border fw-semibold ms-1" style="font-size: 12px;">{{ $pendingApplications->count() }}</span>
                    </h2>
                </div>

                <div class="sf-app-table-container table-responsive">
                    <table class="table sf-app-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 44px;">
                                    <div class="d-flex flex-column align-items-center gap-1">
                                        <input type="checkbox" class="form-check-input mt-0" id="selectAllApps"
                                            aria-label="Select all applicants on this page">
                                        <span id="selectAllAppsBadge"
                                            class="badge rounded-pill d-none align-items-center justify-content-center"
                                            style="background-color: #0F2942; font-size: 0.65rem; min-width: 20px; padding: 0.15rem 0.4rem;"
                                            title="Applicants selected">0</span>
                                    </div>
                                </th>
                                <th style="width: 120px;">Student ID</th>
                                <th style="width: 180px;">Student Name</th>
                                <th style="width: 200px;">Academic Program</th>
                                <th style="width: 85px;">Year Level</th>
                                <th style="width: 90px;">GWA</th>
                                <th style="width: 180px;">Program Applied</th>
                                <th style="width: 135px;">Date Submitted</th>
                                <th style="width: 90px;">Documents</th>
                                <th style="width: 120px;">Status</th>
                                <th class="text-end" style="width: 150px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pendingApplications as $application)
                                @php
                                    $profile = $application->studentProfile;
                                    $isBatchable = in_array($application->id, $selectableApplicationIds, true);
                                    $draftBatch = $application->batchCandidates->first()?->generatedBatch;
                                    $isEndorsed = in_array(
                                        $application->sponsorship_program_id . '|' . trim((string) ($profile->student_id_number ?? '')),
                                        $endorsedKeys ?? [],
                                        true,
                                    );
                                @endphp
                                <tr>
                                    {{-- Selection Checkbox --}}
                                    <td class="text-center">
                                        <input type="checkbox" class="form-check-input app-checkbox"
                                            name="selected_applications[]" value="{{ $application->id }}"
                                            data-program-id="{{ $application->sponsorship_program_id }}"
                                            data-student-id="{{ $application->student_profile_id }}"
                                            data-student-label="{{ $profile->student_id_number }} — {{ $profile->full_name ?: $profile->user?->name }}"
                                            @checked($isBatchable && in_array($application->id, array_map('intval', (array) old('selected_applications', [])), true))
                                            @disabled(! $isBatchable)
                                            @if (! $isBatchable) title="Requires Pending/Verified status, documented SLE-FHE verification, no active sponsorship, and no existing batch" @endif
                                            aria-label="Select {{ $profile->user->name ?? $profile->student_id_number }}">
                                    </td>

                                    {{-- Student ID --}}
                                    <td>
                                        <span class="sf-app-student-id">{{ $profile->student_id_number ?: '—' }}</span>
                                    </td>

                                    {{-- Student Name --}}
                                    <td>
                                        <div class="sf-app-student-name">
                                            {{ $profile->user->name ?? trim($profile->first_name . ' ' . ($profile->middle_name ?? '') . ' ' . $profile->last_name . ($profile->extension_name ? ' ' . $profile->extension_name : '')) }}
                                        </div>
                                        @if ($isEndorsed)
                                            <span class="sf-app-fixed-tag" title="FASSG-endorsed entry on a finalized fixed list for this program">
                                                <i class="bi bi-check-circle-fill"></i> On Finalized Fixed List
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Academic Program --}}
                                    <td>
                                        <div class="sf-app-program-name">{{ $profile->display_course }}</div>
                                    </td>

                                    {{-- Year Level --}}
                                    <td>
                                        @if ($profile->year_level)
                                            <span class="sf-app-year-badge">Year {{ $profile->year_level }}</span>
                                        @else
                                            <span class="text-muted small">Year N/A</span>
                                        @endif
                                    </td>

                                    {{-- GWA --}}
                                    <td>
                                        <span class="sf-app-gwa-text">
                                            {{ $application->gpa_submitted === null ? 'Not recorded' : number_format($application->gpa_submitted, 2) }}
                                        </span>
                                    </td>

                                    {{-- Program Applied --}}
                                    <td>
                                        <div class="sf-app-target-program">
                                            {{ $application->sponsorshipProgram->program_name }}
                                        </div>
                                        <div class="sf-app-sponsor-name">
                                            {{ $application->sponsorshipProgram->sponsor->company_organization_name }}
                                        </div>
                                        @if ($programs->firstWhere('id', $application->sponsorship_program_id)?->fixedLists->isNotEmpty())
                                            <span class="sf-app-fixed-tag">
                                                <i class="bi bi-file-earmark-check"></i> Fixed List Available
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Date Submitted --}}
                                    <td>
                                        <div class="small text-secondary">
                                            {{ optional($application->submitted_at ?? $application->created_at)->format('M d, Y, h:i A') }}
                                        </div>
                                    </td>

                                    {{-- Documents --}}
                                    <td>
                                        @php $docCounts = $application->documentStatusCounts(); @endphp
                                        <span class="sf-app-docs-badge {{ $docCounts['uploaded'] >= $docCounts['required'] ? 'bg-success-subtle text-success-emphasis border border-success-subtle' : 'bg-warning-subtle text-warning-emphasis border border-warning-subtle' }}"
                                            title="{{ $docCounts['uploaded'] }} of {{ $docCounts['required'] }} required docs">
                                            <i class="bi bi-file-earmark-text"></i>
                                            <span>{{ $docCounts['uploaded'] }}</span>
                                        </span>
                                    </td>

                                    {{-- Status --}}
                                    <td>
                                        <x-status-badge :status="$application->status" />
                                    </td>

                                    {{-- Actions --}}
                                    <td class="text-end">
                                        <a href="{{ $draftBatch ? route('fassg.generated-batches.applications.show', [$draftBatch, $application]) : route('fassg.applications.show', $application) }}"
                                            class="sf-app-btn-view">
                                            <span>{{ $draftBatch ? 'Review Batch Member' : 'View Application' }}</span>
                                            <i class="bi bi-chevron-right"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

    {{-- Create Batch List Modal --}}
    <div class="modal fade" id="createBatchModal" tabindex="-1" aria-labelledby="createBatchModalLabel"
        aria-hidden="true" data-open-on-load="{{ $openBatchModal ? '1' : '0' }}">
        <div class="modal-dialog sf-app-modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('fassg.applications.create-batch') }}" class="modal-content sf-app-modal-content"
                id="createBatchForm">
                @csrf
                <div class="modal-header sf-app-modal-header">
                    <div>
                        <h5 class="modal-title sf-app-modal-title" id="createBatchModalLabel">
                            <i class="bi bi-list-check" style="color: #0F2942;"></i> Create Batch List from Selected
                        </h5>
                        <p class="text-secondary small mb-0 mt-1">Review the selected applicants before saving.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body sf-app-modal-body">
                    {{-- Selected count info alert --}}
                    <div id="selectedCountNote" class="sf-app-summary-box mb-3">
                        <i class="bi bi-info-circle me-1"></i>Select ordinary applicants or a finalized Fixed List.
                    </div>

                    {{-- Save As Mode --}}
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark mb-2">Save As</label>
                        <div class="form-check mb-1">
                            <input class="form-check-input" type="radio" name="batch_mode" id="batchModeNew"
                                value="new" @checked(old('batch_mode', 'new') === 'new')>
                            <label class="form-check-label small fw-medium" for="batchModeNew">Create New Batch List</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="batch_mode" id="batchModeAppend"
                                value="append" @checked(old('batch_mode') === 'append')>
                            <label class="form-check-label small fw-medium" for="batchModeAppend">Append to Existing Saved Batch</label>
                        </div>
                    </div>

                    {{-- Batch Name --}}
                    <div id="batchNameGroup" class="mb-3">
                        <label class="form-label small fw-semibold text-dark" for="batch_name">Batch Name</label>
                        <input type="text" id="batch_name" name="batch_name" class="form-control"
                            placeholder="e.g., CHED Batch 1 - 2026" required maxlength="150"
                            value="{{ old('batch_name') }}">
                    </div>

                    {{-- Existing Saved Batch --}}
                    <div id="appendListGroup" class="mb-3 d-none">
                        <label class="form-label small fw-semibold text-dark" for="existing_fixed_list_id">Existing Saved Batch</label>
                        <select id="existing_fixed_list_id" name="existing_fixed_list_id" class="form-select">
                            <option value="">Select batch…</option>
                            @foreach ($savedFixedLists as $savedList)
                                <option value="{{ $savedList->id }}" data-program-id="{{ $savedList->sponsorship_program_id }}"
                                    data-student-ids="{{ $savedList->items->pluck('application.student_profile_id')->filter()->unique()->values()->toJson() }}"
                                    @selected((int) old('existing_fixed_list_id') === $savedList->id)>
                                    {{ $savedList->batch_name }} ({{ $savedList->total_slots }} {{ Str::plural('slot', $savedList->total_slots) }})
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text small text-secondary mt-1">
                            Only Saved batches from the target program can be appended to.
                        </div>
                    </div>

                    {{-- Target Program --}}
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark" for="batch_program">Target Program</label>
                        <select id="batch_program" name="sponsorship_program_id" class="form-select" required>
                            <option value="">Select program…</option>
                            @foreach ($programs as $prog)
                                <option value="{{ $prog->id }}" data-capacity="{{ $programCapacities[$prog->id] }}"
                                    @selected((int) old('sponsorship_program_id', $selectedProgramId) === $prog->id)>
                                    {{ $prog->program_name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text small text-secondary mt-1">
                            Manually select candidates from either source, or both, within available capacity.
                            A Fixed List-only batch needs no ordinary applicants. Saving a draft does not verify or approve anyone.
                        </div>
                    </div>

                    {{-- Include Finalized Fixed List --}}
                    <div id="finalizedListGroup" class="mb-3">
                        <label class="form-label small fw-semibold text-dark" for="fixed_list_source">Include Finalized Fixed List</label>
                        <select id="fixed_list_source" name="fixed_list_source" class="form-select">
                            <option value="">No Fixed List selected</option>
                            @foreach ($finalizedFixedLists as $finalizedList)
                                <option value="{{ $finalizedList->id }}" data-program-id="{{ $finalizedList->sponsorship_program_id }}"
                                    data-candidate-count="{{ $finalizedList->items->count() }}"
                                    data-student-ids="{{ $finalizedList->items->map(fn ($item) => $item->studentProfile?->id ?? ('unmatched:' . $item->id))->values()->toJson() }}"
                                    @selected((int) old('fixed_list_source', $selectedFixedListId) === $finalizedList->id)>
                                    {{ $finalizedList->batch_name }} ({{ $finalizedList->items->count() }} candidates)
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text small text-secondary mt-1">
                            Selecting a list deliberately includes its complete stored roster. Any ineligible member blocks saving; nobody is silently omitted.
                            Fixed-list students need no separate online application. Review each member in the saved Generated Batch before submission.
                        </div>
                    </div>

                    {{-- Manually selected applicants preview --}}
                    <div class="sf-app-preview-card">
                        <div class="small fw-bold text-dark mb-2 d-flex align-items-center gap-2">
                            <i class="bi bi-person-check text-primary"></i>
                            <span>Manually Selected Ordinary Applicants</span>
                        </div>
                        <ul id="selectedOrdinarySummary" class="sf-app-preview-list"></ul>
                    </div>

                    <div id="selectedApplicantsContainer"></div>
                </div>

                <div class="modal-footer sf-app-modal-footer">
                    <button type="button" class="btn btn-outline-secondary fw-semibold px-3"
                        data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-semibold px-3" style="background-color: #0F2942; border-color: #0F2942;">
                        <i class="bi bi-list-check me-1"></i> Create Batch List
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            (function () {
                const modalForm = document.getElementById('createBatchForm');
                if (!modalForm || modalForm.dataset.selectionInitialized === '1') return;
                modalForm.dataset.selectionInitialized = '1';
                const selectAll = document.getElementById('selectAllApps');
                const applications = Array.from(document.querySelectorAll('.app-checkbox'));
                const container = document.getElementById('selectedApplicantsContainer');
                const countNote = document.getElementById('selectedCountNote');
                const batchProgram = document.getElementById('batch_program');
                const fixedListSource = document.getElementById('fixed_list_source');
                const existingList = document.getElementById('existing_fixed_list_id');
                const batchModeAppend = document.getElementById('batchModeAppend');
                const batchNameInput = document.getElementById('batch_name');
                const submitButton = modalForm.querySelector('button[type="submit"]');

                const selected = candidates => candidates.filter(box =>
                    !box.disabled && box.checked && box.dataset.programId === batchProgram.value);
                const identity = box => box.dataset.studentId;
                const capacity = () => Number(batchProgram.selectedOptions[0]?.dataset.capacity || 0);
                const existingStudents = () => batchModeAppend.checked && existingList.value
                    ? JSON.parse(existingList.selectedOptions[0].dataset.studentIds || '[]').map(String) : [];
                // These IDs only preview unique counts. The server loads the roster by list ID.
                const fixedStudents = () => fixedListSource.value && !fixedListSource.selectedOptions[0]?.disabled
                    ? JSON.parse(fixedListSource.selectedOptions[0]?.dataset.studentIds || '[]').map(String) : [];
                const selectedStudents = () => new Set([...selected(applications).map(identity), ...fixedStudents()]);

                function updateState() {
                    const active = applications.filter(box => !box.disabled && box.dataset.programId === batchProgram.value);
                    const ordinaryCount = selected(applications).length;
                    if (selectAll) {
                        selectAll.disabled = active.length === 0;
                        selectAll.checked = ordinaryCount > 0 && ordinaryCount === active.length;
                        selectAll.indeterminate = ordinaryCount > 0 && ordinaryCount < active.length;
                    }
                    const badge = document.getElementById('selectAllAppsBadge');
                    if (badge) {
                        badge.textContent = ordinaryCount;
                        badge.classList.toggle('d-none', ordinaryCount === 0);
                        badge.classList.toggle('d-inline-flex', ordinaryCount > 0);
                    }
                    container.replaceChildren();
                    const summary = document.getElementById('selectedOrdinarySummary');
                    summary.replaceChildren();
                    selected(applications).forEach(box => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'selected_applications[]';
                        input.value = box.value;
                        container.appendChild(input);
                        const label = document.createElement('li');
                        label.textContent = box.dataset.studentLabel;
                        summary.appendChild(label);
                    });
                    if (ordinaryCount === 0) summary.textContent = 'None selected';
                    const count = selectedStudents().size;
                    const total = new Set([...existingStudents(), ...selectedStudents()]).size;
                    const exceedsCapacity = total > capacity();
                    const overlap = ordinaryCount + fixedStudents().length - count;
                    countNote.className = 'sf-app-summary-box mb-3 ' + (exceedsCapacity ? 'alert-danger' : '');
                    countNote.innerHTML = '<i class="bi bi-info-circle me-1"></i><strong>' + count + ' unique student(s) selected</strong> (' + total + ' total in draft; '
                        + capacity() + ' available slots).<br>'
                        + '<span class="small">• Ordinary Applicants selected: <strong>' + ordinaryCount + '</strong><br>'
                        + '• Finalized Fixed List candidates: <strong>' + fixedStudents().length + '</strong></span>'
                        + (overlap > 0 ? '<br><span class="small text-muted">• ' + overlap + ' overlapping student(s) deduplicated; Fixed List provenance retained.</span>' : '')
                        + (exceedsCapacity ? '<div class="mt-2 text-danger fw-bold"><i class="bi bi-exclamation-triangle me-1"></i>Exceeds available capacity. Uncheck ordinary applicants or choose a smaller finalized list.</div>' : '<div class="mt-2 small text-secondary">Review and verify each member in the Generated Batch after saving.</div>');
                    submitButton.disabled = !batchProgram.value || count === 0 || exceedsCapacity;
                    document.getElementById('createBatchBtnLabel').textContent = count > 0
                        ? 'Create Batch (' + count + ' Student(s) Selected)'
                        : 'Select Candidates / Create Batch';
                }

                function filterProgramOptions() {
                    [existingList, fixedListSource].forEach(select => {
                        Array.from(select.options).forEach(option => {
                            const wrongProgram = option.value !== '' && option.dataset.programId !== batchProgram.value;
                            option.hidden = wrongProgram;
                            option.disabled = wrongProgram;
                        });
                        if (select.selectedOptions[0]?.hidden) select.value = '';
                    });
                    applications.forEach(box => {
                        if (box.dataset.programId !== batchProgram.value) box.checked = false;
                    });
                    updateState();
                }

                function syncBatchMode() {
                    const append = batchModeAppend.checked;
                    document.getElementById('batchNameGroup').classList.toggle('d-none', append);
                    document.getElementById('appendListGroup').classList.toggle('d-none', !append);
                    batchNameInput.required = !append;
                    batchNameInput.disabled = append;
                    existingList.required = append;
                    existingList.disabled = !append;
                    updateState();
                }

                selectAll?.addEventListener('change', () => {
                    applications.filter(box => !box.disabled && box.dataset.programId === batchProgram.value)
                        .forEach(box => { box.checked = selectAll.checked; });
                    // Server and form validation reject overflow; selection never hides other applicants.
                    updateState();
                });
                applications.forEach(box => box.addEventListener('change', () => {
                    if (box.checked && box.dataset.programId !== batchProgram.value) {
                        batchProgram.value = box.dataset.programId;
                        filterProgramOptions();
                    }
                    if (box.checked) {
                        applications.filter(other => other !== box && other.dataset.programId === box.dataset.programId
                            && identity(other) === identity(box)).forEach(other => { other.checked = false; });
                    }
                    updateState();
                }));
                fixedListSource.addEventListener('change', updateState);
                existingList.addEventListener('change', updateState);
                batchProgram.addEventListener('change', () => {
                    applications.forEach(box => { box.checked = false; });
                    filterProgramOptions();
                    updateState();
                });
                document.querySelectorAll('input[name="batch_mode"]').forEach(radio => {
                    radio.addEventListener('change', syncBatchMode);
                });
                document.getElementById('createBatchModal').addEventListener('show.bs.modal', updateState);
                modalForm.addEventListener('submit', event => {
                    updateState();
                    if (submitButton.disabled) event.preventDefault();
                });
                // Restore only the user's explicit selections after validation errors.
                // Ordinary applicants are never preselected. A chosen list includes its entire roster.
                filterProgramOptions();
                syncBatchMode();
                const modal = document.getElementById('createBatchModal');
                if (modal.dataset.openOnLoad === '1' && typeof bootstrap !== 'undefined') {
                    bootstrap.Modal.getOrCreateInstance(modal).show();
                }
            })();
        </script>
    @endpush
@endsection
