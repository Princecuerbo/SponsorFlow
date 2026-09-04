@extends('layouts.app')

@section('title', 'Beneficiary Reference')
@section('eyebrow', 'Accounting Office')
@section('page-title', 'Beneficiary Reference')

@section('content')
    <div class="mb-4">
        <a href="{{ route('accounting.beneficiaries.index') }}" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left me-1"></i>Back to Master List</a>
        <p class="text-uppercase small fw-semibold text-warning mb-2">Read-only billing reference</p>
        <h1 class="h2 sf-heading mb-1">{{ $application->studentProfile->user->name }}</h1>
        <p class="text-secondary mb-0">{{ $application->sponsorshipProgram->program_name }} · {{ $application->status->value }}</p>
    </div>

    <div class="sf-readonly-banner d-flex align-items-center gap-3 mb-4"><i class="bi bi-lock fs-5"></i><span class="small fw-semibold">This record is read-only. Accounting cannot modify beneficiary or sponsorship data.</span></div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card sf-card"><div class="card-body p-4">
                <h2 class="h5 sf-heading mb-4">Beneficiary Details</h2>
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-secondary fw-normal py-2">Student ID</dt><dd class="col-sm-8 sf-mono py-2 mb-0">{{ $application->studentProfile->student_id_number }}</dd>
                    <dt class="col-sm-4 text-secondary fw-normal py-2 border-top">Course &amp; Year</dt><dd class="col-sm-8 py-2 mb-0 border-top">{{ $application->studentProfile->course }} · Year {{ $application->studentProfile->year_level }}</dd>
                    <dt class="col-sm-4 text-secondary fw-normal py-2 border-top">Submitted GWA / GPA</dt><dd class="col-sm-8 py-2 mb-0 border-top">{{ number_format($application->gpa_submitted, 2) }}</dd>
                    <dt class="col-sm-4 text-secondary fw-normal py-2 border-top">Address &amp; Rurality</dt><dd class="col-sm-8 py-2 mb-0 border-top">{{ $application->address_submitted }} · {{ $application->is_rural_submitted ? 'Rural' : 'Urban' }}</dd>
                    <dt class="col-sm-4 text-secondary fw-normal py-2 border-top">Sponsor</dt><dd class="col-sm-8 py-2 mb-0 border-top">{{ $application->sponsorshipProgram->sponsor->company_organization_name }}</dd>
                    <dt class="col-sm-4 text-secondary fw-normal py-2 border-top">Billing Contact</dt><dd class="col-sm-8 py-2 mb-0 border-top">{{ $application->sponsorshipProgram->sponsor->contact_person ?: 'Not provided' }}</dd>
                </dl>
            </div></div>
        </div>
        <div class="col-lg-5">
            <div class="card sf-card"><div class="card-body p-4">
                <h2 class="h5 sf-heading mb-4">Billing Reference</h2>
                <dl class="row mb-0">
                    <dt class="col-6 text-secondary fw-normal py-2">Grant Amount</dt><dd class="col-6 text-end py-2 mb-0">Not recorded</dd>
                    <dt class="col-6 text-secondary fw-normal py-2 border-top">Coverage Term</dt><dd class="col-6 text-end py-2 mb-0 border-top">Not recorded</dd>
                    <dt class="col-6 text-secondary fw-normal py-2 border-top">Approved by Sponsor</dt><dd class="col-6 text-end py-2 mb-0 border-top">{{ $application->approved_at?->format('M d, Y') ?? '—' }}</dd>
                </dl>
                @if ($application->sponsor_approval_path)
                    <div class="border-top mt-3 pt-3"><div class="small text-secondary mb-2">Sponsor confirmation document</div><a href="{{ route('accounting.documents.view', $application->id) }}" target="_blank" rel="noopener" class="btn btn-outline-secondary w-100"><i class="bi bi-file-earmark-check me-1"></i>View Confirmation File</a></div>
                @endif
            </div></div>
        </div>
    </div>
@endsection
