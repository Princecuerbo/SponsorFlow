@extends('layouts.app')

@section('title', 'Student Login')

@push('styles')
    <style>
        .sf-content {
            padding: 0 !important;
        }

        .auth-login-wrap {
            min-height: 100vh;
        }

        .form-control.bg-light:focus {
            background-color: #fff !important;
            border-color: #93c5fd;
            box-shadow: 0 0 0 0.2rem rgba(15, 41, 74, 0.12);
        }

        /* Custom Back to Home Button Hover */
        .btn-back-home {
            color: #64748b;
            transition: all 0.2s ease-in-out;
        }

        .btn-back-home:hover {
            color: #0f294a !important;
            transform: translateX(-3px);
        }

        /* Custom Sign In Button Hover Transition */
        .btn-custom-login {
            background-color: #0f294a;
            color: #ffffff;
            border: 1px solid #cbd5e1;
            transition: all 0.2s ease-in-out;
        }

        .btn-custom-login:hover {
            background-color: #0f294a !important;
            color: #ffffff !important;
            border-color: #0f294a !important;
        }

        /* Custom Create Account Button Hover Transition */
        .btn-custom-outline {
            background-color: #ffffff;
            color: #0f172a;
            border: 1px solid #cbd5e1;
            transition: all 0.2s ease-in-out;
        }

        .btn-custom-outline:hover {
            background-color: #0f294a !important;
            color: #ffffff !important;
            border-color: #0f294a !important;
        }

        /* ===== Privacy Modal – Modern Redesign ===== */
        .privacy-modal-overlay {
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            z-index: 99999;
        }

        .privacy-modal-card {
            pointer-events: auto;
            border-radius: 20px !important;
            overflow: hidden;
        }

        /* Gradient navy header */
        .privacy-card-header {
            background: linear-gradient(135deg, #0f294a 0%, #1e3a8a 100%);
            padding: 1.75rem 2rem;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 1.25rem;
            position: relative;
            overflow: hidden;
        }

        /* Decorative geometric circle in header */
        .privacy-card-header::before {
            content: '';
            position: absolute;
            width: 220px;
            height: 220px;
            background: rgba(255, 255, 255, 0.04);
            border-radius: 50%;
            top: -80px;
            right: -60px;
            pointer-events: none;
        }

        .privacy-card-header::after {
            content: '';
            position: absolute;
            width: 120px;
            height: 120px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 50%;
            bottom: -40px;
            right: 80px;
            pointer-events: none;
        }

        /* Glassmorphism shield icon */
        .privacy-header-icon {
            width: 54px;
            height: 54px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.25);
            color: #ffffff;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            position: relative;
            z-index: 1;
        }

        .privacy-header-text {
            position: relative;
            z-index: 1;
        }

        /* Modal body */
        .privacy-modal-body {
            background-color: #f8fafc;
            max-height: 68vh;
            overflow-y: auto;
            padding: 1.5rem 2rem;
        }

        /* Left-bordered accent cards */
        .privacy-accent-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 1rem 1.25rem;
            border-left: 4px solid;
            box-shadow: 0 1px 4px rgba(15, 23, 42, 0.05);
        }

        .privacy-accent-card.card-primary  { border-color: #3b82f6; }
        .privacy-accent-card.card-warning  { border-color: #f59e0b; }
        .privacy-accent-card.card-success  { border-color: #22c55e; }

        /* Consent checkbox card */
        .privacy-consent-card {
            background: #ffffff;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 1rem 1.25rem;
            cursor: pointer;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .privacy-consent-card:hover {
            border-color: #93c5fd;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .privacy-consent-card.is-checked {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
            background: #eff6ff;
        }

        .privacy-consent-card input[type="checkbox"] {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
            cursor: pointer;
            accent-color: #0f294a;
        }

        /* Modal footer */
        .privacy-modal-footer {
            background: #ffffff;
            padding: 1rem 2rem;
            border-top: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 0.75rem;
        }

        /* Transitions */
        #privacy-continue-btn {
            transition: opacity 0.2s ease, background-color 0.2s ease;
        }

        @media (min-width: 576px) {
            .w-sm-auto { width: auto !important; }
        }

        @media (max-width: 575.98px) {
            .privacy-modal-body {
                max-height: 55vh;
                padding: 1rem;
            }
            .privacy-card-header {
                padding: 1rem 1.25rem;
                gap: 0.75rem;
            }
            .privacy-header-icon {
                width: 42px;
                height: 42px;
                font-size: 1.1rem;
            }
            .privacy-modal-footer {
                padding: 0.75rem 1rem;
                flex-wrap: wrap;
            }
            .privacy-modal-footer .btn {
                width: 100%;
            }
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid p-0 min-vh-100">
        <div class="row g-0 auth-login-wrap">

            <!-- Left Hero Brand Column -->
            <div class="col-lg-6 d-none d-lg-flex flex-column justify-content-between text-white p-5 position-relative overflow-hidden"
                style="background-color: #0f294a;">
                <div class="position-absolute rounded-circle"
                    style="width: 450px; height: 450px; background: rgba(255, 255, 255, 0.03); top: -120px; right: -120px; pointer-events: none;">
                </div>
                <div></div>
                <div class="z-1 text-center mx-auto px-4 py-3" style="max-width: 480px;">
                    <div class="d-inline-flex align-items-center gap-2 mb-4 px-3 py-2 rounded-3"
                        style="background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(4px); border: 1px solid rgba(255, 255, 255, 0.2);">
                        <div class="rounded-2 p-2 text-white d-flex align-items-center justify-content-center"
                            style="background-color: #0f294a;">
                            <i class="fa-solid fa-hand-holding-dollar fs-6"></i>
                        </div>
                        <span class="fw-bold text-white fs-6">SponsorFlow</span>
                    </div>
                    <h1 class="display-6 fw-bold mb-2 text-white" style="font-size: 2.25rem; line-height: 1.25;">Welcome to
                        <br><span style="color: #93c5fd;">DOrSU SponsorFlow</span>
                    </h1>
                    <p class="text-white-50 small mx-auto mb-0"
                        style="max-width: 400px; line-height: 1.6; font-size: 0.875rem;">Connecting SLE-FHE Students with
                        Financial Assistance, Grants, and Sponsorship Opportunities.</p>
                </div>
                <div></div>
            </div>

            <!-- Right Form Column -->
            <div class="col-lg-6 d-flex align-items-center justify-content-center p-4 p-md-5 bg-white position-relative">
                <div class="w-100" style="max-width: 400px;">
                    
                    <!-- Back to Home Button -->
                    <div class="mb-4">
                        <a href="{{ url('/') }}" class="d-inline-flex align-items-center gap-2 text-decoration-none btn-back-home small fw-semibold">
                            <i class="bi bi-arrow-left"></i>
                            <span>Back to Home</span>
                        </a>
                    </div>

                    <div class="d-block d-lg-none text-center mb-4">
                        <div class="d-inline-flex align-items-center gap-2 mb-2">
                            <div class="rounded-3 p-2 text-white d-flex align-items-center justify-content-center"
                                style="background-color: #0f294a;">
                                <i class="fa-solid fa-hand-holding-dollar fs-20"></i>
                            </div>
                            <span class="fs-4 fw-bold" style="color: #0f294a;">SponsorFlow</span>
                        </div>
                    </div>
                    <div class="mb-4 text-center text-lg-start">
                        <h3 class="fw-bold mb-1" style="color: #0f172a; font-size: 1.5rem;">Student Login</h3>
                        <p class="text-secondary small" style="font-size: 0.85rem;">Sign in to access your sponsorship
                            portal and applications.</p>
                    </div>

                    <!-- AJAX Error Alert Container -->
                    <div id="login-error-alert" class="alert alert-danger p-2 small mb-3 d-none">
                        These credentials do not match our records.
                    </div>

                    @if (session('status'))
                        <div class="alert alert-success p-2 small mb-3 border-0 shadow-sm"
                            style="background-color: #d1e7dd; color: #0f5132;">
                            <i class="bi bi-check-circle-fill me-1"></i> {{ session('status') }}
                        </div>
                    @endif

                    <!-- Login Form -->
                    <form id="student-login-form" onsubmit="attemptLogin(event)">
                        @csrf
                        <div class="mb-3">
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i
                                        class="bi bi-envelope"></i></span>
                                <input type="email" id="email" name="email"
                                    class="form-control bg-light border-start-0" value="{{ old('email') }}"
                                    placeholder="Email Address" required autofocus autocomplete="email"
                                    style="font-size: 0.875rem;">
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i
                                        class="bi bi-lock"></i></span>
                                <input type="password" id="password" name="password"
                                    class="form-control bg-light border-start-0 border-end-0" placeholder="Password"
                                    required autocomplete="current-password" style="font-size: 0.875rem;">
                                <button type="button"
                                    onclick="togglePasswordVisibility('password', this)"
                                    class="input-group-text bg-light border-start-0 text-gray-400"
                                    style="cursor: pointer;"
                                    aria-label="Toggle password visibility">
                                    <i class="bi bi-eye" style="pointer-events: none;"></i>
                                </button>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="remember" id="remember">
                                <label class="form-check-label small text-secondary" for="remember"
                                    style="font-size: 0.8rem;">Remember Me</label>
                            </div>
                            <a href="#" class="small text-decoration-none fw-semibold"
                                style="color: #0f294a; font-size: 0.8rem;">Forgot Password?</a>
                        </div>
                        <!-- AJAX Trigger Button with Custom Grey/Black Styling & Hover -->
                        <button type="submit" id="sign-in-btn"
                            class="btn w-100 py-2 fw-bold shadow-sm btn-custom-login rounded-3"
                            style="border-radius: 8px; font-size: 0.875rem;">
                            Sign In
                        </button>
                        <div class="text-center mt-4 pt-3">
                            <p class="small text-secondary mb-2" style="font-size: 0.8rem;">Don't have a student account?
                            </p>
                            <a href="{{ route('register') }}"
                                class="btn w-100 py-2 fw-bold text-dark rounded-3 shadow-none btn-custom-outline"
                                style="font-size: 0.85rem;">Create Account</a>
                        </div>
                        <!-- Hidden input for token -->
                        <input type="hidden" name="pending_token" id="pending_token_input">
                    </form>

                    <div class="text-center mt-5 pt-3 text-secondary" style="font-size: 0.72rem;">&copy; 2026 Davao Oriental
                        State University. All rights reserved.<br><a href="#"
                            class="text-secondary text-decoration-none">Privacy Policy</a> &bull; <a href="#"
                            class="text-secondary text-decoration-none">Terms of Service</a> &bull; <a href="#"
                            class="text-secondary text-decoration-none">Contact Support</a></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Privacy Consent Modal – Modern Redesign -->
    <div id="privacyConsentModal" class="modal fade privacy-modal-overlay d-none" tabindex="-1"
        style="background: rgba(0, 0, 0, 0.55);" aria-labelledby="privacyConsentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg mx-2 mx-sm-auto my-3 my-sm-auto">
            <div class="modal-content privacy-modal-card border-0 shadow-lg">

                {{-- ── Header: gradient navy + glassmorphism shield ── --}}
                <div class="privacy-card-header">
                    <div class="privacy-header-icon">
                        <i class="bi bi-shield-lock-fill"></i>
                    </div>
                    <div class="privacy-header-text">
                        <div class="text-uppercase fw-semibold mb-1"
                            style="font-size: 0.65rem; letter-spacing: 0.1em; color: rgba(255,255,255,0.65);">DAVAO ORIENTAL STATE UNIVERSITY &bull; DATA PRIVACY</div>
                        <h5 id="privacyConsentModalLabel" class="fw-bold mb-0 text-white" style="font-size: 1.15rem; line-height: 1.3;">
                            Student Portal &ndash; Data Privacy Consent
                        </h5>
                    </div>
                </div>

                {{-- ── Body: clean #f8fafc + left-border accent cards ── --}}
                <div class="privacy-modal-body">
                    <p class="text-secondary mb-4" style="font-size: 0.875rem; line-height: 1.6;">
                        Welcome to the DOrSU Student Portal. Before accessing your personalized dashboard,
                        please review and accept the following data privacy terms in compliance with Philippine law.
                    </p>

                    {{-- Purpose of Data Collection --}}
                    <div class="privacy-accent-card card-primary mb-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-file-earmark-text text-primary fs-5"></i>
                            <h6 class="fw-bold text-dark mb-0" style="font-size: 0.9rem;">Purpose of Data Collection</h6>
                        </div>
                        <ul class="list-unstyled mb-0 d-flex flex-column gap-1" style="font-size: 0.825rem; color: #475569;">
                            <li class="d-flex align-items-start gap-2">
                                <i class="bi bi-check-circle-fill text-primary mt-1" style="font-size: 0.75rem; flex-shrink:0;"></i>
                                Manage academic records and sponsorship eligibility
                            </li>
                            <li class="d-flex align-items-start gap-2">
                                <i class="bi bi-check-circle-fill text-primary mt-1" style="font-size: 0.75rem; flex-shrink:0;"></i>
                                Provide essential SLE-FHE student services
                            </li>
                            <li class="d-flex align-items-start gap-2">
                                <i class="bi bi-check-circle-fill text-primary mt-1" style="font-size: 0.75rem; flex-shrink:0;"></i>
                                Communicate important updates and program announcements
                            </li>
                        </ul>
                    </div>

                    {{-- Data Protection Commitment --}}
                    <div class="privacy-accent-card card-warning mb-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-shield-check text-warning fs-5"></i>
                            <h6 class="fw-bold text-dark mb-0" style="font-size: 0.9rem;">Data Protection Commitment</h6>
                        </div>
                        <p class="mb-0" style="font-size: 0.825rem; color: #475569; line-height: 1.55;">
                            DOrSU protects your personal information and processes it in accordance with the
                            <strong>Data Privacy Act of 2012 (Republic Act No. 10173)</strong>.
                            Your data is used solely for legitimate university and student-service purposes and
                            is never sold to third parties.
                        </p>
                    </div>

                    {{-- Your Rights as a Data Subject --}}
                    <div class="privacy-accent-card card-success mb-4">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-person-check-fill text-success fs-5"></i>
                            <h6 class="fw-bold text-dark mb-0" style="font-size: 0.9rem;">Your Rights as a Data Subject</h6>
                        </div>
                        <ul class="list-unstyled mb-0 d-flex flex-column gap-1" style="font-size: 0.825rem; color: #475569;">
                            <li class="d-flex align-items-start gap-2">
                                <i class="bi bi-check-circle-fill text-success mt-1" style="font-size: 0.75rem; flex-shrink:0;"></i>
                                Access and obtain a copy of your personal data
                            </li>
                            <li class="d-flex align-items-start gap-2">
                                <i class="bi bi-check-circle-fill text-success mt-1" style="font-size: 0.75rem; flex-shrink:0;"></i>
                                Correct any inaccuracies in your records
                            </li>
                            <li class="d-flex align-items-start gap-2">
                                <i class="bi bi-check-circle-fill text-success mt-1" style="font-size: 0.75rem; flex-shrink:0;"></i>
                                Withdraw consent, subject to applicable legal limitations
                            </li>
                        </ul>
                    </div>

                    {{-- Interactive Consent Checkbox Card --}}
                    <label for="privacy-agree-check" class="privacy-consent-card" id="privacy-consent-card-wrapper">
                        <input class="form-check-input" type="checkbox"
                            id="privacy-agree-check"
                            onchange="toggleContinueBtn()"
                            style="cursor: pointer;">
                        <div>
                            <div class="fw-semibold text-dark" style="font-size: 0.875rem; line-height: 1.4;">
                                I have read and agree to the data privacy terms above.
                            </div>
                            <div class="text-secondary" style="font-size: 0.78rem; margin-top: 2px;">
                                By checking this box you consent to the collection and processing of your personal data
                                as described in this notice.
                            </div>
                        </div>
                    </label>
                </div>

                {{-- ── Footer ── --}}
                <div class="privacy-modal-footer">
                    <button type="button"
                        class="btn btn-outline-danger btn-sm px-4 fw-semibold"
                        onclick="closePrivacyModal()">
                        <i class="bi bi-x-lg me-1"></i> Cancel
                    </button>
                    <button type="button" id="privacy-continue-btn"
                        class="btn btn-sm px-5 fw-bold text-white" disabled
                        onclick="submitConsentFinal()"
                        style="background-color: #0f294a; border-radius: 8px; opacity: 0.55;">
                        <i class="bi bi-check2-circle me-1"></i> Continue
                    </button>
                </div>

            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Initial Login Attempt (Verifies credentials via AJAX)
        async function attemptLogin(event) {
            event.preventDefault();
            const form = document.getElementById('student-login-form');
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const remember = document.getElementById('remember').checked;
            const alertBox = document.getElementById('login-error-alert');
            const signInBtn = document.getElementById('sign-in-btn');

            alertBox.classList.add('d-none');
            signInBtn.disabled = true;
            signInBtn.textContent = 'Verifying...';

            if (!form.checkValidity()) {
                form.reportValidity();
                signInBtn.disabled = false;
                signInBtn.textContent = 'Sign In';
                return;
            }

            try {
                const response = await fetch("{{ route('login.verify') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        email,
                        password,
                        remember
                    })
                });

                const data = await response.json();

                if (response.ok) {
                    document.getElementById('pending_token_input').value = data.pending_token;
                    const privacyModal = document.getElementById('privacyConsentModal');
                    privacyModal.classList.remove('d-none');
                    privacyModal.classList.add('show', 'd-block');
                    signInBtn.disabled = false;
                    signInBtn.textContent = 'Sign In';
                } else {
                    alertBox.textContent = data.message || 'These credentials do not match our records.';
                    alertBox.classList.remove('d-none');
                    signInBtn.disabled = false;
                    signInBtn.textContent = 'Sign In';
                }
            } catch (error) {
                console.error('Login verification error:', error);
                alertBox.textContent = 'An error occurred during verification. Please try again.';
                alertBox.classList.remove('d-none');
                signInBtn.disabled = false;
                signInBtn.textContent = 'Sign In';
            }
        }

        function closePrivacyModal() {
            const privacyModal = document.getElementById('privacyConsentModal');
            privacyModal.classList.remove('show', 'd-block');
            privacyModal.classList.add('d-none');
            document.getElementById('privacy-agree-check').checked = false;
            toggleContinueBtn();
        }

        function toggleContinueBtn() {
            const isChecked = document.getElementById('privacy-agree-check').checked;
            const btn = document.getElementById('privacy-continue-btn');
            const card = document.getElementById('privacy-consent-card-wrapper');
            btn.disabled = !isChecked;
            btn.style.opacity = isChecked ? '1' : '0.55';
            if (card) {
                card.classList.toggle('is-checked', isChecked);
            }
        }

        // Final Login Submission (Modal Continue)
        async function submitConsentFinal() {
            const checkbox = document.getElementById('privacy-agree-check');
            const alertBox = document.getElementById('login-error-alert');

            if (!checkbox.checked) {
                alert('You must agree to the Data Privacy Policy to log in.');
                return;
            }

            const continueBtn = document.getElementById('privacy-continue-btn');
            continueBtn.disabled = true;
            continueBtn.textContent = 'Logging in...';

            try {
                const response = await fetch("{{ route('login.complete') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        privacy_consent: 1
                    })
                });

                const data = await response.json();

                if (response.ok && data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    closePrivacyModal();
                    alertBox.textContent = data.message || 'Login completion failed. Please try again.';
                    alertBox.classList.remove('d-none');
                    continueBtn.disabled = false;
                    continueBtn.textContent = 'Continue';
                }
            } catch (error) {
                console.error('Login completion error:', error);
                closePrivacyModal();
                alertBox.textContent = 'An error occurred completing your login.';
                alertBox.classList.remove('d-none');
                continueBtn.disabled = false;
                continueBtn.textContent = 'Continue';
            }
        }
    </script>
@endpush