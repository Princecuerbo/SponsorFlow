@extends('layouts.app')

@section('title', 'Sponsor Dashboard')
@section('eyebrow', 'Sponsor Portal · Dashboard')
@section('page-title')
    <span class="sponsor-original-copy">Welcome back, {{ auth()->user()->sponsor?->company_organization_name ?? auth()->user()->name }}!</span>
    <span class="sponsor-desktop-copy d-none">Sponsor Dashboard</span>
@endsection
@section('subtitle')
    <span class="sponsor-original-copy">Authorized Sponsor Portal · Davao Oriental State University</span>
    <span class="sponsor-desktop-copy d-none">Welcome back, {{ auth()->user()->sponsor?->company_organization_name ?? auth()->user()->name }}.</span>
@endsection

@push('styles')
    @include('sponsor.partials.desktop-styles')
    <style>
        .badge-step-navy {
            background-color: #0F2942 !important;
            color: #ffffff !important;
        }
    </style>
@endpush

@section('content')
    {{-- Mobile metric cards --}}
    <div class="row g-3 mb-4 sponsor-original-copy">
        <div class="col-md-4">
            <x-metric-card title="Connected programs" value="{{ $connectedPrograms }}" icon="bi-briefcase"
                color="slate" />
        </div>
        <div class="col-md-4">
            <x-metric-card title="Batches pending review" value="{{ $listsPendingReview }}" icon="bi-clock"
                color="amber" />
        </div>
        <div class="col-md-4">
            <x-metric-card title="Approved batches" value="{{ $uploadedApprovals }}" icon="bi-file-earmark-check"
                color="emerald" />
        </div>
    </div>

    {{-- Desktop metric cards --}}
    <div class="sponsor-desktop-metrics d-none">
        @foreach ([
            ['label' => 'Connected programs', 'value' => $connectedPrograms, 'icon' => 'bi-briefcase', 'style' => 'sponsor-icon-navy'],
            ['label' => 'Batches pending review', 'value' => $listsPendingReview, 'icon' => 'bi-clock', 'style' => 'sponsor-icon-amber'],
            ['label' => 'Approved batches', 'value' => $uploadedApprovals, 'icon' => 'bi-file-earmark-check', 'style' => 'sponsor-icon-navy'],
        ] as $metric)
            <div class="sponsor-metric">
                <span class="sponsor-metric-label">{{ $metric['label'] }}</span>
                <span class="sponsor-metric-icon {{ $metric['style'] }}"><i class="bi {{ $metric['icon'] }}" aria-hidden="true"></i></span>
                <strong class="sponsor-metric-value">{{ number_format($metric['value']) }}</strong>
            </div>
        @endforeach
    </div>

    {{-- Workflow Panel --}}
    <div class="card sf-card border-0 shadow-sm sponsor-workflow-card">
        <div class="card-body p-4 p-lg-5">
            <div class="d-flex justify-content-between align-items-center mb-4 sponsor-workflow-header">
                <div>
                    <h3 class="h6 sf-heading mb-3 fw-bold sponsor-workflow-title">
                        <span class="sponsor-original-copy">Sponsor workflow</span>
                        <span class="sponsor-desktop-copy d-none">How Sponsorship Approval Works</span>
                    </h3>
                    <p class="small text-secondary mb-0 sponsor-workflow-subtitle">
                        <span class="sponsor-original-copy">Review FASSG-submitted batches and record an explicit whole-batch decision.</span>
                        <span class="sponsor-desktop-copy d-none">Review submitted batches and provide an explicit decision.</span>
                    </p>
                </div>
                <span class="sponsor-original-copy badge bg-success-subtle text-success-emphasis border border-success-subtle px-3 py-2">
                    <i class="bi bi-shield-check me-1"></i>Human-reviewed
                </span>
                <span class="sponsor-desktop-copy d-none sponsor-workflow-badge">
                    <i class="bi bi-shield-check" aria-hidden="true"></i>Manual Sponsor Decision
                </span>
            </div>
            <div class="row g-4 sponsor-workflow-steps">
                <div class="col-md-6 col-xl-3 sponsor-workflow-col">
                    <div class="d-flex gap-3 sponsor-workflow-step">
                        <span class="badge rounded-circle badge-step-navy p-2 align-self-start sponsor-original-copy">1</span>
                        <span class="sponsor-desktop-copy d-none sponsor-step-icon"><i class="bi bi-inbox" aria-hidden="true"></i></span>
                        <div class="sponsor-step-content">
                            <h3 class="h6 fw-bold sponsor-step-title">
                                <span class="sponsor-original-copy">Receive a submitted batch</span>
                                <span class="sponsor-desktop-copy d-none">Receive Submitted Batch</span>
                            </h3>
                            <p class="small text-secondary mb-0 sponsor-step-desc">
                                <span class="sponsor-original-copy">FASSG verifies SLE-FHE eligibility, program qualifications, and required documents before submission.</span>
                                <span class="sponsor-desktop-copy d-none">Review the batch submitted by FASSG.</span>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3 sponsor-workflow-col">
                    <div class="d-flex gap-3 sponsor-workflow-step">
                        <span class="badge rounded-circle badge-step-navy p-2 align-self-start sponsor-original-copy">2</span>
                        <span class="sponsor-desktop-copy d-none sponsor-step-icon"><i class="bi bi-people" aria-hidden="true"></i></span>
                        <div class="sponsor-step-content">
                            <h3 class="h6 fw-bold sponsor-step-title">
                                <span class="sponsor-original-copy">Review the exact roster</span>
                                <span class="sponsor-desktop-copy d-none">Review Candidate Roster</span>
                            </h3>
                            <p class="small text-secondary mb-0 sponsor-step-desc">
                                <span class="sponsor-original-copy">Review all submitted members: ordinary applicants, Fixed List candidates, or both.</span>
                                <span class="sponsor-desktop-copy d-none">Review the exact roster and supporting details.</span>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3 sponsor-workflow-col">
                    <div class="d-flex gap-3 sponsor-workflow-step">
                        <span class="badge rounded-circle badge-step-navy p-2 align-self-start sponsor-original-copy">3</span>
                        <span class="sponsor-desktop-copy d-none sponsor-step-icon"><i class="bi bi-clipboard-check" aria-hidden="true"></i></span>
                        <div class="sponsor-step-content">
                            <h3 class="h6 fw-bold sponsor-step-title">
                                <span class="sponsor-original-copy">Approve or reject the whole batch</span>
                                <span class="sponsor-desktop-copy d-none">Approve or Reject Batch</span>
                            </h3>
                            <p class="small text-secondary mb-0 sponsor-step-desc">
                                <span class="sponsor-original-copy">Approval requires uploaded confirmation evidence and an explicit decision. Uploading alone does not approve the batch.</span>
                                <span class="sponsor-desktop-copy d-none">Upload required confirmation evidence and explicitly approve or reject the entire batch.</span>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3 sponsor-workflow-col">
                    <div class="d-flex gap-3 sponsor-workflow-step">
                        <span class="badge rounded-circle bg-success p-2 align-self-start sponsor-original-copy">4</span>
                        <span class="sponsor-desktop-copy d-none sponsor-step-icon"><i class="bi bi-send" aria-hidden="true"></i></span>
                        <div class="sponsor-step-content">
                            <h3 class="h6 fw-bold sponsor-step-title">
                                <span class="sponsor-original-copy">Return decision to FASSG</span>
                                <span class="sponsor-desktop-copy d-none">Return Decision to FASSG</span>
                            </h3>
                            <p class="small text-secondary mb-0 sponsor-step-desc">
                                <span class="sponsor-original-copy">FASSG records your decision, then separately forwards eligible approved beneficiaries to Accounting for read-only reference.</span>
                                <span class="sponsor-desktop-copy d-none">FASSG records the Sponsor decision and separately forwards eligible approved beneficiaries to Accounting.</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
