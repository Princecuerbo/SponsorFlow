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

        {{-- Main Banner Notification --}}
        @if ($profile?->is_sle_fhe_verified)
            <div class="alert alert-success-subtle border-0 border-start border-4 border-success rounded-3 p-3 mb-4">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <i class="bi bi-check-circle-fill fs-5 text-success"></i>
                    <h6 class="fw-bold mb-0 text-success-emphasis">Verification Status: Verified</h6>
                </div>
                <p class="small text-secondary mb-0 ms-md-4">
                    Your SLE-FHE status has been verified. You are eligible to apply for open sponsorship programs.
                </p>
            </div>
        @else
            <div class="alert alert-primary-subtle border-0 border-start border-4 border-primary rounded-3 p-3 mb-4">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <i class="bi bi-clock-history fs-5 text-primary"></i>
                    <h6 class="fw-bold mb-0 text-primary-emphasis">Verification Status: Pending Review</h6>
                </div>
                <p class="small text-secondary mb-0 ms-md-4">
                    Your records are being evaluated by FASSG. Your Student ID is being cross-checked against the
                    institutional masterlist.
                </p>
            </div>
        @endif

        <div class="row g-4 align-items-stretch">

            {{-- Academic Profile Details Card --}}
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
                                    style="font-size: 0.875rem;">{{ $profile?->year_level ? 'Year ' . $profile->year_level : '—' }}</span>
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

            {{-- Institutional Eligibility Status Card --}}
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
                                    <p class="text-secondary small mb-0">Your profile is active and verified for the current
                                        academic term.</p>
                                @else
                                    <span class="badge bg-warning text-dark mb-2">Pending Review</span>
                                    <p class="text-secondary small mb-0">Eligibility features and program applications
                                        remain locked until approved.</p>
                                @endif
                            </div>
                        </div>

                        {{-- Action Button --}}
                        @if ($isVerified)
                            <a href="{{ route('student.programs.index') }}" class="btn btn-sf-navy w-100 fw-semibold py-2"
                                style="border-radius: 8px;">
                                <i class="bi bi-search me-1"></i> Browse Sponsorship Opportunities
                            </a>
                        @else
                            <button type="button" id="btn-pending-modal-trigger"
                                class="btn btn-outline-secondary w-100 fw-semibold py-2" data-bs-toggle="modal"
                                data-bs-target="#verificationPendingModal" style="border-radius: 8px;">
                                <i class="bi bi-lock-fill me-1"></i> Browse Sponsorship Opportunities
                            </button>
                        @endif

                        <div class="mt-4 pt-3 border-top">
                            <h6 class="fw-bold text-dark mb-3" style="font-size: 0.9rem;">Verification Guidelines</h6>
                            <div class="d-flex flex-column gap-3">
                                <div class="d-flex align-items-start gap-2">
                                    <i class="bi bi-check-circle-fill text-primary mt-1"></i>
                                    <div>
                                        <div class="small fw-semibold text-dark">Masterlist Verification</div>
                                        <p class="small text-secondary mb-0">Automatically checks your Student ID against
                                            institutional records.</p>
                                    </div>
                                </div>
                                <div class="d-flex align-items-start gap-2">
                                    <i class="bi bi-check-circle-fill text-primary mt-1"></i>
                                    <div>
                                        <div class="small fw-semibold text-dark">Profile Details</div>
                                        <p class="small text-secondary mb-0">Ensure your birthdate and address match your
                                            university profile.</p>
                                    </div>
                                </div>
                                <div class="d-flex align-items-start gap-2">
                                    <i class="bi bi-check-circle-fill text-primary mt-1"></i>
                                    <div>
                                        <div class="small fw-semibold text-dark">Next Steps</div>
                                        <p class="small text-secondary mb-0">Once verified, available sponsorship
                                            opportunities will become active in your portal.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>

    {{-- Verification Pending Modal --}}
    @if (!$isVerified)
        <div class="modal fade" id="verificationPendingModal" tabindex="-1"
            aria-labelledby="verificationPendingModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow rounded-4">
                    <div class="modal-body p-4 text-center">
                        <div class="mb-3 d-inline-flex align-items-center justify-content-center bg-warning-subtle text-warning rounded-circle"
                            style="width: 60px; height: 60px;">
                            <i class="bi bi-hourglass-split fs-2"></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-2" id="verificationPendingModalLabel">Verification Underway</h5>
                        <p class="text-secondary small mb-4">
                            Your student record is currently being cross-checked against the official institutional
                            masterlist. Sponsorship browsing will unlock automatically as soon as FASSG completes the
                            review.
                        </p>
                        <div class="d-flex flex-column gap-2">
                            <button type="button" class="btn btn-sf-navy fw-semibold w-100 py-2 rounded-3"
                                data-bs-dismiss="modal">
                                Got It, I'll Wait
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var btn = document.getElementById('btn-pending-modal-trigger');
                if (btn) {
                    var instance = bootstrap.Tooltip.getInstance(btn);
                    if (instance) {
                        instance.dispose();
                    }
                    btn.removeAttribute('title');
                    btn.removeAttribute('data-bs-original-title');
                }
            });
        </script>
    @endif
@endsection
