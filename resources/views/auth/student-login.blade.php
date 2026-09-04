@extends('layouts.app')

@section('title', 'Student Login')

@push('styles')
    <style>
        .sf-content { padding: 0 !important; }
        .auth-login-wrap { min-height: 100vh; }
        .form-control.bg-light:focus {
            background-color: #fff !important;
            border-color: #93c5fd;
            box-shadow: 0 0 0 0.2rem rgba(15, 41, 74, 0.12);
        }
        
        /* Privacy Modal Styles matching image_8d9864.png */
        .privacy-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(10, 20, 38, 0.82);
            backdrop-filter: blur(6px);
            z-index: 99999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .privacy-modal-card {
            width: 100%;
            max-width: 640px;
            background: #ffffff;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
            border: 1px solid #cbd5e1;
        }
        .privacy-card-header {
            background: #002b66;
            padding: 1.5rem 1.75rem;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }
        .privacy-header-icon {
            width: 48px;
            height: 48px;
            background: #f59e0b;
            color: #002b66;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
        }
        .privacy-section-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 1.25rem;
            margin-bottom: 1rem;
        }
        .privacy-section-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            flex-shrink: 0;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid p-0 min-vh-100">
        <div class="row g-0 auth-login-wrap">

            <!-- Left Hero Brand Column -->
            <div class="col-lg-6 d-none d-lg-flex flex-column justify-content-between text-white p-5 position-relative overflow-hidden" style="background-color: #0f294a;">
                <div class="position-absolute rounded-circle" style="width: 450px; height: 450px; background: rgba(255, 255, 255, 0.03); top: -120px; right: -120px; pointer-events: none;"></div>
                <div></div>
                <div class="z-1 text-center mx-auto px-4 py-3" style="max-width: 480px;">
                    <div class="d-inline-flex align-items-center gap-2 px-3 py-1.5 rounded-pill mb-4" style="background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.15); backdrop-filter: blur(4px);">
                        <div class="rounded-circle bg-white d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; flex-shrink: 0;">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" width="16" height="16" fill="#0f294a">
                                <path d="M312 96c0-13.3-10.7-24-24-24s-24 10.7-24 24l0 8c-30.9 0-56 25.1-56 56s25.1 56 56 56l32 0c13.3 0 24 10.7 24 24s-10.7 24-24 24l-48 0c-13.3 0-24-10.7-24-24c0-13.3-10.7-24-24-24s-24 10.7-24 24c0 30.9 25.1 56 56 56l0 8c0 13.3 10.7 24 24 24s24-10.7 24-24l0-8c30.9 0 56-25.1 56-56s-25.1-56-56-56l-32 0c-13.3 0-24-10.7-24-24s10.7-24 24-24l48 0c13.3 0 24 10.7 24 24c0 13.3 10.7 24 24 24s24-10.7 24-24c0-30.9-25.1-56-56-56l0-8zM0 384c0-35.3 28.7-64 64-64l119.7 0c15.6 0 30.3 5.7 41.7 16l103.8 93.4c22.1 19.9 51.1 30.6 80.8 30.6l102 0c35.3 0 64-28.7 64-64s-28.7-64-64-64l-96 0c-13.3 0-24-10.7-24-24s10.7-24 24-24l96 0c61.9 0 112 50.1 112 112s-50.1 112-112 112l-102 0c-42.5 0-83.9-15.3-115.5-43.7L186.3 368 64 368c-8.8 0-16 7.2-16 16s7.2 16 16 16l112 0c13.3 0 24 10.7 24 24s-10.7 24-24 24L64 448c-35.3 0-64-28.7-64-64z"/>
                            </svg>
                        </div>
                        <span class="fw-semibold text-white tracking-wide" style="font-size: 0.875rem;">SponsorFlow</span>
                    </div>
                    <h1 class="display-6 fw-bold mb-2 text-white" style="font-size: 2.25rem; line-height: 1.25;">Welcome to <br><span style="color: #93c5fd;">DOrSU SponsorFlow</span></h1>
                    <p class="text-white-50 small mx-auto mb-0" style="max-width: 400px; line-height: 1.6; font-size: 0.875rem;">Connecting SLE-FHE Students with Financial Assistance, Grants, and Sponsorship Opportunities.</p>
                </div>
                <div></div>
            </div>

            <!-- Right Form Column -->
            <div class="col-lg-6 d-flex align-items-center justify-content-center p-4 p-md-5 bg-white">
                <div class="w-100" style="max-width: 400px;">
                    <div class="mb-4 text-center text-lg-start">
                        <h3 class="fw-bold mb-1" style="color: #0f172a; font-size: 1.5rem;">Student Login</h3>
                        <p class="text-secondary small" style="font-size: 0.85rem;">Sign in to access your sponsorship portal and applications.</p>
                    </div>

                    <!-- AJAX Error Alert Container (Matching image_29.png) -->
                    <div id="login-error-alert" class="alert alert-danger p-2 small mb-3 d-none">
                        These credentials do not match our records.
                    </div>

                    @if (session('status'))
                        <div class="alert alert-success p-2 small mb-3 border-0 shadow-sm" style="background-color: #d1e7dd; color: #0f5132;">
                            <i class="bi bi-check-circle-fill me-1"></i> {{ session('status') }}
                        </div>
                    @endif

                    <!-- Login Form -->
                    <form id="student-login-form" onsubmit="attemptLogin(event)">
                        @csrf
                        <div class="mb-3">
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-envelope"></i></span>
                                <input type="email" id="email" name="email" class="form-control bg-light border-start-0" value="{{ old('email') }}" placeholder="Email Address" required autofocus autocomplete="email" style="font-size: 0.875rem;">
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock"></i></span>
                                <input type="password" id="password" name="password" class="form-control bg-light border-start-0 border-end-0" placeholder="Password" required autocomplete="current-password" style="font-size: 0.875rem;">
                                <span class="input-group-text bg-light border-start-0 text-muted" style="cursor: pointer;" id="password-toggle"><i class="bi bi-eye"></i></span>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="remember" id="remember">
                                <label class="form-check-label small text-secondary" for="remember" style="font-size: 0.8rem;">Remember Me</label>
                            </div>
                            <a href="#" class="small text-decoration-none fw-semibold" style="color: #0f294a; font-size: 0.8rem;">Forgot Password?</a>
                        </div>
                        <!-- AJAX Trigger Button -->
                        <button type="submit" id="sign-in-btn" class="btn btn-primary w-100 py-2 fw-bold shadow-sm" style="background-color: #0f294a; border: none; border-radius: 8px;">
                            Sign In
                        </button>
                        <div class="text-center mt-4 pt-3">
                            <p class="small text-secondary mb-2" style="font-size: 0.8rem;">Don't have a student account?</p>
                            <a href="{{ route('register') }}" class="btn btn-outline-secondary w-100 py-2 fw-semibold text-slate-700 border-slate-300 rounded-3 shadow-none" style="font-size: 0.85rem;">Create Account</a>
                        </div>
                        <!-- Hidden input for token -->
                        <input type="hidden" name="pending_token" id="pending_token_input">
                    </form>

                    <div class="text-center mt-5 pt-3 text-secondary" style="font-size: 0.72rem;">&copy; 2026 Davao Oriental State University. All rights reserved.<br><a href="#" class="text-secondary text-decoration-none">Privacy Policy</a> &bull; <a href="#" class="text-secondary text-decoration-none">Terms of Service</a> &bull; <a href="#" class="text-secondary text-decoration-none">Contact Support</a></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Privacy Modal Overlay (Hidden by default) -->
    <div id="privacy-consent-modal" class="privacy-modal-overlay d-none">
        <div class="privacy-modal-card">
            <div class="privacy-card-header">
                <div class="privacy-header-icon shadow-sm"><i class="bi bi-shield-lock-fill"></i></div>
                <div>
                    <div class="text-uppercase fw-bold text-warning extra-small" style="font-size: 0.68rem; letter-spacing: 0.08em;">DAVAO ORIENTAL STATE UNIVERSITY</div>
                    <h5 class="fw-bold mb-0 text-white" style="font-size: 1.2rem;">Student Portal – Data Privacy Consent</h5>
                </div>
            </div>
            <div class="p-4 bg-white" style="max-height: 65vh; overflow-y: auto;">
                <p class="text-secondary small mb-4" style="font-size: 0.85rem; line-height: 1.5;">Welcome to the Davao Oriental State University (DOrSU) Student Portal. Before accessing your personalized dashboard, please review and consent to the following data privacy terms:</p>
                <div class="privacy-section-box">
                    <div class="d-flex gap-3">
                        <div class="privacy-section-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-file-earmark-text"></i></div>
                        <div><h6 class="fw-bold text-dark mb-2" style="font-size: 0.925rem;">Purpose of Data Collection</h6><ul class="list-unstyled mb-0 small text-secondary d-flex flex-column gap-1.5" style="font-size: 0.825rem;"><li class="d-flex align-items-center gap-2"><i class="bi bi-check-circle-fill text-warning"></i> Manage academic records</li><li class="d-flex align-items-center gap-2"><i class="bi bi-check-circle-fill text-warning"></i> Provide essential student services</li><li class="d-flex align-items-center gap-2"><i class="bi bi-check-circle-fill text-warning"></i> Communicate important updates and announcements</li></ul></div>
                    </div>
                </div>
                <div class="privacy-section-box">
                    <div class="d-flex gap-3">
                        <div class="privacy-section-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-shield-check"></i></div>
                        <div><h6 class="fw-bold text-dark mb-1" style="font-size: 0.925rem;">Data Privacy Commitment</h6><p class="mb-0 text-secondary small" style="font-size: 0.825rem; line-height: 1.5;">DOrSU protects your personal information and processes it in accordance with the Data Privacy Act of 2012 (Republic Act No. 10173). Your information will be used only for legitimate university and student-service purposes.</p></div>
                    </div>
                </div>
                <div class="privacy-section-box mb-0">
                    <div class="d-flex gap-3">
                        <div class="privacy-section-icon bg-success bg-opacity-10 text-success"><i class="bi bi-hand-thumbs-up"></i></div>
                        <div><h6 class="fw-bold text-dark mb-2" style="font-size: 0.925rem;">Under the Data Privacy Act, you have the right to:</h6><ul class="list-unstyled mb-0 small text-secondary d-flex flex-column gap-1.5" style="font-size: 0.825rem;"><li class="d-flex align-items-center gap-2"><i class="bi bi-check-circle-fill text-success"></i> Access personal data</li><li class="d-flex align-items-center gap-2"><i class="bi bi-check-circle-fill text-success"></i> Correct inaccuracies</li><li class="d-flex align-items-center gap-2"><i class="bi bi-check-circle-fill text-success"></i> Withdraw consent subject to legal limitations</li></ul></div>
                    </div>
                </div>
            </div>
            <div class="px-4 py-3 bg-light border-top d-flex align-items-center justify-content-between">
                <button type="button" class="btn btn-outline-secondary btn-sm px-3 fw-semibold" onclick="closePrivacyModal()"><i class="bi bi-x-lg me-1"></i> Cancel</button>
                <div class="form-check m-0"><input class="form-check-input" type="checkbox" id="privacy-agree-check" onchange="toggleContinueBtn()" style="cursor: pointer;"><label class="form-check-label small fw-semibold text-dark" for="privacy-agree-check" style="cursor: pointer; font-size: 0.825rem;">I agree to the Data Privacy Terms</label></div>
                <button type="button" id="privacy-continue-btn" class="btn btn-sm px-4 fw-bold text-white" disabled onclick="submitConsentFinal()" style="background-color: #0f294a; border-radius: 8px;"><i class="bi bi-check2 me-1"></i> Continue</button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const toggle = document.getElementById('password-toggle');
            const input = document.getElementById('password');
            if (toggle && input) {
                toggle.addEventListener('click', function () {
                    const isPassword = input.type === 'password';
                    input.type = isPassword ? 'text' : 'password';
                    const icon = toggle.querySelector('i');
                    if (icon) {
                        icon.classList.toggle('bi-eye', !isPassword);
                        icon.classList.toggle('bi-eye-slash', isPassword);
                    }
                });
            }
        });

        // 1. Initial Login Attempt (Verifies credentials via AJAX)
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
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ email, password, remember })
                });

                const data = await response.json();

                if (response.ok) {
                    // Store the session ID for the final login step
                    document.getElementById('pending_token_input').value = data.pending_token;
                    document.getElementById('privacy-consent-modal').classList.remove('d-none');
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
            document.getElementById('privacy-consent-modal').classList.add('d-none');
            document.getElementById('privacy-agree-check').checked = false;
            toggleContinueBtn();
        }

        function toggleContinueBtn() {
            const isChecked = document.getElementById('privacy-agree-check').checked;
            const btn = document.getElementById('privacy-continue-btn');
            btn.disabled = !isChecked;
            btn.style.opacity = isChecked ? '1' : '0.6';
        }

// 4. Final Login Submission (Modal Continue)
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
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ privacy_consent: 1 })
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