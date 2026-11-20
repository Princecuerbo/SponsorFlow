@extends('layouts.app')

@section('title', 'Student Forgot Password')

@push('styles')
    @include('auth.partials.student-auth-styles')
@endpush

@section('content')
    <div class="container-fluid p-0 min-vh-100">
        <div class="row g-0 auth-login-wrap">

            @include('auth.partials.student-auth-hero')

            {{-- Right Form Column --}}
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

                    {{-- Heading & Subtitle --}}
                    <div class="mb-4 text-center text-lg-start">
                        <h3 class="fw-bold mb-1" style="color: #0f172a; font-size: 1.5rem;">Verify Your Student Account</h3>
                        <p class="text-secondary small mb-0" style="font-size: 0.8125rem; line-height: 1.45;">Enter your registered student email address and birthdate to continue with account recovery.</p>
                    </div>

                    {{-- Single recovery-level alert; field-format errors stay below their inputs. --}}
                    @if ($errors->studentRecovery->any())
                        <div class="sl-auth-alert-danger d-flex align-items-start gap-2 mb-3"
                            role="alert" aria-live="assertive">
                            <i class="bi bi-exclamation-circle flex-shrink-0 mt-1" aria-hidden="true"></i>
                            <span>{{ $errors->studentRecovery->first() }}</span>
                        </div>
                    @endif

                    <form id="student-verify-form" method="POST" action="{{ route('student.password.verify') }}" novalidate>
                        @csrf

                        {{-- Email Address --}}
                        <div class="mb-3">
                            <label for="email" class="form-label small fw-semibold mb-1" style="font-size: 0.8rem; color: #475569;">Email Address</label>
                            <div class="input-group sl-auth-input-group @error('email') is-invalid @enderror">
                                <span class="input-group-text"><i class="bi bi-envelope" aria-hidden="true"></i></span>
                                <input type="email" id="email" name="email"
                                    class="form-control" value="{{ old('email') }}"
                                    placeholder="Enter your registered student email" required autofocus autocomplete="email"
                                    aria-describedby="email-error" @error('email') aria-invalid="true" @enderror
                                    style="font-size: 0.875rem;">
                            </div>
                            @error('email')
                                <div id="email-error" class="sl-auth-field-error form-text text-danger mt-1 d-flex align-items-start gap-1"
                                    role="alert" aria-live="polite">
                                    <i class="bi bi-exclamation-circle flex-shrink-0 mt-0.5" aria-hidden="true"></i>
                                    <span>{{ $message }}</span>
                                </div>
                            @enderror
                        </div>

                        {{-- Birthdate --}}
                        <div class="mb-4">
                            <label for="birthdate" class="form-label small fw-semibold mb-1" style="font-size: 0.8rem; color: #475569;">Birthdate</label>
                            <div class="input-group sl-auth-input-group @error('birthdate') is-invalid @enderror">
                                <span class="input-group-text"><i class="bi bi-calendar3" aria-hidden="true"></i></span>
                                <input type="date" id="birthdate" name="birthdate"
                                    class="form-control" value="{{ old('birthdate') }}"
                                    required autocomplete="bday" aria-describedby="birthdate-error" @error('birthdate') aria-invalid="true" @enderror
                                    style="font-size: 0.875rem;">
                            </div>
                            @error('birthdate')
                                <div id="birthdate-error" class="sl-auth-field-error form-text text-danger mt-1 d-flex align-items-start gap-1"
                                    role="alert" aria-live="polite">
                                    <i class="bi bi-exclamation-circle flex-shrink-0 mt-0.5" aria-hidden="true"></i>
                                    <span>{{ $message }}</span>
                                </div>
                            @enderror
                        </div>

                        {{-- Verify Account --}}
                        <button type="submit" id="verify-account-btn" class="btn w-100 fw-bold shadow-sm sl-auth-submit-btn">
                            <i class="bi bi-person-check" aria-hidden="true"></i>
                            <span class="sl-auth-btn-label">Verify Account</span>
                        </button>

                        {{-- Back to Student Sign In --}}
                        <div class="text-center mt-3">
                            <a href="{{ route('login') }}" class="btn w-100 fw-bold shadow-none sl-create-account-btn">
                                Back to Student Sign In
                            </a>
                        </div>
                    </form>

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

@push('scripts')
    <script>
        (function () {
            const form = document.getElementById('student-verify-form');
            const button = document.getElementById('verify-account-btn');
            if (!form || !button) return;

            form.addEventListener('submit', function () {
                if (button.disabled) return;
                button.disabled = true;
                button.setAttribute('aria-busy', 'true');
                const label = button.querySelector('.sl-auth-btn-label');
                if (label) label.textContent = 'Verifying...';
            });
        })();
    </script>
@endpush
