@extends('layouts.app')

@section('title', 'Admin Sign In')

@push('styles')
    <style>
        .sf-content { padding: 0 !important; }

        body, html {
            height: 100%;
            margin: 0;
            background-color: #0A1E31 !important;
        }

        .admin-login-wrapper {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            background: radial-gradient(circle at center, #143561 0%, #0A1E31 100%);
        }

        .admin-login-card {
            width: 100%;
            max-width: 430px;
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid rgba(226, 232, 240, 0.85);
            padding: 2.5rem 2.25rem;
            box-shadow: 0 20px 45px -10px rgba(0, 0, 0, 0.45), 0 10px 20px -5px rgba(0, 0, 0, 0.25);
        }

        .sf-auth-brand-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.625rem;
            padding: 0.45rem 1.15rem 0.45rem 0.5rem;
            background-color: #0F2537;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 12px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.18);
        }

        .sf-auth-brand-icon {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            background-color: #173B57;
            color: #FFFFFF;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
        }

        .sf-auth-brand-name {
            color: #FFFFFF;
            font-weight: 700;
            font-size: 1.125rem;
            letter-spacing: -0.01em;
        }

        .sf-portal-badge {
            display: inline-flex;
            align-items: center;
            text-transform: uppercase;
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            padding: 0.35rem 0.85rem;
            border-radius: 50rem;
        }

        .sf-portal-badge-admin {
            background-color: #F0F9FF;
            color: #0284C7;
            border: 1px solid #BAE6FD;
        }

        .sf-auth-title {
            color: #0F2942;
            font-size: 1.5rem;
            font-weight: 700;
            line-height: 1.25;
            letter-spacing: -0.02em;
        }

        .sf-auth-subtitle {
            font-size: 0.8125rem;
            color: #64748B;
            line-height: 1.45;
        }

        .sf-auth-input-group {
            border: 1px solid #CBD5E1;
            border-radius: 8px;
            overflow: hidden;
            background-color: #F8FAFC;
            transition: border-color 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease;
        }

        .sf-auth-input-group:focus-within {
            border-color: #0F2942 !important;
            background-color: #FFFFFF !important;
            box-shadow: 0 0 0 3px rgba(15, 41, 66, 0.12) !important;
        }

        .sf-auth-input-group .input-group-text {
            background-color: transparent !important;
            border: none !important;
            color: #64748B !important;
            width: 42px;
            justify-content: center;
            font-size: 0.95rem;
        }

        .sf-auth-input-group .form-control {
            background-color: transparent !important;
            border: none !important;
            font-size: 0.875rem !important;
            height: 42px !important;
            color: #0F2942 !important;
            box-shadow: none !important;
            padding-left: 0 !important;
        }

        .sf-auth-input-group .form-control::placeholder {
            color: #94A3B8 !important;
            font-size: 0.875rem !important;
        }

        .sf-auth-toggle-btn {
            background-color: transparent !important;
            border: none !important;
            color: #64748B !important;
            width: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: color 0.15s ease;
        }

        .sf-auth-toggle-btn:hover {
            color: #0F2942 !important;
        }

        .sf-auth-toggle-btn:focus-visible {
            outline: none !important;
            box-shadow: inset 0 0 0 2px #0F2942 !important;
            border-radius: 6px;
        }

        .sf-auth-submit-btn {
            background-color: #0F2942 !important;
            border: 1px solid #0F2942 !important;
            border-radius: 8px !important;
            height: 44px !important;
            font-size: 0.875rem !important;
            font-weight: 600 !important;
            color: #FFFFFF !important;
            box-shadow: 0 2px 4px rgba(15, 41, 66, 0.15);
            transition: background-color 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease !important;
        }

        .sf-auth-submit-btn:hover {
            background-color: #173B57 !important;
            border-color: #173B57 !important;
            color: #FFFFFF !important;
        }

        .sf-auth-submit-btn:focus-visible {
            outline: none !important;
            border-color: #0F2942 !important;
            box-shadow: 0 0 0 3px rgba(15, 41, 66, 0.25) !important;
        }

        .sf-auth-nav-link {
            font-size: 0.8125rem;
            color: #64748B;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            transition: color 0.15s ease;
        }

        .sf-auth-nav-link:hover {
            color: #0F2942;
            text-decoration: underline;
        }

        .sf-auth-nav-link:focus-visible {
            outline: 2px solid #0F2942;
            outline-offset: 2px;
            border-radius: 4px;
        }

        .sf-auth-copyright {
            font-size: 0.75rem;
            color: #94A3B8;
        }

        @media screen and (min-width: 992px) {
            .admin-login-card {
                max-width: 430px;
            }
        }
    </style>
