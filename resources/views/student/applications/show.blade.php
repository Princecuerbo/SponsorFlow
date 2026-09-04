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

        $isStep1Complete = in_array(
            $statusValue,
            ['Pending', 'Verified', 'FASSG Verified', 'Sponsor Reviewed', 'Approved', 'Confirmed'],
            true,
        );
        $isStep2Complete = in_array(
            $statusValue,
            ['Verified', 'FASSG Verified', 'Sponsor Reviewed', 'Approved', 'Confirmed'],
            true,
        );
        $isStep3Complete = in_array($statusValue, ['Sponsor Reviewed', 'Approved', 'Confirmed'], true);
        $isStep4Complete = in_array($statusValue, ['Approved', 'Confirmed', 'Final Approval'], true);

        $completedSteps = [$isStep1Complete, $isStep2Complete, $isStep3Complete, $isStep4Complete];
    @endphp

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
                            @php($stepComplete = $completedSteps[$i])
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
                                            {{ \Illuminate\Support\Str::headline(is_object($doc->document_type) && property_exists($doc->document_type, 'value') ? $doc->document_type->value : (string) $doc->document_type) }}
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
