@extends('layouts.app')

@section('title', 'Application Detail')
@section('eyebrow', 'Student Portal · My Applications')
@section('page-title', $application->sponsorshipProgram->program_name ?? 'Application Detail')

@push('styles')
    <style>
        /* Scoped to the application detail view; no global Bootstrap overrides. */
        .sf-appdetail-card {
            border-radius: 14px;
            border-color: #E1E8EC;
            box-shadow: 0 1px 3px 0 rgba(15, 37, 55, 0.05);
        }

        .sf-appdetail-filename {
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            overflow: hidden;
            overflow-wrap: anywhere;
            max-width: 100%;
            line-height: 1.35;
        }

        .sf-appdetail-doc {
            min-height: 92px;
        }

        .sf-appdetail-filename:focus-visible {
            outline: 3px solid #0F2942;
            outline-offset: 2px;
        }

        .sf-appdetail-summary li {
            gap: 0.75rem;
        }

        .sf-appdetail-summary dt {
            color: #64748B;
            font-weight: 500;
        }

        @media (max-width: 575.98px) {
            .sf-appdetail-summary li {
                flex-direction: column;
                gap: 0.125rem;
            }

            .sf-appdetail-doc {
                min-height: 128px;
            }
        }
    </style>
@endpush

@section('content')

    @php
        $statusValue =
            $application->status instanceof \BackedEnum ? $application->status->value : (string) $application->status;

        $status = $statusValue;
        $terminal = in_array($statusValue, ['Rejected', 'Expired'], true);
        $resubmissionRequested = $statusValue === 'Resubmission Requested';

        $isStep1Done = true;
        $isStep2Done = $application->verified_at !== null || in_array($statusValue, ['Verified', 'Approved', 'Ongoing'], true);
        $isStep3Done = $application->approved_at !== null || in_array($statusValue, ['Approved', 'Ongoing'], true);
        $isStep4Done = in_array($statusValue, ['Approved', 'Ongoing'], true);

        $steps = [
            [
                'label' => 'Application Submitted',
                'icon' => 'bi-send',
                'done' => $isStep1Done,
                'status' => optional($application->submitted_at ?? $application->created_at)->format('M d, Y, h:i A') ?? '—',
            ],
            [
                'label' => 'FASSG Application Review',
                'icon' => 'bi-search',
                'done' => $isStep2Done,
                'status' => $isStep2Done ? 'Completed' : ($isStep1Done ? 'Awaiting Review' : 'Pending'),
            ],
            [
                'label' => 'Sponsor Batch Decision',
                'icon' => 'bi-clipboard-check',
                'done' => $isStep3Done,
                'status' => $isStep3Done ? 'Completed' : ($isStep2Done ? 'Awaiting Decision' : 'Pending'),
            ],
            [
                'label' => 'Ongoing',
                'icon' => 'bi-award',
                'done' => $isStep4Done,
                'status' => $isStep4Done ? 'Approved & Confirmed' : 'Pending',
            ],
        ];

        $currentStepIndex = null;
        if (!$terminal) {
            foreach ($steps as $idx => $s) {
                if (!$s['done']) {
                    $currentStepIndex = $idx;
                    break;
                }
            }
        }
    @endphp

    {{-- Resubmission Requested Banner --}}
    @if ($resubmissionRequested)
        <div class="alert alert-warning alert-dismissible fade show mb-4 border border-warning-subtle" role="alert">
            <div class="d-flex align-items-start gap-3">
                <i class="bi bi-exclamation-triangle fs-4 flex-shrink-0" aria-hidden="true"></i>
                <div class="flex-grow-1">
                    <div class="fw-bold mb-1">Document Resubmission Requested</div>
                    <p class="small mb-0">
                        The FASSG office has asked you to replace or provide a clearer copy of one or more documents before this application can continue being reviewed.
                    </p>
                    @if ($application->resubmission_notes)
                        <div class="mt-2 p-2 bg-white border rounded-3">
                            <span class="fw-semibold small">FASSG note:</span>
                            <span class="small">{{ $application->resubmission_notes }}</span>
                        </div>
                    @endif
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Dismiss notice"></button>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-8">

            {{-- Application Progress Timeline --}}
            <div class="card sf-card sf-appdetail-card mb-4">
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                        <h3 class="h6 sf-heading mb-0">Application Progress</h3>
                        <x-student-application-status-badge :status="$status" />
                    </div>

                    @if ($terminal)
                        <div class="alert {{ $status === 'Rejected' ? 'alert-danger' : 'alert-secondary' }} border-0 rounded-3 mb-4">
                            <i class="bi {{ $status === 'Rejected' ? 'bi-x-circle' : 'bi-clock-history' }} me-1" aria-hidden="true"></i>
                            This application is <strong>{{ $status }}</strong>.
                            @if ($status === 'Rejected' && $application->rejection_reason)
                                <div class="small mt-2"><strong>Reason:</strong> {{ $application->rejection_reason }}</div>
                            @endif
                            @if ($status === 'Expired')
                                You're eligible to re-apply to a new open program.
                            @endif
                        </div>
                    @endif

                    <div class="sf-app-timeline">
                        <div class="sf-app-timeline-track" aria-hidden="true"></div>
                        @foreach ($steps as $i => $step)
                            @php
                                if (!$terminal && $step['done']) {
                                    $stepState = 'completed';
                                } elseif (!$terminal && $i === $currentStepIndex) {
                                    $stepState = 'current';
                                } else {
                                    $stepState = 'upcoming';
                                }
                            @endphp
                            <div class="sf-app-timeline-step">
                                <div class="sf-app-timeline-circle is-{{ $stepState }}"
                                    aria-label="Stage {{ $i + 1 }}: {{ $step['label'] }} ({{ ucfirst($stepState) }})">
                                    <i class="bi {{ $stepState === 'completed' ? 'bi-check-lg' : $step['icon'] }}" aria-hidden="true"></i>
                                </div>
                                <p class="sf-app-timeline-label is-{{ $stepState }}">
                                    {{ $step['label'] }}
                                </p>
                                @if (
                                    $step['label'] === 'Ongoing' &&
                                        strtolower((string) ($application->status?->value ?? $application->status)) === 'approved')
                                    <div class="sf-app-timeline-status fw-bold text-success">Approved &amp; Confirmed</div>
                                    <small class="text-muted d-block" style="font-size: 0.72rem;">{{ $application->updated_at?->format('M d, Y, h:i A') ?? '—' }}</small>
                                @else
                                    <p class="sf-app-timeline-status {{ $stepState === 'completed' ? 'text-success fw-medium' : ($stepState === 'current' ? 'text-primary fw-medium' : 'text-muted') }}">
                                        {{ $step['status'] }}
                                    </p>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <p class="small text-secondary mb-0 mt-4 pt-3 border-top" style="line-height: 1.55;">
                        SLE-FHE verification is a separate institutional eligibility record. FASSG reviews your documents,
                        and the sponsor approves the whole batch. Submitting an application does not guarantee a grant.
                    </p>
                </div>
            </div>

            {{-- Submitted Details --}}
            <div class="card sf-card sf-appdetail-card mb-4">
                <div class="card-body p-4">
                    <h3 class="h6 sf-heading mb-3">Submitted Details</h3>
                    <dl class="row g-0 mb-0 sf-appdetail-summary">
                        <dt class="col-sm-4 small text-secondary fw-normal py-2">GPA Submitted</dt>
                        <dd class="col-sm-8 py-2 mb-0">
                            {{ number_format((float) ($application->gpa_submitted ?? ($application->current_gpa ?? 0)), 2) }}
                        </dd>

                        <dt class="col-sm-4 small text-secondary fw-normal py-2 border-top">Address Submitted</dt>
                        <dd class="col-sm-8 py-2 mb-0 border-top">
                            {{ $application->address_submitted ?? ($application->current_address ?? 'N/A') }}</dd>

                        <dt class="col-sm-4 small text-secondary fw-normal py-2 border-top">Campus Submitted</dt>
                        <dd class="col-sm-8 py-2 mb-0 border-top">
                            @if (filled($application->studentProfile?->campus))
                                {{ $application->studentProfile->campus }}
                            @else
                                <span aria-label="Campus not recorded">—</span>
                            @endif
                        </dd>

                        <dt class="col-sm-4 small text-secondary fw-normal py-2 border-top">Address Classification</dt>
                        <dd class="col-sm-8 py-2 mb-0 border-top">
                            @if ($application->is_rural_submitted === null)
                                <span class="text-secondary" aria-label="Address classification not recorded">—</span>
                            @else
                                {{ $application->is_rural_submitted ? 'Rural' : 'Urban' }}
                            @endif
                        </dd>
                    </dl>
                </div>
            </div>

            {{-- Uploaded Documents --}}
            <div class="card sf-card sf-appdetail-card mb-4 overflow-hidden">
                <div class="card-header bg-white border-bottom py-3 px-3 px-sm-4">
                    <h3 class="h6 sf-heading mb-0">Uploaded Documents</h3>
                </div>
                <div class="card-body p-3 p-sm-4">
                    <div class="d-flex flex-column gap-3">
                        @forelse ($application->documents as $doc)
                            @php
                                $documentType = $doc->document_type instanceof \App\Enums\DocumentType
                                    ? $doc->document_type->label()
                                    : \Illuminate\Support\Str::headline(str_replace('_', ' ', (string) ($doc->document_type ?? 'Document')));
                                $fileName = !empty($doc->file_name)
                                    ? $doc->file_name
                                    : (!empty($doc->file_path) ? basename($doc->file_path) : 'No file recorded');
                                $hasFile = !empty($doc->file_path);
                            @endphp
                            <div class="sf-appdetail-doc d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between p-3 border rounded-3 bg-light gap-2 w-100">
                                <div class="d-flex align-items-center gap-3 min-w-0 w-100">
                                    <span class="sf-stat-icon bg-white border text-primary flex-shrink-0"
                                        style="width: 40px; height: 40px; border-radius: 10px;">
                                        <i class="bi bi-file-earmark" aria-hidden="true"></i>
                                    </span>
                                    <div class="min-w-0 flex-grow-1">
                                        <div class="fw-semibold text-dark small">{{ $documentType }}</div>
                                        <div class="sf-appdetail-filename text-secondary small" tabindex="0"
                                            title="Filename: {{ $fileName }}" aria-label="Filename: {{ $fileName }}">{{ $fileName }}</div>
                                    </div>
                                </div>
                                @if ($hasFile)
                                    <a href="{{ route('documents.show', $doc) }}" target="_blank" rel="noopener"
                                        class="sf-btn-student-secondary flex-shrink-0 align-self-end align-self-sm-center"
                                        aria-label="View {{ $documentType }}: {{ $fileName }}">
                                        <i class="bi bi-eye" aria-hidden="true"></i> View
                                    </a>
                                @else
                                    <span class="small text-secondary flex-shrink-0 align-self-end align-self-sm-center"
                                        title="The stored file is unavailable">
                                        <i class="bi bi-exclamation-circle" aria-hidden="true"></i> Unavailable
                                    </span>
                                @endif
                            </div>
                        @empty
                            <div class="p-3 text-center text-muted small">No documents uploaded for this application.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Re-upload Corrected Documents (Resubmission) --}}
            @if ($resubmissionRequested)
                @php
                    $upDocCanons = $application->sponsorshipProgram?->requiredDocumentCanonicalValues() ?? [];
                    $upDocsList = $application->documents->values();
                    $requestedCanons = array_values((array) $application->requested_documents);
                    $resubGroups = [];
                    $resubCovered = [];

                    foreach ($upDocCanons as $canon) {
                        $label = $canon;
                        foreach (\App\Enums\DocumentType::cases() as $type) {
                            if (\App\Enums\DocumentType::canonicalValue($type) === $canon) {
                                $label = $type->label();
                                break;
                            }
                        }
                        $resubGroups[] = [
                            'canon' => $canon,
                            'label' => $label,
                            'document' => $upDocsList->first(
                                fn ($doc) => \App\Enums\DocumentType::canonicalValue($doc->document_type) === $canon,
                            ),
                        ];
                        $resubCovered[] = $canon;
                    }

                    foreach ($upDocsList as $extraDoc) {
                        $canon = \App\Enums\DocumentType::canonicalValue($extraDoc->document_type);
                        if (in_array($canon, $resubCovered, true)) {
                            continue;
                        }
                        $resubCovered[] = $canon;
                        $resubGroups[] = [
                            'canon' => $canon,
                            'label' => $extraDoc->document_type instanceof \BackedEnum
                                ? $extraDoc->document_type->label()
                                : (string) $extraDoc->document_type,
                            'document' => $extraDoc,
                        ];
                    }
                @endphp

                <div class="card sf-card sf-appdetail-card mb-4 border-warning border-2">
                    <div class="card-header bg-white border-bottom py-3 px-3 px-sm-4">
                        <h3 class="h6 sf-heading mb-0">
                            <i class="bi bi-arrow-counterclockwise text-warning me-2" aria-hidden="true"></i>Re-upload Corrected Documents
                        </h3>
                    </div>
                    <div class="card-body p-3 p-sm-4">
                        <p class="small text-secondary mb-3">
                            Select an updated file below. Only documents you attach a new file for will be replaced. Once you resubmit, your application goes back under review by the FASSG office.
                        </p>
                        <form method="POST" action="{{ route('student.applications.resubmit', $application) }}" enctype="multipart/form-data">
                            @csrf
                            <div class="d-flex flex-column gap-3">
                                @foreach ($resubGroups as $group)
                                    @php($groupActionRequired = in_array($group['canon'], $requestedCanons, true))
                                    <div class="border rounded-3 p-3 bg-light {{ $groupActionRequired ? 'border-warning border-2' : '' }}">
                                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                            <span class="fw-semibold small">{{ $group['label'] }}</span>
                                            @if ($groupActionRequired)
                                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                                                    <i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i>Action Required: Re-upload Needed
                                                </span>
                                            @endif
                                            @if ($group['document'])
                                                <span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                    Uploaded
                                                </span>
                                            @else
                                                <span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle">
                                                    Missing
                                                </span>
                                            @endif
                                        </div>
                                        @if ($group['document'])
                                            <div class="small text-secondary sf-appdetail-filename mb-1"
                                                title="Filename: {{ $group['document']->file_name }}"
                                                aria-label="Filename: {{ $group['document']->file_name }}">{{ $group['document']->file_name }}</div>
                                        @endif
                                        <input type="file" class="form-control form-control-sm"
                                            name="documents[{{ $group['canon'] }}]"
                                            accept=".pdf,.jpg,.jpeg,.png"
                                            aria-label="Replace {{ $group['label'] }}">
                                    </div>
                                @endforeach
                            </div>
                            <button type="submit" class="btn btn-warning w-100 fw-semibold text-dark mt-3">
                                <i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i> Resubmit Corrected Documents
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </div>

        {{-- Right Side Program Summary --}}
        <div class="col-lg-4">
            <div class="card sf-card sf-appdetail-card">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <span class="rounded-3 d-inline-flex align-items-center justify-content-center flex-shrink-0"
                            style="width: 40px; height: 40px; background-color: #E8F0F6; color: #0F2942;">
                            <i class="bi bi-file-earmark-text fs-5" aria-hidden="true"></i>
                        </span>
                        <h3 class="h6 sf-heading mb-0">Program Summary</h3>
                    </div>
                    <div class="fw-semibold text-dark">{{ $application->sponsorshipProgram->program_name ?? 'Sponsorship Program' }}</div>
                    <div class="small text-secondary mb-3">
                        {{ $application->sponsorshipProgram->sponsor->company_organization_name ?? 'Sponsor' }}</div>

                    <ul class="list-unstyled small mb-0 sf-appdetail-summary">
                        <li class="d-flex justify-content-between py-2 border-top">
                            <span class="text-secondary">Submitted</span>
                            <span class="text-end">{{ optional($application->submitted_at ?? $application->created_at)->format('M d, Y, h:i A') ?? '—' }}</span>
                        </li>
                        <li class="d-flex justify-content-between py-2 border-top">
                            <span class="text-secondary">Verified</span>
                            <span class="text-end">{{ optional($application->verified_at)->format('M d, Y, h:i A') ?? '—' }}</span>
                        </li>
                        <li class="d-flex justify-content-between py-2 border-top">
                            <span class="text-secondary">Approved</span>
                            <span class="text-end">{{ optional($application->approved_at)->format('M d, Y, h:i A') ?? '—' }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <a href="{{ route('student.applications.index') }}"
                class="sf-btn-student-secondary w-100 mt-3">
                <i class="bi bi-arrow-left" aria-hidden="true"></i> Back to My Applications
            </a>
        </div>
    </div>

@endsection
