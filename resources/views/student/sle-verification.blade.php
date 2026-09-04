@extends('layouts.app')

@section('title', 'SLE-FHE Verification')

@section('content')
    @php
        $isRural = (bool) old('is_rural', $profile?->is_rural);
        $isVerified = (bool) ($profile?->is_sle_fhe_verified ?? false);
    @endphp

    <div class="container-fluid px-3 px-md-4 py-4 mb-5" style="background-color: #f8fafc; min-height: calc(100vh - 70px);">

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
            <div>
                <h3 class="fw-bold mb-1" style="color: #0f172a; font-size: 1.6rem;">SLE-FHE Student Verification</h3>
                <p class="text-secondary small mb-0" style="font-size: 0.875rem;">
                    Submit and manage your verification records for Davao Oriental State University sponsorship eligibility.
                </p>
            </div>
        </div>

        @if (session('status'))
            <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4" role="alert"
                style="font-size: 0.875rem;">
                <i class="bi bi-check-circle me-1"></i> {{ session('status') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4" role="alert"
                style="font-size: 0.875rem;">
                <i class="bi bi-exclamation-triangle me-1"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if ($profile?->is_sle_fhe_verified)
            <div class="alert alert-success-subtle border-0 border-start border-4 border-success rounded-3 p-3 mb-4">
                <div
                    class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-2 mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill fs-5 text-success"></i>
                        <h6 class="fw-bold mb-0 text-success-emphasis">Verification Status: Verified</h6>
                    </div>
                    <span class="badge bg-success text-white rounded-pill px-3 py-2">VERIFIED</span>
                </div>
                <p class="small text-secondary mb-0 ms-md-4">
                    Your SLE-FHE status has been verified. You are eligible to apply for open sponsorship programs.
                </p>
            </div>
        @else
            <div class="alert alert-primary-subtle border-0 border-start border-4 border-primary rounded-3 p-3 mb-4">
                <div
                    class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-2 mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-clock-history fs-5 text-primary"></i>
                        <h6 class="fw-bold mb-0 text-primary-emphasis">Verification Status: Pending Review</h6>
                    </div>
                    <span class="badge bg-primary text-white rounded-pill px-3 py-2">IN PROGRESS</span>
                </div>
                <p class="small text-secondary mb-0 ms-md-4">
                    Your institutional verification records are currently being evaluated by the FASSG Admin team. Your
                    Student ID is currently being cross-checked against the FASSG institutional masterlist.
                </p>
            </div>
        @endif

        <div class="row g-4 align-items-stretch">

            <div class="col-12 col-lg-5">
                <div class="card h-100 shadow-sm border-0 rounded-3 bg-white">
                    <div class="card-header bg-white border-bottom-0 pt-3 pb-0">
                        <h6 class="fw-bold mb-0 text-slate-800 d-flex align-items-center gap-2">
                            <i class="fa-solid fa-id-card text-primary"></i> Academic Profile Details
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3 mb-3">
                            <div class="col-12 pb-2 border-bottom">
                                <span class="text-secondary extra-small text-uppercase d-block mb-1"
                                    style="font-size: 0.7rem; letter-spacing: 0.05em;">Full Name</span>
                                <span class="fw-bold text-dark"
                                    style="font-size: 0.925rem;">{{ auth()->user()->name }}</span>
                            </div>

                            <div class="col-6 pb-2 border-bottom">
                                <span class="text-secondary extra-small text-uppercase d-block mb-1"
                                    style="font-size: 0.7rem; letter-spacing: 0.05em;">Student ID</span>
                                <span class="fw-semibold text-dark font-monospace"
                                    style="font-size: 0.875rem;">{{ $profile?->student_id_number ?? '—' }}</span>
                            </div>

                            <div class="col-6 pb-2 border-bottom">
                                <span class="text-secondary extra-small text-uppercase d-block mb-1"
                                    style="font-size: 0.7rem; letter-spacing: 0.05em;">Year Level</span>
                                <span class="fw-semibold text-dark"
                                    style="font-size: 0.875rem;">{{ $profile?->year_level ? $profile->year_level . ' Year' : '—' }}</span>
                            </div>

                            <div class="col-6 pb-2 border-bottom">
                                <span class="text-secondary extra-small text-uppercase d-block mb-1"
                                    style="font-size: 0.7rem; letter-spacing: 0.05em;">Course</span>
                                <span class="fw-semibold text-dark"
                                    style="font-size: 0.875rem;">{{ $profile?->course ?? '—' }}</span>
                            </div>

                            <div class="col-6 pb-2 border-bottom">
                                <span class="text-secondary extra-small text-uppercase d-block mb-1"
                                    style="font-size: 0.7rem; letter-spacing: 0.05em;">Residency</span>
                                <span id="sle-residency-badge" class="badge {{ $isRural ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $isRural ? 'Rural' : 'Urban' }}
                                </span>
                            </div>

                            <div class="col-12 pt-1">
                                <span class="text-secondary extra-small text-uppercase d-block mb-1"
                                    style="font-size: 0.7rem; letter-spacing: 0.05em;">SLE-FHE Status</span>
                                @if ($profile?->is_sle_fhe_verified)
                                    <span
                                        class="badge bg-success-subtle text-success-emphasis rounded-2 fw-semibold px-2.5 py-1"
                                        style="font-size: 0.75rem;">
                                        <i class="bi bi-patch-check-fill me-1"></i> Verified
                                    </span>
                                @else
                                    <span
                                        class="badge bg-warning-subtle text-warning-emphasis rounded-2 fw-semibold px-2.5 py-1"
                                        style="font-size: 0.75rem;">
                                        <i class="bi bi-hourglass-split me-1"></i> Pending
                                    </span>
                                @endif
                            </div>
                        </div>

                        <hr class="my-3">

                        <div class="row g-3">
                            <div class="col-12 pb-2 border-bottom">
                                <span class="text-secondary extra-small text-uppercase d-block mb-1"
                                    style="font-size: 0.7rem; letter-spacing: 0.05em;">Birthdate</span>
                                <span class="fw-semibold text-dark" style="font-size: 0.875rem;">
                                    {{ $profile?->birthdate ? $profile->birthdate->format('F j, Y') : '—' }}
                                </span>
                            </div>
                            <div class="col-12 pb-2 border-bottom">
                                <span class="text-secondary extra-small text-uppercase d-block mb-1"
                                    style="font-size: 0.7rem; letter-spacing: 0.05em;">Address</span>
                                <span class="fw-semibold text-dark" style="font-size: 0.875rem;">
                                    {{ $profile?->address ?? '—' }}
                                </span>
                            </div>
                            <div class="col-12">
                                <span class="text-secondary extra-small text-uppercase d-block mb-1"
                                    style="font-size: 0.7rem; letter-spacing: 0.05em;">Barangay</span>
                                <span class="fw-semibold text-dark" style="font-size: 0.875rem;">
                                    {{ $profile?->barangay ?? '—' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-7">
                <div class="card h-100 shadow-sm border-0 rounded-3 bg-white">
                    <div class="card-header bg-white border-bottom pt-3.5 px-4 pb-3">
                        <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2" style="font-size: 0.95rem;">
                            <i class="bi bi-shield-check text-primary"></i> Institutional Eligibility Status
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="d-flex align-items-start gap-3 mb-4">
                            <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                                style="width: 44px; height: 44px; background-color: {{ $profile?->is_sle_fhe_verified ? '#dcfce7' : '#fef3c7' }}; color: {{ $profile?->is_sle_fhe_verified ? '#16a34a' : '#d97706' }};">
                                <i
                                    class="bi {{ $profile?->is_sle_fhe_verified ? 'bi-check-circle-fill' : 'bi-hourglass-split' }} fs-5"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold text-dark mb-1">Masterlist Verification</h6>
                                @if ($profile?->is_sle_fhe_verified)
                                    <span class="badge bg-success mb-2">Verified</span>
                                    <p class="text-secondary small mb-0">Your Student ID matches the FASSG masterlist.
                                        Sponsorship opportunities are available.</p>
                                @else
                                    <span class="badge bg-warning text-dark mb-2">Pending Review</span>
                                    <p class="text-secondary small mb-0">Your Student ID is awaiting verification against
                                        the FASSG masterlist.</p>
                                @endif
                            </div>
                        </div>
                        <a @if ($isVerified) href="{{ route('student.programs.index') }}" @endif
                            class="btn {{ $isVerified ? 'btn-primary' : 'btn-secondary disabled' }} w-100 fw-semibold"
                            @if (!$isVerified) aria-disabled="true" tabindex="-1" @endif
                            style="background-color: #0f294a; border: none; border-radius: 8px;">
                            <i class="bi bi-search me-1"></i> Browse Sponsorship Opportunities
                        </a>

                        <div class="mt-4 pt-3 border-top">
                            <h6 class="fw-bold text-dark mb-3" style="font-size: 0.9rem;">Verification Guidelines</h6>
                            <div class="d-flex flex-column gap-3">
                                <div class="d-flex align-items-start gap-2">
                                    <i class="bi bi-check-circle-fill text-primary mt-1"></i>
                                    <div>
                                        <div class="small fw-semibold text-dark">FASSG Masterlist Matching</div>
                                        <p class="small text-secondary mb-0">System automatically verifies your Student ID
                                            against institutional records.</p>
                                    </div>
                                </div>
                                <div class="d-flex align-items-start gap-2">
                                    <i class="bi bi-check-circle-fill text-primary mt-1"></i>
                                    <div>
                                        <div class="small fw-semibold text-dark">Profile Updates</div>
                                        <p class="small text-secondary mb-0">Ensure your birthdate, address, and barangay
                                            info match your official university profile.</p>
                                    </div>
                                </div>
                                <div class="d-flex align-items-start gap-2">
                                    <i class="bi bi-check-circle-fill text-primary mt-1"></i>
                                    <div>
                                        <div class="small fw-semibold text-dark">Next Steps</div>
                                        <p class="small text-secondary mb-0">Once verified, open group, individual, and
                                            employee-based sponsorships will become active in your portal.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection
