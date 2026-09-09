@extends('layouts.app')

@section('title', 'Review Application')
@section('eyebrow', 'FASSG Office · Verification')
@section('page-title', 'Review Application: ' . $application->studentProfile->user->name)

@push('styles')
    <style>
        .btn-navy-primary,
        a.btn-navy-primary,
        button.btn-navy-primary {
            background-color: #0F2942 !important;
            border-color: #0F2942 !important;
            color: #ffffff !important;
            font-weight: 600;
            box-shadow: none !important;
            transition: all 0.2s ease-in-out;
        }

        .btn-navy-primary:hover,
        .btn-navy-primary:focus,
        a.btn-navy-primary:hover,
        a.btn-navy-primary:focus,
        button.btn-navy-primary:hover,
        button.btn-navy-primary:focus {
            background-color: #0A1E31 !important;
            border-color: #0A1E31 !important;
            color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(15, 41, 66, 0.15) !important;
        }

        .btn-approve {
            background: linear-gradient(135deg, #1a7a4a 0%, #145f39 100%) !important;
            border-color: #1a7a4a !important;
            color: #fff !important;
            font-weight: 700;
            box-shadow: 0 2px 6px rgba(26, 122, 74, 0.25);
            transition: all 0.2s ease-in-out;
        }

        .btn-approve:hover {
            background: linear-gradient(135deg, #145f39 0%, #0f4a2c 100%) !important;
            box-shadow: 0 4px 14px rgba(26, 122, 74, 0.35) !important;
            color: #fff !important;
        }

        .detail-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #6c757d;
            margin-bottom: 0.2rem;
            font-weight: 600;
        }

        .detail-value {
            font-weight: 600;
            color: #1a1a2e;
        }

        .action-card {
            position: sticky;
            top: 5.5rem;
        }

        .doc-row:not(:last-child) {
            border-bottom: 1px solid #f0f0f0;
            padding-bottom: 0.85rem;
            margin-bottom: 0.85rem;
        }

        .stat-highlight {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
        }
    </style>
@endpush

@section('content')
    @php
        $profile    = $application->studentProfile;
        $program    = $application->sponsorshipProgram;
        $status     = $application->status->value;
        $canAct     = in_array($application->status, [
            \App\Enums\ApplicationStatus::Pending,
            \App\Enums\ApplicationStatus::ResubmissionRequested,
        ], true);
        $isSettledVerified = in_array($application->status, [
            \App\Enums\ApplicationStatus::Verified,
            \App\Enums\ApplicationStatus::Approved,
        ], true);

        $meetsGpa = $program->min_gpa === null
            || (float) $application->gpa_submitted <= (float) $program->min_gpa;

        // Year level eligibility check
        $allowedYearLevels   = $program->eligible_year_levels ?? [];
        $studentYearLevel    = $profile->year_level;
        $yearLevelRestricted = ! empty($allowedYearLevels);
        $yearLevelMeets      = ! $yearLevelRestricted
            || ($studentYearLevel !== null && in_array((string) $studentYearLevel, array_map('strval', $allowedYearLevels), true));
    @endphp

    {{-- Page Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <a href="{{ route('fassg.verification.index') }}" class="btn btn-sm btn-outline-secondary mb-3">
                <i class="bi bi-arrow-left me-1"></i> Back to Review Queue
            </a>
            <p class="text-uppercase small fw-semibold text-secondary mb-1">FASSG Office · Verification &amp; Review</p>
            <h1 class="h2 sf-heading mb-1">{{ $profile->user->name }}</h1>
            <p class="text-secondary mb-0">
                {{ $program->program_name }}
                <span class="mx-1">&bull;</span>
                {{ $program->sponsor->company_organization_name }}
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <x-status-badge :status="$application->status" />
        </div>
    </div>

    {{-- Flash / Error Messages --}}
    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Eligibility Warnings --}}
    @if (! empty($eligibilityErrors))
        <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
            <strong><i class="bi bi-exclamation-triangle me-1"></i> Eligibility criteria notice:</strong>
            <ul class="mb-0 mt-2 ps-3">
                @foreach ($eligibilityErrors as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-4">
        {{-- ── Left column: Applicant Summary & Program Info ──────────── --}}
        <div class="col-xl-7">

            {{-- Applicant Profile Summary --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="p-2 rounded" style="background-color:#e9ecef; color:#0F2942;">
                                <i class="bi bi-person-badge fs-4"></i>
                            </div>
                            <div>
                                <h2 class="h5 sf-heading mb-0">Applicant Profile Summary</h2>
                                <p class="small text-secondary mb-0">Key verification details &amp; demographics</p>
                            </div>
                        </div>
                        <span class="badge bg-light text-dark border">
                            <i class="bi bi-clock me-1"></i> Submitted {{ $application->submitted_at?->diffForHumans() ?? 'recently' }}
                        </span>
                    </div>

                    {{-- Highlight Stats Row --}}
                    <div class="row g-3 mb-4">
                        {{-- Student ID --}}
                        <div class="col-sm-6 col-md-4">
                            <div class="stat-highlight">
                                <div class="detail-label"><i class="bi bi-card-heading me-1"></i> Student ID</div>
                                <div class="detail-value sf-mono fs-6">{{ $profile->student_id_number ?: 'Not specified' }}</div>
                            </div>
                        </div>
                        {{-- Submitted GPA --}}
                        <div class="col-sm-6 col-md-4">
                            <div class="stat-highlight">
                                <div class="detail-label"><i class="bi bi-award me-1"></i> Submitted GPA / GWA</div>
                                <div class="detail-value fs-6 d-flex align-items-center gap-1">
                                    {{ number_format($application->gpa_submitted, 2) }}
                                    @if ($program->min_gpa !== null)
                                        @if ($meetsGpa)
                                            <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle ms-1" style="font-size: 0.7rem;">
                                                Meets Min
                                            </span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle ms-1" style="font-size: 0.7rem;">
                                                Below Min
                                            </span>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                        {{-- Year Level --}}
                        <div class="col-sm-6 col-md-4">
                            <div class="stat-highlight">
                                <div class="detail-label"><i class="bi bi-mortarboard me-1"></i> Year Level</div>
                                <div class="detail-value fs-6 d-flex align-items-center gap-1">
                                    Year {{ $profile->year_level ?? '—' }}
                                    @if ($yearLevelRestricted)
                                        @if ($yearLevelMeets)
                                            <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle ms-1" style="font-size: 0.7rem;">
                                                Eligible
                                            </span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle ms-1" style="font-size: 0.7rem;">
                                                Ineligible
                                            </span>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                        {{-- Gender --}}
                        <div class="col-sm-6 col-md-4">
                            <div class="stat-highlight">
                                <div class="detail-label"><i class="bi bi-gender-ambiguous me-1"></i> Gender</div>
                                <div class="detail-value fs-6">{{ $profile->gender ?: 'Not specified' }}</div>
                            </div>
                        </div>
                        {{-- Residence / Area --}}
                        <div class="col-sm-12 col-md-8">
                            <div class="stat-highlight">
                                <div class="detail-label"><i class="bi bi-geo-alt me-1"></i> Residence &amp; Barangay</div>
                                <div class="detail-value fs-6 text-truncate d-flex align-items-center gap-2">
                                    <span class="text-truncate">{{ $application->address_submitted ?: ($profile->barangay ?: 'Address not provided') }}</span>
                                    @if ($application->is_rural_submitted)
                                        <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle flex-shrink-0" style="font-size: 0.7rem;">
                                            <i class="bi bi-tree me-1"></i>Rural
                                        </span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle flex-shrink-0" style="font-size: 0.7rem;">
                                            <i class="bi bi-building me-1"></i>Urban
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Additional Profile Details --}}
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="detail-label">Full Name</div>
                            <div class="detail-value">{{ $profile->user->name }}</div>
                            <div class="small text-secondary">{{ $profile->user->email }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-label">Course / Degree</div>
                            <div class="detail-value">{{ $profile->course ?: '—' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-label">SLE-FHE Status</div>
                            <div>
                                @if ($profile->is_sle_fhe_verified)
                                    <span class="badge bg-cyan-50 text-cyan-700 border border-cyan-200 px-2 py-1">
                                        <i class="bi bi-patch-check me-1"></i> SLE-FHE Verified
                                    </span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1">
                                        <i class="bi bi-hourglass-split me-1"></i> SLE-FHE Pending
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-label">Complete Address on Application</div>
                            <div class="small text-dark">{{ $application->address_submitted ?: 'Same as student profile' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Target Program Details --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h2 class="h5 sf-heading mb-3">
                        <i class="bi bi-building-check me-2" style="color:#0F2942;"></i>Target Program &amp; Slots
                    </h2>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="detail-label">Program Name</div>
                            <div class="detail-value">{{ $program->program_name }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-label">Sponsoring Organization</div>
                            <div class="detail-value">{{ $program->sponsor->company_organization_name }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-label">Category</div>
                            <div>
                                <span class="badge rounded-2 fw-medium"
                                    style="background-color: rgba(15,41,66,0.08); color:#0F2942;">
                                    {{ $program->category->value }}
                                </span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-label">Available Slots</div>
                            <div class="fw-bold {{ $program->available_slots <= 0 ? 'text-danger' : 'text-success' }}">
                                {{ $program->available_slots }} remaining
                                @if ($program->available_slots <= 0)
                                    <span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle ms-1">Full</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-label">Target Year Levels</div>
                            <div>
                                @if (! empty($allowedYearLevels))
                                    {{ implode(', ', array_map(fn($y) => "Year {$y}", (array) $allowedYearLevels)) }}
                                @else
                                    <span class="text-success fw-semibold">All Year Levels</span>
                                @endif
                            </div>
                        </div>
                        @if ($program->min_gpa)
                            <div class="col-md-4">
                                <div class="detail-label">Min. GPA Required</div>
                                <div>{{ number_format($program->min_gpa, 2) }} or better</div>
                            </div>
                        @endif
                        @if ($program->address_requirement)
                            <div class="col-md-8">
                                <div class="detail-label">Address Requirement</div>
                                <div>{{ $program->address_requirement }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Application History / Timeline --}}
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h2 class="h6 sf-heading mb-3 text-secondary text-uppercase">
                        <i class="bi bi-clock-history me-2"></i>Review Timeline &amp; Notes
                    </h2>
                    <div class="row g-3 small">
                        <div class="col-md-4">
                            <div class="detail-label">Submitted</div>
                            <div>{{ $application->submitted_at?->format('M d, Y · h:i A') ?? '—' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-label">Verified At</div>
                            <div>{{ $application->verified_at?->format('M d, Y · h:i A') ?? 'Not yet verified' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-label">Approved At</div>
                            <div>{{ $application->approved_at?->format('M d, Y · h:i A') ?? 'Not yet approved' }}</div>
                        </div>
                        @if ($application->rejection_reason)
                            <div class="col-12 mt-2">
                                <div class="detail-label text-danger">Rejection Reason</div>
                                <div class="alert alert-danger border border-danger-subtle rounded-2 p-2 mb-0 text-danger-emphasis">
                                    <i class="bi bi-info-circle me-1"></i> {{ $application->rejection_reason }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Right column: Supporting Documents & Decision Controls ─── --}}
        <div class="col-xl-5">

            {{-- Supporting Documents Section --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                        <div>
                            <h2 class="h5 sf-heading mb-1">Supporting Documents</h2>
                            <p class="small text-secondary mb-0">Documents required for this program &amp; uploaded by the applicant</p>
                        </div>
                        <i class="bi bi-file-earmark-check fs-3" style="color:#0F2942;"></i>
                    </div>

                    @php
                        $allDocs = $application->documents->values();
                        $programLabels = array_values(array_filter(array_map(
                            'strval',
                            (array) ($program->required_documents ?? []),
                        )));
                        $requiredTypes = [];
                        foreach ($programLabels as $label) {
                            foreach (\App\Enums\DocumentType::typesForLabel($label) as $type) {
                                $requiredTypes[] = $type;
                            }
                        }
                        if ($requiredTypes === []) {
                            $requiredTypes = \App\Enums\DocumentType::requiredForApplication();
                        }

                        $docGroups = [];
                        $covered = [];
                        foreach ($requiredTypes as $requiredType) {
                            $canon = \App\Enums\DocumentType::canonicalValue($requiredType);
                            if (in_array($canon, $covered, true)) {
                                continue;
                            }
                            $covered[] = $canon;

                            $displayLabel = $requiredType->label();
                            foreach ($programLabels as $label) {
                                foreach (\App\Enums\DocumentType::typesForLabel($label) as $labelType) {
                                    if (\App\Enums\DocumentType::canonicalValue($labelType) === $canon) {
                                        $displayLabel = $label;
                                        break 2;
                                    }
                                }
                            }

                            $docGroups[] = [
                                'label' => $displayLabel,
                                'document' => $allDocs->first(
                                    fn ($item) => \App\Enums\DocumentType::canonicalValue($item->document_type) === $canon,
                                ),
                            ];
                        }

                        foreach ($allDocs as $extraDoc) {
                            $canon = \App\Enums\DocumentType::canonicalValue($extraDoc->document_type);
                            if (in_array($canon, $covered, true)) {
                                continue;
                            }
                            $covered[] = $canon;
                            $docGroups[] = [
                                'label' => $extraDoc->document_type instanceof \BackedEnum
                                    ? $extraDoc->document_type->label()
                                    : \Illuminate\Support\Str::headline(str_replace('_', ' ', (string) $extraDoc->document_type)),
                                'document' => $extraDoc,
                            ];
                        }
                    @endphp

                    @foreach ($docGroups as $docGroup)
                        @php
                            $document = $docGroup['document'];
                            $docModalId = 'docPreviewModal_' . \Illuminate\Support\Str::slug($docGroup['label']);
                        @endphp
                        <div class="doc-row">
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <div class="p-2 rounded {{ $document ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                                    <i class="bi {{ $document ? 'bi-file-earmark-check' : 'bi-file-earmark-x' }} fs-4"></i>
                                </div>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-semibold small">{{ $docGroup['label'] }}</div>
                                    <div class="small text-secondary text-truncate">
                                        {{ $document?->file_name ?? 'Not uploaded yet' }}
                                    </div>
                                </div>
                                @if ($document)
                                    <span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Uploaded
                                    </span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle">
                                        Missing
                                    </span>
                                @endif
                            </div>

                            @if ($document)
                                <div class="d-flex gap-2 ms-5 ps-1">
                                    {{-- Inline Preview Modal Button --}}
                                    <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2"
                                        data-bs-toggle="modal" data-bs-target="#{{ $docModalId }}">
                                        <i class="bi bi-eye me-1"></i> Preview
                                    </button>
                                    {{-- Open / View Button --}}
                                    <a href="{{ route('fassg.verification.documents.show', [$application, $document]) }}"
                                        target="_blank" rel="noopener"
                                        class="btn btn-sm btn-outline-secondary py-1 px-2">
                                        <i class="bi bi-box-arrow-up-right me-1"></i> Open Tab
                                    </a>
                                </div>

                                {{-- Inline Document Preview Modal --}}
                                <div class="modal fade" id="{{ $docModalId }}" tabindex="-1" aria-labelledby="{{ $docModalId }}Label" aria-hidden="true">
                                    <div class="modal-dialog modal-xl modal-dialog-centered">
                                        <div class="modal-content border-0 shadow">
                                            <div class="modal-header" style="background:#0F2942; color:#fff;">
                                                <h5 class="modal-title h6 mb-0 text-white" id="{{ $docModalId }}Label">
                                                    <i class="bi bi-file-earmark-text me-2"></i>{{ $docGroup['label'] }} — {{ $document->file_name }}
                                                </h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body p-0" style="background: #f1f3f5; min-height: 70vh;">
                                                <iframe src="{{ route('fassg.verification.documents.show', [$application, $document]) }}"
                                                    style="width: 100%; height: 75vh; border: none;"
                                                    title="{{ $docGroup['label'] }}">
                                                </iframe>
                                            </div>
                                            <div class="modal-footer d-flex justify-content-between">
                                                <span class="small text-secondary">
                                                    Applicant: <strong>{{ $profile->user->name }}</strong> ({{ $profile->student_id_number }})
                                                </span>
                                                <div class="d-flex gap-2">
                                                    <a href="{{ route('fassg.verification.documents.show', [$application, $document]) }}"
                                                        target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">
                                                        <i class="bi bi-box-arrow-up-right me-1"></i> Open in New Tab
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Decision Controls --}}
            <div class="card border-0 shadow-sm bg-light action-card">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <h2 class="h5 sf-heading mb-0">Decision Controls</h2>
                            <p class="small text-secondary mb-0">Review decision and slot reservation</p>
                        </div>
                        <span class="badge {{ $program->available_slots > 0 ? 'bg-success-subtle text-success-emphasis' : 'bg-danger-subtle text-danger-emphasis' }} border px-2 py-1">
                            {{ $program->available_slots }} slot{{ $program->available_slots !== 1 ? 's' : '' }} left
                        </span>
                    </div>

                    @if ($canAct)
                        {{-- ── 1. VERIFY & FORWARD TO SPONSOR ACTION ── --}}
                        <div class="mb-4">
                            <form method="POST" action="{{ route('fassg.verification.approve', $application) }}"
                                onsubmit="return confirm('Confirm verification: This will verify {{ addslashes($profile->user->name) }} and forward the application to {{ addslashes($program->program_name) }} for sponsor review. Continue?');">
                                    @csrf
                                    @method('PATCH')
                                    @php
                                        $attCanon = static fn ($type) => \App\Enums\DocumentType::canonicalValue($type);
                                        $attAllTypes = array_values(array_unique(array_merge(
                                            $program->requiredDocumentCanonicalValues(),
                                            $application->documents->map(fn ($d) => $attCanon($d->document_type))->all(),
                                        )));
                                        $needsCorAttest = in_array(\App\Enums\DocumentType::CertificateOfRegistration->value, $attAllTypes, true);
                                        $needsKinshipAttest = in_array(\App\Enums\DocumentType::EmployeeProofOfKinship->value, $attAllTypes, true);
                                    @endphp
                                    <div class="p-3 bg-white border rounded-3 mb-3">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <i class="bi bi-patch-check" style="color:#0F2942;"></i>
                                            <span class="small fw-semibold text-dark">Verification Attestation</span>
                                        </div>
                                        <p class="small text-secondary mb-2">
                                            Check each box to confirm you have reviewed the corresponding document.
                                        </p>
                                        <label class="d-flex align-items-start gap-2 small mb-2">
                                            <input type="checkbox" class="form-check-input mt-1" name="confirm_gwa" value="1" required>
                                            <span>Confirm that the grade slip matches the submitted GWA.</span>
                                        </label>
                                        <label class="d-flex align-items-start gap-2 small mb-2">
                                            <input type="checkbox" class="form-check-input mt-1" name="confirm_address" value="1" required>
                                            <span>Confirm that the proof of residence and barangay certificate match the submitted address.</span>
                                        </label>
                                        @if ($needsCorAttest)
                                            <label class="d-flex align-items-start gap-2 small mb-2">
                                                <input type="checkbox" class="form-check-input mt-1" name="confirm_cor" value="1" required>
                                                <span>Confirm that the Certificate of Registration (COR) matches current enrollment.</span>
                                            </label>
                                        @endif
                                        @if ($needsKinshipAttest)
                                            <label class="d-flex align-items-start gap-2 small mb-2">
                                                <input type="checkbox" class="form-check-input mt-1" name="confirm_kinship" value="1" required>
                                                <span>Confirm that the Employee ID / Proof of Kinship matches the submitted employee dependency details.</span>
                                            </label>
                                        @endif
                                    </div>
                                    <button type="submit" class="btn btn-approve w-100 py-2 mb-2 d-flex align-items-center justify-content-center gap-2">
                                        <i class="bi bi-check2-circle fs-5"></i>
                                        <span>Verify &amp; Forward to Sponsor</span>
                                    </button>
                                </form>
                                <p class="small text-muted mb-0">
                                    <i class="bi bi-shield-check me-1 text-success"></i>
                                    Verifies the application and forwards it to the Sponsor Review queue for final approval. Slot reservation happens when the sponsor approves.
                                </p>
                        </div>

                        <hr class="my-3">

                        {{-- ── 2. REJECT APPLICATION ACTION (Mandatory Reason) ── --}}
                        <div>
                            <h3 class="h6 fw-bold text-danger mb-2">
                                <i class="bi bi-x-circle me-1"></i> Reject Application
                            </h3>
                            <p class="small text-secondary mb-3">
                                Provide a mandatory explanation so the student understands why their application was rejected.
                            </p>

                            <form method="POST" action="{{ route('fassg.verification.reject', $application) }}"
                                onsubmit="return confirm('Are you sure you want to reject this application? This action cannot be undone.');">
                                @csrf
                                @method('PATCH')

                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-dark" for="reason">
                                        Rejection Reason <span class="text-danger">*</span>
                                    </label>
                                    <textarea class="form-control" id="reason" name="reason" rows="3"
                                        placeholder="Enter the specific reason for rejecting this application (e.g., Incomplete grade slip, does not meet residency requirement, or year level mismatch)..."
                                        required maxlength="500">{{ old('reason') }}</textarea>
                                    <div class="form-text small text-secondary">
                                        Mandatory. The student will be notified of this reason.
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-outline-danger w-100 fw-semibold">
                                    <i class="bi bi-x-octagon me-1"></i> Reject Application
                                </button>
                            </form>
                        </div>

                        <hr class="my-3">

                        {{-- ── 3. REQUEST DOCUMENT RESUBMISSION ACTION ── --}}
                        <div>
                            <h3 class="h6 fw-bold mb-2" style="color:#9a6b00;">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> Request Document Resubmission
                            </h3>
                            <p class="small text-secondary mb-3">
                                Ask the student to upload a corrected or clearer copy of a document. A mandatory note is attached so the student knows exactly what to fix.
                            </p>
                            <form method="POST" action="{{ route('fassg.verification.request-resubmission', $application) }}"
                                onsubmit="return confirm('Send this application back to the student for document resubmission?');">
                                @csrf
                                @method('PATCH')
                                @php
                                    $resubReqCanon = static fn ($type) => \App\Enums\DocumentType::canonicalValue($type);
                                    $resubReqTypes = array_values(array_unique(array_merge(
                                        $program->requiredDocumentCanonicalValues(),
                                        $application->documents->map(fn ($d) => $resubReqCanon($d->document_type))->all(),
                                    )));
                                    $resubReqLabels = [];
                                    foreach ($resubReqTypes as $canon) {
                                        $label = $canon;
                                        foreach (\App\Enums\DocumentType::cases() as $type) {
                                            if ($resubReqCanon($type) === $canon) {
                                                $label = $type->label();
                                                break;
                                            }
                                        }
                                        $resubReqLabels[$canon] = $label;
                                    }
                                @endphp
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-dark">
                                        Documents to Re-upload <span class="text-danger">*</span>
                                    </label>
                                    <div class="border rounded-3 bg-white p-3" style="max-height: 220px; overflow-y: auto;">
                                        @foreach ($resubReqLabels as $resubReqCanonValue => $resubReqLabel)
                                            <label class="d-flex align-items-start gap-2 small mb-2">
                                                <input type="checkbox" class="form-check-input mt-1"
                                                    name="requested_documents[]" value="{{ $resubReqCanonValue }}"
                                                    @checked(in_array($resubReqCanonValue, (array) old('requested_documents', []), true))>
                                                <span>{{ $resubReqLabel }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    @error('requested_documents')
                                        <div class="text-danger small mt-1"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>
                                    @enderror
                                    <div class="form-text small text-secondary">
                                        Select at least one document you want the student to replace or provide a clearer copy of.
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-dark" for="resubmission_notes">
                                        Resubmission Notes <span class="text-danger">*</span>
                                    </label>
                                    <textarea class="form-control" id="resubmission_notes" name="resubmission_notes" rows="3"
                                        placeholder="e.g., Uploaded COR is unreadable. Please upload a clear copy."
                                        required minlength="5" maxlength="1000">{{ old('resubmission_notes') }}</textarea>
                                    <div class="form-text small text-secondary">
                                        Mandatory. Displayed to the student on their application page.
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-warning w-100 fw-semibold text-dark">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i> Request Document Resubmission
                                </button>
                            </form>
                        </div>

                        <div class="small text-secondary mt-4 pt-3 border-top">
                            <i class="bi bi-info-circle me-1"></i>
                            Verifying forwards the application to the Sponsor Review queue. The sponsor's final approval reserves the program slot and registers the student for beneficiary processing.
                        </div>
                    @elseif ($isSettledVerified)
                        {{-- Application Verified & Forwarded (read-only) --}}
                        <div class="p-4 text-center">
                            <div class="mb-3">
                                <i class="bi bi-patch-check-fill display-4" style="color:#1a7a4a;"></i>
                            </div>
                            <h3 class="h5 sf-heading mb-2">Application Verified &amp; Forwarded</h3>
                            <p class="small text-secondary mb-0">
                                This application was verified on
                                <strong>{{ $application->verified_at?->format('F j, Y') ?? '—' }}</strong>
                                and is currently under Sponsor Review.
                            </p>
                        </div>
                    @else
                        {{-- Application Already Finalized --}}
                        <div class="alert {{ $application->status === \App\Enums\ApplicationStatus::Approved ? 'alert-success' : 'alert-secondary' }} mb-0">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <i class="bi {{ $application->status === \App\Enums\ApplicationStatus::Approved ? 'bi-check-circle-fill text-success' : 'bi-info-circle' }} fs-5"></i>
                                <strong>Status: {{ $status }}</strong>
                            </div>
                            <p class="small mb-0">
                                This application is marked as <strong>{{ $status }}</strong> and no further review decisions can be submitted.
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
