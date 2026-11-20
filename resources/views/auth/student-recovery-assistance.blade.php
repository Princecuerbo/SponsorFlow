@extends('layouts.app')

@section('title', 'Account Recovery Assistance')

@push('styles')
    @include('auth.partials.student-auth-styles')
@endpush

@section('content')
    <div class="container-fluid p-0 min-vh-100">
        <div class="row g-0 auth-login-wrap">

            @include('auth.partials.student-auth-hero')

            {{-- Right Content Column --}}
            <div class="col-lg-6 d-flex align-items-center justify-content-center p-4 p-md-5 bg-white position-relative">
                <div class="w-100 sl-form-container" style="max-width: 410px;">

                    {{-- Back to Home Button --}}
                    <div class="mb-4">
                        <a href="{{ url('/') }}" class="d-inline-flex align-items-center gap-2 text-decoration-none btn-back-home small fw-semibold">
                            <i class="bi bi-arrow-left" aria-hidden="true"></i>
                            <span>Back to Home</span>
                        </a>
                    </div>

                    {{-- Mobile branding --}}
                    <div class="d-block d-lg-none text-center mb-4">
                        <div class="d-inline-flex align-items-center gap-2 mb-2">
                            <div class="rounded-3 p-2 text-white d-flex align-items-center justify-content-center"
                                style="background-color: #0F2537;">
                                <i class="fa-solid fa-hand-holding-dollar fs-20"></i>
                            </div>
                            <span class="fs-4 fw-bold" style="color: #0F2537;">SponsorFlow</span>
                        </div>
                    </div>

                    <div class="mb-4 text-center text-lg-start">
                        <h3 class="fw-bold mb-1" style="color: #0f172a; font-size: 1.5rem;">Account Recovery Assistance</h3>
                        <p class="text-secondary small mb-0" style="font-size: 0.8125rem; line-height: 1.45;">Self-service password recovery is currently unavailable. Please contact the authorized university support personnel for assistance.</p>
                    </div>

                    <div class="sl-auth-info-panel d-flex align-items-start gap-2 mb-4" role="note">
                        <i class="bi bi-shield-lock mt-1 flex-shrink-0" aria-hidden="true"></i>
                        <span>To reset your student account password, please contact the authorized DOrSU system administrator for identity verification.</span>
                    </div>

                    <a href="{{ route('login') }}" class="btn w-100 fw-bold shadow-none sl-create-account-btn">
                        Back to Student Sign In
                    </a>

                    <div class="text-center mt-5 pt-3 text-secondary" style="font-size: 0.72rem;">&copy; {{ date('Y') }} Davao Oriental
                        State University. All rights reserved.<br><a href="#"
                            class="text-secondary text-decoration-none">Privacy Policy</a> &bull; <a href="#"
                            class="text-secondary text-decoration-none">Terms of Service</a> &bull; <a href="#"
                            class="text-secondary text-decoration-none">Contact Support</a></div>
                </div>
            </div>
        </div>
    </div>
@endsection
