@extends('layouts.app')

@section('title', 'Browse Programs')
@section('eyebrow', 'Student Portal')
@section('page-title', 'Open Sponsorship Programs')

@push('styles')
    <style>
        /* Primary Navy Styling for Apply Now Button */
        .btn-navy-primary,
        a.btn-navy-primary {
            background-color: #0F2942 !important;
            border-color: #0F2942 !important;
            color: #ffffff !important;
            box-shadow: none !important;
        }

        .btn-navy-primary:hover,
        .btn-navy-primary:focus,
        a.btn-navy-primary:hover,
        a.btn-navy-primary:focus {
            background-color: #0A1E31 !important;
            border-color: #0A1E31 !important;
            color: #ffffff !important;
        }
    </style>
@endpush

@section('content')
    @php
        $isVerified = (bool) ($profile?->is_sle_fhe_verified ?? false);
    @endphp

    <h4 class="fw-bold text-dark mb-1">Sponsorship Opportunities</h4>
    <p class="text-muted small mb-3">Browse and apply for available university sponsorship programs.</p>

    @unless ($isVerified)
        <div class="alert border-0 border-start border-4 rounded-3 p-3 mb-4 d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-2"
            style="background-color: rgba(15, 41, 66, 0.05); color: #0F2942; border-color: #0F2942 !important;">
            <div class="d-flex align-items-start gap-2">
                <i class="bi bi-info-circle-fill mt-1" style="color: #0F2942;"></i>
                <span>Your SLE-FHE status is still pending verification. Open sponsorship programs will appear after FASSG
                    verifies your record.</span>
            </div>
            <a href="{{ route('student.verification.show') }}"
                class="d-block d-md-inline mt-2 mt-md-0 fw-bold text-decoration-underline p-2" style="color: #0F2942;">
                Check status <i class="bi bi-arrow-right"></i>
            </a>
        </div>
    @endunless

    @if ($isVerified)
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
                                <option value="{{ $cat }}" @selected(request('category') === $cat)>{{ $cat }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <a href="{{ route('student.programs.index') }}" class="btn btn-outline-secondary w-100">Reset</a>
                    </div>
                </div>
            </div>
        </form>
    @endif

    @if ($programs->isEmpty())
        @if (!$isVerified)
            <div class="card border-0 shadow-sm rounded-4 p-5 text-center my-4">
                <div class="mb-3"><i class="bi bi-lock-fill text-muted display-4"></i></div>
                <h5 class="fw-bold text-dark">Sponsorship Opportunities Locked</h5>
                <p class="text-muted max-w-md mx-auto">Programs will become available after FASSG verifies your Student ID
                    against the institutional masterlist.</p>
                <div>
                    <a href="{{ route('student.sle-fhe') }}" class="btn btn-navy-primary px-4">View Verification Status</a>
                </div>
            </div>
        @else
            <div class="card sf-card">
                <div class="sf-empty-state">
                    <i class="bi bi-inbox"></i>
                    <div class="fw-semibold">No open programs match your search</div>
                    <div class="small">Try clearing filters, or check back later — new programs open regularly.</div>
                </div>
            </div>
        @endif
    @else
        <div class="row g-4">
            @foreach ($programs as $program)
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card sf-card h-100">
                        <div class="card-body p-4 d-flex flex-column min-w-0">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge rounded-2 px-2.5 py-1.5 fw-medium"
                                    style="background-color: rgba(15, 41, 66, 0.08) !important; color: #0F2942 !important;">
                                    {{ $program->category->value }}
                                </span>
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
                                        class="btn btn-navy-primary btn-sm w-100 fw-semibold">
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
