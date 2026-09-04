@extends('layouts.app')

@section('title', 'Staff & Sponsor Sign In')

@push('styles')
    <style>
        .sf-content { padding: 0 !important; }

        body, html {
            height: 100%;
            margin: 0;
            background-color: #0a1b30 !important;
        }

        .staff-login-wrapper {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            background: radial-gradient(circle at center, #143561 0%, #0a1b30 100%);
        }

        .staff-login-card {
            width: 100%;
            max-width: 420px;
            background: #ffffff;
            border-radius: 20px;
            padding: 2.75rem 2.25rem;
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.4);
        }

        .staff-logo-box {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.5rem 1.1rem;
            background-color: #0f294a;
            border-radius: 10px;
            color: #ffffff;
            font-weight: 700;
            font-size: 1.15rem;
        }

        .form-control.bg-light:focus {
            background-color: #ffffff !important;
            border-color: #93c5fd;
            box-shadow: 0 0 0 0.2rem rgba(15, 41, 74, 0.12);
        }
    </style>
@endpush

@section('content')
    <div class="staff-login-wrapper">
        <div class="staff-login-card text-center">

            <div class="mb-3">
                <div class="staff-logo-box shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" width="18" height="18" fill="#ffffff">
                        <path d="M312 96c0-13.3-10.7-24-24-24s-24 10.7-24 24l0 8c-30.9 0-56 25.1-56 56s25.1 56 56 56l32 0c13.3 0 24 10.7 24 24s-10.7 24-24 24l-48 0c-13.3 0-24-10.7-24-24c0-13.3-10.7-24-24-24s-24 10.7-24 24c0 30.9 25.1 56 56 56l0 8c0 13.3 10.7 24 24 24s24-10.7 24-24l0-8c30.9 0 56-25.1 56-56s-25.1-56-56-56l-32 0c-13.3 0-24-10.7-24-24s10.7-24 24-24l48 0c13.3 0 24 10.7 24 24c0 13.3 10.7 24 24 24s24-10.7 24-24c0-30.9-25.1-56-56-56l0-8zM0 384c0-35.3 28.7-64 64-64l119.7 0c15.6 0 30.3 5.7 41.7 16l103.8 93.4c22.1 19.9 51.1 30.6 80.8 30.6l102 0c35.3 0 64-28.7 64-64s-28.7-64-64-64l-96 0c-13.3 0-24-10.7-24-24s10.7-24 24-24l96 0c61.9 0 112 50.1 112 112s-50.1 112-112 112l-102 0c-42.5 0-83.9-15.3-115.5-43.7L186.3 368 64 368c-8.8 0-16 7.2-16 16s7.2 16 16 16l112 0c13.3 0 24 10.7 24 24s-10.7 24-24 24L64 448c-35.3 0-64-28.7-64-64z"/>
                    </svg>
                    <span>SponsorFlow</span>
                </div>
            </div>

            <div class="mb-3">
                <span class="badge rounded-pill text-uppercase" style="background-color: #ecfdf5; color: #059669; font-size: 0.65rem; font-weight: 700; letter-spacing: 0.08em; padding: 0.35rem 0.85rem; border: 1px solid #a7f3d0;">
                    STAFF &amp; SPONSOR PORTAL
                </span>
            </div>

            <h3 class="fw-bold mb-1" style="color: #0f172a; font-size: 1.5rem;">Portal Sign In</h3>
            <p class="text-secondary mb-4" style="font-size: 0.8rem; color: #64748b;">Access panel for FASSG Staff, Accounting, and Organization Sponsors</p>

            @if ($errors->any())
                <div class="alert alert-danger p-2 small mb-3 border-0 text-start" style="font-size: 0.8rem;">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('staff.login.store') }}" class="text-start">
                @csrf

                <div class="mb-3">
                    <label for="email" class="form-label small fw-semibold mb-1" style="font-size: 0.8rem; color: #475569;">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-envelope"></i></span>
                        <input type="email" id="email" name="email" class="form-control bg-light border-start-0" value="{{ old('email') }}" placeholder="name@dorsu.edu.ph" required autofocus style="font-size: 0.875rem; height: 42px;">
                    </div>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label small fw-semibold mb-1" style="font-size: 0.8rem; color: #475569;">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock"></i></span>
                        <input type="password" id="password" name="password" class="form-control bg-light border-start-0 border-end-0" placeholder="Enter your password" required style="font-size: 0.875rem; height: 42px;">
                        <span class="input-group-text bg-light border-start-0 text-muted" style="cursor: pointer;" id="password-toggle"><i class="bi bi-eye"></i></span>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 fw-semibold shadow-sm mb-4" style="background-color: #0f294a; border: none; border-radius: 8px; height: 44px; font-size: 0.875rem;">
                    Sign In
                </button>

            </form>

        </div>

        <div class="text-center mt-4" style="font-size: 0.75rem; color: rgba(255, 255, 255, 0.4);">
            &copy; 2026 Davao Oriental State University. All rights reserved.
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
    </script>
@endpush
