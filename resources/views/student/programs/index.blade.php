@extends('layouts.app')

@section('title', 'Browse Programs')
@section('eyebrow', 'Student Portal')
@section('page-title', 'Open Sponsorship Programs')

@section('content')

    @unless (auth()->user()->studentProfile?->is_sle_fhe_verified)
        <div class="alert alert-warning border-0 shadow-sm rounded-3 mb-4">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>
            Your SLE-FHE status is still pending verification. Open sponsorship programs will appear after FASSG verifies your
            record.
            <a href="{{ route('student.verification.show') }}" class="alert-link">Check status</a>
        </div>
    @endunless

    <form method="GET" action="{{ route('student.programs.index') }}" id="filter-form" class="card sf-card mb-4">
        <div class="card-body p-3">
            <div class="row g-2 align-items-center">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i
                                class="bi bi-search text-secondary"></i></span>
                        <input type="text" name="q" value="{{ request('q') }}"
                            class="form-control border-start-0 ps-0" placeholder="Search program or sponsor name…"
                            oninput="clearTimeout(window.searchTimer); window.searchTimer = setTimeout(() => this.form.submit(), 600)">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="category" onchange="this.form.submit()" class="form-select">
                        <option value="">All Categories</option>
                        @foreach (['Group', 'Individual', 'Employee-Based'] as $cat)
                            <option value="{{ $cat }}" @selected(request('category') === $cat)>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <a href="{{ route('student.programs.index') }}" class="btn btn-outline-secondary w-100">Reset</a>
                </div>
            </div>
        </div>
    </form>

    @if ($programs->isEmpty())
        <div class="card sf-card">
            <div class="sf-empty-state">
                <i class="bi bi-inbox"></i>
                <div class="fw-semibold">No open programs match your search</div>
                <div class="small">Try clearing filters, or check back later — new programs open regularly.</div>
            </div>
        </div>
    @else
        <div class="row g-4">
            @foreach ($programs as $program)
                <div class="col-md-6 col-xl-4">
                    <div class="card sf-card h-100">
                        <div class="card-body p-4 d-flex flex-column min-w-0">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span
                                    class="badge bg-primary-subtle text-primary-emphasis">{{ $program->category->value }}</span>
                                <x-status-badge :status="$program->status" />
                            </div>

                            <h3 class="h6 sf-heading mb-1 text-break">{{ $program->program_name }}</h3>
                            <div class="small text-secondary mb-3 text-break">
                                <i class="bi bi-building me-1"></i>{{ $program->sponsor->company_organization_name }}
                            </div>

                            <ul class="list-unstyled small mb-3 flex-grow-1">
                                <li class="d-flex justify-content-between gap-3 py-1 border-bottom">
                                    <span class="text-secondary">Available slots</span>
                                    <span class="fw-semibold">{{ $program->available_slots }}</span>
                                </li>
                                @if ($program->min_gpa)
                                    <li class="d-flex justify-content-between gap-3 py-1 border-bottom">
                                        <span class="text-secondary">Minimum GPA</span>
                                        <span class="fw-semibold">{{ number_format($program->min_gpa, 2) }}</span>
                                    </li>
                                @endif
                                @if ($program->target_course)
                                    <li class="d-flex justify-content-between gap-3 py-1 border-bottom">
                                        <span class="text-secondary">Target course</span>
                                        <span class="fw-semibold text-end text-break">{{ $program->target_course }}</span>
                                    </li>
                                @endif
                                @if ($program->address_requirement)
                                    <li class="d-flex justify-content-between gap-3 py-1">
                                        <span class="text-secondary">Address requirement</span>
                                        <span
                                            class="fw-semibold text-end text-break">{{ $program->address_requirement }}</span>
                                    </li>
                                @endif
                            </ul>

                            <div class="mt-auto pt-3">
                                @if ($profile && $program->hasActiveApplicationForStudent($profile->id))
                                    <button type="button" class="btn btn-secondary btn-sm w-100" disabled>
                                        <i class="bi bi-check2-circle me-1"></i>Already Applied
                                    </button>
                                @elseif ($program->available_slots <= 0)
                                    <button type="button" class="btn btn-outline-secondary btn-sm w-100" disabled>
                                        <i class="bi bi-lock me-1"></i>No Slots Available
                                    </button>
                                @elseif ($hasActiveSponsorship)
                                    <button type="button" class="btn btn-outline-secondary btn-sm w-100" disabled
                                        title="You already have an active approved sponsorship.">
                                        <i class="bi bi-lock me-1"></i>Active Sponsorship Lock
                                    </button>
                                @elseif (!$profile?->is_sle_fhe_verified)
                                    <button type="button" class="btn btn-outline-secondary btn-sm w-100" disabled
                                        title="Applications are unavailable for this program.">
                                        <i class="bi bi-lock me-1"></i>Applications Unavailable
                                    </button>
                                @else
                                    <a href="{{ route('student.applications.create', ['sponsorshipProgram' => $program->id]) }}"
                                        class="btn btn-sf-gold btn-sm w-100">
                                        <i class="bi bi-pencil-square me-1"></i>Apply Now
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

    @endif

@endsection
