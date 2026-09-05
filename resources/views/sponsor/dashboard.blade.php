@extends('layouts.app')

@section('title', 'Sponsor Dashboard')
@section('eyebrow', 'Sponsor Portal')
@section('page-title', 'Dashboard')

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

        .badge-step-navy {
            background-color: #0F2942 !important;
            color: #ffffff !important;
        }
    </style>
@endpush

@section('content')
    <div class="mb-4">
        <h1 class="h2 sf-heading mb-1">Welcome back,
            {{ auth()->user()->sponsor?->company_organization_name ?? auth()->user()->name }}!</h1>
        <p class="text-secondary mb-0">Authorized Sponsor Portal · Davao Oriental State University</p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="sf-stat-card p-4 h-100 card border-0 shadow-sm">
                <div class="sf-stat-icon mb-3 p-2 rounded d-inline-block" style="background-color: #e9ecef; color: #0F2942;">
                    <i class="bi bi-briefcase fs-4"></i>
                </div>
                <div class="sf-eyebrow text-secondary small text-uppercase fw-semibold">Connected programs</div>
                <div class="h3 sf-heading mb-0 fw-bold">{{ $connectedPrograms }}</div>
                <div class="small text-secondary mt-1">Active programs linked to your organization</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="sf-stat-card p-4 h-100 card border-0 shadow-sm">
                <div class="sf-stat-icon bg-warning-subtle text-warning mb-3 p-2 rounded d-inline-block">
                    <i class="bi bi-hourglass-split fs-4"></i>
                </div>
                <div class="sf-eyebrow text-secondary small text-uppercase fw-semibold">Lists pending review</div>
                <div class="h3 sf-heading mb-0 fw-bold">{{ $listsPendingReview }}</div>
                <div class="small text-secondary mt-1">FASSG-forwarded batches awaiting your response</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="sf-stat-card p-4 h-100 card border-0 shadow-sm">
                <div class="sf-stat-icon bg-success-subtle text-success mb-3 p-2 rounded d-inline-block">
                    <i class="bi bi-file-earmark-check fs-4"></i>
                </div>
                <div class="sf-eyebrow text-secondary small text-uppercase fw-semibold">Uploaded approvals</div>
                <div class="h3 sf-heading mb-0 fw-bold">{{ $uploadedApprovals }}</div>
                <div class="small text-secondary mt-1">Signed approval documents on file</div>
            </div>
        </div>
    </div>

    <div class="card sf-card border-0 shadow-sm">
        <div class="card-body p-4 p-lg-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="h5 sf-heading mb-1 fw-bold">Sponsor workflow</h2>
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
