@extends('layouts.app')

@section('title', 'Register')

@push('styles')
    <style>
        /* Text link hover style: Black text, Blue hover */
        .link-text-hover {
            color: #000000;
            transition: color 0.2s ease-in-out;
        }

        .link-text-hover:hover {
            color: #0f294a !important;
            /* Bootstrap primary blue */
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
            background-color: #0f294a;
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

        /* Custom Register Submit Button Style Matching Login */
        .btn-custom-register {
            background-color: #0f294a;
            color: #ffffffff;
            border: 1px solid #cbd5e1;
            transition: all 0.2s ease-in-out;
        }

        .btn-custom-register:hover {
            background-color: #0f294a !important;
            color: #ffffff !important;
            border-color: #0f294a !important;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid p-0 vh-100 overflow-hidden">
        <div class="row g-0 auth-register-wrap h-100">

            <!-- Fixed / Sticky Left Sidebar -->
            <div class="col-lg-6 d-none d-lg-flex flex-column justify-content-between text-white p-5 position-relative overflow-hidden h-100 position-lg-sticky top-0"
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
                    <p class="text-white-50 small mx-auto mb-4"
                        style="max-width: 400px; line-height: 1.6; font-size: 0.875rem;">Create your account to apply for
                        financial assistance, connect with sponsors, and track your SLE-FHE status.</p>
                </div>

                <div></div>
            </div>

            <!-- Scrollable Right Content Panel -->
            <div class="col-lg-6 d-flex align-items-start justify-content-center px-0 px-md-5 py-5 h-100 overflow-y-auto">
                <div class="w-100 px-3 px-sm-4 py-4" style="max-width: 560px;">

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
                        <span class="badge bg-white text-primary border mb-2 px-3 py-1.5 rounded-pill fw-semibold shadow-sm"
                            style="font-size: 0.72rem; color: #0f294a !important; border-color: #cbd5e1 !important;">SLE-FHE
                            STUDENT ACCESS</span>
                        <h2 class="fw-bold mb-1" style="color: #0f172a; font-size: 1.75rem;">Create your account</h2>
                        <p class="text-secondary small mb-0" style="font-size: 0.875rem;">Use your institutional DOrSU
                            email. SLE-FHE status remains pending until verified.</p>
                    </div>

                    @if ($errors->any())
                        <div class="alert alert-danger p-3 rounded-3 small mb-4 shadow-sm border-0">
                            <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Please correct
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

                        <!-- Section 1: Account Information -->
                        <div class="register-card-box">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <span class="section-badge">1</span>
                                <h6 class="fw-bold mb-0 text-dark" style="font-size: 0.95rem;">Account Information</h6>
                            </div>

                            <!-- Row 1: First, Middle, and Last Name -->
                            <div class="row g-2.5 mb-3">
                                <div class="col-md-4">
                                    <label for="first_name" class="form-label small fw-semibold text-secondary mb-1">First
                                        Name *</label>
                                    <input type="text" id="first_name" name="first_name"
                                        class="form-control form-control-md bg-light border-1"
                                        value="{{ old('first_name') }}" placeholder="Juan" required autofocus
                                        style="font-size: 0.875rem; border-radius: 8px;">
                                </div>
                                <div class="col-md-4">
                                    <label for="middle_name" class="form-label small fw-semibold text-secondary mb-1">Middle
                                        Name</label>
                                    <input type="text" id="middle_name" name="middle_name"
                                        class="form-control form-control-md bg-light border-1"
                                        value="{{ old('middle_name') }}" placeholder="Carlos"
                                        style="font-size: 0.875rem; border-radius: 8px;">
                                </div>
                                <div class="col-md-4">
                                    <label for="last_name" class="form-label small fw-semibold text-secondary mb-1">Last
                                        Name *</label>
                                    <input type="text" id="last_name" name="last_name"
                                        class="form-control form-control-md bg-light border-1"
                                        value="{{ old('last_name') }}" placeholder="Dela Cruz" required
                                        style="font-size: 0.875rem; border-radius: 8px;">
                                </div>
                            </div>

                            <!-- Row 2: Sex / Gender and Institutional Email -->
                            <div class="row g-2.5 mb-3">
                                <div class="col-md-4">
                                    <label for="gender" class="form-label small fw-semibold text-secondary mb-1">Sex / Gender *</label>
                                    <select id="gender" name="gender"
                                        class="form-select form-select-md bg-light border-1 @error('gender') is-invalid @enderror"
                                        required style="font-size: 0.875rem; border-radius: 8px;">
                                        <option value="" disabled selected>Select Sex</option>
                                        <option value="Male" @selected(old('gender') == 'Male')>Male</option>
                                        <option value="Female" @selected(old('gender') == 'Female')>Female</option>
                                    </select>
                                    @error('gender')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-8">
                                    <label for="email" class="form-label small fw-semibold text-secondary mb-1">DOrSU
                                        Institutional Email *</label>
                                    <input type="email" id="email" name="email"
                                        class="form-control form-control-md bg-light border-1" value="{{ old('email') }}"
                                        placeholder="name@dorsu.edu.ph" required
                                        style="font-size: 0.875rem; border-radius: 8px;">
                                </div>
                            </div>

                            <!-- Row 3: Contact Number -->
                            <div class="row g-2.5 mb-3">
                                <div class="col-md-6">
                                    <label for="contact_number"
                                        class="form-label small fw-semibold text-secondary mb-1">Contact Number *</label>
                                    <input type="tel" id="contact_number" name="contact_number"
                                        class="form-control form-control-md bg-light border-1 @error('contact_number') is-invalid @enderror"
                                        value="{{ old('contact_number') }}" placeholder="09123456789" required
                                        pattern="09[0-9]{9}" maxlength="11" inputmode="numeric" autocomplete="tel"
                                        data-mask-contact-number title="Format: 09123456789"
                                        style="font-size: 0.875rem; border-radius: 8px;">
                                    <div class="form-text" style="font-size: 0.75rem;">Format: 09123456789</div>
                                    @error('contact_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Row 4: Passwords -->
                            <div class="row g-2.5">
                                <div class="col-md-6">
                                    <label for="password" class="form-label small fw-semibold text-secondary mb-1">Password
                                        *</label>
                                    <div class="input-group">
                                        <input type="password" id="password" name="password"
                                            class="form-control form-control-md bg-light border-1 border-end-0" required
                                            autocomplete="new-password" minlength="8"
                                            style="font-size: 0.875rem; border-top-left-radius: 8px; border-bottom-left-radius: 8px;">
                                        <span class="input-group-text bg-light border-1 border-start-0 text-muted"
                                            style="cursor: pointer; border-top-right-radius: 8px; border-bottom-right-radius: 8px;"
                                            id="password-toggle"><i class="bi bi-eye"></i></span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="password_confirmation"
                                        class="form-label small fw-semibold text-secondary mb-1">Confirm Password *</label>
                                    <div class="input-group">
                                        <input type="password" id="password_confirmation" name="password_confirmation"
                                            class="form-control form-control-md bg-light border-1 border-end-0" required
                                            autocomplete="new-password"
                                            style="font-size: 0.875rem; border-top-left-radius: 8px; border-bottom-left-radius: 8px;">
                                        <span class="input-group-text bg-light border-1 border-start-0 text-muted"
                                            style="cursor: pointer; border-top-right-radius: 8px; border-bottom-right-radius: 8px;"
                                            id="password-confirm-toggle"><i class="bi bi-eye"></i></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section 2: Academic Details -->
                        <div class="register-card-box">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <span class="section-badge">2</span>
                                <h6 class="fw-bold mb-0 text-dark" style="font-size: 0.95rem;">Academic Details</h6>
                            </div>

                            <div class="row g-2.5 mb-3">
                                <div class="col-md-4">
                                    <label for="student_id_number"
                                        class="form-label small fw-semibold text-secondary mb-1">Student ID Number
                                        *</label>
                                    <input type="text" id="student_id_number" name="student_id_number"
                                        class="form-control form-control-md bg-light border-1 @error('student_id_number') is-invalid @enderror"
                                        value="{{ old('student_id_number') }}" placeholder="2024-0001" required
                                        pattern="[0-9]{4}-[0-9]{4}" maxlength="10" inputmode="numeric"
                                        autocomplete="off" data-mask-student-id
                                        title="Format: 2024-0001 (4 digits, hyphen, 4 digits)"
                                        style="font-size: 0.875rem; border-radius: 8px;">
                                    <div class="form-text" style="font-size: 0.75rem;">Format: 2024-0001</div>
                                    @error('student_id_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-4">
                                    <label for="campus"
                                        class="form-label small fw-semibold text-secondary mb-1">Campus *</label>
                                    <select id="campus" name="campus"
                                        class="form-select form-select-md bg-light border-1 @error('campus') is-invalid @enderror"
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
                                <div class="col-md-4">
                                    <label for="year_level" class="form-label small fw-semibold text-secondary mb-1">Year
                                        Level *</label>
                                    <select id="year_level" name="year_level"
                                        class="form-select form-select-md bg-light border-1" required
                                        style="font-size: 0.875rem; border-radius: 8px;">
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
                                    <label for="academic_program_id"
                                        class="form-label small fw-semibold text-secondary mb-1">Academic Program / Course
                                        *</label>
                                    <select id="academic_program_id" name="academic_program_id"
                                        class="form-select form-select-md bg-light border-1 @error('academic_program_id') is-invalid @enderror"
                                        required style="font-size: 0.875rem; border-radius: 8px;">
                                        <option value="" disabled selected>-- Select Your Course/Program --</option>
                                        @foreach ($programs as $program)
                                            <option value="{{ $program->program_id }}" @selected(old('academic_program_id') == $program->program_id)>
                                                {{ $program->code }} — {{ $program->short_name ?: $program->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('academic_program_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-5">
                                    <label for="birthdate"
                                        class="form-label small fw-semibold text-secondary mb-1">Birthdate *</label>
                                    <input type="date" id="birthdate" name="birthdate"
                                        class="form-control form-control-md bg-light border-1"
                                        value="{{ old('birthdate') }}" required
                                        style="font-size: 0.875rem; border-radius: 8px;">
                                </div>
                            </div>
                        </div>

                        <!-- Section 3: Address & Rurality -->
                        <div class="register-card-box">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <span class="section-badge">3</span>
                                <h6 class="fw-bold mb-0 text-dark" style="font-size: 0.95rem;">Address &amp; Rurality</h6>
                            </div>

                            <div class="row g-2.5 mb-3">
                                <div class="col-md-6">
                                    <label for="province"
                                        class="form-label small fw-semibold text-secondary mb-1">Province *</label>
                                    <select id="province" name="province"
                                        class="form-select form-select-md bg-light border-1 @error('province') is-invalid @enderror"
                                        required style="font-size: 0.875rem; border-radius: 8px;">
                                        <option value="" disabled selected>-- Select Province --</option>
                                    </select>
                                    @error('province')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="municipality"
                                        class="form-label small fw-semibold text-secondary mb-1">Municipality / City *</label>
                                    <select id="municipality" name="municipality"
                                        class="form-select form-select-md bg-light border-1 @error('municipality') is-invalid @enderror"
                                        required style="font-size: 0.875rem; border-radius: 8px;">
                                        <option value="" disabled selected>-- Select Municipality / City --</option>
                                    </select>
                                    @error('municipality')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="row g-2.5 mb-3">
                                <div class="col-md-6">
                                    <label for="barangay"
                                        class="form-label small fw-semibold text-secondary mb-1">Barangay *</label>
                                    <input type="text" id="barangay" name="barangay"
                                        class="form-control form-control-md bg-light border-1 @error('barangay') is-invalid @enderror"
                                        value="{{ old('barangay') }}" placeholder="Barangay" required
                                        style="font-size: 0.875rem; border-radius: 8px;">
                                    @error('barangay')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="home_address"
                                        class="form-label small fw-semibold text-secondary mb-1">Street / Purok / House
                                        No. *</label>
                                    <input type="text" id="home_address" name="home_address"
                                        class="form-control form-control-md bg-light border-1 @error('home_address') is-invalid @enderror"
                                        value="{{ old('home_address') }}" placeholder="Street / Purok / House No."
                                        required style="font-size: 0.875rem; border-radius: 8px;">
                                    @error('home_address')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="small text-secondary d-flex align-items-center gap-1"
                                style="font-size: 0.8rem;">
                                <i class="bi bi-geo-alt-fill"></i>
                                Your residence is automatically classified as rural or urban for FASSG eligibility.
                            </div>
                        </div>

                        <!-- Data Privacy Act Consent -->
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="privacy_consent" name="privacy_consent"
                                value="1" @checked(old('privacy_consent')) required style="cursor: pointer;">
                            <label class="form-check-label small text-secondary fw-medium" for="privacy_consent"
                                style="cursor: pointer; font-size: 0.825rem;">
                                I certify that all information provided is accurate and consent to FASSG verifying my
                                SLE-FHE records under the Data Privacy Act.
                            </label>
                            @error('privacy_consent')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Register Submit Button -->
                        <button type="submit"
                            class="btn w-100 py-2.5 fw-bold shadow-sm mb-3 btn-custom-register rounded-3"
                            style="border-radius: 10px; font-size: 0.95rem;">
                            <i class="bi bi-person-plus me-1.5"></i> Create account and proceed
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
        document.addEventListener('DOMContentLoaded', function() {
            function setupPasswordToggle(toggleId, inputId) {
                const toggle = document.getElementById(toggleId);
                const input = document.getElementById(inputId);
                if (toggle && input) {
                    toggle.addEventListener('click', function() {
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

            // Province -> Municipality address chain (from localaddress lookup API)
            const provinceSelect = document.getElementById('province');
            const municipalitySelect = document.getElementById('municipality');
            if (provinceSelect && municipalitySelect) {
                const DEFAULT_PROVINCE = 'Davao Oriental';
                const OLD_PROVINCE = @json(old('province'));
                const OLD_MUNICIPALITY = @json(old('municipality'));
                const ENDPOINT = @json(route('api.address.lookup'));

                const fetchJSON = function (url) {
                    return fetch(url, { headers: { 'Accept': 'application/json' } })
                        .then(function (res) {
                            if (!res.ok) throw new Error('HTTP ' + res.status);
                            return res.json();
                        });
                };

                const loadMunicipalities = function () {
                    const province = provinceSelect.value;
                    municipalitySelect.innerHTML = '<option value="" disabled selected>-- Select Municipality / City --</option>';
                    municipalitySelect.disabled = province ? false : true;
                    if (!province) return;
                    return fetchJSON(ENDPOINT + '?type=municipalities&province=' + encodeURIComponent(province))
                        .then(function (cities) {
                            cities.forEach(function (city) {
                                const opt = document.createElement('option');
                                opt.value = city;
                                opt.textContent = city;
                                municipalitySelect.appendChild(opt);
                            });
                            if (OLD_MUNICIPALITY && cities.indexOf(OLD_MUNICIPALITY) !== -1) {
                                municipalitySelect.value = OLD_MUNICIPALITY;
                            }
                        });
                };

                const loadProvinces = function () {
                    return fetchJSON(ENDPOINT + '?type=provinces')
                        .then(function (provinces) {
                            provinces.forEach(function (p) {
                                const opt = document.createElement('option');
                                opt.value = p;
                                opt.textContent = p;
                                provinceSelect.appendChild(opt);
                            });
                            provinceSelect.value = OLD_PROVINCE && provinces.indexOf(OLD_PROVINCE) !== -1
                                ? OLD_PROVINCE
                                : (provinces.indexOf(DEFAULT_PROVINCE) !== -1 ? DEFAULT_PROVINCE : provinces[0] || '');
                            return loadMunicipalities();
                        })
                        .catch(function () {
                            /* leave selects empty; native 'required' will block submission */
                        });
                };

                provinceSelect.addEventListener('change', function () {
                    municipalitySelect.disabled = true;
                    loadMunicipalities();
                });

                loadProvinces();
            }
        });
    </script>
@endpush