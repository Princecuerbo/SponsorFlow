@extends('layouts.app')

@section('title', 'New Application')
@section('eyebrow', 'Student Portal · ' . $program->program_name)
@section('page-title', 'Apply for Sponsorship')

@push('styles')
    <style>
        /* Primary Navy Styling for Step Badges */
        .sf-step-badge {
            background-color: #0F2942 !important;
            color: #ffffff !important;
            width: 26px;
            height: 26px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        /* Primary Navy Submit Button */
        .btn-navy-submit,
        button.btn-navy-submit {
            background-color: #0F2942 !important;
            border-color: #0F2942 !important;
            color: #ffffff !important;
            font-weight: 600;
            box-shadow: none !important;
            transition: all 0.2s ease-in-out;
        }

        .btn-navy-submit:hover,
        .btn-navy-submit:focus,
        button.btn-navy-submit:hover,
        button.btn-navy-submit:focus {
            background-color: #0A1E31 !important;
            border-color: #0A1E31 !important;
            color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(15, 41, 66, 0.15) !important;
        }

        /* Switch Focus/Checked Accent */
        .form-check-input:checked {
            background-color: #0F2942 !important;
            border-color: #0F2942 !important;
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
        $hasAddressRequirement = $programRequiresRural || $programRequiresUrban;

        $profileIsRural = (bool) $profile->is_rural;
        $profileMunicipality = trim((string) ($profile->municipality ?? ''));

        $residencyMismatch = $hasAddressRequirement
            && (($programRequiresRural && ! $profileIsRural)
                || ($programRequiresUrban && $profileIsRural));

        $allowedCampuses = (array) ($program->eligible_campuses ?? []);
        $allowedCampusesNote = $allowedCampuses ? ' (' . implode(', ', $allowedCampuses) . ')' : '';
        $campusRestricted = $allowedCampuses !== []
            && ! in_array($profile->campus, $allowedCampuses, true);
        $submissionLocked = $residencyMismatch || $campusRestricted;

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
    @endphp

    @if ($profile->hasActiveSponsorship())
        <div class="alert alert-danger border-0 shadow-sm rounded-3">
            <i class="bi bi-exclamation-octagon-fill me-1"></i>
            You already have an active sponsorship. Only one active sponsorship is allowed at a time — you may re-apply once
            it expires.
            <a href="{{ route('student.applications.index') }}" class="alert-link">View my applications</a>
        </div>
    @else
        {{-- ─── Residency Mismatch Warning ─────────────────────────────────────── --}}
        @if ($residencyMismatch)
            <div class="alert border-0 rounded-3 shadow-sm d-flex gap-3 align-items-start mb-4"
                 id="residency-mismatch-alert"
                 role="alert"
                 style="background: linear-gradient(135deg,#fff7ed 0%,#fef3c7 100%); border-left: 4px solid #f59e0b !important;">
                <i class="bi bi-exclamation-triangle-fill fs-4 flex-shrink-0" style="color:#d97706;margin-top:2px;"></i>
                <div>
                    <p class="fw-semibold mb-1" style="color:#92400e;">Residency Classification Notice</p>
                    <p class="small mb-0" style="color:#78350f;">
                        @if ($programRequiresUrban && $profileIsRural)
                            This program is intended for <strong>Urban</strong> residents, but your profile address is classified as <strong>Rural</strong>.
                            Applying for Urban-specific grants requires a valid urban address or certification.
                        @elseif ($programRequiresRural && ! $profileIsRural)
                            This program is intended for <strong>Rural</strong> residents, but your profile address is currently classified as <strong>Urban</strong>@if ($profileMunicipality) ({{ $profileMunicipality }})@endif.
                            Applying for Rural-specific grants requires a valid rural address or Barangay certification.
                        @endif
                        Form submission has been locked accordingly.
                        If your address has changed, please
                        <a href="{{ route('student.verification.show') }}" class="alert-link fw-semibold">update your profile</a>
                        before applying.
                    </p>
                </div>
            </div>
        @endif

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
              data-profile-is-rural="{{ $profile->is_rural ? '1' : '0' }}"
              data-residency-mismatch="{{ $residencyMismatch ? '1' : '0' }}"
              data-campus-restricted="{{ $campusRestricted ? '1' : '0' }}"
              @if ($submissionLocked) onsubmit="return false;" @endif>
            @csrf
            <input type="hidden" name="sponsorship_program_id" value="{{ $program->id }}">

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
                            <h2 class="h6 sf-heading mb-0">Student &amp; SLE-FHE Profile</h2>
                        </div>
                        <p class="small text-secondary mb-3">Pulled from your verified profile. To change any of this,
                            update your profile before applying.</p>

                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label small text-secondary">Student ID Number</label>
                                <input type="text" class="form-control sf-mono" value="{{ $profile->student_id_number }}"
                                    disabled>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label small text-secondary">Full Name</label>
                                <input type="text" class="form-control" value="{{ $user?->name ?? auth()->user()?->name ?? '—' }}" disabled>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label small text-secondary">Course</label>
                                <input type="text" class="form-control" value="{{ $profile->course }}" disabled>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label small text-secondary">Campus</label>
                                <input type="text" class="form-control" value="{{ $profile->campus ?? 'Not Assigned' }}"
                                    disabled>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label small text-secondary">SLE-FHE Status</label>
                                <div class="pt-2"><x-status-badge status="Verified" /></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Section 2: Encoded inputs --}}
                <div class="card sf-card mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="badge rounded-circle sf-step-badge">2</span>
                            <h2 class="h6 sf-heading mb-0">Application Details</h2>
                        </div>

                        <div class="row g-3">
                            <div class="col-sm-4">
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
                            <div class="col-sm-8">
                                <label for="current_address" class="form-label small text-secondary">Current Address <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="current_address" id="current_address"
                                    value="{{ old('current_address', old('address_submitted', $profile->full_address)) }}"
                                    class="form-control @error('current_address') is-invalid @enderror" required>
                                @error('current_address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                @if ($hasAddressRequirement)
                                    <div id="residency-verified-block" class="d-flex flex-wrap align-items-center gap-2">
                                        <span class="badge border rounded-3 d-inline-flex align-items-center gap-2"
                                              style="background:#f0fdf4;color:#166534;font-size:0.75rem;font-weight:600;border-color:#86efac !important;">
                                            <i class="bi bi-patch-check-fill text-success" style="font-size:0.8rem;"></i>
                                            Verified Residence: <strong>{{ $profileIsRural ? 'Rural' : 'Urban' }}</strong>
                                        </span>
                                        @if ($residencyMismatch)
                                            <span class="badge rounded-3 d-inline-flex align-items-center gap-2"
                                                  style="background:#fef2f2;color:#b91c1c;font-size:0.75rem;font-weight:600;border:1px solid #fca5a5;">
                                                <i class="bi bi-exclamation-triangle-fill" style="font-size:0.75rem;"></i>
                                                Residence requirement mismatch (Requires: {{ $program->address_requirement }})
                                            </span>
                                        @endif
                                    </div>
                                @endif
                                <input type="hidden" name="is_rural_submitted"
                                       value="{{ $profileIsRural ? '1' : '0' }}">
                                @error('is_rural_submitted')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
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
                                <h2 class="h6 sf-heading mb-0">Institutional Employee Verification</h2>
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
                            <h2 class="h6 sf-heading mb-0">Supporting Documents</h2>
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
                                    <div class="col-md-4">
                                        <label class="form-label small text-secondary d-block">{{ $cfg['label'] }} upload
                                            <span class="text-danger">*</span></label>
                                        <label for="{{ $cfg['field'] }}"
                                            class="d-block border border-2 border-dashed rounded-3 text-center p-4 bg-light"
                                            style="cursor:pointer; border-style:dashed !important;">
                                            <i class="bi {{ $cfg['icon'] }} fs-3 text-secondary d-block mb-2"></i>
                                            <span class="small fw-semibold d-block">Click to upload</span>
                                            <span class="small text-secondary" data-filename-for="{{ $cfg['field'] }}">or drag
                                                file here</span>
                                            <input type="file" name="{{ $cfg['field'] }}" id="{{ $cfg['field'] }}"
                                                class="d-none" accept=".pdf,.jpg,.jpeg,.png"
                                                onchange="updateSelectedFile(this)">
                                        </label>
                                        @error($cfg['field'])
                                            <div class="text-danger small mt-1">{{ $message }}</div>
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
                        <h2 class="h6 sf-heading mb-3">{{ $program->program_name }}</h2>
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

                        <button type="submit" class="btn btn-navy-submit w-100 mb-2 py-2"
                            @if ($submissionLocked) disabled style="opacity: 0.55; cursor: not-allowed;" title="Submission locked due to eligibility requirements" @endif>
                            <i class="bi bi-send me-1"></i> Submit Application
                        </button>
                        @if ($campusRestricted)
                            <div class="small text-danger mb-2 fw-semibold">
                                <i class="bi bi-lock-fill me-1"></i>
                                Form submission is locked because this program is restricted to specific campuses.
                            </div>
                        @elseif ($residencyMismatch)
                            <div class="small text-danger mb-2 fw-semibold">
                                <i class="bi bi-lock-fill me-1"></i>
                                Form submission is locked due to residency requirements.
                            </div>
                        @endif
                        <a href="{{ route('student.programs.index') }}"
                            class="btn btn-outline-danger w-100">
                            <i class="bi bi-x-lg me-1"></i> Cancel
                        </a>

                        <div class="small text-secondary mt-3">
                            <i class="bi bi-shield-check me-1"></i>
                            Submitting locks this program's slot request into FASSG's verification queue. You'll be notified
                            at each stage.
                        </div>
                    </div>
                </div>
            </div>
        </form>

        @push('scripts')
            <script>
                (function () {
                    'use strict';

                    /* ── File upload helpers ─────────────────────────────────────────── */
                    window.updateSelectedFile = function (input) {
                        const filename = input.files[0]?.name || 'or drag file here';
                        document.querySelector(`[data-filename-for="${input.id}"]`).textContent = filename;
                    };

                    document.querySelectorAll('label[for]').forEach(function (label) {
                        const input = document.getElementById(label.htmlFor);
                        if (!input) return;

                        label.addEventListener('dragover', function (event) {
                            event.preventDefault();
                        });
                        label.addEventListener('drop', function (event) {
                            event.preventDefault();
                            if (event.dataTransfer.files.length) {
                                const transfer = new DataTransfer();
                                transfer.items.add(event.dataTransfer.files[0]);
                                input.files = transfer.files;
                                window.updateSelectedFile(input);
                            }
                        });
                    });

                    /* ── Residency mismatch & submission are enforced server-side and in
                       Blade (disabled submit button, onsubmit guard). ──────────────── */
                })();
            </script>
        @endpush

    @endif

@endsection
