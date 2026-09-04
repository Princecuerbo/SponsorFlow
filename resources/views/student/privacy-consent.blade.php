@extends('layouts.app')

@section('title', 'Privacy Consent')
@section('eyebrow', 'Student Portal')
@section('page-title', 'Privacy & Data Consent')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card sf-card">
                <div class="card-body p-4 p-lg-5">
                    <div class="text-center mb-4">
                        <div class="sf-stat-icon bg-primary-subtle text-primary mx-auto mb-3" style="width:56px;height:56px;font-size:1.5rem;">
                            <i class="bi bi-shield-lock"></i>
                        </div>
                        <h2 class="h4 fw-bold mb-2">Privacy &amp; Data Consent</h2>
                        <p class="text-secondary small mb-0">Please review and accept the data privacy terms before proceeding.</p>
                    </div>

                    <div class="border rounded-3 p-4 mb-4 bg-light" style="max-height:320px;overflow-y:auto;">
                        <h5 class="h6 fw-bold mb-3">SponsorFlow Data Privacy Notice</h5>
                        <p class="small text-secondary mb-3">
                            SponsorFlow, operated by Davao Oriental State University (DORSU), collects and processes your personal information for the sole purpose of administering the Student Loan and Employment – Financial Hardship Extension (SLE-FHE) sponsorship program.
                        </p>
                        <p class="small text-secondary mb-3"><strong>What we collect:</strong></p>
                        <ul class="small text-secondary mb-3 ps-3">
                            <li>Full name, student ID number, course, year level, and contact details</li>
                            <li>Academic records (GPA, grade slips) and residential address</li>
                            <li>Sponsorship application history and verification status</li>
                        </ul>
                        <p class="small text-secondary mb-3"><strong>How we use it:</strong></p>
                        <ul class="small text-secondary mb-3 ps-3">
                            <li>To verify your SLE-FHE eligibility against sponsor-provided beneficiary lists</li>
                            <li>To route your application through FASSG verification and sponsor approval workflows</li>
                            <li>To maintain audit logs for accountability and compliance</li>
                        </ul>
                        <p class="small text-secondary mb-0">
                            Your data is shared only with authorized FASSG staff, designated sponsor representatives, and the university accounting office involved in the sponsorship process. We do not sell or share your data with third parties outside this program.
                        </p>
                    </div>

                    <form method="POST" action="{{ route('student.privacy-consent.store') }}">
                        @csrf
                        <div class="form-check mb-4">
                            <input class="form-check-input @error('privacy_consent') is-invalid @enderror"
                                type="checkbox" name="privacy_consent" value="1" id="privacy_consent" required>
                            <label class="form-check-label fw-semibold" for="privacy_consent">
                                I have read and agree to the collection and processing of my personal data as described above.
                            </label>
                            @error('privacy_consent')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-sf-gold px-4">
                            <i class="bi bi-check2-circle me-1"></i>Accept &amp; Continue
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
