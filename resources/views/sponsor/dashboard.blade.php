@extends('layouts.app')

@section('title', 'Sponsor Dashboard')
@section('eyebrow', 'Sponsor Portal')
@section('page-title', 'Dashboard')

@section('content')
    <div class="mb-4">
        <h1 class="h2 sf-heading mb-1">Welcome back,
            {{ auth()->user()->sponsor?->company_organization_name ?? auth()->user()->name }}!</h1>
        <p class="text-secondary mb-0">Authorized Sponsor Portal · Davao Oriental State University</p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="sf-stat-card p-4 h-100">
                <div class="sf-stat-icon bg-primary-subtle text-primary mb-3"><i class="bi bi-briefcase"></i></div>
                <div class="sf-eyebrow">Connected programs</div>
                <div class="h3 sf-heading mb-0">{{ $connectedPrograms }}</div>
                <div class="small text-secondary mt-1">Active programs linked to your organization</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="sf-stat-card p-4 h-100">
                <div class="sf-stat-icon bg-warning-subtle text-warning mb-3"><i class="bi bi-hourglass-split"></i></div>
                <div class="sf-eyebrow">Lists pending review</div>
                <div class="h3 sf-heading mb-0">{{ $listsPendingReview }}</div>
                <div class="small text-secondary mt-1">FASSG-forwarded batches awaiting your response</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="sf-stat-card p-4 h-100">
                <div class="sf-stat-icon bg-success-subtle text-success mb-3"><i class="bi bi-file-earmark-check"></i></div>
                <div class="sf-eyebrow">Uploaded approvals</div>
                <div class="h3 sf-heading mb-0">{{ $uploadedApprovals }}</div>
                <div class="small text-secondary mt-1">Signed approval documents on file</div>
            </div>
        </div>
    </div>

    <div class="card sf-card">
        <div class="card-body p-4 p-lg-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="h5 sf-heading mb-1">Sponsor workflow</h2>
                    <p class="small text-secondary mb-0">Each step keeps beneficiary decisions traceable and ready for
                        billing reference.</p>
                </div><span class="badge bg-success-subtle text-success-emphasis"><i
                        class="bi bi-shield-check me-1"></i>Human-reviewed</span>
            </div>
            <div class="row g-4">
                <div class="col-md-6 col-xl-3">
                    <div class="d-flex gap-3"><span class="badge rounded-circle bg-primary p-2 align-self-start">1</span>
                        <div>
                            <h3 class="h6 fw-bold">Receive verified list</h3>
                            <p class="small text-secondary mb-0">FASSG checks SLE-FHE status and eligibility criteria.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="d-flex gap-3"><span class="badge rounded-circle bg-primary p-2 align-self-start">2</span>
                        <div>
                            <h3 class="h6 fw-bold">Review applicants and lists</h3>
                            <p class="small text-secondary mb-0">Inspect forwarded students and fixed-list details.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="d-flex gap-3"><span class="badge rounded-circle bg-primary p-2 align-self-start">3</span>
                        <div>
                            <h3 class="h6 fw-bold">Upload signed approval</h3>
                            <p class="small text-secondary mb-0">Submit the signed PDF, JPG, or PNG approval document.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="d-flex gap-3"><span class="badge rounded-circle bg-success p-2 align-self-start">4</span>
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
