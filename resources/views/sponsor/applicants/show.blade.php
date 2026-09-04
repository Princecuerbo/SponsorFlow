@extends('layouts.app')

@section('title', 'Review Applicant')
@section('eyebrow', 'Sponsor Portal')
@section('page-title', 'Review Applicant')

@section('content')
    <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
        <div><a href="{{ route('sponsor.applicants.index') }}" class="btn btn-sm btn-outline-secondary mb-3"><i
                    class="bi bi-arrow-left me-1"></i>Back to Applicants</a>
            <p class="text-uppercase small fw-semibold text-success mb-2">Sponsor review</p>
            <h1 class="h2 sf-heading mb-1">{{ $application->studentProfile->user->name }}</h1>
            <p class="text-secondary mb-0">{{ $application->sponsorshipProgram->program_name }}</p>
        </div><x-status-badge :status="$application->status" />
    </div>
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card sf-card">
                <div class="card-body p-4">
                    <h2 class="h5 sf-heading mb-4">Applicant Profile</h2>
                    <dl class="row mb-0">
                        <dt class="col-sm-4 text-secondary fw-normal py-2">Student ID</dt>
                        <dd class="col-sm-8 sf-mono py-2 mb-0">{{ $application->studentProfile->student_id_number }}</dd>
                        <dt class="col-sm-4 text-secondary fw-normal py-2 border-top">Course &amp; Year</dt>
                        <dd class="col-sm-8 py-2 mb-0 border-top">{{ $application->studentProfile->course }} · Year
                            {{ $application->studentProfile->year_level }}</dd>
                        <dt class="col-sm-4 text-secondary fw-normal py-2 border-top">GWA</dt>
                        <dd class="col-sm-8 py-2 mb-0 border-top">{{ number_format($application->gpa_submitted, 2) }}</dd>
                        <dt class="col-sm-4 text-secondary fw-normal py-2 border-top">Address</dt>
                        <dd class="col-sm-8 py-2 mb-0 border-top">{{ $application->address_submitted }} ·
                            {{ $application->is_rural_submitted ? 'Rural' : 'Urban' }}</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card sf-card">
                <div class="card-body p-4">
                    <h2 class="h5 sf-heading mb-2">Sponsor Confirmation</h2>
                    <p class="small text-secondary mb-4">Upload the signed endorsement to confirm this FASSG-verified
                        application.</p>
                    @if (
                        $application->sponsorshipProgram->status === \App\Enums\ProgramStatus::Expired ||
                            $application->status === \App\Enums\ApplicationStatus::Expired)
                        <div class="alert alert-warning">This program has concluded and is no longer accepting approvals.
                        </div>
                    @elseif ($application->status === \App\Enums\ApplicationStatus::Verified)
                        <form method="POST" action="{{ route('sponsor.applicants.confirm', $application) }}"
                            enctype="multipart/form-data">@csrf<label class="form-label small fw-semibold"
                                for="approval_document">Signed approval document</label><input class="form-control mb-2"
                                id="approval_document" type="file" name="approval_document" accept=".pdf,.jpg,.jpeg,.png"
                                required>
                            <div class="form-text mb-4">PDF, JPG, or PNG up to 5 MB.</div><button type="submit"
                                class="btn btn-success w-100"><i class="bi bi-check2-circle me-1"></i>Upload &amp; Confirm
                                Application</button>
                        </form>
                        <button type="button" class="btn btn-outline-danger w-100 mt-2" data-bs-toggle="modal"
                            data-bs-target="#rejectApplicantModal"><i class="bi bi-x-circle me-1"></i>Decline
                            Applicant</button>
                        <div class="modal fade" id="rejectApplicantModal" tabindex="-1"
                            aria-labelledby="rejectApplicantModalLabel" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h2 class="modal-title h5" id="rejectApplicantModalLabel">Decline Applicant</h2>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                            aria-label="Close"></button>
                                    </div>
                                    <form method="POST" action="{{ route('sponsor.applicants.reject', $application) }}">
                                        @csrf
                                        <div class="modal-body">
                                            <label class="form-label" for="rejection_reason">Optional remarks</label>
                                            <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="4" maxlength="500"
                                                placeholder="Add a reason for declining this application."></textarea>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary"
                                                data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-danger"><i
                                                    class="bi bi-x-circle me-1"></i>Decline Applicant</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @else<div class="alert alert-secondary mb-0">This application is already
                            {{ $application->status->value }}.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
