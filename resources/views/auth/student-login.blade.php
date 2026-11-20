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

        /* ===== Unified Input Groups & Buttons (Mobile & Desktop) ===== */
        .sl-auth-input-group {
            border: 1px solid #CBD5E1;
            border-radius: 8px;
            overflow: hidden;
            background-color: #F8FAFC;
            transition: border-color 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease;
            display: flex;
        }

        .sl-auth-input-group:focus-within {
            border-color: #0F2942 !important;
            background-color: #FFFFFF !important;
            box-shadow: 0 0 0 3px rgba(15, 41, 66, 0.12) !important;
        }

        .sl-auth-input-group .input-group-text {
            background-color: transparent !important;
            border: none !important;
            color: #64748B !important;
            width: 42px !important;
            min-width: 42px !important;
            height: 42px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-size: 0.95rem;
            padding: 0 !important;
        }

        .sl-auth-input-group .form-control {
            background-color: transparent !important;
            border: none !important;
            font-size: 0.875rem !important;
            height: 42px !important;
            color: #0F2942 !important;
            box-shadow: none !important;
            padding-left: 0 !important;
            padding-right: 0.5rem !important;
        }

        .sl-auth-input-group .form-control:focus {
            background-color: transparent !important;
            border: none !important;
            box-shadow: none !important;
            outline: none !important;
        }

        .sl-auth-input-group .form-control::placeholder {
            color: #94A3B8 !important;
            font-size: 0.875rem !important;
        }

        /* Password visibility toggle - mobile & desktop */
        .sl-auth-toggle-btn {
            background-color: transparent !important;
            border: none !important;
            color: #64748B !important;
            width: 42px !important;
            min-width: 42px !important;
            height: 42px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 0 !important;
            cursor: pointer;
            transition: color 0.15s ease;
        }

        .sl-auth-toggle-btn:hover {
            color: #0F2942 !important;
        }

        .sl-auth-toggle-btn:focus-visible {
            outline: none !important;
            box-shadow: inset 0 0 0 2px #0F2942 !important;
            border-radius: 6px;
        }

        /* Compact post-reset success notice. */
        .sl-login-status {
            background-color: #ECFDF3 !important;
            color: #166534 !important;
            border: 1px solid #BBF7D0 !important;
            border-radius: 8px !important;
            padding: 0.5rem 0.625rem !important;
            font-size: 0.8125rem !important;
            line-height: 1.45 !important;
            transition: opacity 180ms ease, transform 180ms ease;
        }

        .sl-login-status > i {
            color: #15803D !important;
            font-size: 0.95rem;
        }

        .sl-login-status.is-dismissing {
            opacity: 0;
            transform: translateY(-2px);
        }

        .sl-status-dismiss {
            background: transparent !important;
            border: 0 !important;
            color: #166534 !important;
            padding: 0 !important;
            margin-left: auto !important;
            width: 20px !important;
            min-width: 20px !important;
            height: 20px !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            border-radius: 6px !important;
            cursor: pointer;
            transition: color 180ms ease, background-color 180ms ease;
        }

        .sl-status-dismiss:hover {
            color: #14532D !important;
            background-color: rgba(21, 128, 61, 0.1) !important;
        }

        .sl-status-dismiss:focus-visible {
            outline: none !important;
            box-shadow: 0 0 0 3px rgba(21, 128, 61, 0.25) !important;
        }

        @media (prefers-reduced-motion: reduce) {
            .sl-login-status,
            .sl-status-dismiss {
                transition: none !important;
            }

            .sl-login-status.is-dismissing {
                transform: none !important;
            }
        }

        /* Primary Sign In Button */
        .sl-auth-submit-btn {
            background-color: #0F2942 !important;
            border: 1px solid #0F2942 !important;
            border-radius: 8px !important;
            height: 44px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-size: 0.875rem !important;
            font-weight: 600 !important;
            color: #FFFFFF !important;
            box-shadow: 0 2px 4px rgba(15, 41, 66, 0.15);
            transition: background-color 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease, transform 0.1s ease !important;
        }

        .sl-auth-submit-btn:hover {
            background-color: #173B57 !important;
            border-color: #173B57 !important;
            color: #FFFFFF !important;
            transform: translateY(-1px);
        }

        .sl-auth-submit-btn:active {
            transform: translateY(0);
        }

        .sl-auth-submit-btn:focus-visible {
            outline: none !important;
            border-color: #0F2942 !important;
            box-shadow: 0 0 0 3px rgba(15, 41, 66, 0.25) !important;
        }

        /* Secondary Create Account Button - Perfectly centered on all viewports */
        .sl-create-account-btn {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            text-align: center !important;
            height: 42px !important;
            padding: 0 1rem !important;
            background-color: #FFFFFF !important;
            color: #0F2942 !important;
            border: 1px solid #CBD5E1 !important;
            border-radius: 8px !important;
            font-size: 0.875rem !important;
            font-weight: 600 !important;
            line-height: 1 !important;
            text-decoration: none !important;
            transition: background-color 0.15s ease, color 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease, transform 0.1s ease !important;
        }

        .sl-create-account-btn:hover {
            background-color: #0F2942 !important;
            color: #FFFFFF !important;
            border-color: #0F2942 !important;
            transform: translateY(-1px);
        }

        .sl-create-account-btn:focus-visible {
            outline: none !important;
            border-color: #0F2942 !important;
            box-shadow: 0 0 0 3px rgba(15, 41, 66, 0.2) !important;
        }

        .sl-create-account-btn:active {
            background-color: #0A1E31 !important;
            border-color: #0A1E31 !important;
            color: #FFFFFF !important;
            transform: translateY(0);
        }

        /* ===== Desktop Scoped Constraints (992px+) ===== */
        @media screen and (min-width: 992px) {
            .sl-form-container {
                max-width: 410px !important;
            }
        }

        /* Custom Back to Home Button Hover */
        .btn-back-home {
            color: #64748b;
            transition: all 0.2s ease-in-out;
        }

        .btn-back-home:hover {
            color: #0F2537 !important;
            transform: translateX(-3px);
        }

        /* ===== Privacy Modal – Institutional UI/UX Refinement ===== */
        .privacy-modal-overlay {
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            z-index: 1055;
            background: rgba(15, 23, 42, 0.65) !important;
        }

        .privacy-modal-dialog {
            width: 100%;
            max-width: 800px;
            margin: 1.5rem auto;
            display: flex;
            align-items: center;
            min-height: calc(100% - 3rem);
        }

        @media (min-width: 992px) {
            .privacy-modal-dialog {
                max-width: 780px !important;
            }
        }

        .privacy-modal-card {
            width: 100%;
            max-height: calc(100dvh - 3rem);
            display: flex;
            flex-direction: column;
            pointer-events: auto;
            border-radius: 16px !important;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.25) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            background-color: #ffffff;
        }

        /* Institutional gradient navy header */
        .privacy-card-header {
            background: linear-gradient(135deg, #0F2537 0%, #0A1E31 100%);
            padding: 1.25rem 1.75rem;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 1rem;
            position: relative;
            overflow: hidden;
            flex-shrink: 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        /* Decorative geometric circle in header */
        .privacy-card-header::before {
            content: '';
            position: absolute;
            width: 220px;
            height: 220px;
            background: rgba(255, 255, 255, 0.04);
            border-radius: 50%;
            top: -90px;
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
            bottom: -50px;
            right: 80px;
            pointer-events: none;
        }

        /* Glassmorphism shield icon */
        .privacy-header-icon {
            width: 48px;
            height: 48px;
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #ffffff;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            flex-shrink: 0;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
            position: relative;
            z-index: 1;
        }

        .privacy-header-text {
            position: relative;
            z-index: 1;
            flex: 1;
        }

        /* Modal body with sleek custom scrollbar */
        .privacy-modal-body {
            background-color: #f8fafc;
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            padding: 1.35rem 1.75rem;
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 transparent;
        }

        .privacy-modal-body::-webkit-scrollbar {
            width: 6px;
        }

        .privacy-modal-body::-webkit-scrollbar-track {
            background: transparent;
        }

        .privacy-modal-body::-webkit-scrollbar-thumb {
            background-color: #cbd5e1;
            border-radius: 9999px;
        }

        .privacy-modal-body::-webkit-scrollbar-thumb:hover {
            background-color: #94a3b8;
        }

        /* Standardized informational accent cards */
        .privacy-accent-card {
            background: #ffffff;
            border-radius: 10px;
            padding: 0.875rem 1.125rem;
            border: 1px solid #e2e8f0;
            border-left: 4px solid;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
            margin-bottom: 0.875rem;
        }

        .privacy-accent-card:last-of-type {
            margin-bottom: 1rem;
        }

        .privacy-accent-card.card-primary {
            border-left-color: #2563eb;
        }
        .privacy-accent-card.card-primary .privacy-card-icon {
            color: #2563eb;
        }

        .privacy-accent-card.card-warning {
            border-left-color: #d97706;
        }
        .privacy-accent-card.card-warning .privacy-card-icon {
            color: #d97706;
        }

        .privacy-accent-card.card-success {
            border-left-color: #059669;
        }
        .privacy-accent-card.card-success .privacy-card-icon {
            color: #059669;
        }

        .privacy-card-title {
            font-size: 0.875rem;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 0;
        }

        .privacy-card-icon {
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .privacy-card-list {
            margin-bottom: 0;
            padding-left: 0;
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
            font-size: 0.8125rem;
            color: #475569;
        }

        .privacy-card-list li {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            line-height: 1.5;
        }

        .privacy-card-list li i {
            font-size: 0.8rem;
            margin-top: 0.2rem;
            flex-shrink: 0;
        }

        /* Consent checkbox card */
        .privacy-consent-card {
            background: #ffffff;
            border: 1.5px solid #cbd5e1;
            border-radius: 10px;
            padding: 0.875rem 1.125rem;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            user-select: none;
        }

        .privacy-consent-card:hover {
            border-color: #93c5fd;
            background-color: #f8fafc;
        }

        .privacy-consent-card.is-checked {
            border-color: #0F2537;
            background-color: #f0f7ff;
            box-shadow: 0 0 0 1px #0F2537;
        }

        .privacy-consent-checkbox {
            width: 18px;
            height: 18px;
            margin-top: 2px;
            margin-left: 0;
            flex-shrink: 0;
            cursor: pointer;
            accent-color: #0F2537;
        }

        .privacy-consent-checkbox:focus-visible {
            outline: 2px solid #0F2537 !important;
            outline-offset: 2px !important;
            box-shadow: none !important;
        }

        .privacy-consent-content {
            flex: 1;
        }

        .privacy-consent-title {
            font-size: 0.875rem;
            font-weight: 600;
            color: #0f172a;
            line-height: 1.45;
        }

        .privacy-consent-desc {
            font-size: 0.775rem;
            color: #64748b;
            line-height: 1.45;
            margin-top: 3px;
        }

        /* Modal footer */
        .privacy-modal-footer {
            background: #ffffff;
            padding: 0.875rem 1.75rem;
            border-top: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 0.75rem;
            flex-shrink: 0;
        }

        /* Cancel Button: White bg, slate text, subtle border; Red on hover */
        .privacy-btn-cancel {
            height: 42px;
            min-height: 42px;
            padding: 0 1.25rem;
            font-size: 0.875rem;
            font-weight: 600;
            border-radius: 8px;
            background-color: #ffffff !important;
            color: #475569 !important;
            border: 1px solid #cbd5e1 !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.2s ease-in-out;
            cursor: pointer;
        }

        .privacy-btn-cancel:hover {
            background-color: #dc2626 !important;
            border-color: #dc2626 !important;
            color: #ffffff !important;
            transform: translateY(-1px);
            box-shadow: 0 2px 6px rgba(220, 38, 38, 0.25);
        }

        .privacy-btn-cancel:focus-visible {
            outline: none !important;
            border-color: #dc2626 !important;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.2) !important;
        }

        .privacy-btn-cancel:active {
            transform: translateY(0);
        }

        /* Agree & Continue Button: Gray disabled, institutional navy enabled */
        .privacy-btn-continue {
            height: 42px;
            min-height: 42px;
            padding: 0 1.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.2s ease-in-out;
            border: 1px solid transparent;
        }

        .privacy-btn-continue:disabled,
        .privacy-btn-continue[disabled] {
            background-color: #e2e8f0 !important;
            border-color: #e2e8f0 !important;
            color: #94a3b8 !important;
            cursor: not-allowed !important;
            opacity: 1 !important;
            box-shadow: none !important;
            transform: none !important;
        }

        .privacy-btn-continue:not(:disabled) {
            background-color: #0F2537 !important;
            border-color: #0F2537 !important;
            color: #ffffff !important;
            cursor: pointer !important;
            box-shadow: 0 2px 4px rgba(15, 37, 55, 0.15);
        }

        .privacy-btn-continue:not(:disabled):hover {
            background-color: #0A1E31 !important;
            border-color: #0A1E31 !important;
            color: #ffffff !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(15, 37, 55, 0.25);
        }

        .privacy-btn-continue:not(:disabled):focus-visible {
            outline: none !important;
            box-shadow: 0 0 0 3px rgba(15, 37, 55, 0.25) !important;
        }

        .privacy-btn-continue:not(:disabled):active {
            transform: translateY(0);
        }

        @media (min-width: 576px) {
            .w-sm-auto { width: auto !important; }
        }

        @media (max-width: 575.98px) {
            .privacy-modal-dialog {
                margin: 0.75rem auto;
                min-height: calc(100% - 1.5rem);
                max-width: calc(100% - 1.5rem);
            }
            .privacy-modal-card {
                max-height: calc(100dvh - 1.5rem);
                border-radius: 14px !important;
            }
            .privacy-card-header {
                padding: 1rem 1.25rem;
                gap: 0.75rem;
            }
            .privacy-header-icon {
                width: 40px;
                height: 40px;
                font-size: 1.15rem;
                border-radius: 10px;
            }
            .privacy-modal-body {
                padding: 1rem 1.125rem;
            }
            .privacy-modal-footer {
                padding: 0.75rem 1rem;
                flex-direction: column-reverse;
                gap: 0.5rem;
            }
            .privacy-modal-footer .privacy-btn-cancel,
            .privacy-modal-footer .privacy-btn-continue {
                width: 100%;
            }
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid p-0 min-vh-100">
        <div class="row g-0 auth-login-wrap">

            {{-- Left Hero Brand Column --}}
            <div class="col-lg-6 d-none d-lg-flex flex-column justify-content-between text-white p-5 position-relative overflow-hidden"
                style="background-color: #0F2537;">
                <div class="position-absolute rounded-circle"
                    style="width: 450px; height: 450px; background: rgba(255, 255, 255, 0.03); top: -120px; right: -120px; pointer-events: none;">
                </div>
                <div></div>
                <div class="z-1 text-center mx-auto px-4 py-3" style="max-width: 480px;">
                    <div class="d-inline-flex align-items-center gap-2 mb-4 px-3 py-2 rounded-3"
                        style="background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(4px); border: 1px solid rgba(255, 255, 255, 0.2);">
                        <div class="rounded-2 p-2 text-white d-flex align-items-center justify-content-center"
                            style="background-color: #0F2537;">
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
                        <h3 class="fw-bold mb-1" style="color: #0f172a; font-size: 1.5rem;">Student Sign In</h3>
                        <p class="text-secondary small mb-0" style="font-size: 0.8125rem; line-height: 1.45;">Sign in to manage your SLE-FHE verification and sponsorship applications.</p>
                    </div>

                    {{-- AJAX Error Alert Container --}}
                    <div id="login-error-alert" class="alert alert-danger p-2 small mb-3 d-none">
                        These credentials do not match our records.
                    </div>

                    @if (session('warning') || request()->has('session_expired'))
                        <div class="alert alert-warning p-2 small mb-3 border-0 shadow-sm d-flex align-items-center gap-2 rounded-3"
                            style="background-color: #fff3cd; color: #664d03; font-size: 0.85rem;">
                            <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
                            <span>{{ session('warning') ?? 'Your session has expired due to inactivity. Please sign in again.' }}</span>
                        </div>
                    @endif

                    @if (session('status'))
                        <div id="student-login-status" class="alert p-2 small mb-3 d-flex align-items-start gap-2 sl-login-status"
                            data-student-login-status data-auto-dismiss="6000"
                            role="status" aria-live="polite" aria-atomic="true">
                            <i class="bi bi-check-circle flex-shrink-0 mt-1" aria-hidden="true"></i>
                            <span>{{ session('status') }}</span>
                            <button type="button" class="sl-status-dismiss" data-dismiss-student-login-status
                                aria-label="Dismiss success message" title="Dismiss success message">
                                <i class="bi bi-x-lg" aria-hidden="true"></i>
                            </button>
                        </div>
                    @endif

                    {{-- Login Form --}}
                    <form id="student-login-form" onsubmit="attemptLogin(event)">
                        @csrf

                        {{-- Email Address --}}
                        <div class="mb-3">
                            <label for="email" class="form-label small fw-semibold mb-1" style="font-size: 0.8rem; color: #475569;">Email Address</label>
                            <div class="input-group sl-auth-input-group">
                                <span class="input-group-text"><i class="bi bi-envelope" aria-hidden="true"></i></span>
                                <input type="email" id="email" name="email"
                                    class="form-control" value="{{ old('email') }}"
                                    placeholder="Enter your registered email" required autofocus autocomplete="email"
                                    style="font-size: 0.875rem;">
                            </div>
                        </div>

                        {{-- Password --}}
                        <div class="mb-3">
                            <label for="password" class="form-label small fw-semibold mb-1" style="font-size: 0.8rem; color: #475569;">Password</label>
                            <div class="input-group sl-auth-input-group">
                                <span class="input-group-text"><i class="bi bi-lock" aria-hidden="true"></i></span>
                                <input type="password" id="password" name="password"
                                    class="form-control" placeholder="Enter your password"
                                    required autocomplete="current-password" style="font-size: 0.875rem;">
                                <button type="button"
                                    onclick="togglePasswordVisibility('password', this)"
                                    class="sl-auth-toggle-btn"
                                    aria-label="Show password"
                                    title="Show password">
                                    <i class="bi bi-eye" style="pointer-events: none;" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>

                        {{-- Remember Me & Forgot Password --}}
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="remember" id="remember">
                                <label class="form-check-label small text-secondary" for="remember"
                                    style="font-size: 0.8rem;">Remember Me</label>
                            </div>
                            <a href="{{ route('student.password.request') }}" class="small text-decoration-none fw-semibold"
                                style="color: #0F2537; font-size: 0.8rem;">Forgot Password?</a>
                        </div>

                        {{-- Sign In Button --}}
                        <button type="submit" id="sign-in-btn"
                            class="btn w-100 fw-bold shadow-sm sl-auth-submit-btn"
                            style="font-size: 0.875rem;">
                            Sign In
                        </button>

                        {{-- Create Account --}}
                        <div class="text-center mt-4 pt-3">
                            <p class="small text-secondary mb-2" style="font-size: 0.8rem;">Don't have a student account?</p>
                            <a href="{{ route('register') }}"
                                class="btn w-100 fw-bold shadow-none sl-create-account-btn">Create Account</a>
                        </div>

                        {{-- Hidden input for token --}}
                        <input type="hidden" name="pending_token" id="pending_token_input">
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

    {{-- Data Privacy Consent Modal – Institutional UI/UX Refinement --}}
    <div id="privacyConsentModal" class="modal fade privacy-modal-overlay d-none" tabindex="-1"
        role="dialog" aria-modal="true" aria-labelledby="privacyConsentModalLabel" aria-describedby="privacyConsentModalDesc" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered privacy-modal-dialog">
            <div class="modal-content privacy-modal-card">

                {{-- ── Header: institutional gradient navy + glassmorphism shield ── --}}
                <div class="privacy-card-header">
                    <div class="privacy-header-icon" aria-hidden="true">
                        <i class="bi bi-shield-lock"></i>
                    </div>
                    <div class="privacy-header-text">
                        <div class="text-uppercase fw-semibold mb-1"
                            style="font-size: 0.65rem; letter-spacing: 0.1em; color: rgba(255,255,255,0.7);">DAVAO ORIENTAL STATE UNIVERSITY &bull; DATA PRIVACY</div>
                        <h5 id="privacyConsentModalLabel" class="fw-bold mb-0 text-white" style="font-size: 1.15rem; line-height: 1.3;">
                            Student Portal &ndash; Data Privacy Consent
                        </h5>
                    </div>
                </div>

                {{-- ── Body: clean #f8fafc + standardized accent cards ── --}}
                <div class="privacy-modal-body">
                    <p id="privacyConsentModalDesc" class="text-secondary mb-3" style="font-size: 0.85rem; line-height: 1.6;">
                        Welcome to the DOrSU Student Portal. Before accessing your personalized dashboard,
                        please review and accept the following data privacy terms in compliance with Philippine law.
                    </p>

                    {{-- Purpose of Data Collection --}}
                    <div class="privacy-accent-card card-primary">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-file-earmark-text privacy-card-icon" aria-hidden="true"></i>
                            <h6 class="privacy-card-title">Purpose of Data Collection</h6>
                        </div>
                        <ul class="privacy-card-list">
                            <li>
                                <i class="bi bi-check-circle text-primary" aria-hidden="true"></i>
                                <span>Manage academic records and sponsorship eligibility</span>
                            </li>
                            <li>
                                <i class="bi bi-check-circle text-primary" aria-hidden="true"></i>
                                <span>Provide essential SLE-FHE student services</span>
                            </li>
                            <li>
                                <i class="bi bi-check-circle text-primary" aria-hidden="true"></i>
                                <span>Communicate important updates and program announcements</span>
                            </li>
                        </ul>
                    </div>

                    {{-- Data Protection Commitment --}}
                    <div class="privacy-accent-card card-warning">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-shield-check privacy-card-icon" aria-hidden="true"></i>
                            <h6 class="privacy-card-title">Data Protection Commitment</h6>
                        </div>
                        <p class="mb-0" style="font-size: 0.8125rem; color: #475569; line-height: 1.55;">
                            DOrSU protects your personal information and processes it in accordance with the
                            <strong>Data Privacy Act of 2012 (Republic Act No. 10173)</strong>.
                            Your data is used solely for legitimate university and student-service purposes and
                            is never sold to third parties.
                        </p>
                    </div>

                    {{-- Your Rights as a Data Subject --}}
                    <div class="privacy-accent-card card-success">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-person-check privacy-card-icon" aria-hidden="true"></i>
                            <h6 class="privacy-card-title">Your Rights as a Data Subject</h6>
                        </div>
                        <ul class="privacy-card-list">
                            <li>
                                <i class="bi bi-check-circle text-success" aria-hidden="true"></i>
                                <span>Access and obtain a copy of your personal data</span>
                            </li>
                            <li>
                                <i class="bi bi-check-circle text-success" aria-hidden="true"></i>
                                <span>Correct any inaccuracies in your records</span>
                            </li>
                            <li>
                                <i class="bi bi-check-circle text-success" aria-hidden="true"></i>
                                <span>Withdraw consent, subject to applicable legal limitations</span>
                            </li>
                        </ul>
                    </div>

                    {{-- Interactive Consent Checkbox Card --}}
                    <label for="privacy-agree-check" class="privacy-consent-card" id="privacy-consent-card-wrapper">
                        <input class="form-check-input privacy-consent-checkbox" type="checkbox"
                            id="privacy-agree-check"
                            onchange="toggleContinueBtn()">
                        <div class="privacy-consent-content">
                            <div class="privacy-consent-title">
                                I have read and agree to the data privacy terms above.
                            </div>
                            <div class="privacy-consent-desc">
                                By checking this box you consent to the collection and processing of your personal data
                                as described in this notice.
                            </div>
                        </div>
                    </label>
                </div>

                {{-- ── Footer ── --}}
                <div class="privacy-modal-footer">
                    <button type="button"
                        class="privacy-btn-cancel"
                        id="privacy-cancel-btn"
                        onclick="closePrivacyModal()">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                        <span>Cancel</span>
                    </button>
                    <button type="button" id="privacy-continue-btn"
                        class="privacy-btn-continue" disabled
                        onclick="submitConsentFinal()">
                        <i class="bi bi-check2-circle" aria-hidden="true"></i>
                        <span id="privacy-continue-btn-text">Agree & Continue</span>
                    </button>
                </div>

            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Enhanced Accessible Password Visibility Toggle
        function togglePasswordVisibility(inputId, btn) {
            const input = document.getElementById(inputId);
            if (!input) return;

            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';

            const icon = btn.querySelector('i');
            if (icon) {
                if (isPassword) {
                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-slash');
                } else {
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');
                }
            }

            const label = isPassword ? 'Hide password' : 'Show password';
            btn.setAttribute('aria-label', label);
            btn.setAttribute('title', label);
        }
        window.togglePasswordVisibility = togglePasswordVisibility;

        // Scoped lifecycle for the post-reset success notice only.
        (function () {
            const alert = document.getElementById('student-login-status');
            if (!alert) return;

            let timer = null;
            let dismissed = false;

            const finalizeDismissal = () => {
                if (alert.parentNode) alert.parentNode.removeChild(alert);
            };

            const dismiss = () => {
                if (dismissed) return;
                dismissed = true;

                if (timer !== null) {
                    window.clearTimeout(timer);
                    timer = null;
                }

                alert.setAttribute('aria-hidden', 'true');
                alert.classList.add('is-dismissing');

                if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                    finalizeDismissal();
                    return;
                }

                alert.addEventListener('transitionend', finalizeDismissal, { once: true });
                window.setTimeout(finalizeDismissal, 250);
            };

            const dismissButton = alert.querySelector('[data-dismiss-student-login-status]');
            if (dismissButton) dismissButton.addEventListener('click', dismiss);

            document.addEventListener('student-login-attempt', dismiss);

            const delay = Number(alert.dataset.autoDismiss);
            if (Number.isFinite(delay) && delay > 0) {
                timer = window.setTimeout(dismiss, delay);
            }
        })();

        // Initial Login Attempt (Verifies credentials via AJAX)
        async function attemptLogin(event) {
            event.preventDefault();
            document.dispatchEvent(new CustomEvent('student-login-attempt'));
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
                    openPrivacyModal();
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

        // Accessible Modal Open Handler
        function openPrivacyModal() {
            const privacyModal = document.getElementById('privacyConsentModal');
            if (!privacyModal) return;
            privacyModal.classList.remove('d-none');
            privacyModal.classList.add('show', 'd-block');
            privacyModal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-open');
            document.body.style.overflow = 'hidden';

            // Reset state
            const checkbox = document.getElementById('privacy-agree-check');
            if (checkbox) {
                checkbox.checked = false;
                setTimeout(() => checkbox.focus(), 100);
            }
            toggleContinueBtn();
        }
        window.openPrivacyModal = openPrivacyModal;

        // Accessible Modal Close Handler
        function closePrivacyModal() {
            const privacyModal = document.getElementById('privacyConsentModal');
            if (!privacyModal) return;
            privacyModal.classList.remove('show', 'd-block');
            privacyModal.classList.add('d-none');
            privacyModal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';

            const checkbox = document.getElementById('privacy-agree-check');
            if (checkbox) checkbox.checked = false;
            toggleContinueBtn();

            // Return focus to sign-in button
            const signInBtn = document.getElementById('sign-in-btn');
            if (signInBtn) signInBtn.focus();
        }
        window.closePrivacyModal = closePrivacyModal;

        function toggleContinueBtn() {
            const checkbox = document.getElementById('privacy-agree-check');
            const isChecked = checkbox ? checkbox.checked : false;
            const btn = document.getElementById('privacy-continue-btn');
            const card = document.getElementById('privacy-consent-card-wrapper');
            if (btn) {
                btn.disabled = !isChecked;
            }
            if (card) {
                card.classList.toggle('is-checked', isChecked);
            }
        }
        window.toggleContinueBtn = toggleContinueBtn;

        // Escape Key Listener for Accessible Modal Dismissal
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const privacyModal = document.getElementById('privacyConsentModal');
                if (privacyModal && !privacyModal.classList.contains('d-none') && privacyModal.classList.contains('show')) {
                    closePrivacyModal();
                }
            }
        });

        // Final Login Submission with Duplicate Submission Prevention
        let isSubmittingConsent = false;

        async function submitConsentFinal() {
            if (isSubmittingConsent) return;

            const checkbox = document.getElementById('privacy-agree-check');
            const alertBox = document.getElementById('login-error-alert');
            const continueBtn = document.getElementById('privacy-continue-btn');
            const continueBtnText = document.getElementById('privacy-continue-btn-text');

            if (!checkbox || !checkbox.checked) {
                alert('You must agree to the Data Privacy Policy to log in.');
                return;
            }

            isSubmittingConsent = true;
            if (continueBtn) continueBtn.disabled = true;
            if (continueBtnText) continueBtnText.textContent = 'Logging in...';

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
                    isSubmittingConsent = false;
                    closePrivacyModal();
                    alertBox.textContent = data.message || 'Login completion failed. Please try again.';
                    alertBox.classList.remove('d-none');
                    if (continueBtn) continueBtn.disabled = false;
                    if (continueBtnText) continueBtnText.textContent = 'Agree & Continue';
                }
            } catch (error) {
                console.error('Login completion error:', error);
                isSubmittingConsent = false;
                closePrivacyModal();
                alertBox.textContent = 'An error occurred completing your login.';
                alertBox.classList.remove('d-none');
                if (continueBtn) continueBtn.disabled = false;
                if (continueBtnText) continueBtnText.textContent = 'Agree & Continue';
            }
        }
        window.submitConsentFinal = submitConsentFinal;
    </script>
@endpush