@endpush

@section('content')
    <div class="admin-login-wrapper">
        <div class="admin-login-card text-center">

            {{-- Brand Logo Badge --}}
            <div class="mb-3">
                <div class="sf-auth-brand-badge shadow-sm">
                    <div class="sf-auth-brand-icon">
                        <i class="fa-solid fa-hand-holding-dollar" aria-hidden="true"></i>
                    </div>
                    <span class="sf-auth-brand-name">SponsorFlow</span>
                </div>
            </div>

            {{-- Portal Badge --}}
            <div class="mb-2.5">
                <span class="sf-portal-badge sf-portal-badge-admin">
                    ADMIN PORTAL
                </span>
            </div>

            {{-- Heading & Subtitle --}}
            <h2 class="sf-auth-title mb-1">Administrator Sign In</h2>
            <p class="sf-auth-subtitle mb-4">Sign in to access the System Administrator portal.</p>

            {{-- Alerts / Session Messages --}}
            @if (session('warning') || request()->has('session_expired'))
                <div class="alert alert-warning p-2.5 small mb-3 border-0 text-start d-flex align-items-center gap-2 rounded-3" style="font-size: 0.8rem; background-color: #fff3cd; color: #664d03;">
                    <i class="bi bi-exclamation-triangle-fill flex-shrink-0" aria-hidden="true"></i>
                    <span>{{ session('warning') ?? 'Your session has expired due to inactivity. Please sign in again.' }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger p-2.5 small mb-3 border-0 text-start d-flex align-items-center gap-2 rounded-3" style="font-size: 0.8rem;">
                    <i class="bi bi-exclamation-circle-fill flex-shrink-0" aria-hidden="true"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            {{-- Login Form --}}
            <form method="POST" action="{{ route('admin.login.store') }}" class="text-start">
                @csrf

                {{-- Email Address --}}
                <div class="mb-3">
                    <label for="email" class="form-label small fw-semibold mb-1" style="font-size: 0.8rem; color: #475569;">Email Address</label>
                    <div class="input-group sf-auth-input-group">
                        <span class="input-group-text"><i class="bi bi-envelope" aria-hidden="true"></i></span>
                        <input type="email" id="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="Enter your registered email" required autofocus>
                    </div>
                </div>

                {{-- Password --}}
                <div class="mb-4">
                    <label for="password" class="form-label small fw-semibold mb-1" style="font-size: 0.8rem; color: #475569;">Password</label>
                    <div class="input-group sf-auth-input-group">
                        <span class="input-group-text"><i class="bi bi-lock" aria-hidden="true"></i></span>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required>
                        <button type="button" onclick="togglePasswordVisibility('password', this); const i = this.querySelector('i'); if (i) { i.classList.toggle('bi-eye'); i.classList.toggle('bi-eye-slash'); }"
                            class="sf-auth-toggle-btn"
                            aria-label="Toggle password visibility">
                            <i class="bi bi-eye" style="pointer-events: none;" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                {{-- Submit Button --}}
                <button type="submit" class="btn w-100 sf-auth-submit-btn mb-3.5">
                    Sign In
                </button>

                {{-- Secondary Navigation --}}
                <div class="text-center pt-1">
                    <a href="{{ route('login') }}" class="sf-auth-nav-link">
                        <i class="bi bi-arrow-left" aria-hidden="true"></i>
                        <span>Back to Student Login</span>
                    </a>
                </div>
            </form>

        </div>

        {{-- Footer --}}
        <div class="text-center mt-4 sf-auth-copyright">
            &copy; {{ date('Y') }} Davao Oriental State University. All rights reserved.
        </div>
    </div>
@endsection
