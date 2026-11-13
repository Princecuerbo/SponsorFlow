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
                            {{ $application->gpa_submitted === null ? 'Not recorded' : number_format($application->gpa_submitted, 2) }}</dd>
                        <dt class="col-sm-4 text-secondary fw-normal py-2 border-top">Address</dt>
                        <dd class="col-sm-8 py-2 mb-0 border-top">{{ $application->address_submitted }} ·
                            {{ $application->is_rural_submitted === null ? 'Not recorded' : ($application->is_rural_submitted ? 'Rural' : 'Urban') }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card sf-card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h3 class="h6 sf-heading mb-3 fw-bold">Sponsor Confirmation</h3>
                    <p class="small text-secondary mb-4">Review the complete FASSG-submitted Generated Batch to record a Sponsor decision.</p>

                    @if (
                        $application->sponsorshipProgram->status === \App\Enums\ProgramStatus::Expired ||
                            $application->status === \App\Enums\ApplicationStatus::Expired)
                        <div class="alert alert-warning mb-0">This program has concluded and is no longer accepting
                            approvals.</div>
                    @elseif ($application->status === \App\Enums\ApplicationStatus::Verified)
                        <p>Decisions are made for the complete Generated Batch prepared by FASSG.</p>
                        @foreach ($application->batchCandidates->filter(fn ($item) => $item->generatedBatch?->status === \App\Enums\GeneratedBatchStatus::Submitted) as $membership)
                            <a class="btn btn-outline-primary" href="{{ route('sponsor.lists.show', $membership->generated_batch_id) }}">Review Generated Batch</a>
                        @endforeach
                    @else
                        <div class="alert alert-secondary mb-0">This application is already
                            {{ $application->status->value }}.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
