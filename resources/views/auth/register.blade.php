@extends('layouts.app')

@section('title', 'Register')

@push('styles')
    <style>
        .sf-content { padding: 0 !important; }
        .auth-register-wrap { min-height: 100vh; background-color: #f8fafc; }

        .register-card-box {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.25rem;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
        }

        .form-control.bg-light:focus, .form-select.bg-light:focus {
            background-color: #fff !important;
            border-color: #93c5fd;
            box-shadow: 0 0 0 0.2rem rgba(15, 41, 74, 0.12);
        }

        .section-badge {
            width: 26px;
            height: 26px;
            background-color: #0f294a;
            color: #ffffff;
            font-size: 0.75rem;
            font-weight: 700;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid p-0 min-vh-100">
        <div class="row g-0 auth-register-wrap">

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
                    <p class="text-white-50 small mx-auto mb-4" style="max-width: 400px; line-height: 1.6; font-size: 0.875rem;">Create your account to apply for financial assistance, connect with sponsors, and track your SLE-FHE status.</p>
                </div>

                <div></div>
            </div>

            <div class="col-lg-6 d-flex align-items-center justify-content-center px-4 px-md-5 py-5 overflow-y-auto" style="min-height: 100vh;">
                <div class="w-100 py-3" style="max-width: 560px;">

                    <div class="mb-4 text-center text-lg-start">
                        <span class="badge bg-white text-primary border mb-2 px-3 py-1.5 rounded-pill fw-semibold shadow-sm" style="font-size: 0.72rem; color: #0f294a !important; border-color: #cbd5e1 !important;">SLE-FHE STUDENT ACCESS</span>
                        <h2 class="fw-bold mb-1" style="color: #0f172a; font-size: 1.75rem;">Create your account</h2>
                        <p class="text-secondary small mb-0" style="font-size: 0.875rem;">Use your institutional DOrSU email. SLE-FHE status remains pending until verified.</p>
                    </div>

                    @if ($errors->any())
                        <div class="alert alert-danger p-3 rounded-3 small mb-4 shadow-sm border-0">
                            <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Please correct the following errors:</div>
                            <ul class="mb-0 ps-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('register.store') }}">
                        @csrf

                        <div class="register-card-box">
                            <div class="d-flex align-items-center gap-2.5 mb-3">
                                <span class="section-badge">1</span>
                                <h6 class="fw-bold mb-0 text-dark" style="font-size: 0.95rem;">Account Information</h6>
                            </div>

                            <div class="row g-2.5 mb-3">
                                <div class="col-md-4">
                                    <label for="first_name" class="form-label small fw-semibold text-secondary mb-1">First Name *</label>
                                    <input type="text" id="first_name" name="first_name" class="form-control form-control-md bg-light border-1" value="{{ old('first_name') }}" placeholder="Juan" required autofocus style="font-size: 0.875rem; border-radius: 8px;">
                                </div>
                                <div class="col-md-4">
                                    <label for="middle_name" class="form-label small fw-semibold text-secondary mb-1">Middle Name</label>
                                    <input type="text" id="middle_name" name="middle_name" class="form-control form-control-md bg-light border-1" value="{{ old('middle_name') }}" placeholder="Carlos" style="font-size: 0.875rem; border-radius: 8px;">
                                </div>
                                <div class="col-md-4">
                                    <label for="last_name" class="form-label small fw-semibold text-secondary mb-1">Last Name *</label>
                                    <input type="text" id="last_name" name="last_name" class="form-control form-control-md bg-light border-1" value="{{ old('last_name') }}" placeholder="Dela Cruz" required style="font-size: 0.875rem; border-radius: 8px;">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label small fw-semibold text-secondary mb-1">DOrSU Institutional Email *</label>
                                <input type="email" id="email" name="email" class="form-control form-control-md bg-light border-1" value="{{ old('email') }}" placeholder="name@dorsu.edu.ph" required style="font-size: 0.875rem; border-radius: 8px;">
                            </div>

                            <div class="row g-2.5">
                                <div class="col-md-6">
                                    <label for="password" class="form-label small fw-semibold text-secondary mb-1">Password *</label>
                                    <div class="input-group">
                                        <input type="password" id="password" name="password" class="form-control form-control-md bg-light border-1 border-end-0" required autocomplete="new-password" minlength="8" style="font-size: 0.875rem; border-top-left-radius: 8px; border-bottom-left-radius: 8px;">
                                        <span class="input-group-text bg-light border-1 border-start-0 text-muted" style="cursor: pointer; border-top-right-radius: 8px; border-bottom-right-radius: 8px;" id="password-toggle"><i class="bi bi-eye"></i></span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="password_confirmation" class="form-label small fw-semibold text-secondary mb-1">Confirm Password *</label>
                                    <div class="input-group">
                                        <input type="password" id="password_confirmation" name="password_confirmation" class="form-control form-control-md bg-light border-1 border-end-0" required autocomplete="new-password" style="font-size: 0.875rem; border-top-left-radius: 8px; border-bottom-left-radius: 8px;">
                                        <span class="input-group-text bg-light border-1 border-start-0 text-muted" style="cursor: pointer; border-top-right-radius: 8px; border-bottom-right-radius: 8px;" id="password-confirm-toggle"><i class="bi bi-eye"></i></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="register-card-box">
                            <div class="d-flex align-items-center gap-2.5 mb-3">
                                <span class="section-badge">2</span>
                                <h6 class="fw-bold mb-0 text-dark" style="font-size: 0.95rem;">Academic Details</h6>
                            </div>

                            <div class="row g-2.5 mb-3">
                                <div class="col-md-8">
                                    <label for="student_id_number" class="form-label small fw-semibold text-secondary mb-1">Student ID Number *</label>
                                    <input type="text" id="student_id_number" name="student_id_number" class="form-control form-control-md bg-light border-1" value="{{ old('student_id_number') }}" placeholder="2024-00001" required style="font-size: 0.875rem; border-radius: 8px;">
                                </div>
                                <div class="col-md-4">
                                    <label for="year_level" class="form-label small fw-semibold text-secondary mb-1">Year Level *</label>
                                    <select id="year_level" name="year_level" class="form-select form-select-md bg-light border-1" required style="font-size: 0.875rem; border-radius: 8px;">
                                        <option value="" disabled selected>Select</option>
                                        <option value="1" @selected(old('year_level') == 1)>1st Year</option>
                                        <option value="2" @selected(old('year_level') == 2)>2nd Year</option>
                                        <option value="3" @selected(old('year_level') == 3)>3rd Year</option>
                                        <option value="4" @selected(old('year_level') == 4)>4th Year</option>
                                        <option value="5" @selected(old('year_level') == 5)>5th Year</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row g-2.5">
                                <div class="col-md-7">
                                    <label for="academic_program_id" class="form-label small fw-semibold text-secondary mb-1">Academic Program / Course *</label>
                                    <select id="academic_program_id" name="academic_program_id" class="form-select form-select-md bg-light border-1 @error('academic_program_id') is-invalid @enderror" required style="font-size: 0.875rem; border-radius: 8px;">
                                        <option value="" disabled selected>-- Select Your Course/Program --</option>
                                        @foreach ($programs as $program)
                                            <option value="{{ $program->program_id }}" @selected(old('academic_program_id') == $program->program_id)>
                                                {{ $program->code }} - {{ $program->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('academic_program_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-5">
                                    <label for="birthdate" class="form-label small fw-semibold text-secondary mb-1">Birthdate *</label>
                                    <input type="date" id="birthdate" name="birthdate" class="form-control form-control-md bg-light border-1" value="{{ old('birthdate') }}" required style="font-size: 0.875rem; border-radius: 8px;">
                                </div>
                            </div>
                        </div>

                        <div class="register-card-box">
                            <div class="d-flex align-items-center gap-2.5 mb-3">
                                <span class="section-badge">3</span>
                                <h6 class="fw-bold mb-0 text-dark" style="font-size: 0.95rem;">Address &amp; Rurality</h6>
                            </div>

                            <div class="row g-2.5 mb-3">
                                <div class="col-md-6">
                                    <label for="barangay" class="form-label small fw-semibold text-secondary mb-1">Barangay *</label>
                                    <input type="text" id="barangay" name="barangay" class="form-control form-control-md bg-light border-1" value="{{ old('barangay') }}" placeholder="Barangay Name" required style="font-size: 0.875rem; border-radius: 8px;">
                                </div>
                                <div class="col-md-6">
                                    <label for="address" class="form-label small fw-semibold text-secondary mb-1">Home Address *</label>
                                    <input type="text" id="address" name="address" class="form-control form-control-md bg-light border-1" value="{{ old('address') }}" placeholder="Street / Purok" required style="font-size: 0.875rem; border-radius: 8px;">
                                </div>
                            </div>

                            <div class="form-check m-0">
                                <input class="form-check-input" type="checkbox" id="is_rural" name="is_rural" value="1" @checked(old('is_rural')) style="cursor: pointer;">
                                <label class="form-check-label small text-secondary fw-medium" for="is_rural" style="cursor: pointer; font-size: 0.825rem;">
                                    I reside in a rural barangay
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2.5 fw-bold shadow-sm mb-3" style="background-color: #0f294a; border: none; border-radius: 10px; font-size: 0.95rem;">
                            <i class="bi bi-person-plus me-1.5"></i> Create account and proceed
                        </button>

                        <div class="text-center pt-2">
                            <p class="small text-secondary mb-0" style="font-size: 0.85rem;">
                                Already have an account? 
                                <a href="{{ route('login') }}" class="fw-bold text-decoration-none ms-1" style="color: #0f294a;">Sign In</a>
                            </p>
                        </div>
                    </form>

                    <div class="text-center mt-5 pt-3 text-secondary" style="font-size: 0.72rem;">
                        &copy; 2026 Davao Oriental State University. All rights reserved.<br>
                        <a href="#" class="text-secondary text-decoration-none">Privacy Policy</a> &bull; 
                        <a href="#" class="text-secondary text-decoration-none">Terms of Service</a> &bull; 
                        <a href="#" class="text-secondary text-decoration-none">Contact Support</a>
                    </div>

                </div>
            </div>

        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            function setupPasswordToggle(toggleId, inputId) {
                const toggle = document.getElementById(toggleId);
                const input = document.getElementById(inputId);
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
            }

            setupPasswordToggle('password-toggle', 'password');
            setupPasswordToggle('password-confirm-toggle', 'password_confirmation');
        });
    </script>
@endpush
