@extends('layouts.app')

@section('title', 'Beneficiary Reference')
@section('eyebrow', 'Accounting Office · Beneficiary Reference')
@section('page-title', $application->studentProfile->user->name)
@section('subtitle', $application->sponsorshipProgram->program_name . ' · ' . $application->status->value)

@section('header-actions')
    <a href="{{ route('accounting.beneficiaries.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to Master List</a>
@endsection

@section('content')
    <div class="sf-readonly-banner d-flex align-items-center gap-3 mb-4"><i class="bi bi-lock fs-5"></i><span class="small fw-semibold">This record is read-only. Accounting cannot modify beneficiary or sponsorship data.</span></div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card sf-card"><div class="card-body p-4">
                <h3 class="h6 sf-heading mb-3">Beneficiary Details</h3>
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-secondary fw-normal py-2">Student ID</dt><dd class="col-sm-8 sf-mono py-2 mb-0">{{ $application->studentProfile->student_id_number }}</dd>
                    <dt class="col-sm-4 text-secondary fw-normal py-2 border-top">Academic Program &amp; Year</dt><dd class="col-sm-8 py-2 mb-0 border-top">{{ $application->studentProfile->display_course }} · Year {{ $application->studentProfile->year_level }}</dd>
                    <dt class="col-sm-4 text-secondary fw-normal py-2 border-top">Submitted GWA / GPA</dt><dd class="col-sm-8 py-2 mb-0 border-top">{{ number_format($application->gpa_submitted, 2) }}</dd>
                    <dt class="col-sm-4 text-secondary fw-normal py-2 border-top">Address &amp; Rurality</dt><dd class="col-sm-8 py-2 mb-0 border-top">{{ $application->address_submitted }} · {{ $application->is_rural_submitted ? 'Rural' : 'Urban' }}</dd>
                    <dt class="col-sm-4 text-secondary fw-normal py-2 border-top">Sponsor</dt><dd class="col-sm-8 py-2 mb-0 border-top">{{ $application->sponsorshipProgram->sponsor->company_organization_name }}</dd>
                    <dt class="col-sm-4 text-secondary fw-normal py-2 border-top">Billing Contact</dt><dd class="col-sm-8 py-2 mb-0 border-top">{{ $application->sponsorshipProgram->sponsor->contact_person ?: 'Not provided' }}</dd>
                </dl>
            </div></div>
        </div>
        <div class="col-lg-5">
            <div class="card sf-card"><div class="card-body p-4">
                <h3 class="h6 sf-heading mb-3">Billing Reference</h3>
                <dl class="row mb-0">
                    <dt class="col-6 text-secondary fw-normal py-2">Grant Amount</dt><dd class="col-6 text-end py-2 mb-0">Not recorded</dd>
                    <dt class="col-6 text-secondary fw-normal py-2 border-top">Coverage Term</dt><dd class="col-6 text-end py-2 mb-0 border-top">Not recorded</dd>
                    <dt class="col-6 text-secondary fw-normal py-2 border-top">Approved by Sponsor</dt><dd class="col-6 text-end py-2 mb-0 border-top">{{ $application->approved_at?->format('M d, Y, h:i A') ?? '—' }}</dd>
                </dl>
                @if ($application->sponsor_approval_path)
                    <div class="border-top mt-3 pt-3"><div class="small text-secondary mb-2">Sponsor confirmation document</div><a href="{{ route('accounting.documents.view', $application->id) }}" target="_blank" rel="noopener" class="btn btn-outline-secondary w-100"><i class="bi bi-file-earmark-check me-1"></i>View Confirmation File</a></div>
                @endif
            </div></div>
        </div>
    </div>
@endsection
