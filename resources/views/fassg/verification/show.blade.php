@extends('layouts.app')

@section('title', 'Review Application')
@section('eyebrow', 'FASSG Hybrid Verification')
@section('page-title', 'Verify Application: ' . $application->studentProfile->user->name)

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
    </style>
@endpush

@section('content')
    @php
        $profile = $application->studentProfile;
        $program = $application->sponsorshipProgram;
        $status = $application->status->value;
        $isPending = $status === 'Pending';
        $meetsGpa = $program->min_gpa === null || (float) $application->gpa_submitted <= (float) $program->min_gpa;
    @endphp

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <a href="{{ route('fassg.verification.index') }}" class="btn btn-sm btn-outline-secondary mb-3">
                <i class="bi bi-arrow-left me-1"></i> Back to Verification Queue
            </a>
            <p class="text-uppercase small fw-semibold text-secondary mb-2">FASSG Hybrid Verification</p>
            <h1 class="h2 sf-heading mb-1">Verify Application: {{ $profile->user->name }}</h1>
            <p class="text-secondary mb-0">
                {{ $program->program_name }} <span class="mx-1">&bull;</span> FASSG Hybrid Verification
            </p>
        </div>
        <x-status-badge :status="$application->status" />
    </div>

    <div class="row g-4">
        <!-- Left Column: Student Details, Application Info & Sponsor Program -->
        <div class="col-xl-7">
            <!-- Student Overview Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-2 mb-4">
                        <div class="sf-stat-icon p-2 rounded" style="background-color: #e9ecef; color: #0F2942;">
                            <i class="bi bi-person-vcard fs-4"></i>
                        </div>
                        <div>
                            <h2 class="h5 sf-heading mb-0">Student Overview</h2>
                            <p class="small text-secondary mb-0">Applicant identity and eligibility profile</p>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="small text-secondary">Full Name</div>
                            <div class="fw-semibold">{{ $profile->user->name }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="small text-secondary">Student ID Number</div>
                            <div class="sf-mono fw-bold">{{ $profile->student_id_number }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="small text-secondary">Course &amp; Year</div>
                            <div>{{ $profile->course }} · Year {{ $profile->year_level }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="small text-secondary">SLE-FHE Verification</div>
                            <div>
                                @if ($profile->is_sle_fhe_verified)
                                    <span
                                        class="badge bg-success-subtle text-success-emphasis border border-success-subtle px-2 py-1">
                                        <i class="bi bi-patch-check me-1"></i> Verified
                                    </span>
                                @else
                                    <span
                                        class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1">
                                        <i class="bi bi-hourglass-split me-1"></i> Pending
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Application Details Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h2 class="h5 sf-heading mb-4">
                        <i class="bi bi-clipboard-data me-2" style="color: #0F2942;"></i> Application Details
                    </h2>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="small text-secondary">Submitted GWA / GPA</div>
                            <div class="fw-semibold">
                                {{ number_format($application->gpa_submitted, 2) }}
                                @if ($program->min_gpa !== null)
                                    <span
                                        class="badge {{ $meetsGpa ? 'bg-success-subtle text-success-emphasis border border-success-subtle' : 'bg-danger-subtle text-danger-emphasis border border-danger-subtle' }} ms-1">
                                        {{ $meetsGpa ? 'Meets minimum' : 'Below minimum' }}
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="small text-secondary">Minimum Required GPA</div>
                            <div>
                                {{ $program->min_gpa !== null ? number_format($program->min_gpa, 2) . ' or better' : 'No minimum specified' }}
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="small text-secondary">Complete Address</div>
                            <div class="fw-semibold">{{ $application->address_submitted }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="small text-secondary">Barangay</div>
                            <div>{{ $profile->barangay ?: 'Not provided' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="small text-secondary">Rural Classification</div>
                            <div>
                                @if ($application->is_rural_submitted)
                                    <span
                                        class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1">
                                        <i class="bi bi-tree me-1"></i> Rural
                                    </span>
                                @else
                                    <span
                                        class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle px-2 py-1">
                                        <i class="bi bi-building me-1"></i> Urban
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Target Program Card -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h2 class="h5 sf-heading mb-4">
                        <i class="bi bi-building-check me-2" style="color: #0F2942;"></i> Target Program &amp; Sponsor
                    </h2>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="small text-secondary">Program Title</div>
                            <div class="fw-semibold">{{ $program->program_name }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="small text-secondary">Sponsor</div>
                            <div class="fw-semibold">{{ $program->sponsor->company_organization_name }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="small text-secondary">Category</div>
                            <div>{{ $program->category->value }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="small text-secondary">Available Slots</div>
                            <div>{{ $program->available_slots }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Documents Preview & Actions -->
        <div class="col-xl-5">
            <!-- Supporting Documents Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between gap-2 mb-4">
                        <div>
                            <h2 class="h5 sf-heading mb-1">Supporting Documents</h2>
                            <p class="small text-secondary mb-0">Review each required proof before deciding</p>
                        </div>
                        <i class="bi bi-file-earmark-check fs-3" style="color: #0F2942;"></i>
                    </div>

                    @foreach (\App\Enums\DocumentType::requiredForApplication() as $documentType)
                        @php
                            $document = $application->documents->first(
                                fn($item) => ($item->document_type instanceof \BackedEnum
                                    ? $item->document_type->value
                                    : (string) $item->document_type) === $documentType->value,
                            );
                        @endphp
                        <div class="d-flex align-items-center gap-3 {{ !$loop->last ? 'border-bottom pb-3 mb-3' : '' }}">
                            <div class="sf-stat-icon bg-danger-subtle text-danger p-2 rounded">
                                <i class="bi bi-file-earmark-pdf fs-4"></i>
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-semibold small">{{ $documentType->label() }}</div>
                                <div class="small text-secondary text-truncate">
                                    {{ $document?->file_name ?? 'Not uploaded' }}
                                </div>
                            </div>
                            @if ($document)
                                <a href="{{ route('fassg.applications.documents.show', [$application, $document]) }}"
                                    target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary flex-shrink-0">
                                    <i class="bi bi-eye me-1"></i> View Document
                                </a>
                            @else
                                <span
                                    class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle flex-shrink-0">Missing</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Verification Action Box -->
            <div class="card border-0 shadow-sm bg-light position-sticky" style="top:5.5rem;">
                <div class="card-body p-4">
                    <h2 class="h5 sf-heading mb-1">Verification Actions</h2>
                    <p class="small text-secondary mb-4">Confirm the evidence before advancing this application.</p>

                    @if ($isPending)
                        <!-- Approve Form -->
                        <form method="POST" action="{{ route('fassg.verification.verify', $application) }}">
                            @csrf
                            @method('PATCH')

                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" role="switch" id="grades_verified"
                                    name="grades_verified" value="1" required>
                                <label class="form-check-label small fw-semibold text-dark" for="grades_verified">
                                    Grade slip matches submitted GWA
                                </label>
                            </div>

                            <div class="form-check form-switch mb-4">
                                <input class="form-check-input" type="checkbox" role="switch" id="address_verified"
                                    name="address_verified" value="1" required>
                                <label class="form-check-label small fw-semibold text-dark" for="address_verified">
                                    Proof of residence and barangay certificate match the given address
                                </label>
                            </div>

                            <button type="submit" class="btn btn-navy-primary w-100 py-2 shadow-sm fw-semibold">
                                <i class="bi bi-check2-circle me-1"></i> Mark Verified &amp; Approve Application
                            </button>
                        </form>

                        <hr class="my-4">

                        <!-- Reject Form -->
                        <form method="POST" action="{{ route('fassg.verification.reject', $application) }}">
                            @csrf
                            @method('PATCH')

                            <div class="mb-3">
                                <label class="form-label small fw-semibold" for="reason">Reason for Rejection</label>
                                <input type="text" class="form-control" id="reason" name="reason"
                                    placeholder="Describe what needs correction" required>
                            </div>

                            <button type="submit" class="btn btn-outline-danger w-100 fw-semibold">
                                <i class="bi bi-x-circle me-1"></i> Reject Application
                            </button>
                        </form>
                    @else
                        <div class="alert alert-secondary mb-0">
                            <i class="bi bi-info-circle me-1"></i> This application is already
                            <strong>{{ $status }}</strong> and no longer accepts FASSG decisions.
                        </div>
                    @endif

                    <div class="small text-secondary mt-4 pt-3 border-top">
                        <i class="bi bi-shield-check me-1"></i> FASSG verification advances the application for sponsor
                        review. Final beneficiary confirmation remains with the sponsor.
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
