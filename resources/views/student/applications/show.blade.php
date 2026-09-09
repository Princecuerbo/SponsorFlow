@extends('layouts.app')

@section('title', 'Application Detail')
@section('eyebrow', 'Student Portal · My Applications')
@section('page-title', $application->sponsorshipProgram->program_name ?? 'Application Detail')

@section('content')

    @php
        $steps = ['Pending', 'FASSG Verified', 'Sponsor Reviewed', 'Final Approval'];

        $statusValue =
            $application->status instanceof \BackedEnum ? $application->status->value : (string) $application->status;

        $status = $statusValue;
        $terminal = in_array($statusValue, ['Rejected', 'Expired'], true);
        $resubmissionRequested = $statusValue === 'Resubmission Requested';

        $isStep1Complete = in_array(
            $statusValue,
            ['Pending', 'Verified', 'FASSG Verified', 'Sponsor Reviewed', 'Approved', 'Confirmed', 'Ongoing'],
            true,
        );
        $isStep2Complete = $application->verified_at !== null;
        $isStep3Complete = in_array($statusValue, ['Sponsor Reviewed', 'Approved', 'Confirmed'], true);
        $isStep4Complete = in_array($statusValue, ['Approved', 'Confirmed', 'Final Approval'], true)
            && $application->approved_at !== null;

        $completedSteps = [$isStep1Complete, $isStep2Complete, $isStep3Complete, $isStep4Complete];
    @endphp

    {{-- Resubmission Requested Banner --}}
    @if ($resubmissionRequested)
        <div class="alert alert-warning alert-dismissible fade show mb-4 border border-warning-subtle shadow-sm" role="alert">
            <div class="d-flex align-items-start gap-3">
                <i class="bi bi-exclamation-triangle-fill fs-4 flex-shrink-0"></i>
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
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-8">

            {{-- Application Progress Timeline --}}
            <div class="card sf-card mb-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="h6 sf-heading mb-0">Application Progress</h2>
                        <x-status-badge :status="$status" />
                    </div>

                    @if ($terminal)
                        <div
                            class="alert {{ $status === 'Rejected' ? 'alert-danger' : 'alert-secondary' }} border-0 rounded-3 mb-4">
                            <i class="bi {{ $status === 'Rejected' ? 'bi-x-circle-fill' : 'bi-clock-history' }} me-1"></i>
                            This application is <strong>{{ $status }}</strong>.
                            @if ($status === 'Rejected' && $application->rejection_reason)
                                <div class="small mt-2"><strong>Reason:</strong> {{ $application->rejection_reason }}</div>
                            @endif
                            @if ($status === 'Expired')
                                You're eligible to re-apply to a new open program.
                            @endif
                        </div>
                    @endif

                    <div class="d-flex justify-content-between position-relative px-2">
                        <div class="position-absolute top-50 start-0 end-0 translate-middle-y"
                            style="height:2px; background:#e6e9ee; z-index:0;"></div>
                        @foreach ($steps as $i => $step)
                            @php $stepComplete = $completedSteps[$i]; @endphp
                            <div class="text-center position-relative" style="z-index:1; flex:1;">
                                <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center
                                    {{ !$terminal && $stepComplete ? 'bg-sf-navy text-white' : 'bg-white border' }}"
                                    style="width:36px;height:36px; {{ !$terminal && $stepComplete ? 'background:var(--sf-navy);color:#fff;' : 'color:#94a3b8;' }}">
                                    @if (!$terminal && $stepComplete)
                                        <i class="bi bi-check-lg"></i>
                                    @else
                                        <span class="small fw-bold">{{ $i + 1 }}</span>
                                    @endif
                                </div>
                                <div
                                    class="small mt-2 {{ !$terminal && $stepComplete ? 'fw-semibold' : 'text-secondary' }}">
                                    {{ $step }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Submitted Details --}}
            <div class="card sf-card mb-4">
                <div class="card-body p-4">
                    <h2 class="h6 sf-heading mb-3">Submitted Details</h2>
                    <dl class="row mb-0">
                        <dt class="col-sm-4 small text-secondary fw-normal py-2">GPA Submitted</dt>
                        <dd class="col-sm-8 py-2 mb-0">
                            {{ number_format((float) ($application->gpa_submitted ?? ($application->current_gpa ?? 0)), 2) }}
                        </dd>

                        <dt class="col-sm-4 small text-secondary fw-normal py-2 border-top">Address Submitted</dt>
                        <dd class="col-sm-8 py-2 mb-0 border-top">
                            {{ $application->address_submitted ?? ($application->current_address ?? 'N/A') }}</dd>

                        <dt class="col-sm-4 small text-secondary fw-normal py-2 border-top">Campus Submitted</dt>
                        <dd class="col-sm-8 py-2 mb-0 border-top">
                            {{ $application->studentProfile?->campus ?? 'Not Assigned' }}</dd>

                        <dt class="col-sm-4 small text-secondary fw-normal py-2 border-top">Rurality Confirmed</dt>
                        <dd class="col-sm-8 py-2 mb-0 border-top">
                            {{ $application->is_rural_submitted ?? $application->is_rural ? 'Yes — Rural' : 'No — Urban' }}
                        </dd>
                    </dl>
                </div>
            </div>

            {{-- Uploaded Documents --}}
            <div class="card sf-card mb-4 overflow-hidden">
                <div class="card-header bg-white border-bottom py-3 px-3 px-sm-4">
                    <h3 class="h6 mb-0 fw-bold text-dark">Uploaded Documents</h3>
                </div>
                <div class="card-body p-3 p-sm-4">
                    <div class="d-flex flex-column gap-3">
                        @forelse ($application->documents as $doc)
                            <div
                                class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between p-3 border rounded-3 bg-light gap-2 overflow-hidden w-100">
                                <div class="d-flex align-items-center gap-3 min-w-0 w-100">
                                    <div class="sf-stat-icon bg-white border text-primary flex-shrink-0">
                                        <i class="bi bi-file-earmark-text"></i>
                                    </div>
                                    <div class="min-w-0 flex-grow-1">
                                        <div class="fw-bold text-dark text-truncate small">
                                            {{ $doc->document_type instanceof \App\Enums\DocumentType
                                                ? $doc->document_type->label()
                                                : \Illuminate\Support\Str::headline(str_replace('_', ' ', (string) ($doc->document_type ?? 'Document'))) }}
                                        </div>
                                        <div class="text-muted small text-truncate" style="max-width: 180px;">
                                            {{ !empty($doc->file_name) ? $doc->file_name : (!empty($doc->file_path) ? basename($doc->file_path) : 'No file recorded') }}
                                        </div>
                                    </div>
                                </div>
                                @if (!empty($doc->file_path))
                                    <a href="{{ route('documents.show', $doc) }}" target="_blank" rel="noopener"
                                        class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1 mt-2 mt-sm-0 flex-shrink-0 align-self-end align-self-sm-center">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                @else
                                    <span
                                        class="small text-secondary mt-2 mt-sm-0 flex-shrink-0 align-self-end align-self-sm-center"
                                        title="The stored file is unavailable">Unavailable</span>
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

                <div class="card sf-card mb-4 border-warning border-2">
                    <div class="card-header bg-white border-bottom py-3 px-3 px-sm-4">
                        <h3 class="h6 mb-0 fw-bold text-dark">
                            <i class="bi bi-arrow-counterclockwise text-warning me-2"></i>Re-upload Corrected Documents
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
                                                    <i class="bi bi-exclamation-triangle-fill me-1"></i>Action Required: Re-upload Needed
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
                                            <div class="small text-secondary text-truncate mb-1">{{ $group['document']->file_name }}</div>
                                        @endif
                                        <input type="file" class="form-control form-control-sm"
                                            name="documents[{{ $group['canon'] }}]"
                                            accept=".pdf,.jpg,.jpeg,.png"
                                            aria-label="Replace {{ $group['label'] }}">
                                    </div>
                                @endforeach
                            </div>
                            <button type="submit" class="btn btn-warning w-100 fw-semibold text-dark mt-3">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> Resubmit Corrected Documents
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </div>

        {{-- Right Side Program Box --}}
        <div class="col-lg-4">
            <div class="card sf-card">
                <div class="card-body p-4">
                    <h2 class="h6 sf-heading mb-3">Program</h2>
                    <div class="fw-semibold">{{ $application->sponsorshipProgram->program_name ?? 'Sponsorship Program' }}
                    </div>
                    <div class="small text-secondary mb-3">
                        {{ $application->sponsorshipProgram->sponsor->company_organization_name ?? 'Sponsor' }}</div>

                    <ul class="list-unstyled small mb-0">
                        <li class="d-flex justify-content-between py-2 border-top">
                            <span class="text-secondary">Submitted</span>
                            <span>{{ optional($application->submitted_at ?? $application->created_at)->format('M d, Y') ?? '—' }}</span>
                        </li>
                        <li class="d-flex justify-content-between py-2 border-top">
                            <span class="text-secondary">Verified</span>
                            <span>{{ optional($application->verified_at)->format('M d, Y') ?? '—' }}</span>
                        </li>
                        <li class="d-flex justify-content-between py-2 border-top">
                            <span class="text-secondary">Approved</span>
                            <span>{{ optional($application->approved_at)->format('M d, Y') ?? '—' }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <a href="{{ route('student.applications.index') }}" class="btn btn-outline-secondary w-100 mt-3">
                <i class="bi bi-arrow-left me-1"></i> Back to My Applications
            </a>
        </div>
    </div>

@endsection
