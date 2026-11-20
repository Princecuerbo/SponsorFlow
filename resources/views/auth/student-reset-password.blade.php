@extends('layouts.app')

@section('title', 'Create New Password')

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
                        <h3 class="fw-bold mb-1" style="color: #0f172a; font-size: 1.5rem;">Create New Password</h3>
                        <p class="text-secondary small mb-0" style="font-size: 0.8125rem; line-height: 1.45;">Enter and confirm your new password to complete account recovery.</p>
                    </div>

                    <form id="student-reset-form" method="POST" action="{{ route('student.password.reset.store') }}" novalidate>
                        @csrf

                        {{-- New Password --}}
                        <div class="mb-3">
                            <label for="password" class="form-label small fw-semibold mb-1" style="font-size: 0.8rem; color: #475569;">New Password</label>
                            <div class="input-group sl-auth-input-group @error('password') is-invalid @enderror">
                                <span class="input-group-text"><i class="bi bi-lock" aria-hidden="true"></i></span>
                                <input type="password" id="password" name="password"
                                    class="form-control" placeholder="Enter your new password"
                                    required autocomplete="new-password"
                                    aria-describedby="password-help @error('password') password-error @enderror"
                                    @error('password') aria-invalid="true" @enderror
                                    style="font-size: 0.875rem;">
                                <button type="button"
                                    onclick="togglePasswordVisibility('password', this)"
                                    class="sl-auth-toggle-btn"
                                    aria-label="Show password"
                                    aria-controls="password"
                                    aria-pressed="false"
                                    title="Show password">
                                    <i class="bi bi-eye" style="pointer-events: none;" aria-hidden="true"></i>
                                </button>
                            </div>
                            <div id="password-help" class="form-text mt-1 d-flex align-items-start gap-1 sl-auth-policy"
                                style="font-size: 0.73rem; line-height: 1.4;">
                                <i class="bi bi-info-circle flex-shrink-0 mt-0.5" style="font-size: 0.75rem;" aria-hidden="true"></i>
                                <span>Password must contain at least 8 characters, including uppercase and lowercase letters, numbers, and symbols.</span>
                            </div>
                            @error('password')
                                <div id="password-error" class="sl-auth-field-error form-text text-danger mt-1 d-flex align-items-start gap-1"
                                    role="alert" aria-live="polite">
                                    <i class="bi bi-exclamation-circle flex-shrink-0 mt-0.5" aria-hidden="true"></i>
                                    <span>{{ $message }}</span>
                                </div>
                            @enderror
                        </div>

                        {{-- Confirm New Password --}}
                        <div class="mb-4">
                            <label for="password_confirmation" class="form-label small fw-semibold mb-1" style="font-size: 0.8rem; color: #475569;">Confirm New Password</label>
                            <div class="input-group sl-auth-input-group @error('password_confirmation') is-invalid @enderror">
                                <span class="input-group-text"><i class="bi bi-lock" aria-hidden="true"></i></span>
                                <input type="password" id="password_confirmation" name="password_confirmation"
                                    class="form-control" placeholder="Re-enter your new password"
                                    required autocomplete="new-password"
                                    aria-describedby="@error('password_confirmation') password-confirmation-error @enderror"
                                    @error('password_confirmation') aria-invalid="true" @enderror
                                    style="font-size: 0.875rem;">
                                <button type="button"
                                    onclick="togglePasswordVisibility('password_confirmation', this)"
                                    class="sl-auth-toggle-btn"
                                    aria-label="Show password"
                                    aria-controls="password_confirmation"
                                    aria-pressed="false"
                                    title="Show password">
                                    <i class="bi bi-eye" style="pointer-events: none;" aria-hidden="true"></i>
                                </button>
                            </div>
                            @error('password_confirmation')
                                <div id="password-confirmation-error" class="sl-auth-field-error form-text text-danger mt-1 d-flex align-items-start gap-1"
                                    role="alert" aria-live="polite">
                                    <i class="bi bi-exclamation-circle flex-shrink-0 mt-0.5" aria-hidden="true"></i>
                                    <span>{{ $message }}</span>
                                </div>
                            @enderror
                        </div>

                        {{-- Reset Password --}}
                        <button type="submit" id="reset-password-btn" class="btn w-100 fw-bold shadow-sm sl-auth-submit-btn">
                            <i class="bi bi-key" aria-hidden="true"></i>
                            <span class="sl-auth-btn-label">Reset Password</span>
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
        function togglePasswordVisibility(inputId, btn) {
            const input = document.getElementById(inputId);
            if (!input) return;

            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';

            const icon = btn.querySelector('i');
            if (icon) {
                icon.classList.toggle('bi-eye', !isPassword);
                icon.classList.toggle('bi-eye-slash', isPassword);
            }

            const label = isPassword ? 'Hide password' : 'Show password';
            btn.setAttribute('aria-label', label);
            btn.setAttribute('title', label);
            btn.setAttribute('aria-pressed', isPassword ? 'true' : 'false');
        }
        window.togglePasswordVisibility = togglePasswordVisibility;

        (function () {
            const form = document.getElementById('student-reset-form');
            const button = document.getElementById('reset-password-btn');
            if (!form || !button) return;

            form.addEventListener('submit', function () {
                if (button.disabled) return;
                button.disabled = true;
                button.setAttribute('aria-busy', 'true');
                const label = button.querySelector('.sl-auth-btn-label');
                if (label) label.textContent = 'Resetting...';
            });
        })();
    </script>
@endpush
