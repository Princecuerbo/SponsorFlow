@extends('layouts.app')

@section('title', 'New Application')
@section('eyebrow', 'Student Portal · ' . $program->program_name)
@section('page-title', 'Apply for Sponsorship')

@push('styles')
    <style>
        /* ===== Step Number Badges ===== */
        .sf-step-badge {
            background-color: #0F2942 !important;
            color: #ffffff !important;
            width: 28px;
            height: 28px;
            font-size: 0.8rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        /* ===== Primary Navy Submit Button ===== */
        .btn-navy-submit,
        button.btn-navy-submit {
            background-color: #0F2942 !important;
            border-color: #0F2942 !important;
            color: #ffffff !important;
            font-weight: 600;
            height: 42px;
            border-radius: 8px;
            box-shadow: none !important;
            transition: all 0.2s ease-in-out;
        }

        .btn-navy-submit:hover,
        .btn-navy-submit:focus-visible,
        button.btn-navy-submit:hover,
        button.btn-navy-submit:focus-visible {
            background-color: #0A1E31 !important;
            border-color: #0A1E31 !important;
            color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(15, 41, 66, 0.2) !important;
            transform: translateY(-1px);
        }

        .btn-navy-submit:active,
        button.btn-navy-submit:active {
            transform: translateY(0);
        }

        .btn-navy-submit:disabled,
        button.btn-navy-submit:disabled {
            background-color: #e2e8f0 !important;
            border-color: #e2e8f0 !important;
            color: #94a3b8 !important;
            cursor: not-allowed !important;
            opacity: 1 !important;
            transform: none !important;
        }

        .btn-navy-submit:focus-visible,
        button.btn-navy-submit:focus-visible {
            outline: none;
            box-shadow: 0 0 0 3px rgba(15, 41, 66, 0.25) !important;
        }

        /* ===== Cancel Button (SponsorFlow convention) ===== */
        .btn-app-cancel {
            height: 42px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.875rem;
            background-color: #ffffff !important;
            color: #475569 !important;
            border: 1px solid #cbd5e1 !important;
            transition: all 0.2s ease-in-out;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
        }

        .btn-app-cancel:hover {
            background-color: #dc2626 !important;
            border-color: #dc2626 !important;
            color: #ffffff !important;
            transform: translateY(-1px);
            box-shadow: 0 2px 6px rgba(220, 38, 38, 0.25);
        }

        .btn-app-cancel:focus-visible {
            outline: none !important;
            border-color: #dc2626 !important;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.2) !important;
        }

        .btn-app-cancel:active {
            transform: translateY(0);
        }

        /* ===== Document Upload Cards ===== */
        .sf-upload-card {
            border: 1.5px dashed #cbd5e1;
            border-radius: 10px;
            background-color: #f8fafc;
            padding: 1.25rem 1rem;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 155px;
            position: relative;
        }

        .sf-upload-card:hover {
            border-color: #93c5fd;
            background-color: #eff6ff;
        }

        .sf-upload-card:focus-within {
            border-color: #0F2942;
            box-shadow: 0 0 0 3px rgba(15, 41, 66, 0.12);
        }

        .sf-upload-card.has-file {
            border-style: solid;
            border-color: #0F2942;
            background-color: #f0f7ff;
        }

        .sf-upload-card.has-file:hover {
            background-color: #e0f0ff;
        }

        .sf-upload-card.has-error {
            border-color: #dc2626;
            background-color: #fef2f2;
        }

        .sf-upload-icon {
            font-size: 1.75rem;
            color: #94a3b8;
            margin-bottom: 0.5rem;
            line-height: 1;
        }

        .sf-upload-card.has-file .sf-upload-icon {
            color: #0F2942;
        }

        .sf-upload-label {
            font-size: 0.8125rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 0.25rem;
        }

        .sf-upload-action {
            font-size: 0.75rem;
            color: #64748b;
        }

        .sf-upload-card.has-file .sf-upload-action {
            color: #0F2942;
            font-weight: 500;
        }

        /* ===== Filename Display — Robust Truncation ===== */
        .sf-upload-filename {
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            overflow: hidden;
            overflow-wrap: anywhere;
            word-break: break-word;
            max-width: 100%;
            font-size: 0.75rem;
            color: #334155;
            font-weight: 500;
            line-height: 1.4;
            margin-top: 0.35rem;
            padding: 0 0.25rem;
        }

        .sf-upload-format {
            font-size: 0.6875rem;
            color: #94a3b8;
            margin-top: 0.35rem;
        }

        .sf-upload-required {
            position: absolute;
            top: 8px;
            right: 10px;
            font-size: 0.625rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #dc2626;
        }

        /* ===== Program Summary (Sidebar) ===== */
        .sf-summary-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 0.5rem 0;
            border-bottom: 1px solid #f1f5f9;
            gap: 0.75rem;
        }

        .sf-summary-row:last-child {
            border-bottom: none;
        }

        .sf-summary-label {
            color: #64748b;
            font-size: 0.8125rem;
            flex-shrink: 0;
        }

        .sf-summary-value {
            font-weight: 600;
            font-size: 0.8125rem;
            color: #0f172a;
            text-align: right;
        }

        .sf-submission-note {
            font-size: 0.75rem;
            color: #64748b;
            line-height: 1.5;
            margin-top: 0.75rem;
        }

        /* ===== Checkbox Accent ===== */
        .form-check-input:checked {
            background-color: #0F2942 !important;
            border-color: #0F2942 !important;
        }

        /* ===== Readonly field styling ===== */
        .sf-readonly-field {
            background-color: #f8fafc !important;
            border-color: #e2e8f0 !important;
            color: #334155 !important;
            cursor: default;
        }
    </style>
