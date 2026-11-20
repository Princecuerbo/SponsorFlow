@extends('layouts.app')

@section('title', 'Register')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2/dist/css/tom-select.bootstrap5.min.css">
    <style>
        /* Text link hover style: Black text, Blue hover */
        .link-text-hover {
            color: #000000;
            transition: color 0.2s ease-in-out;
        }

        .link-text-hover:hover {
            color: #0F2537 !important;
        }

        .sf-content {
            padding: 0 !important;
        }

        .auth-register-wrap {
            height: 100vh;
            background-color: #f8fafc;
        }

        .register-card-box {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.25rem;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
        }

        .form-control.bg-light:focus,
        .form-select.bg-light:focus {
            background-color: #fff !important;
            border-color: #93c5fd;
            box-shadow: 0 0 0 0.2rem rgba(15, 41, 74, 0.12);
        }

        .section-badge {
            width: 26px;
            height: 26px;
            background-color: #0F2537;
            color: #ffffff;
            font-size: 0.75rem;
            font-weight: 700;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        select.form-select {
            max-width: 100% !important;
            text-overflow: ellipsis;
            white-space: nowrap;
            overflow: hidden;
        }

        /* ===== Standardized Input & Password Controls (Mobile & Desktop) ===== */
        .sr-input-standard {
            height: 42px !important;
            font-size: 0.875rem !important;
            border-radius: 8px !important;
        }

        /* Password input group cohesion - mobile and desktop */
        .sr-password-group {
            border: 1px solid #CBD5E1;
            border-radius: 8px;
            overflow: hidden;
            background-color: #F8FAFC;
            transition: border-color 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease;
            display: flex;
        }

        .sr-password-group:focus-within {
            border-color: #0F2942 !important;
            background-color: #FFFFFF !important;
            box-shadow: 0 0 0 3px rgba(15, 41, 66, 0.12) !important;
        }

        .sr-password-group .form-control {
            background-color: transparent !important;
            border: none !important;
            font-size: 0.875rem !important;
            height: 42px !important;
            color: #0F2942 !important;
            box-shadow: none !important;
            padding-right: 0.5rem !important;
        }

        .sr-password-group .form-control:focus {
            background-color: transparent !important;
            border: none !important;
            box-shadow: none !important;
            outline: none !important;
        }

        .sr-password-group .form-control::placeholder {
            color: #94A3B8 !important;
        }

        .sr-password-toggle {
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

        .sr-password-toggle:hover {
            color: #0F2942 !important;
        }

        .sr-password-toggle:focus-visible {
            outline: none !important;
            box-shadow: inset 0 0 0 2px #0F2942 !important;
            border-radius: 6px;
        }

        /* ===== Tom Select — Searchable Academic Program Selector ===== */
        .ts-wrapper.form-control,
        .ts-wrapper.form-select {
            padding: 0 !important;
            border: none !important;
            box-shadow: none !important;
        }

        .ts-control {
            min-height: 42px !important;
            height: 42px !important;
            padding: 0.375rem 2.25rem 0.375rem 0.75rem !important;
            font-size: 0.875rem !important;
            border-radius: 8px !important;
            background-color: #f8f9fa !important;
            border: 1px solid #dee2e6 !important;
            box-shadow: none !important;
            display: flex !important;
            align-items: center !important;
            position: relative;
        }

        .ts-control > .item {
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            max-width: calc(100% - 2rem) !important;
            color: #0F2942 !important;
            font-weight: 500 !important;
        }

        .ts-control > input {
            font-size: 0.875rem !important;
            color: #0F2942 !important;
        }

        .ts-control > input::placeholder {
            color: #94A3B8 !important;
            font-size: 0.875rem !important;
        }

        .ts-wrapper.is-focused .ts-control,
        .ts-wrapper.focus .ts-control {
            background-color: #fff !important;
            border-color: #0F2942 !important;
            box-shadow: 0 0 0 3px rgba(15, 41, 66, 0.12) !important;
        }

        .ts-dropdown {
            font-size: 0.875rem;
            border-radius: 8px;
            border-color: #dee2e6;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
            z-index: 1050 !important;
        }

        .ts-dropdown .option.active,
        .ts-dropdown .option:hover {
            background-color: #0F2537;
            color: #fff;
        }

        /* ===== Data Privacy Consent Card (Mobile & Desktop) ===== */
        .sr-consent-card {
            background: #FFFFFF;
            border: 1.5px solid #E2E8F0;
            border-radius: 10px;
            padding: 0.875rem 1rem;
            cursor: pointer;
            transition: border-color 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease;
        }

        .sr-consent-card:hover {
            border-color: #93c5fd;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.08);
        }

        .sr-consent-card:has(input:checked) {
            border-color: #0F2942;
            background-color: #F8FAFC;
        }

        .sr-consent-card:focus-within {
            border-color: #0F2942;
            box-shadow: 0 0 0 3px rgba(15, 41, 66, 0.15);
        }

        .sr-consent-flex {
            display: flex;
            align-items: flex-start;
            gap: 12px; /* Explicit 12px spacing between checkbox and text */
        }

        .sr-consent-checkbox {
            width: 17px !important;
            height: 17px !important;
            min-width: 17px !important;
            margin: 2px 0 0 0 !important; /* aligned with the top of first line of text */
            flex-shrink: 0 !important;
            cursor: pointer;
        }

        .sr-consent-text {
            flex: 1;
            font-size: 0.8125rem;
            line-height: 1.5;
            color: #475569;
            font-weight: 500;
        }

        /* Register Submit Button Style */
        .sr-submit-btn {
            background-color: #0F2942 !important;
            border: 1px solid #0F2942 !important;
            border-radius: 8px !important;
            height: 44px !important;
            font-size: 0.875rem !important;
            font-weight: 600 !important;
            color: #FFFFFF !important;
            box-shadow: 0 2px 4px rgba(15, 41, 66, 0.15);
            transition: background-color 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease, transform 0.1s ease !important;
        }

        .sr-submit-btn:hover {
            background-color: #173B57 !important;
            border-color: #173B57 !important;
            color: #FFFFFF !important;
            transform: translateY(-1px);
        }

        .sr-submit-btn:active {
            transform: translateY(0);
        }

        .sr-submit-btn:focus-visible {
            outline: none !important;
            border-color: #0F2942 !important;
            box-shadow: 0 0 0 3px rgba(15, 41, 66, 0.25) !important;
        }

        /* ===== Desktop Registration Refinements (992px+) ===== */
        @media screen and (min-width: 992px) {
            .sr-form-container {
                max-width: 580px !important;
            }

            .register-card-box {
                padding: 1.5rem 1.75rem;
            }

            /* Campus select overflow fix */
            select#campus.form-select {
                text-overflow: ellipsis;
                white-space: nowrap;
                overflow: hidden;
                padding-right: 2.25rem !important;
                font-size: 0.85rem !important;
            }
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid p-0 vh-100 overflow-hidden">
        <div class="row g-0 auth-register-wrap h-100">

            {{-- Fixed / Sticky Left Sidebar --}}
            <div class="col-lg-6 d-none d-lg-flex flex-column justify-content-between text-white p-5 position-relative overflow-hidden h-100 position-lg-sticky top-0"
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
                    <p class="text-white-50 small mx-auto mb-4"
                        style="max-width: 400px; line-height: 1.6; font-size: 0.875rem;">Create your account to apply for
                        financial assistance, connect with sponsors, and track your SLE-FHE status.</p>
                </div>

                <div></div>
            </div>

            {{-- Scrollable Right Content Panel --}}
            <div class="col-lg-6 d-flex align-items-start justify-content-center px-0 px-md-4 px-xl-5 py-5 h-100 overflow-y-auto">
                <div class="w-100 px-3 px-sm-4 py-4 sr-form-container" style="max-width: 580px;">

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
                        <span class="badge bg-white text-primary border mb-2 px-3 py-1 rounded-pill fw-semibold shadow-sm"
                            style="font-size: 0.72rem; color: #0F2537 !important; border-color: #cbd5e1 !important;">SLE-FHE
                            STUDENT ACCESS</span>
                        <h2 class="fw-bold mb-1" style="color: #0f172a; font-size: 1.75rem;">Create your account</h2>
                        <p class="text-secondary small mb-0" style="font-size: 0.8125rem; line-height: 1.45;">Use your institutional DOrSU
                            email. SLE-FHE status remains pending until verified by FASSG.</p>
                    </div>

                    @if ($errors->any())
                        <div class="alert alert-danger p-3 rounded-3 small mb-4 shadow-sm border-0">
                            <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1" aria-hidden="true"></i> Please correct
                                the following errors:</div>
                            <ul class="mb-0 ps-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('register.store') }}">
                        @csrf

                        {{-- Section 1: Account Information --}}
                        <div class="register-card-box">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <span class="section-badge">1</span>
                                <h6 class="fw-bold mb-0 text-dark" style="font-size: 0.95rem;">Account Information</h6>
                            </div>

                            {{-- Row 1: First Name & Middle Name --}}
                            <div class="row g-3 mb-3">
                                <div class="col-sm-6">
                                    <label for="first_name" class="form-label small fw-semibold text-secondary mb-1">First Name</label>
                                    <input type="text" id="first_name" name="first_name"
                                        class="form-control form-control-md bg-light border-1 sr-input-standard"
                                        value="{{ old('first_name') }}" placeholder="Enter first name" required autofocus
                                        style="font-size: 0.875rem; border-radius: 8px;">
                                </div>
                                <div class="col-sm-6">
                                    <label for="middle_name" class="form-label small fw-semibold text-secondary mb-1">
                                        Middle Name <span class="fw-normal text-muted" style="font-size: 0.75rem;">(optional)</span>
                                    </label>
                                    <input type="text" id="middle_name" name="middle_name"
                                        class="form-control form-control-md bg-light border-1 sr-input-standard"
                                        value="{{ old('middle_name') }}" placeholder="Enter middle name"
                                        style="font-size: 0.875rem; border-radius: 8px;">
                                </div>
                            </div>

                            {{-- Row 2: Last Name & Extension Name --}}
                            <div class="row g-3 mb-3">
                                <div class="col-sm-6">
                                    <label for="last_name" class="form-label small fw-semibold text-secondary mb-1">Last Name</label>
                                    <input type="text" id="last_name" name="last_name"
                                        class="form-control form-control-md bg-light border-1 sr-input-standard"
                                        value="{{ old('last_name') }}" placeholder="Enter last name" required
                                        style="font-size: 0.875rem; border-radius: 8px;">
                                </div>
                                <div class="col-sm-6">
                                    <label for="extension_name" class="form-label small fw-semibold text-secondary mb-1">
                                        Ext. Name <span class="fw-normal text-muted" style="font-size: 0.75rem;">(optional)</span>
                                    </label>
                                    <input type="text" id="extension_name" name="extension_name"
                                        class="form-control form-control-md bg-light border-1 sr-input-standard"
                                        value="{{ old('extension_name') }}" placeholder="Jr., III"
                                        maxlength="10"
                                        style="font-size: 0.875rem; border-radius: 8px;">
                                </div>
                            </div>

                            {{-- Row 3: DOrSU Institutional Email (Full Width for Long Institutional Addresses) --}}
                            <div class="row g-3 mb-3">
                                <div class="col-12">
                                    <label for="email" class="form-label small fw-semibold text-secondary mb-1">DOrSU Institutional Email</label>
                                    <input type="email" id="email" name="email"
                                        class="form-control form-control-md bg-light border-1 sr-input-standard" value="{{ old('email') }}"
                                        placeholder="name@dorsu.edu.ph" required
                                        style="font-size: 0.875rem; border-radius: 8px;">
                                </div>
                            </div>

                            {{-- Row 4: Sex / Gender & Contact Number (Rebalanced Pair) --}}
                            <div class="row g-3 mb-3">
                                <div class="col-sm-6">
                                    <label for="gender" class="form-label small fw-semibold text-secondary mb-1">Sex / Gender</label>
                                    <select id="gender" name="gender"
                                        class="form-select form-select-md bg-light border-1 sr-input-standard @error('gender') is-invalid @enderror"
                                        required style="font-size: 0.875rem; border-radius: 8px;">
                                        <option value="" disabled selected>Select Sex/Gender</option>
                                        <option value="Male" @selected(old('gender') == 'Male')>Male</option>
                                        <option value="Female" @selected(old('gender') == 'Female')>Female</option>
                                    </select>
                                    @error('gender')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-sm-6">
                                    <label for="contact_number"
                                        class="form-label small fw-semibold text-secondary mb-1">Contact Number</label>
                                    <input type="tel" id="contact_number" name="contact_number"
                                        class="form-control form-control-md bg-light border-1 sr-input-standard @error('contact_number') is-invalid @enderror"
                                        value="{{ old('contact_number') }}" placeholder="09123456789" required
                                        pattern="09[0-9]{9}" maxlength="11" inputmode="numeric" autocomplete="tel"
                                        data-mask-contact-number title="Format: 09123456789"
                                        style="font-size: 0.875rem; border-radius: 8px;">
                                    <div class="form-text mt-1" style="font-size: 0.72rem;">Format: 09123456789</div>
                                    @error('contact_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            {{-- Row 5: Passwords & Password Policy --}}
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <label for="password" class="form-label small fw-semibold text-secondary mb-1">Password</label>
                                    <div class="input-group sr-password-group">
                                        <input type="password" id="password" name="password"
                                            class="form-control" required
                                            autocomplete="new-password" minlength="8"
                                            placeholder="Min. 8 characters"
                                            style="font-size: 0.875rem;">
                                        <button type="button"
                                            onclick="togglePasswordVisibility('password', this)"
                                            class="sr-password-toggle"
                                            aria-label="Show password"
                                            title="Show password">
                                            <i class="bi bi-eye" style="pointer-events: none;" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                    {{-- Enforced Password Policy Helper Text --}}
                                    <div class="form-text mt-1.5 d-flex align-items-start gap-1.5 text-muted" style="font-size: 0.73rem; line-height: 1.4; color: #64748B !important;">
                                        <i class="bi bi-info-circle flex-shrink-0 mt-0.5" style="font-size: 0.75rem;" aria-hidden="true"></i>
                                        <span>Password must contain at least 8 characters, including uppercase and lowercase letters, numbers, and symbols.</span>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <label for="password_confirmation"
                                        class="form-label small fw-semibold text-secondary mb-1">Confirm Password</label>
                                    <div class="input-group sr-password-group">
                                        <input type="password" id="password_confirmation" name="password_confirmation"
                                            class="form-control" required
                                            autocomplete="new-password"
                                            placeholder="Re-enter password"
                                            style="font-size: 0.875rem;">
                                        <button type="button"
                                            onclick="togglePasswordVisibility('password_confirmation', this)"
                                            class="sr-password-toggle"
                                            aria-label="Show password"
                                            title="Show password">
                                            <i class="bi bi-eye" style="pointer-events: none;" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Section 2: Academic Details --}}
                        <div class="register-card-box">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <span class="section-badge">2</span>
                                <h6 class="fw-bold mb-0 text-dark" style="font-size: 0.95rem;">Academic Details</h6>
                            </div>

                            {{-- Row 1: Student ID Number & Campus --}}
                            <div class="row g-3 mb-3">
                                <div class="col-sm-6">
                                    <label for="student_id_number"
                                        class="form-label small fw-semibold text-secondary mb-1">Student ID Number</label>
                                    <input type="text" id="student_id_number" name="student_id_number"
                                        class="form-control form-control-md bg-light border-1 sr-input-standard @error('student_id_number') is-invalid @enderror"
                                        value="{{ old('student_id_number') }}" placeholder="2024-0001" required
                                        pattern="[0-9]{4}-[0-9]{4}" maxlength="10" inputmode="numeric"
                                        autocomplete="off" data-mask-student-id
                                        title="Format: 2024-0001 (4 digits, hyphen, 4 digits)"
                                        style="font-size: 0.875rem; border-radius: 8px;">
                                    <div class="form-text mt-1" style="font-size: 0.72rem;">Format: 2024-0001</div>
                                    @error('student_id_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-sm-6">
                                    <label for="campus"
                                        class="form-label small fw-semibold text-secondary mb-1">Campus</label>
                                    <select id="campus" name="campus"
                                        class="form-select form-select-md bg-light border-1 sr-input-standard @error('campus') is-invalid @enderror"
                                        required style="font-size: 0.875rem; border-radius: 8px;">
                                        <option value="" disabled selected>-- Select Campus --</option>
                                        @foreach (['Main Campus (City of Mati)', 'Baganga Campus', 'Banaybanay Campus', 'Cateel Campus', 'San Isidro Campus', 'Tarragona Campus'] as $campusOption)
                                            <option value="{{ $campusOption }}" @selected(old('campus') === $campusOption)>
                                                {{ $campusOption }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('campus')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            {{-- Row 2: Year Level & Birthdate --}}
                            <div class="row g-3 mb-3">
                                <div class="col-sm-6">
                                    <label for="year_level" class="form-label small fw-semibold text-secondary mb-1">Year Level</label>
                                    <select id="year_level" name="year_level"
                                        class="form-select form-select-md bg-light border-1 sr-input-standard" required
                                        style="font-size: 0.875rem; border-radius: 8px;">
                                        <option value="" disabled selected>Select Year Level</option>
                                        <option value="1" @selected(old('year_level') == 1)>1st Year</option>
                                        <option value="2" @selected(old('year_level') == 2)>2nd Year</option>
                                        <option value="3" @selected(old('year_level') == 3)>3rd Year</option>
                                        <option value="4" @selected(old('year_level') == 4)>4th Year</option>
                                        <option value="5" @selected(old('year_level') == 5)>5th Year</option>
                                    </select>
                                </div>
                                <div class="col-sm-6">
                                    <label for="birthdate"
                                        class="form-label small fw-semibold text-secondary mb-1">Birthdate</label>
                                    <input type="date" id="birthdate" name="birthdate"
                                        class="form-control form-control-md bg-light border-1 sr-input-standard"
                                        value="{{ old('birthdate') }}" required
                                        style="font-size: 0.875rem; border-radius: 8px;">
                                </div>
                            </div>

                            {{-- Row 3: Academic Program (Searchable Select, Full Width) --}}
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="academic_program_id"
                                        class="form-label small fw-semibold text-secondary mb-1">Academic Program</label>
                                    <select id="academic_program_id" name="academic_program_id"
                                        class="form-select form-select-md bg-light border-1 @error('academic_program_id') is-invalid @enderror"
                                        required style="font-size: 0.875rem; border-radius: 8px;">
                                        <option value="" selected></option>
                                        @foreach ($programs as $program)
                                            <option value="{{ $program->program_id }}" @selected(old('academic_program_id') == $program->program_id)>
                                                {{ $program->code }} — {{ $program->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('academic_program_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        {{-- Data Privacy Act Consent --}}
                        <label for="privacy_consent" class="d-block mb-3 sr-consent-card" style="cursor: pointer;">
                            <div class="sr-consent-flex">
                                <input class="form-check-input sr-consent-checkbox" type="checkbox" id="privacy_consent" name="privacy_consent"
                                    value="1" @checked(old('privacy_consent')) required>
                                <span class="sr-consent-text">
                                    I certify that all information provided is accurate and consent to FASSG verifying my
                                    SLE-FHE records under the Data Privacy Act.
                                </span>
                            </div>
                            @error('privacy_consent')
                                <div class="text-danger small mt-1.5" style="margin-left: 29px;">{{ $message }}</div>
                            @enderror
                        </label>

                        {{-- Register Submit Button --}}
                        <button type="submit"
                            class="btn w-100 fw-bold shadow-sm mb-3 sr-submit-btn rounded-3"
                            style="font-size: 0.95rem;">
                            <i class="bi bi-person-plus me-1.5" aria-hidden="true"></i> Create account and proceed
                        </button>

                        <div class="text-center pt-2">
                            <p class="small text-secondary mb-0" style="font-size: 0.85rem;">
                                Already have an account?
                                <a href="{{ route('login') }}"
                                    class="fw-bold text-decoration-none ms-1 link-text-hover">Sign In</a>
                            </p>
                        </div>
                    </form>

                    <div class="text-center mt-5 pt-3 text-secondary" style="font-size: 0.72rem;">
                        &copy; {{ date('Y') }} Davao Oriental State University. All rights reserved.<br>
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
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2/dist/js/tom-select.complete.min.js"></script>
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

        document.addEventListener('DOMContentLoaded', function() {
            // Tom Select: searchable academic program dropdown with clean placeholder and typing behavior
            const programSelect = document.getElementById('academic_program_id');
            if (programSelect) {
                const ts = new TomSelect(programSelect, {
                    placeholder: 'Search or select your academic program...',
                    allowEmptyOption: true,
                    maxOptions: null,
                    sortField: { field: 'text', direction: 'asc' },
                    render: {
                        no_results: function(data, escape) {
                            return '<div class="no-results p-2 text-muted small">No academic programs found matching "' + escape(data.input) + '"</div>';
                        }
                    }
                });

                // If no program was preselected, ensure selection is empty so placeholder displays cleanly
                if (!programSelect.value) {
                    ts.clear(true);
                }

                // When user starts typing, clear any previously selected item so placeholder and query never overlap
                ts.control_input.addEventListener('input', function() {
                    if (ts.control_input.value.trim().length > 0 && ts.items.length > 0) {
                        ts.clear(true);
                    }
                });
            }

            // Strict Student ID masking: allow up to 4 digits, auto-insert hyphen, then up to 4 digits
            document.querySelectorAll('[data-mask-student-id]').forEach(function (input) {
                const maskStudentId = function () {
                    const digits = input.value.replace(/\D/g, '').slice(0, 8);
                    input.value = digits.length > 4
                        ? digits.slice(0, 4) + '-' + digits.slice(4)
                        : digits;
                };
                input.addEventListener('input', maskStudentId);
            });

            // Contact number masking: strip to the first 11 digits (09123456789)
            document.querySelectorAll('[data-mask-contact-number]').forEach(function (input) {
                input.addEventListener('input', function () {
                    input.value = input.value.replace(/\D/g, '').slice(0, 11);
                });
            });
        });
    </script>
@endpush
