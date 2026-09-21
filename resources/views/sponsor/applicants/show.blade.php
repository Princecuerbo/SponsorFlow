@extends('layouts.app')

@section('title', 'Review Applicant')
@section('eyebrow', 'Sponsor Portal · Applicant Review')
@section('page-title', $application->studentProfile->user->name)
@section('subtitle', $application->sponsorshipProgram->program_name)

@section('header-actions')
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('sponsor.applicants.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Applicants
        </a>
        <x-status-badge :status="$application->status" />
    </div>
@endsection

@section('content')
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card sf-card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h3 class="h6 sf-heading mb-3 fw-bold">Applicant Profile</h3>
                    <dl class="row mb-0">
                        <dt class="col-sm-4 text-secondary fw-normal py-2">Student ID</dt>
                        <dd class="col-sm-8 sf-mono py-2 mb-0 fw-semibold">
                            {{ $application->studentProfile->student_id_number }}</dd>
                        <dt class="col-sm-4 text-secondary fw-normal py-2 border-top">Academic Program &amp; Year</dt>
                        <dd class="col-sm-8 py-2 mb-0 border-top">{{ $application->studentProfile->display_course }} · Year
                            {{ $application->studentProfile->year_level }}</dd>
                        <dt class="col-sm-4 text-secondary fw-normal py-2 border-top">GWA</dt>
                        <dd class="col-sm-8 py-2 mb-0 border-top fw-semibold">
                            {{ number_format($application->gpa_submitted, 2) }}</dd>
                        <dt class="col-sm-4 text-secondary fw-normal py-2 border-top">Address</dt>
                        <dd class="col-sm-8 py-2 mb-0 border-top">{{ $application->address_submitted }} ·
                            {{ $application->is_rural_submitted ? 'Rural' : 'Urban' }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card sf-card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h3 class="h6 sf-heading mb-3 fw-bold">Sponsor Confirmation</h3>
                    <p class="small text-secondary mb-4">Upload the signed endorsement to confirm this FASSG-verified
                        application.</p>

                    @if (
                        $application->sponsorshipProgram->status === \App\Enums\ProgramStatus::Expired ||
                            $application->status === \App\Enums\ApplicationStatus::Expired)
                        <div class="alert alert-warning mb-0">This program has concluded and is no longer accepting
                            approvals.</div>
                    @elseif ($application->status === \App\Enums\ApplicationStatus::Verified)
                        <form method="POST" action="{{ route('sponsor.applicants.confirm', $application) }}"
                            enctype="multipart/form-data">
                            @csrf
                            <label class="form-label small fw-semibold" for="approval_document">Signed approval
                                document</label>
                            <input class="form-control mb-2" id="approval_document" type="file" name="approval_document"
                                accept=".pdf,.jpg,.jpeg,.png" required>
                            <div class="form-text mb-4">PDF, JPG, or PNG up to 5 MB.</div>

                            <button type="submit" class="btn btn-sf-navy w-100 py-2">
                                <i class="bi bi-check2-circle me-1"></i>Upload &amp; Confirm Application
                            </button>
                        </form>

                        <button type="button" class="btn btn-outline-danger w-100 mt-2" data-bs-toggle="modal"
                            data-bs-target="#rejectApplicantModal">
                            <i class="bi bi-x-circle me-1"></i>Decline Applicant
                        </button>

                        <div class="modal fade" id="rejectApplicantModal" tabindex="-1"
                            aria-labelledby="rejectApplicantModalLabel" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h2 class="modal-title h5 fw-bold" id="rejectApplicantModalLabel">Decline Applicant
                                        </h2>
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
                                            <button type="button" class="btn btn-outline-danger"
                                                data-bs-dismiss="modal"><i class="bi bi-x-lg me-1"></i> Cancel</button>
                                            <button type="submit" class="btn btn-danger"><i
                                                    class="bi bi-x-circle me-1"></i>Decline Applicant</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-secondary mb-0">This application is already
                            {{ $application->status->value }}.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
