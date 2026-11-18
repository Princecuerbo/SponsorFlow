@once
    @push('styles')
        @include('fassg.partials.applications-styles')
        @include('fassg.partials.modal-styles')
    @endpush
@endonce

@php
    $reviewContext = $reviewContext ?? 'applications';
    $isVerification = $reviewContext === 'verification';

    $profile = $application->studentProfile;
    $program = $application->sponsorshipProgram;
    $status = $application->status->value;

    $config = $isVerification
        ? [
            'backRoute' => 'fassg.verification.index',
            'backLabel' => 'Back to Review Queue',
            'eyebrow' => 'FASSG Office · Verification & Review',
            'docRoute' => 'fassg.verification.documents.show',
            'docModals' => true,
            'approveRoute' => 'fassg.verification.approve',
            'rejectRoute' => 'fassg.verification.reject',
            'resubmissionRoute' => 'fassg.verification.request-resubmission',
            'approveLabel' => 'Record Eligibility Verification',
            'settledTitle' => 'Application Eligibility Verified',
            'settledNote' => 'FASSG eligibility verification is recorded. Sponsor review requires a separately submitted Generated Batch.',
            'approveButtonClass' => 'btn-success',
            'confirmMessage' => 'Record eligibility verification? Sponsor review requires a separately submitted Generated Batch.',
        ]
        : [
            'backRoute' => 'fassg.applications.index',
            'backLabel' => 'Back to Application Queue',
            'eyebrow' => 'FASSG Office · Application Verification',
            'docRoute' => 'fassg.applications.documents.download',
            'docModals' => false,
            'approveRoute' => 'fassg.applications.verify',
            'rejectRoute' => 'fassg.applications.reject',
            'resubmissionRoute' => 'fassg.applications.request-resubmission',
            'approveLabel' => 'Mark as Verified',
            'settledTitle' => 'Application Verified',
            'settledNote' => 'This application was verified and no further review decisions can be submitted.',
            'approveButtonClass' => 'btn-success',
            'confirmMessage' => 'Mark this application as Verified? This forwards nothing to the sponsor yet but confirms your review.',
        ];

    $isBatchReview = isset($generatedBatch);
    if ($isBatchReview) {
        $config['backRoute'] = 'fassg.generated-batches.show';
        $config['backLabel'] = 'Back to Generated Batch';
        $config['approveRoute'] = 'fassg.generated-batches.applications.verify';
        $config['approveLabel'] = 'Verify Batch Member';
    }
    $canAct = $application->status === \App\Enums\ApplicationStatus::Pending
        && (! $isBatchReview || ($generatedBatch->status === \App\Enums\GeneratedBatchStatus::Saved
            && $generatedBatch->submission_version === 0 && $generatedBatch->submitted_roster === null));
    $qualificationRequirements = $program->beneficiaryQualificationRequirements();

    $isSettledVerified = in_array($application->status, [
        \App\Enums\ApplicationStatus::Verified,
        \App\Enums\ApplicationStatus::Approved,
    ], true);

    $meetsGpa = $program->min_gpa === null
        || ($application->gpa_submitted !== null && (float) $application->gpa_submitted <= (float) $program->min_gpa);

    $allowedYearLevels = $program->eligible_year_levels ?? [];
    $studentYearLevel = $profile->year_level;
    $yearLevelRestricted = ! empty($allowedYearLevels);
    $yearLevelMeets = ! $yearLevelRestricted
        || ($studentYearLevel !== null && in_array((string) $studentYearLevel, array_map('strval', $allowedYearLevels), true));

    $attCanon = static fn ($type) => \App\Enums\DocumentType::canonicalValue($type);
    $attAllTypes = array_values(array_unique(array_merge(
        $program->beneficiaryRequiredDocumentTypes(),
        $application->documents->map(fn ($d) => $attCanon($d->document_type))->all(),
    )));
    $needsCorAttest = in_array(\App\Enums\DocumentType::CertificateOfRegistration->value, $program->beneficiaryRequiredDocumentTypes(), true);
    $needsKinshipAttest = in_array(\App\Enums\DocumentType::EmployeeProofOfKinship->value, $program->beneficiaryRequiredDocumentTypes(), true);

    $resubReqTypes = $attAllTypes;
    $resubReqLabels = [];
    foreach ($resubReqTypes as $canon) {
        $label = $canon;
        foreach (\App\Enums\DocumentType::cases() as $type) {
            if ($attCanon($type) === $canon) {
                $label = $type->label();
                break;
            }
        }
        $resubReqLabels[$canon] = $label;
    }
@endphp

