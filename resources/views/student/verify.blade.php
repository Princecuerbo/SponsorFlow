@extends('layouts.app')

@section('title', 'SLE-FHE Verification')
@section('eyebrow', 'Student Portal')
@section('page-title', 'SLE-FHE Verification Status')

@section('content')

    @if (auth()->user()->studentProfile?->is_sle_fhe_verified)

        <div class="alert border-0 shadow-sm rounded-3 d-flex align-items-center gap-3" style="background:#ecfdf5;">
            <div class="sf-stat-icon bg-success-subtle text-success"><i class="bi bi-patch-check-fill"></i></div>
            <div>
                <div class="fw-semibold text-success-emphasis">You're verified as an SLE-FHE student.</div>
                <div class="small text-secondary">You can now browse and apply to open sponsorship programs.</div>
            </div>
            <a href="{{ route('student.programs.index') }}" class="btn btn-sf-navy btn-sm ms-auto">
                Proceed to Open Sponsorships <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>

    @else

        <div class="alert border-0 shadow-sm rounded-3 d-flex align-items-center gap-3" style="background:#fffbeb;">
            <div class="sf-stat-icon bg-warning-subtle text-warning"><i class="bi bi-hourglass-split"></i></div>
            <div>
                <div class="fw-semibold" style="color:#92400e;">Your SLE-FHE status has not been verified yet.</div>
                <div class="small text-secondary">FASSG matches your student number against the SLE-FHE fixed list. This is usually resolved within a few working days.</div>
            </div>
        </div>

    @endif

    @if ($profile)
    <div class="row g-4 mt-1">
        <div class="col-lg-7">
            <div class="card sf-card">
                <div class="card-body p-4">
                    <h2 class="h6 sf-heading mb-3">Profile on record</h2>

                    <dl class="row mb-0">
                        <dt class="col-sm-4 text-secondary fw-normal small py-2">Student ID Number</dt>
                        <dd class="col-sm-8 sf-mono py-2 mb-0">{{ $profile->student_id_number }}</dd>

                        <dt class="col-sm-4 text-secondary fw-normal small py-2 border-top">Full Name</dt>
                        <dd class="col-sm-8 py-2 mb-0 border-top">{{ $profile->user->name }}</dd>

                        <dt class="col-sm-4 text-secondary fw-normal small py-2 border-top">Course</dt>
                        <dd class="col-sm-8 py-2 mb-0 border-top">{{ $profile->course }}</dd>

                        <dt class="col-sm-4 text-secondary fw-normal small py-2 border-top">Year Level</dt>
                        <dd class="col-sm-8 py-2 mb-0 border-top">Year {{ $profile->year_level }}</dd>

                        <dt class="col-sm-4 text-secondary fw-normal small py-2 border-top">Address</dt>
                        <dd class="col-sm-8 py-2 mb-0 border-top">{{ $profile->address ?? '—' }}</dd>

                        <dt class="col-sm-4 text-secondary fw-normal small py-2 border-top">Barangay</dt>
                        <dd class="col-sm-8 py-2 mb-0 border-top">{{ $profile->barangay ?? '—' }}</dd>

                        <dt class="col-sm-4 text-secondary fw-normal small py-2 border-top">Residency Classification</dt>
                        <dd class="col-sm-8 py-2 mb-0 border-top">
                            @if ($profile->is_rural)
                                <span class="badge bg-info-subtle text-info-emphasis"><i class="bi bi-tree me-1"></i>Rural</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary-emphasis"><i class="bi bi-building me-1"></i>Urban</span>
                            @endif
                        </dd>

                        <dt class="col-sm-4 text-secondary fw-normal small py-2 border-top">SLE-FHE Status</dt>
                        <dd class="col-sm-8 py-2 mb-0 border-top">
                            <x-status-badge :status="$profile->is_sle_fhe_verified ? 'Verified' : 'Pending'" />
                        </dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card sf-card h-100">
                <div class="card-body p-4">
                    <h2 class="h6 sf-heading mb-3"><i class="bi bi-info-circle text-secondary me-1"></i> How verification works</h2>
                    <ol class="small text-secondary ps-3 mb-0" style="line-height:1.9;">
                        <li>FASSG receives the official SLE-FHE fixed list from the sponsor or the school's beneficiary registry.</li>
                        <li>Your student ID number is cross-checked against that list (hybrid verification).</li>
                        <li>Once matched, your account is flagged <strong>Verified</strong> and program browsing unlocks automatically.</li>
                        <li>If your details look outdated, contact the FASSG office to have your student profile corrected.</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    @else
        <div class="alert alert-warning">Complete your student profile to begin SLE-FHE verification.</div>
    @endif

@endsection
