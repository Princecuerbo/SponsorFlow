@extends('layouts.app')

@section('title', 'Application Queue')
@section('eyebrow', 'FASSG Office · Applications')
@section('page-title', 'Application Queue')
@section('subtitle', 'Prepare draft batches, then verify each member in Generated Batch review.')

@section('header-actions')
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
@endsection

@push('styles')
    <style>

        .btn-batch-nav,
        a.btn-batch-nav,
        button.btn-batch-nav {
            background-color: #0F2537 !important;
            border-color: #0F2537 !important;
            color: #ffffff !important;
            font-weight: 600;
            font-size: 0.875rem;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 1px 3px rgba(15, 37, 55, 0.15) !important;
            transition: all 0.2s ease-in-out;
        }

        .btn-batch-nav:hover,
        .btn-batch-nav:focus,
        a.btn-batch-nav:hover,
        a.btn-batch-nav:focus,
        button.btn-batch-nav:hover,
        button.btn-batch-nav:focus {
            background-color: #0A1E31 !important;
            border-color: #0A1E31 !important;
            color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(15, 37, 55, 0.15) !important;
        }

        .btn-batch-nav:disabled {
            background-color: #f1f5f9 !important;
            border-color: #e2e8f0 !important;
            color: #94a3b8 !important;
            box-shadow: none !important;
            cursor: not-allowed;
            pointer-events: none;
        }

        .app-checkbox:disabled {
            cursor: not-allowed;
            opacity: 0.5;
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
                            placeholder="Name, student ID, academic program…"
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
                            <h3 class="h6 sf-heading mb-3">Program Eligibility Criteria: {{ $selectedProgram->program_name }}</h3>
                            <small class="text-secondary">{{ $selectedProgram->sponsor->company_organization_name ?? 'Sponsor' }}</small>
                            @if ($selectedProgram->fixedLists->isNotEmpty())
                                <span class="badge bg-warning-subtle text-warning-emphasis">Fixed List Available</span>
                            @endif
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
                        <div class="small text-muted fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Eligible Academic Programs</div>
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

    <div class="d-flex justify-content-end mb-3">
        <button type="button" id="createBatchBtn" class="btn btn-batch-nav"
            data-bs-toggle="modal" data-bs-target="#createBatchModal">
            <i class="bi bi-list-check text-white"></i>
            <span id="createBatchBtnLabel">Select Candidates / Create Batch</span>
        </button>
    </div>

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
                Showing all {{ $pendingApplications->count() }} applicant(s) matching the filters; capacity does not hide applicants.
                @if ($selectedProgram)
                    <span class="mx-1">·</span>
                    <span class="badge bg-cyan-50 text-cyan-700 border border-cyan-200">
                        <i class="bi bi-person-check me-1"></i>Manual FASSG selection — oldest submitted first for display
                    </span>
                @endif
            </div>

        </div>

        <div class="card sf-card">
            <div class="table-responsive">
                <table class="table sf-table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-3" style="width: 40px;">
                                <div class="d-flex flex-column align-items-center gap-1">
                                    <input type="checkbox" class="form-check-input" id="selectAllApps"
                                        aria-label="Select all applicants on this page">
                                    <span id="selectAllAppsBadge"
                                        class="badge rounded-pill d-none align-items-center justify-content-center"
                                        style="background-color: #0F2537; font-size: 0.62rem; min-width: 20px; padding: 0.15rem 0.4rem;"
                                        title="Applicants selected">0</span>
                                </div>
                            </th>
                            <th class="ps-4">Student ID</th>
                            <th>Student Name</th>
                            <th>Academic Program</th>
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
                                $isBatchable = in_array($application->id, $selectableApplicationIds, true);
                                $draftBatch = $application->batchCandidates->first()?->generatedBatch;
                                $isEndorsed = in_array(
                                    $application->sponsorship_program_id . '|' . trim((string) ($profile->student_id_number ?? '')),
                                    $endorsedKeys ?? [],
                                    true,
                                );
                            @endphp
                            <tr>
                                {{-- Selection --}}
                                <td class="ps-3">
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
                                <td class="ps-4">
                                    <span class="small text-secondary sf-mono fw-semibold">{{ $profile->student_id_number ?: '—' }}</span>
                                </td>

                                {{-- Student Name --}}
                                <td>
<div class="d-flex align-items-center gap-2 flex-wrap">
                                            <div class="fw-semibold">{{ $profile->user->name ?? trim($profile->first_name . ' ' . ($profile->middle_name ?? '') . ' ' . $profile->last_name . ($profile->extension_name ? ' ' . $profile->extension_name : '')) }}</div>
                                            @if ($isEndorsed)
                                                <span class="badge bg-warning-subtle text-warning-emphasis fw-bold" title="FASSG-endorsed entry on a finalized fixed list for this program">On Finalized Fixed List</span>
                                            @endif

                                    </div>
                                </td>

                                {{-- Academic Program --}}
                                <td>
                                    <div>{{ $profile->display_course }}</div>
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
                                        <span class="fw-semibold sf-mono">{{ $application->gpa_submitted === null ? 'Not recorded' : number_format($application->gpa_submitted, 2) }}</span>

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
                                    @if ($programs->firstWhere('id', $application->sponsorship_program_id)?->fixedLists->isNotEmpty())
                                        <span class="badge bg-warning-subtle text-warning-emphasis">Fixed List Available</span>
                                    @endif
                                </td>

                                {{-- Date Submitted --}}
                                <td>
                                    <div class="small">{{ optional($application->submitted_at ?? $application->created_at)->format('M d, Y, h:i A') }}</div>
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
                                    <a href="{{ $draftBatch ? route('fassg.generated-batches.applications.show', [$draftBatch, $application]) : route('fassg.applications.show', $application) }}"
                                        class="btn btn-sm btn-sf-navy">
                                        {{ $draftBatch ? 'Review Batch Member' : 'View Application' }} <i class="bi bi-chevron-right"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    @endif

    {{-- Create Batch List Modal --}}
    <div class="modal fade" id="createBatchModal" tabindex="-1" aria-labelledby="createBatchModalLabel"
        aria-hidden="true" data-open-on-load="{{ $openBatchModal ? '1' : '0' }}">
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
                        <i class="bi bi-info-circle me-1"></i>Select ordinary applicants or a finalized Fixed List.
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
                                    data-student-ids="{{ $savedList->items->pluck('application.student_profile_id')->filter()->unique()->values()->toJson() }}"
                                    @selected((int) old('existing_fixed_list_id') === $savedList->id)>
                                    {{ $savedList->batch_name }} ({{ $savedList->total_slots }} {{ Str::plural('slot', $savedList->total_slots) }})
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text small text-secondary">
                            Only Saved batches from the target program can be appended to.
                        </div>
                    </div>
                    <div class="mb-2">
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
                    </div>
                    <div class="form-text small text-secondary">
                        Manually select candidates from either source, or both, within available capacity.
                        A Fixed List-only batch needs no ordinary applicants. Saving a draft does not verify or approve anyone.
                    </div>
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
                        <div class="form-text small text-secondary">
                            Selecting a list deliberately includes its complete stored roster. Any ineligible member blocks saving; nobody is silently omitted.
                            Fixed-list students need no separate online application. Review each member in the saved Generated Batch before submission.
                        </div>
                    </div>
                    <div class="small mb-3"><strong>Manually selected ordinary applicants</strong><ul id="selectedOrdinarySummary" class="mb-0"></ul></div>
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
                    countNote.className = 'alert py-2 small mb-3 ' + (exceedsCapacity ? 'alert-danger' : 'alert-info');
                    countNote.textContent = count + ' student(s) selected; ' + total + ' in the resulting draft; '
                        + capacity() + ' available slot(s).'
                        + ' Fixed List: ' + fixedStudents().length + '; ordinary applicants: ' + ordinaryCount + '.'
                        + (overlap > 0 ? ' ' + overlap + ' overlapping student(s) counted once; Fixed List provenance retained.' : '')
                        + (exceedsCapacity ? ' Uncheck ordinary applicants or choose a smaller finalized list. Nobody will be omitted.' : ' Review and verify each member after saving.');
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