@endpush

@section('content')

    @php
        $eligibleCourses = $program->academicPrograms ?? collect();

        $requiresInstitutionalVerification = $program->category?->value === 'Employee-Based'
            || (bool) $program->requires_relative_verification;

        $addressReqLower = strtolower((string) ($program->address_requirement ?? ''));
        $programRequiresRural = str_contains($addressReqLower, 'rural');
        $programRequiresUrban = str_contains($addressReqLower, 'urban');
        $hasAddressRequirement = $programRequiresRural || $programRequiresUrban
            || filled($program->target_province) || filled($program->target_municipality);

        $allowedCampuses = (array) ($program->eligible_campuses ?? []);
        $allowedCampusesNote = $allowedCampuses ? ' (' . implode(', ', $allowedCampuses) . ')' : '';
        $campusRestricted = $allowedCampuses !== []
            && ! in_array($profile->campus, $allowedCampuses, true);
        $submissionLocked = $campusRestricted;

        $requiredDocuments = (array) ($program->required_documents ?? []);
        $documentFieldMap = [
            'Report Card / Certificate of Grades' => ['field' => 'grade_slip', 'label' => 'Grade Slip / TOR', 'icon' => 'bi-mortarboard'],
            'Certificate of Indigency' => ['field' => 'indigency_doc', 'label' => 'Certificate of Indigency', 'icon' => 'bi-file-earmark-medical'],
            'Certificate of Registration (COR)' => ['field' => 'cor_doc', 'label' => 'COR', 'icon' => 'bi-file-earmark-text'],
            'Proof of Residence / Barangay Cert' => ['field' => 'proof_of_residence', 'label' => 'Barangay / Residency Cert', 'icon' => 'bi-house-door'],
            'Employee ID / Proof of Kinship' => ['field' => 'employee_id_doc', 'label' => 'Employee ID / Proof of Kinship', 'icon' => 'bi-person-badge'],
        ];
        $docsToUpload = array_filter(
            $documentFieldMap,
            fn ($cfg, $label) => in_array($label, $requiredDocuments, true),
            ARRAY_FILTER_USE_BOTH,
        );

        // Document labels for clearer display
        $docDisplayLabels = [
            'grade_slip' => 'Grade Slip / Transcript of Records',
            'indigency_doc' => 'Certificate of Indigency',
            'cor_doc' => 'Certificate of Registration (COR)',
            'proof_of_residence' => 'Barangay / Residency Certificate',
            'employee_id_doc' => 'Employee ID / Proof of Kinship',
        ];

        // Document icons (outline versions)
        $docDisplayIcons = [
            'grade_slip' => 'bi-file-earmark-text',
            'indigency_doc' => 'bi-file-earmark-check',
            'cor_doc' => 'bi-file-earmark-text',
            'proof_of_residence' => 'bi-house',
            'employee_id_doc' => 'bi-person-vcard',
        ];
    @endphp

    @if ($profile->hasActiveSponsorship())
        <div class="alert alert-danger border-0 shadow-sm rounded-3">
            <i class="bi bi-exclamation-octagon-fill me-1"></i>
            You already have an active sponsorship. Only one active sponsorship is allowed at a time — you may re-apply once
            it expires.
            <a href="{{ route('student.applications.index') }}" class="alert-link">View my applications</a>
        </div>
    @else
        {{-- ─── Campus Restriction Warning ─────────────────────────────────────── --}}
        @if ($campusRestricted)
            <div class="alert border-0 rounded-3 shadow-sm d-flex gap-3 align-items-start mb-4"
                 id="campus-restriction-alert"
                 role="alert"
                 style="background: linear-gradient(135deg,#fff7ed 0%,#fef3c7 100%); border-left: 4px solid #f59e0b !important;">
                <i class="bi bi-geo-alt-fill fs-4 flex-shrink-0" style="color:#d97706;margin-top:2px;"></i>
                <div>
                    <p class="fw-semibold mb-1" style="color:#92400e;">Notice: This program is restricted to specific
                        campuses.</p>
                    <p class="small mb-0" style="color:#78350f;">
                        Your registered campus
                        (<strong>{{ $profile->campus ?? 'Not Assigned' }}</strong>) is not in this program's eligible
                        campus list{{ $allowedCampusesNote }}. Form submission
                        has been locked accordingly. If you have transferred campuses, please
                        <a href="{{ route('student.verification.show') }}" class="alert-link fw-semibold">update your
                            profile</a> before applying.
                    </p>
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('student.applications.store') }}" enctype="multipart/form-data" class="row g-4"
              data-address-requirement="{{ $program->address_requirement }}"
              data-campus-restricted="{{ $campusRestricted ? '1' : '0' }}"
              @if ($submissionLocked) onsubmit="return false;" @endif>
            @csrf
            <input type="hidden" name="sponsorship_program_id" value="{{ $program->id }}">
            <input type="hidden" name="address_submitted" value="{{ $profile->full_address }}">
            <input type="hidden" name="is_rural_submitted" value="{{ $programRequiresRural ? 1 : 0 }}">

            <div class="col-lg-8">

                {{-- Application-level validation errors (eligibility, rural mismatch, etc.) --}}
                @if ($errors->has('application'))
                    <div class="alert border-0 rounded-3 shadow-sm d-flex gap-3 align-items-start mb-4"
                         role="alert"
                         style="background:linear-gradient(135deg,#fef2f2 0%,#fee2e2 100%);border-left:4px solid #dc2626 !important;">
                        <i class="bi bi-x-octagon-fill fs-4 flex-shrink-0" style="color:#dc2626;margin-top:2px;"></i>
                        <div class="w-100">
                            <p class="fw-semibold mb-1" style="color:#7f1d1d;">Application Cannot Be Submitted</p>
                            <ul class="small mb-0 ps-3" style="color:#991b1b;">
                                @foreach ((array) $errors->get('application') as $msg)
                                    <li>{{ $msg }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                {{-- Section 1: Profile summary --}}
                <div class="card sf-card mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="badge rounded-circle sf-step-badge">1</span>
                            <h3 class="h6 sf-heading mb-0">Student &amp; SLE-FHE Profile</h3>
                        </div>
                        <p class="small text-secondary mb-3">Pulled from your verified profile. To change any of this,
                            update your profile before applying.</p>

                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label small text-secondary">Student ID Number</label>
                                <input type="text" class="form-control sf-mono sf-readonly-field" value="{{ $profile->student_id_number }}"
                                    disabled aria-label="Student ID Number (read-only)">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label small text-secondary">Full Name</label>
                                <input type="text" class="form-control sf-readonly-field" value="{{ $user?->name ?? auth()->user()?->name ?? '—' }}" disabled aria-label="Full Name (read-only)">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label small text-secondary">Academic Program</label>
                                <input type="text" class="form-control sf-readonly-field" value="{{ $profile->display_course }}" disabled aria-label="Academic Program (read-only)">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label small text-secondary">Campus</label>
                                <input type="text" class="form-control sf-readonly-field" value="{{ $profile->campus ?? 'Not Assigned' }}"
                                    disabled aria-label="Campus (read-only)">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label small text-secondary">SLE-FHE Status</label>
                                <div class="pt-2"><x-status-badge :status="$profile->sle_fhe_status" /></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Section 2: Encoded inputs --}}
                <div class="card sf-card mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="badge rounded-circle sf-step-badge">2</span>
                            <h3 class="h6 sf-heading mb-0">Application Details</h3>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="current_gpa" class="form-label small text-secondary">Current GPA <span
                                        class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="1" max="5" name="current_gpa"
                                    id="current_gpa" value="{{ old('current_gpa', old('gpa_submitted')) }}"
                                    class="form-control @error('current_gpa') is-invalid @enderror" required>
                                @if ($program->min_gpa)
                                    <div class="form-text">Program requires {{ number_format($program->min_gpa, 2) }} or
                                        better.</div>
                                @endif
                                @error('current_gpa')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-secondary">Current Address</label>
                                <input type="text" class="form-control sf-readonly-field" value="{{ $profile->full_address ?: '—' }}" disabled aria-label="Current Address (read-only)">
                                <div class="form-text">Verified residential address from your SLE-FHE profile.</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Section 3: Institutional Employee Verification --}}
                @if ($requiresInstitutionalVerification)
                    <div class="card sf-card mb-4">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <span class="badge rounded-circle sf-step-badge">
                                    <i class="bi bi-person-badge fs-6"></i>
                                </span>
                                <h3 class="h6 sf-heading mb-0">Institutional Employee Verification</h3>
                            </div>
                            <p class="small text-secondary mb-3">This program requires verification of an immediate
                                relative who is an employee of the institution.</p>

                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <label for="employee_name" class="form-label small text-secondary">Relative Employee
                                        Name <span class="text-danger">*</span></label>
                                    <input type="text" name="employee_name" id="employee_name" required
                                        placeholder="e.g., Juan Dela Cruz"
                                        value="{{ old('employee_name') }}"
                                        class="form-control @error('employee_name') is-invalid @enderror">
                                    @error('employee_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-sm-6">
                                    <label for="employee_id_number" class="form-label small text-secondary">Employee ID
                                        Number <span class="text-danger">*</span></label>
                                    <input type="text" name="employee_id_number" id="employee_id_number" required
                                        placeholder="e.g., EMP-2024-0012"
                                        value="{{ old('employee_id_number') }}"
                                        class="form-control @error('employee_id_number') is-invalid @enderror">
                                    @error('employee_id_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-sm-6">
                                    <label for="employee_relationship" class="form-label small text-secondary">Relationship
                                        to Employee <span class="text-danger">*</span></label>
                                    <select name="employee_relationship" id="employee_relationship" required
                                        class="form-select @error('employee_relationship') is-invalid @enderror">
                                        <option value="">Select relationship…</option>
                                        @foreach (['Parent', 'Spouse', 'Sibling', 'Guardian'] as $rel)
                                            <option value="{{ $rel }}" @selected(old('employee_relationship') === $rel)>
                                                {{ $rel }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('employee_relationship')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Section 3: Document uploads --}}
                <div class="card sf-card">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="badge rounded-circle sf-step-badge">3</span>
                            <h3 class="h6 sf-heading mb-0">Supporting Documents</h3>
                        </div>
                        <p class="small text-secondary mb-3">Accepted formats: PDF, JPG, PNG · Max 5MB each.</p>

                        @if ($docsToUpload === [])
                            <div class="alert alert-secondary border-0 rounded-3 mb-0">
                                <i class="bi bi-info-circle me-1"></i>
                                No supporting documents are required for this program.
                            </div>
                        @else
                            <div class="row g-3">
                                @foreach ($docsToUpload as $cfg)
                                    @php
                                        $fieldName = $cfg['field'];
                                        $displayLabel = $docDisplayLabels[$fieldName] ?? $cfg['label'];
                                        $displayIcon = $docDisplayIcons[$fieldName] ?? $cfg['icon'];
                                        $hasError = $errors->has($fieldName);
                                    @endphp
                                    <div class="col-md-4">
                                        <label for="{{ $fieldName }}"
                                            class="sf-upload-card d-block {{ $hasError ? 'has-error' : '' }}"
                                            id="upload-card-{{ $fieldName }}"
                                            role="button"
                                            tabindex="0"
                                            aria-label="Upload {{ $displayLabel }}"
                                            onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();document.getElementById('{{ $fieldName }}').click();}">
                                            <span class="sf-upload-required">Required</span>
                                            <i class="bi {{ $displayIcon }} sf-upload-icon" aria-hidden="true"></i>
                                            <span class="sf-upload-label">{{ $displayLabel }}</span>
                                            <span class="sf-upload-action" id="upload-action-{{ $fieldName }}">
                                                <i class="bi bi-upload me-1" aria-hidden="true"></i>Click to upload
                                            </span>
                                            <span class="sf-upload-filename d-none" id="upload-filename-{{ $fieldName }}"></span>
                                            <span class="sf-upload-format">PDF, JPG, PNG · Max 5 MB</span>
                                            <input type="file" name="{{ $fieldName }}" id="{{ $fieldName }}"
                                                class="d-none" accept=".pdf,.jpg,.jpeg,.png"
                                                onchange="handleFileSelect(this)"
                                                aria-describedby="upload-error-{{ $fieldName }}">
                                        </label>
                                        @error($fieldName)
                                            <div class="text-danger small mt-1" id="upload-error-{{ $fieldName }}" role="alert">
                                                <i class="bi bi-exclamation-circle me-1" aria-hidden="true"></i>{{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Side summary / submit --}}
            <div class="col-lg-4">
                <div class="card sf-card position-sticky" style="top:5.5rem;">
                    <div class="card-body p-4">
                        <h3 class="h6 sf-heading mb-3">{{ $program->program_name }}</h3>
                        <ul class="list-unstyled small mb-4">
                            <li class="d-flex justify-content-between py-1 border-bottom">
                                <span class="text-secondary">Sponsor</span>
                                <span class="fw-semibold">{{ $program->sponsor?->company_organization_name ?? '—' }}</span>
                            </li>
                            <li class="d-flex justify-content-between py-1 border-bottom">
                                <span class="text-secondary">Category</span>
                                <span class="fw-semibold">{{ $program->category?->value ?? '—' }}</span>
                            </li>
                            <li class="d-flex justify-content-between py-1">
                                <span class="text-secondary">Available slots</span>
                                <span class="fw-semibold">{{ $program->available_slots }}</span>
                            </li>
                        </ul>

                        <button type="submit" class="btn btn-navy-submit w-100 mb-2 d-flex align-items-center justify-content-center gap-2"
                            @if ($submissionLocked) disabled title="Submission locked due to eligibility requirements" @endif>
                            <i class="bi bi-send" aria-hidden="true"></i>
                            <span>Submit Application</span>
                        </button>
                        @if ($campusRestricted)
                            <div class="small text-danger mb-2 fw-semibold">
                                <i class="bi bi-lock-fill me-1" aria-hidden="true"></i>
                                Form submission is locked because this program is restricted to specific campuses.
                            </div>
                        @endif
                        <a href="{{ route('student.programs.index') }}"
                            class="btn btn-app-cancel w-100">
                            <i class="bi bi-x-lg" aria-hidden="true"></i>
                            <span>Cancel</span>
                        </a>

                        <div class="sf-submission-note d-flex align-items-start gap-2">
                            <i class="bi bi-shield-check text-secondary flex-shrink-0 mt-0.5" aria-hidden="true"></i>
                            <span>Submitting sends your application to FASSG for review. Submission does not guarantee a sponsorship slot or approval.</span>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        @push('scripts')
            <script>
                (function () {
                    'use strict';

                    /* ── Robust File Upload & Selection Presentation ──────────────── */
                    window.handleFileSelect = function (input) {
                        const card = document.getElementById(`upload-card-${input.id}`);
                        const actionEl = document.getElementById(`upload-action-${input.id}`);
                        const filenameEl = document.getElementById(`upload-filename-${input.id}`);

                        if (!card || !actionEl || !filenameEl) return;

                        if (input.files && input.files[0]) {
                            const file = input.files[0];
                            const filename = file.name;

                            // Secure text-based DOM insertion
                            filenameEl.textContent = filename;
                            filenameEl.title = filename; // full filename accessible via tooltip
                            filenameEl.classList.remove('d-none');

                            // Update action button text
                            actionEl.innerHTML = '<i class="bi bi-arrow-repeat me-1" aria-hidden="true"></i>Replace File';

                            // Toggle visual card states
                            card.classList.add('has-file');
                            card.classList.remove('has-error');

                            // Outlined success checkmark icon
                            const iconEl = card.querySelector('.sf-upload-icon');
                            if (iconEl) {
                                iconEl.className = 'bi bi-file-earmark-check sf-upload-icon text-primary';
                            }
                        } else {
                            filenameEl.textContent = '';
                            filenameEl.removeAttribute('title');
                            filenameEl.classList.add('d-none');
                            actionEl.innerHTML = '<i class="bi bi-upload me-1" aria-hidden="true"></i>Click to upload';
                            card.classList.remove('has-file');
                        }
                    };

                    // Backward-compatibility alias
                    window.updateSelectedFile = window.handleFileSelect;

                    // Drag and Drop listeners
                    document.querySelectorAll('label[for]').forEach(function (label) {
                        const input = document.getElementById(label.htmlFor);
                        if (!input || input.type !== 'file') return;

                        label.addEventListener('dragover', function (event) {
                            event.preventDefault();
                            label.classList.add('has-file');
                        });
                        label.addEventListener('dragleave', function (event) {
                            event.preventDefault();
                            if (!input.files || !input.files.length) {
                                label.classList.remove('has-file');
                            }
                        });
                        label.addEventListener('drop', function (event) {
                            event.preventDefault();
                            if (event.dataTransfer.files.length) {
                                const transfer = new DataTransfer();
                                transfer.items.add(event.dataTransfer.files[0]);
                                input.files = transfer.files;
                                window.handleFileSelect(input);
                            }
                        });
                    });
                })();
            </script>
        @endpush

    @endif

@endsection
