@extends('layouts.app')

@section('title', 'Sponsor Dashboard')
@section('eyebrow', 'Sponsor Portal · Dashboard')
@section('page-title', 'Welcome back, ' . (auth()->user()->sponsor?->company_organization_name ?? auth()->user()->name) . '!')
@section('subtitle', 'Authorized Sponsor Portal · Davao Oriental State University')

@push('styles')
    <style>

        .badge-step-navy {
            background-color: #0F2942 !important;
            color: #ffffff !important;
        }
    </style>
@endpush

@section('content')
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <x-metric-card title="Connected programs" value="{{ $connectedPrograms }}" icon="bi-briefcase"
                color="slate" />
        </div>
        <div class="col-md-4">
            <x-metric-card title="Lists pending review" value="{{ $listsPendingReview }}" icon="bi-hourglass-split"
                color="amber" />
        </div>
        <div class="col-md-4">
            <x-metric-card title="Uploaded approvals" value="{{ $uploadedApprovals }}" icon="bi-file-earmark-check"
                color="emerald" />
        </div>
    </div>

    <div class="card sf-card border-0 shadow-sm">
        <div class="card-body p-4 p-lg-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="h6 sf-heading mb-3 fw-bold">Sponsor workflow</h3>
                    <p class="small text-secondary mb-0">Each step keeps beneficiary decisions traceable and ready for
                        billing reference.</p>
                </div>
                <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle px-3 py-2">
                    <i class="bi bi-shield-check me-1"></i>Human-reviewed
                </span>
            </div>
            <div class="row g-4">
                <div class="col-md-6 col-xl-3">
                    <div class="d-flex gap-3">
                        <span class="badge rounded-circle badge-step-navy p-2 align-self-start">1</span>
                        <div>
                            <h3 class="h6 fw-bold">Receive verified list</h3>
                            <p class="small text-secondary mb-0">FASSG checks SLE-FHE status and eligibility criteria.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="d-flex gap-3">
                        <span class="badge rounded-circle badge-step-navy p-2 align-self-start">2</span>
                        <div>
                            <h3 class="h6 fw-bold">Review applicants and lists</h3>
                            <p class="small text-secondary mb-0">Inspect forwarded students and fixed-list details.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="d-flex gap-3">
                        <span class="badge rounded-circle badge-step-navy p-2 align-self-start">3</span>
                        <div>
                            <h3 class="h6 fw-bold">Upload signed approval</h3>
                            <p class="small text-secondary mb-0">Submit the signed PDF, JPG, or PNG approval document.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="d-flex gap-3">
                        <span class="badge rounded-circle bg-success p-2 align-self-start">4</span>
                        <div>
                            <h3 class="h6 fw-bold">Forward to Accounting</h3>
                            <p class="small text-secondary mb-0">Confirmed beneficiaries become available for tuition
                                reference.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