<div class="sf-review-page">
    {{-- Page Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div>
            <a href="{{ route($config['backRoute'], $generatedBatch ?? []) }}" class="btn btn-sm btn-outline-secondary mb-2"
                style="height: 34px; display: inline-flex; align-items: center; gap: 6px; border-radius: 8px; font-weight: 600; font-size: 13px;">
                <i class="bi bi-arrow-left"></i>
                <span>{{ $config['backLabel'] }}</span>
            </a>
            <span class="sf-eyebrow d-block mb-1 text-uppercase text-secondary fw-semibold" style="font-size: 11px; letter-spacing: 0.05em;">
                {{ $config['eyebrow'] }}
            </span>
            <h2 class="h2 sf-heading mb-1 fw-bold" style="color: #0F2942;">{{ $profile->user->name }}</h2>
            <p class="text-secondary mb-0" style="font-size: 13.5px;">
                <span class="fw-semibold text-dark">{{ $program->program_name }}</span>
                <span class="mx-1 text-muted">&bull;</span>
                <span>{{ $program->sponsor->company_organization_name }}</span>
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <x-status-badge :status="$application->status" />
        </div>
    </div>

    {{-- Flash / Error Messages --}}
    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show mb-4 border-success-subtle rounded-3" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-4 border-danger-subtle rounded-3" role="alert">
            <strong><i class="bi bi-exclamation-triangle me-1"></i> Unable to process request:</strong>
            <ul class="mb-0 mt-2 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Eligibility Warnings --}}
    @if (! empty($eligibilityErrors))
        <div class="alert alert-warning alert-dismissible fade show mb-4 rounded-3" role="alert"
            style="background-color: #FFF8EA; border: 1px solid #F6DDA1; color: #986515;">
            <div class="d-flex align-items-start gap-2">
                <i class="bi bi-exclamation-triangle-fill fs-5 mt-1 flex-shrink-0" style="color: #B88219;"></i>
                <div class="flex-grow-1">
                    <strong class="d-block mb-1" style="color: #784E0B;">Eligibility criteria notice:</strong>
                    <ul class="mb-0 ps-3">
                        @foreach ($eligibilityErrors as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-4">
        {{-- ── Left column: Applicant Summary & Program Info ──────────── --}}
        <div class="col-xl-7">

            {{-- Applicant Profile Summary --}}
            <div class="sf-review-card">
                <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <div class="p-2 rounded" style="background-color: rgba(15, 41, 66, 0.08); color: #0F2942;">
                            <i class="bi bi-person-badge fs-5"></i>
                        </div>
                        <div>
                            <h3 class="sf-review-card-title mb-0">Applicant Profile Summary</h3>
                            <p class="small text-secondary mb-0">Key verification details &amp; demographics</p>
                        </div>
                    </div>
                    <span class="badge bg-light text-secondary border">
                        <i class="bi bi-clock me-1"></i> Submitted {{ $application->submitted_at?->format('M d, Y, h:i A') ?? 'recently' }}
                    </span>
                </div>

                {{-- Highlight Stats Row --}}
                <div class="row g-3 mb-4">
                    {{-- Student ID --}}
                    <div class="col-sm-6 col-md-4">
                        <div class="sf-review-stat-tile">
                            <div class="sf-review-tile-label"><i class="bi bi-card-heading"></i> Student ID</div>
                            <div class="sf-review-tile-value sf-mono">{{ $profile->student_id_number ?: 'Not specified' }}</div>
                        </div>
                    </div>
                    {{-- Submitted GPA --}}
                    <div class="col-sm-6 col-md-4">
                        <div class="sf-review-stat-tile">
                            <div class="sf-review-tile-label"><i class="bi bi-award"></i> Submitted GWA</div>
                            <div class="sf-review-tile-value sf-mono d-flex align-items-center gap-1">
                                <span>{{ $application->gpa_submitted === null ? 'Not recorded' : number_format($application->gpa_submitted, 2) }}</span>
                                @if ($program->min_gpa !== null)
                                    @if ($meetsGpa)
                                        <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle ms-1" style="font-size: 0.68rem;">
                                            Meets Min
                                        </span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle ms-1" style="font-size: 0.68rem;">
                                            Below Min
                                        </span>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                    {{-- Year Level --}}
                    <div class="col-sm-6 col-md-4">
                        <div class="sf-review-stat-tile">
                            <div class="sf-review-tile-label"><i class="bi bi-mortarboard"></i> Year Level</div>
                            <div class="sf-review-tile-value d-flex align-items-center gap-1">
                                <span>Year {{ $profile->year_level ?? '—' }}</span>
                                @if ($yearLevelRestricted)
                                    @if ($yearLevelMeets)
                                        <x-status-badge :status="'Eligible'" class="ms-1" />
                                    @else
                                        <x-status-badge :status="'Ineligible'" class="ms-1" />
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                    {{-- Gender --}}
                    <div class="col-sm-6 col-md-4">
                        <div class="sf-review-stat-tile">
                            <div class="sf-review-tile-label"><i class="bi bi-gender-ambiguous"></i> Gender</div>
                            <div class="sf-review-tile-value">{{ $profile->gender ?: 'Not specified' }}</div>
                        </div>
                    </div>
                    {{-- Residence / Area --}}
                    <div class="col-sm-12 col-md-8">
                        <div class="sf-review-stat-tile">
                            <div class="sf-review-tile-label"><i class="bi bi-geo-alt"></i> Residence &amp; Barangay</div>
                            <div class="sf-review-tile-value text-truncate d-flex align-items-center gap-2">
                                <span class="text-truncate" style="font-size: 13px;">{{ $application->address_submitted ?: 'Address not recorded' }}</span>
                                @if ($application->is_rural_submitted === null)
                                    <span class="small text-secondary flex-shrink-0" style="font-size: 11px;">Classification N/A</span>
                                @elseif ($application->is_rural_submitted)
                                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle flex-shrink-0" style="font-size: 0.68rem;">
                                        <i class="bi bi-tree me-1"></i>Rural
                                    </span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle flex-shrink-0" style="font-size: 0.68rem;">
                                        <i class="bi bi-building me-1"></i>Urban
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Additional Profile Details --}}
                <div class="row g-3 pt-2 border-top">
                    <div class="col-md-6">
                        <div class="sf-review-tile-label">Full Name</div>
                        <div class="fw-semibold text-dark" style="font-size: 13.5px;">{{ $profile->user->name }}</div>
                        <div class="small text-secondary">{{ $profile->user->email }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="sf-review-tile-label">Academic Program</div>
                        <div class="fw-semibold text-dark" style="font-size: 13.5px;">{{ $profile->display_course }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="sf-review-tile-label">SLE-FHE Status</div>
                        <div>
                            <x-status-badge :status="($profile->sle_fhe_status ?? null) === 'Verified' ? 'Verified' : 'Pending'" />
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="sf-review-tile-label">Complete Address on Application</div>
                        <div class="small text-dark">{{ $application->address_submitted ?: 'Same as student profile' }}</div>
                    </div>
                </div>
            </div>

            {{-- Target Program Details --}}
            <div class="sf-review-card">
                <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                    <div class="p-2 rounded" style="background-color: rgba(15, 41, 66, 0.08); color: #0F2942;">
                        <i class="bi bi-building-check fs-5"></i>
                    </div>
                    <div>
                        <h3 class="sf-review-card-title mb-0">Target Program &amp; Slots</h3>
                        <p class="small text-secondary mb-0">Program guidelines, requirements &amp; remaining capacity</p>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="sf-review-tile-label">Program Name</div>
                        <div class="fw-semibold text-dark" style="font-size: 13.5px;">{{ $program->program_name }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="sf-review-tile-label">Sponsoring Organization</div>
                        <div class="fw-semibold text-dark" style="font-size: 13.5px;">{{ $program->sponsor->company_organization_name }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="sf-review-tile-label">Category</div>
                        <div>
                            <span class="badge rounded-2 fw-medium"
                                style="background-color: rgba(15,41,66,0.08); color:#0F2942;">
                                {{ $program->category->value }}
                            </span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="sf-review-tile-label">Available Slots</div>
                        <div class="fw-bold {{ $program->available_slots <= 0 ? 'text-danger' : 'text-success' }}" style="font-size: 13.5px;">
                            {{ $program->available_slots }} remaining
                            @if ($program->available_slots <= 0)
                                <span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle ms-1">Full</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="sf-review-tile-label">Target Year Levels</div>
                        <div class="small fw-semibold text-dark">
                            @if (! empty($allowedYearLevels))
                                {{ implode(', ', array_map(fn($y) => "Year {$y}", (array) $allowedYearLevels)) }}
                            @else
                                <span class="text-success fw-semibold">All Year Levels</span>
                            @endif
                        </div>
                    </div>
                    @if ($program->min_gpa)
                        <div class="col-md-4">
                            <div class="sf-review-tile-label">Min. GPA Required</div>
                            <div class="small fw-semibold text-dark">{{ number_format($program->min_gpa, 2) }} or better</div>
                        </div>
                    @endif
                    @if ($program->address_requirement)
                        <div class="col-md-8">
                            <div class="sf-review-tile-label">Address Requirement</div>
                            <div class="small fw-semibold text-dark">{{ $program->address_requirement }}</div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Application History / Timeline --}}
            <div class="sf-review-card">
                <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                    <div class="p-2 rounded" style="background-color: rgba(15, 41, 66, 0.08); color: #0F2942;">
                        <i class="bi bi-clock-history fs-5"></i>
                    </div>
                    <div>
                        <h3 class="sf-review-card-title mb-0">Review Timeline &amp; Notes</h3>
                        <p class="small text-secondary mb-0">Milestones recorded during application lifecycle</p>
                    </div>
                </div>
                <div class="row g-3 small">
                    <div class="col-md-4">
                        <div class="sf-review-tile-label">Submitted</div>
                        <div class="fw-semibold text-dark">{{ $application->submitted_at?->format('M d, Y, h:i A') ?? '—' }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="sf-review-tile-label">Verified At</div>
                        <div class="fw-semibold text-dark">{{ $application->verified_at?->format('M d, Y, h:i A') ?? 'Not yet verified' }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="sf-review-tile-label">Approved At</div>
                        <div class="fw-semibold text-dark">{{ $application->approved_at?->format('M d, Y, h:i A') ?? 'Not yet approved' }}</div>
                    </div>
                    @if ($application->rejection_reason)
                        <div class="col-12 mt-2">
                            <div class="sf-review-tile-label text-danger">Rejection Reason</div>
                            <div class="alert alert-danger border border-danger-subtle rounded-3 p-3 mb-0 text-danger-emphasis">
                                <i class="bi bi-info-circle me-1"></i> {{ $application->rejection_reason }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ── Right column: Supporting Documents & Decision Controls ─── --}}
        <div class="col-xl-5">

            {{-- Supporting Documents Section --}}
            <div class="sf-review-card">
                <div class="d-flex align-items-center justify-content-between gap-2 mb-3 pb-2 border-bottom">
                    <div>
                        <h3 class="sf-review-card-title mb-0">Supporting Documents</h3>
                        <p class="small text-secondary mb-0">Uploaded evidence for review</p>
                    </div>
                    <div class="p-2 rounded" style="background-color: rgba(15, 41, 66, 0.08); color: #0F2942;">
                        <i class="bi bi-file-earmark-check fs-5"></i>
                    </div>
                </div>

                @php
                    $allDocs = $application->documents->values();
                    $programLabels = array_values(array_filter(array_map(
                        'strval',
                        (array) ($program->required_documents ?? []),
                    )));
                    $requiredTypes = array_map(fn ($type) => \App\Enums\DocumentType::from($type), $program->beneficiaryRequiredDocumentTypes());

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
                    <div class="sf-review-doc-row">
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <div class="p-2 rounded {{ $document ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}" style="flex-shrink: 0;">
                                <i class="bi {{ $document ? 'bi-file-earmark-check' : 'bi-file-earmark-x' }} fs-5"></i>
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-semibold small text-dark">{{ $docGroup['label'] }}</div>
                                <div class="small text-secondary text-truncate" style="font-size: 11.5px;">
                                    {{ $document?->file_name ?? 'Not uploaded yet' }}
                                </div>
                            </div>
                            @if ($document)
                                <span class="sf-review-doc-badge-uploaded">
                                    <i class="bi bi-check-circle"></i> Uploaded
                                </span>
                            @else
                                <span class="sf-review-doc-badge-missing">
                                    <i class="bi bi-x-circle"></i> Missing
                                </span>
                            @endif
                        </div>

                        @if ($document)
                            <div class="d-flex gap-2 ms-5 ps-1">
                                @if ($config['docModals'])
                                    {{-- Inline Preview Modal Button --}}
                                    <button type="button" class="sf-review-doc-btn sf-review-doc-btn-view"
                                        data-bs-toggle="modal" data-bs-target="#{{ $docModalId }}">
                                        <i class="bi bi-eye"></i> Preview
                                    </button>
                                @else
                                    {{-- Direct Preview / Download Links --}}
                                    <a href="{{ route($config['docRoute'], [$application, $document]) }}"
                                        target="_blank" rel="noopener"
                                        class="sf-review-doc-btn sf-review-doc-btn-view">
                                        <i class="bi bi-eye"></i> Preview
                                    </a>
                                    <a href="{{ route($config['docRoute'], [$application, $document]) }}"
                                        download
                                        class="sf-review-doc-btn sf-review-doc-btn-download">
                                        <i class="bi bi-download"></i> Download
                                    </a>
                                @endif
                                {{-- Open Tab / View Button --}}
                                <a href="{{ route($config['docRoute'], [$application, $document]) }}"
                                    target="_blank" rel="noopener"
                                    class="sf-review-doc-btn sf-review-doc-btn-download">
                                    <i class="bi bi-box-arrow-up-right"></i> Open Tab
                                </a>
                            </div>

                            @if ($config['docModals'])
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
                                                <iframe src="{{ route($config['docRoute'], [$application, $document]) }}"
                                                    style="width: 100%; height: 75vh; border: none;"
                                                    title="{{ $docGroup['label'] }}">
                                                </iframe>
                                            </div>
                                            <div class="modal-footer d-flex justify-content-between">
                                                <span class="small text-secondary">
                                                    Applicant: <strong>{{ $profile->user->name }}</strong> ({{ $profile->student_id_number }})
                                                </span>
                                                <div class="d-flex gap-2">
                                                    <a href="{{ route($config['docRoute'], [$application, $document]) }}"
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
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Decision Controls --}}
            <div class="sf-review-decision-card">
                <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                    <div>
                        <h3 class="sf-review-card-title mb-0">Decision Controls</h3>
                        <p class="small text-secondary mb-0">FASSG eligibility review</p>
                    </div>
                    <span class="badge {{ $program->available_slots > 0 ? 'bg-success-subtle text-success-emphasis border border-success-subtle' : 'bg-danger-subtle text-danger-emphasis border border-danger-subtle' }} px-2 py-1">
                        {{ $program->available_slots }} slot{{ $program->available_slots !== 1 ? 's' : '' }} left
                    </span>
                </div>

                @if ($canAct)
                    @if ($isBatchReview)
                    {{-- Eligibility verification; submission is a separate batch action. --}}
                    <div class="mb-4">
                        <form method="POST" enctype="multipart/form-data" action="{{ route($config['approveRoute'], [$generatedBatch, $application]) }}"
                            onsubmit="return confirm('{{ $config['confirmMessage'] }}');">
                            @csrf
                            @method('PATCH')
                            @if ($application->is_auto_provisioned)
                                <div class="border rounded-3 p-3 mb-3 bg-white">
                                    <h4 class="h6 fw-bold text-dark mb-1">Fixed-list qualification evidence</h4>
                                    <p class="small text-secondary mb-2">Record only documented facts. Leave unknown optional values blank. This internal processing record does not require a student application.</p>
                                    <label for="gpa_submitted" class="form-label small fw-semibold text-dark">Documented GWA</label>
                                    <input id="gpa_submitted" name="gpa_submitted" type="number" step="0.01" min="1" max="5"
                                        class="form-control mb-2" value="{{ old('gpa_submitted', $application->gpa_submitted) }}"
                                        @required($qualificationRequirements['gpa_submitted'])>
                                    <label for="address_submitted" class="form-label small fw-semibold text-dark">Documented address</label>
                                    <input id="address_submitted" name="address_submitted" class="form-control mb-2" maxlength="255"
                                        value="{{ old('address_submitted', $application->address_submitted) }}"
                                        @required($qualificationRequirements['address_submitted'])>
                                    <label for="is_rural_submitted" class="form-label small fw-semibold text-dark">Documented residence classification</label>
                                    <select id="is_rural_submitted" name="is_rural_submitted" class="form-select mb-2"
                                        @required($qualificationRequirements['is_rural_submitted'])>
                                        <option value="" @selected(old('is_rural_submitted', $application->is_rural_submitted) === null)>Not recorded</option>
                                        <option value="1" @selected((string) old('is_rural_submitted', $application->is_rural_submitted) === '1')>Rural</option>
                                        <option value="0" @selected(old('is_rural_submitted', $application->is_rural_submitted) === false || (string) old('is_rural_submitted', $application->is_rural_submitted) === '0')>Urban</option>
                                    </select>
                                    @if ($program->requiresBeneficiaryRelationshipEvidence())
                                        @foreach (['employee_name' => 'Employee name', 'employee_id_number' => 'Employee ID', 'employee_relationship' => 'Relationship'] as $field => $label)
                                            <label class="form-label small fw-semibold text-dark" for="{{ $field }}">{{ $label }}</label>
                                            <input class="form-control mb-2" id="{{ $field }}" name="{{ $field }}" required maxlength="255"
                                                value="{{ old($field, $application->$field) }}">
                                        @endforeach
                                    @endif
                                    @foreach (array_unique($program->beneficiaryRequiredDocumentTypes()) as $documentType)
                                        <label class="form-label small fw-semibold text-dark" for="evidence-{{ $loop->index }}">{{ \App\Enums\DocumentType::from($documentType)->label() }}</label>
                                        <input class="form-control mb-2" type="file" id="evidence-{{ $loop->index }}"
                                            name="qualification_documents[{{ $documentType }}]" accept=".pdf,.jpg,.jpeg,.png">
                                    @endforeach
                                    <p class="small text-secondary mb-0">Existing documents are retained. Upload any missing program-required evidence (maximum 5 MB per file).</p>
                                </div>
                            @endif
                            <div class="p-3 bg-white border rounded-3 mb-3">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <i class="bi bi-patch-check" style="color:#0F2942;"></i>
                                    <span class="small fw-semibold text-dark">Verification Attestation</span>
                                </div>
                                <p class="small text-secondary mb-2">
                                    Confirm eligibility and each applicable qualification. Optional facts must not be invented.
                                </p>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="confirm_eligibility" value="1" id="confirm_eligibility" required>
                                    <label class="form-check-label small" for="confirm_eligibility">I reviewed the genuine SLE-FHE verification and all applicable program requirements.</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="confirm_gwa" value="1"
                                        id="confirm_gwa" @required($qualificationRequirements['gpa_submitted'])>
                                    <label class="form-check-label small" for="confirm_gwa">
                                        Confirm that the grade slip matches the submitted GWA.
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="confirm_address" value="1"
                                        id="confirm_address" @required($qualificationRequirements['address_submitted'])>
                                    <label class="form-check-label small" for="confirm_address">
                                        Confirm that the proof of residence and barangay certificate match the submitted address.
                                    </label>
                                </div>
                                @if ($needsCorAttest)
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="confirm_cor" value="1"
                                            id="confirm_cor" required>
                                        <label class="form-check-label small" for="confirm_cor">
                                            Confirm that the Certificate of Registration (COR) matches current enrollment.
                                        </label>
                                    </div>
                                @endif
                                @if ($needsKinshipAttest)
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="confirm_kinship" value="1"
                                            id="confirm_kinship" required>
                                        <label class="form-check-label small" for="confirm_kinship">
                                            Confirm that the Employee ID / Proof of Kinship matches the submitted employee dependency details.
                                        </label>
                                    </div>
                                @endif

                                @foreach (['confirm_eligibility', 'confirm_gwa', 'confirm_address', 'confirm_cor', 'confirm_kinship'] as $confirmField)
                                    @error($confirmField)
                                        <div class="text-danger small mt-2"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>
                                    @enderror
                                @endforeach
                            </div>
                            <button type="submit" class="btn {{ $config['approveButtonClass'] }} w-100 py-2 d-flex align-items-center justify-content-center gap-2"
                                style="border-radius: 8px; font-weight: 600;">
                                <i class="bi bi-check2-circle fs-5"></i>
                                <span>{!! $config['approveLabel'] !!}</span>
                            </button>
                        </form>
                        <p class="small text-muted mb-0 mt-2">
                            <i class="bi bi-shield-check me-1 text-success"></i>
                            @if ($isVerification)
                                Records eligibility only. Submit the Generated Batch separately after every member is verified.
                            @else
                                Marks the application as Verified and moves it into the verified review set.
                            @endif
                        </p>
                    </div>
                    @else
                        <div class="alert alert-info border-info-subtle rounded-3 small">
                            <i class="bi bi-info-circle me-1"></i> Add this applicant to a Saved Generated Batch, then review the member's eligibility and documents from that batch.
                        </div>
                    @endif
                    <hr class="my-3">

                    {{-- ── 2. REJECT APPLICATION ACTION (Mandatory Reason) ── --}}
                    <div>
                        <h3 class="h6 fw-bold text-danger mb-1 d-flex align-items-center gap-2">
                            <i class="bi bi-x-circle"></i> <span>Reject Application</span>
                        </h3>
                        <p class="small text-secondary mb-3">
                            Provide a reason so the student understands why their application was rejected.
                        </p>

                        <form method="POST" action="{{ route($config['rejectRoute'], $application) }}"
                            id="sfRejectForm-{{ $application->id }}" data-sf-reject-form>
                            @csrf
                            @method('PATCH')

                            <div class="mb-2">
                                <label for="reject-reason-{{ $application->id }}" class="form-label small fw-semibold text-dark mb-1">Rejection Reason <span class="text-danger">*</span></label>
                                <textarea id="reject-reason-{{ $application->id }}" class="form-control" name="reason" rows="3"
                                    placeholder="Enter specific rejection reason (mandatory)..." required maxlength="500"
                                    aria-label="Rejection reason">{{ old('reason') }}</textarea>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="small text-secondary" style="font-size: 11.5px;">Student will receive this explanation.</span>
                                <button type="submit" class="btn btn-outline-danger btn-sm fw-semibold px-3" style="border-radius: 6px; height: 34px;">
                                    <i class="bi bi-x-octagon me-1"></i> Reject Application
                                </button>
                            </div>
                        </form>
                    </div>

                    <hr class="my-3">

                    {{-- ── 3. REQUEST DOCUMENT RESUBMISSION ACTION ── --}}
                    <div>
                        <h3 class="h6 fw-bold mb-1 d-flex align-items-center gap-2" style="color: #986515;">
                            <i class="bi bi-arrow-counterclockwise"></i> <span>Request Document Resubmission</span>
                        </h3>
                        <p class="small text-secondary mb-3">
                            Ask the student to upload a corrected or clearer copy of a document. A mandatory note is attached so the student knows exactly what to fix.
                        </p>
                        <form method="POST" action="{{ route($config['resubmissionRoute'], $application) }}"
                            id="sfResubmitForm-{{ $application->id }}" data-sf-resubmit-form>
                            @csrf
                            @method('PATCH')
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-dark mb-1">
                                    Documents to Re-upload <span class="text-danger">*</span>
                                </label>
                                <div class="border rounded-3 bg-white p-3" style="max-height: 200px; overflow-y: auto;">
                                    @foreach ($resubReqLabels as $resubReqCanonValue => $resubReqLabel)
                                        <label class="d-flex align-items-start gap-2 small mb-2 text-dark">
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
                                <div class="form-text small text-secondary mt-1">
                                    Select at least one document the student must replace.
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-dark mb-1" for="resubmission_notes">
                                    Resubmission Notes <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control" id="resubmission_notes" name="resubmission_notes" rows="3"
                                    placeholder="e.g., Uploaded COR is unreadable. Please upload a clear copy."
                                    required minlength="5" maxlength="1000">{{ old('resubmission_notes') }}</textarea>
                                <div class="form-text small text-secondary mt-1">
                                    Mandatory. Displayed to the student on their application page.
                                </div>
                            </div>
                            <button type="submit" class="btn btn-warning w-100 fw-semibold text-dark py-2" style="border-radius: 8px;">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> Request Document Resubmission
                            </button>
                        </form>
                    </div>

                    <div class="small text-secondary mt-4 pt-3 border-top" style="font-size: 11.5px;">
                        <i class="bi bi-info-circle me-1"></i>
                        Verification records eligibility only. It does not submit the batch, approve sponsorship, or forward anyone to Accounting.
                    </div>
                @elseif ($isSettledVerified)
                    {{-- Application Verified (read-only) --}}
                    <div class="p-4 text-center">
                        <div class="mb-3">
                            <i class="bi bi-patch-check-fill display-4" style="color: #15803D;"></i>
                        </div>
                        <h3 class="h6 sf-heading mb-2 fw-bold" style="color: #0F2942;">{!! $config['settledTitle'] !!}</h3>
                        <p class="small text-secondary mb-0">
                            This application was verified on
                            <strong>{{ $application->verified_at?->format('M d, Y, h:i A') ?? '—' }}</strong>.
                            Sponsor review requires a separately submitted Generated Batch.
                        </p>
                    </div>
                @elseif ($application->status === \App\Enums\ApplicationStatus::Pending)
                    <div class="alert alert-info mb-0 border-info-subtle rounded-3 small">
                        <i class="bi bi-info-circle me-1"></i>
                        Add this applicant to a Saved Generated Batch, then review the member's eligibility and documents from that batch.
                        Submitted batch records cannot be verified again here.
                    </div>
                @else
                    {{-- Application Already Finalized --}}
                    <div class="alert {{ $application->status === \App\Enums\ApplicationStatus::Approved ? 'alert-success border-success-subtle' : 'alert-secondary border-secondary-subtle' }} mb-0 rounded-3">
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

    @if ($canAct)
        {{-- Custom Reject Application Confirmation Modal (Desktop >= 992px) --}}
        <div class="modal fade sf-confirm-modal" id="sfRejectApplicationModal" tabindex="-1"
            aria-labelledby="sfRejectApplicationModalTitle" aria-hidden="true"
            data-bs-keyboard="true" data-bs-backdrop="true">
            <div class="modal-dialog modal-dialog-centered" style="max-width: 560px;">
                <div class="modal-content">
                    <div class="sf-confirm-header">
                        <div class="sf-confirm-header-left">
                            <div class="sf-confirm-icon-box red">
                                <i class="bi bi-x-circle"></i>
                            </div>
                            <div class="sf-confirm-title-area">
                                <h3 class="sf-confirm-title" id="sfRejectApplicationModalTitle">Reject Application?</h3>
                                <p class="sf-confirm-subtitle">{{ $profile->user->name }} · {{ $profile->student_id_number ?: 'Student ID N/A' }}</p>
                            </div>
                        </div>
                        <button type="button" class="sf-confirm-close" data-bs-dismiss="modal" aria-label="Close">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <div class="sf-confirm-body">
                        <p class="sf-confirm-desc">Are you sure you want to reject this student's sponsorship application?</p>

                        <div class="sf-confirm-preview-box">
                            <div class="sf-confirm-preview-label">Recorded Rejection Reason</div>
                            <div class="sf-confirm-preview-content" id="sfRejectReasonPreview">—</div>
                        </div>

                        <div class="sf-confirm-notice red">
                            <i class="bi bi-info-circle"></i>
                            <div>This decision will mark the application as rejected. The rejection reason you entered will be recorded and communicated to the student.</div>
                        </div>

                        <div class="sf-confirm-notice neutral">
                            <i class="bi bi-exclamation-triangle"></i>
                            <div>This action cannot be undone through this confirmation.</div>
                        </div>
                    </div>
                    <div class="sf-confirm-footer">
                        <button type="button" class="sf-modal-btn sf-modal-btn-cancel" data-bs-dismiss="modal">Keep Application</button>
                        <button type="button" class="sf-modal-btn sf-modal-btn-danger" id="sfRejectConfirmBtn">
                            <i class="bi bi-x-circle"></i>Reject Application
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Custom Request Document Resubmission Confirmation Modal (Desktop >= 992px) --}}
        <div class="modal fade sf-confirm-modal sf-confirm-modal-wide" id="sfResubmitModal" tabindex="-1"
            aria-labelledby="sfResubmitModalTitle" aria-hidden="true"
            data-bs-keyboard="true" data-bs-backdrop="true">
            <div class="modal-dialog modal-dialog-centered" style="max-width: 600px;">
                <div class="modal-content">
                    <div class="sf-confirm-header">
                        <div class="sf-confirm-header-left">
                            <div class="sf-confirm-icon-box amber">
                                <i class="bi bi-arrow-repeat"></i>
                            </div>
                            <div class="sf-confirm-title-area">
                                <h3 class="sf-confirm-title" id="sfResubmitModalTitle">Request Document Resubmission?</h3>
                                <p class="sf-confirm-subtitle">{{ $profile->user->name }} · {{ $profile->student_id_number ?: 'Student ID N/A' }}</p>
                            </div>
                        </div>
                        <button type="button" class="sf-confirm-close" data-bs-dismiss="modal" aria-label="Close">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <div class="sf-confirm-body">
                        <p class="sf-confirm-desc">The student will be asked to upload corrected or clearer copies of the selected documents.</p>

                        <div class="sf-confirm-preview-box">
                            <div class="sf-confirm-preview-label">Selected Documents Requiring Replacement</div>
                            <ul class="sf-confirm-preview-list" id="sfResubmitDocsPreview">
                                {{-- Populated dynamically --}}
                            </ul>
                            <div class="sf-confirm-preview-label mt-3">Entered Resubmission Notes</div>
                            <div class="sf-confirm-preview-content" id="sfResubmitNotesPreview">—</div>
                        </div>

                        <div class="sf-confirm-notice amber">
                            <i class="bi bi-info-circle"></i>
                            <div>The student's application will follow the existing document resubmission workflow. The selected requirements and instructions will be communicated to the student.</div>
                        </div>
                    </div>
                    <div class="sf-confirm-footer">
                        <button type="button" class="sf-modal-btn sf-modal-btn-cancel" data-bs-dismiss="modal">Continue Editing</button>
                        <button type="button" class="sf-modal-btn sf-modal-btn-amber" id="sfResubmitConfirmBtn">
                            <i class="bi bi-arrow-repeat"></i>Request Resubmission
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@if ($canAct)
@push('scripts')
<script>
(function () {
    'use strict';

    // ── C. REJECT APPLICATION CONFIRMATION ──
    var rejectModalEl = document.getElementById('sfRejectApplicationModal');
    var rejectForm = document.querySelector('form[data-sf-reject-form]');
    if (rejectModalEl && rejectForm) {
        var rejectReasonPreview = document.getElementById('sfRejectReasonPreview');
        var rejectConfirmBtn = document.getElementById('sfRejectConfirmBtn');
        var bsRejectModal = new bootstrap.Modal(rejectModalEl);
        var rejecting = false;
        var REJECT_MOBILE_FALLBACK = 'Are you sure you want to reject this application? This action cannot be undone.';

        rejectForm.addEventListener('submit', function (e) {
            if (rejectForm.dataset.sfConfirmed === '1') {
                rejectForm.dataset.sfConfirmed = '';
                return;
            }

            // Client-side validation must pass first!
            if (!rejectForm.checkValidity()) {
                rejectForm.reportValidity();
                e.preventDefault();
                return;
            }

            e.preventDefault();

            if (window.innerWidth < 992) {
                if (confirm(REJECT_MOBILE_FALLBACK)) {
                    rejectForm.dataset.sfConfirmed = '1';
                    rejectForm.requestSubmit();
                }
                return;
            }

            var reasonTextarea = rejectForm.querySelector('textarea[name="reason"]');
            var reasonValue = reasonTextarea ? reasonTextarea.value.trim() : '';
            rejectReasonPreview.textContent = reasonValue || 'No reason specified';

            rejecting = false;
            rejectConfirmBtn.disabled = false;
            rejectConfirmBtn.innerHTML = '<i class="bi bi-x-circle"></i>Reject Application';
            bsRejectModal.show();
        });

        rejectConfirmBtn.addEventListener('click', function () {
            if (rejecting) return;
            rejecting = true;
            rejectConfirmBtn.disabled = true;
            rejectConfirmBtn.innerHTML = '<i class="bi bi-hourglass-split"></i>Rejecting\u2026';
            rejectForm.dataset.sfConfirmed = '1';
            try {
                rejectForm.requestSubmit();
            } catch (err) {
                rejecting = false;
                rejectConfirmBtn.disabled = false;
                rejectConfirmBtn.innerHTML = '<i class="bi bi-x-circle"></i>Reject Application';
                rejectForm.dataset.sfConfirmed = '';
            }
        });

        window.addEventListener('pageshow', function (e) {
            if (e.persisted) {
                rejecting = false;
                if (rejectConfirmBtn) {
                    rejectConfirmBtn.disabled = false;
                    rejectConfirmBtn.innerHTML = '<i class="bi bi-x-circle"></i>Reject Application';
                }
            }
        });
    }

    // ── D. REQUEST DOCUMENT RESUBMISSION CONFIRMATION ──
    var resubmitModalEl = document.getElementById('sfResubmitModal');
    var resubmitForm = document.querySelector('form[data-sf-resubmit-form]');
    if (resubmitModalEl && resubmitForm) {
        var resubmitDocsPreview = document.getElementById('sfResubmitDocsPreview');
        var resubmitNotesPreview = document.getElementById('sfResubmitNotesPreview');
        var resubmitConfirmBtn = document.getElementById('sfResubmitConfirmBtn');
        var bsResubmitModal = new bootstrap.Modal(resubmitModalEl);
        var resubmitting = false;
        var RESUBMIT_MOBILE_FALLBACK = 'Send this application back to the student for document resubmission?';

        resubmitForm.addEventListener('submit', function (e) {
            if (resubmitForm.dataset.sfConfirmed === '1') {
                resubmitForm.dataset.sfConfirmed = '';
                return;
            }

            // Check that at least one document is selected
            var checkedDocs = Array.from(resubmitForm.querySelectorAll('input[name="requested_documents[]"]:checked'));
            if (checkedDocs.length === 0) {
                e.preventDefault();
                alert('Please select at least one document the student must replace.');
                var firstDoc = resubmitForm.querySelector('input[name="requested_documents[]"]');
                if (firstDoc) firstDoc.focus();
                return;
            }

            // Check client-side validation for textarea
            if (!resubmitForm.checkValidity()) {
                resubmitForm.reportValidity();
                e.preventDefault();
                return;
            }

            e.preventDefault();

            if (window.innerWidth < 992) {
                if (confirm(RESUBMIT_MOBILE_FALLBACK)) {
                    resubmitForm.dataset.sfConfirmed = '1';
                    resubmitForm.requestSubmit();
                }
                return;
            }

            // Populate document labels
            resubmitDocsPreview.innerHTML = '';
            checkedDocs.forEach(function (chk) {
                var labelText = chk.closest('label')?.querySelector('span')?.textContent?.trim() || chk.value;
                var li = document.createElement('li');
                var icon = document.createElement('i');
                icon.className = 'bi bi-file-earmark-arrow-up';
                li.appendChild(icon);
                li.appendChild(document.createTextNode(labelText));
                resubmitDocsPreview.appendChild(li);
            });

            // Populate notes
            var notesTextarea = resubmitForm.querySelector('textarea[name="resubmission_notes"]');
            var notesValue = notesTextarea ? notesTextarea.value.trim() : '';
            resubmitNotesPreview.textContent = notesValue || 'No notes specified';

            resubmitting = false;
            resubmitConfirmBtn.disabled = false;
            resubmitConfirmBtn.innerHTML = '<i class="bi bi-arrow-repeat"></i>Request Resubmission';
            bsResubmitModal.show();
        });

        resubmitConfirmBtn.addEventListener('click', function () {
            if (resubmitting) return;
            resubmitting = true;
            resubmitConfirmBtn.disabled = true;
            resubmitConfirmBtn.innerHTML = '<i class="bi bi-hourglass-split"></i>Requesting\u2026';
            resubmitForm.dataset.sfConfirmed = '1';
            try {
                resubmitForm.requestSubmit();
            } catch (err) {
                resubmitting = false;
                resubmitConfirmBtn.disabled = false;
                resubmitConfirmBtn.innerHTML = '<i class="bi bi-arrow-repeat"></i>Request Resubmission';
                resubmitForm.dataset.sfConfirmed = '';
            }
        });

        window.addEventListener('pageshow', function (e) {
            if (e.persisted) {
                resubmitting = false;
                if (resubmitConfirmBtn) {
                    resubmitConfirmBtn.disabled = false;
                    resubmitConfirmBtn.innerHTML = '<i class="bi bi-arrow-repeat"></i>Request Resubmission';
                }
            }
        });
    }
})();
</script>
@endpush
@endif
