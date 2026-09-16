@extends('layouts.app')

@section('title', 'Review Application')
@section('eyebrow', 'FASSG Office · Applications')
@section('page-title', 'Review Application: ' . $application->studentProfile->user->name)

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

        .detail-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #6c757d;
            margin-bottom: 0.2rem;
            font-weight: 600;
        }

        .detail-value {
            font-weight: 600;
            color: #1a1a2e;
        }

        .action-card {
            position: sticky;
            top: 5.5rem;
        }

        .doc-row:not(:last-child) {
            border-bottom: 1px solid #f0f0f0;
            padding-bottom: 0.85rem;
            margin-bottom: 0.85rem;
        }

        .stat-highlight {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
        }
    </style>
@endpush

@section('content')
    @php
        $profile = $application->studentProfile;
        $program = $application->sponsorshipProgram;
        $isPending = $application->status === \App\Enums\ApplicationStatus::Pending;
        $isVerified = in_array($application->status, [
            \App\Enums\ApplicationStatus::Verified,
            \App\Enums\ApplicationStatus::Approved,
        ], true);

        $attCanon = static fn ($type) => \App\Enums\DocumentType::canonicalValue($type);
        $attAllTypes = array_values(array_unique(array_merge(
            $program->requiredDocumentCanonicalValues() ?? [],
            $application->documents->map(fn ($d) => $attCanon($d->document_type))->all(),
        )));
        $needsCorAttest = in_array(\App\Enums\DocumentType::CertificateOfRegistration->value, $attAllTypes, true);
        $needsKinshipAttest = in_array(\App\Enums\DocumentType::EmployeeProofOfKinship->value, $attAllTypes, true);
    @endphp

    {{-- Page Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <a href="{{ route('fassg.applications.index') }}" class="btn btn-sm btn-outline-secondary mb-3">
                <i class="bi bi-arrow-left me-1"></i> Back to Application Queue
            </a>
            <p class="text-uppercase small fw-semibold text-secondary mb-1">FASSG Office · Application Verification</p>
            <h1 class="h2 sf-heading mb-1">{{ $profile->user->name }}</h1>
            <p class="text-secondary mb-0">
                {{ $program->program_name }}
                <span class="mx-1">&bull;</span>
                {{ $program->sponsor->company_organization_name }}
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <x-status-badge :status="$application->status" />
        </div>
    </div>

    {{-- Flash / Error Messages --}}
    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <strong><i class="bi bi-exclamation-triangle me-1"></i> Unable to process request:</strong>
            <ul class="mb-0 mt-2 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Eligibility Warnings --}}
    @if (! empty($eligibilityErrors))
        <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
            <strong><i class="bi bi-exclamation-triangle me-1"></i> Eligibility criteria notice:</strong>
            <ul class="mb-0 mt-2 ps-3">
                @foreach ($eligibilityErrors as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-4">
        {{-- ── Left column: Applicant Summary ──────────── --}}
        <div class="col-xl-7">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="p-2 rounded" style="background-color:#e9ecef; color:#0F2942;">
                                <i class="bi bi-person-badge fs-4"></i>
                            </div>
                            <div>
                                <h2 class="h5 sf-heading mb-0">Applicant Profile Summary</h2>
                                <p class="small text-secondary mb-0">Key verification details &amp; demographics</p>
                            </div>
                        </div>
                        <span class="badge bg-light text-dark border">
                            <i class="bi bi-clock me-1"></i> Submitted {{ $application->submitted_at?->diffForHumans() ?? 'recently' }}
                        </span>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-sm-6 col-md-4">
                            <div class="stat-highlight">
                                <div class="detail-label"><i class="bi bi-card-heading me-1"></i> Student ID</div>
                                <div class="detail-value sf-mono fs-6">{{ $profile->student_id_number ?: 'Not specified' }}</div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-4">
                            <div class="stat-highlight">
                                <div class="detail-label"><i class="bi bi-award me-1"></i> Submitted GWA</div>
                                <div class="detail-value fs-6">{{ number_format($application->gpa_submitted, 2) }}</div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-4">
                            <div class="stat-highlight">
                                <div class="detail-label"><i class="bi bi-mortarboard me-1"></i> Year Level</div>
                                <div class="detail-value fs-6">Year {{ $profile->year_level ?? '—' }}</div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-4">
                            <div class="stat-highlight">
                                <div class="detail-label"><i class="bi bi-gender-ambiguous me-1"></i> Gender</div>
                                <div class="detail-value fs-6">{{ $profile->gender ?: 'Not specified' }}</div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-4">
                            <div class="stat-highlight">
                                <div class="detail-label"><i class="bi bi-book me-1"></i> Course / Degree</div>
                                <div class="detail-value fs-6">{{ $profile->course ?: '—' }}</div>
                            </div>
                        </div>
                        <div class="col-sm-12 col-md-4">
                            <div class="stat-highlight">
                                <div class="detail-label"><i class="bi bi-tree me-1"></i> SLE-FHE Status</div>
                                <div>
                                    @if ($profile->is_sle_fhe_verified)
                                        <span class="badge bg-cyan-50 text-cyan-700 border border-cyan-200 px-2 py-1">
                                            <i class="bi bi-patch-check me-1"></i> Verified
                                        </span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1">
                                            <i class="bi bi-hourglass-split me-1"></i> Pending
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="detail-label">Full Name</div>
                            <div class="detail-value">{{ $profile->user->name }}</div>
                            <div class="small text-secondary">{{ $profile->user->email }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-label">Submitted Address</div>
                            <div class="detail-value small">{{ $application->address_submitted ?: 'Same as student profile' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-label">Residency</div>
                            <div>
                                @if ($application->is_rural_submitted)
                                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1">
                                        <i class="bi bi-tree me-1"></i> Rural
                                    </span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle px-2 py-1">
                                        <i class="bi bi-building me-1"></i> Urban
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-label">Available Slots</div>
                            <div class="fw-bold {{ $program->available_slots <= 0 ? 'text-danger' : 'text-success' }}">
                                {{ $program->available_slots }} remaining
                                @if ($program->available_slots <= 0)
                                    <span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle ms-1">Full</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Application Timeline --}}
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h2 class="h6 sf-heading mb-3 text-secondary text-uppercase">
                        <i class="bi bi-clock-history me-2"></i>Review Timeline &amp; Notes
                    </h2>
                    <div class="row g-3 small">
                        <div class="col-md-4">
                            <div class="detail-label">Submitted</div>
                            <div>{{ $application->submitted_at?->format('M d, Y · h:i A') ?? '—' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-label">Verified At</div>
                            <div>{{ $application->verified_at?->format('M d, Y · h:i A') ?? 'Not yet verified' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-label">Approved At</div>
                            <div>{{ $application->approved_at?->format('M d, Y · h:i A') ?? 'Not yet approved' }}</div>
                        </div>
                        @if ($application->rejection_reason)
                            <div class="col-12 mt-2">
                                <div class="detail-label text-danger">Rejection Reason</div>
                                <div class="alert alert-danger border border-danger-subtle rounded-2 p-2 mb-0 text-danger-emphasis">
                                    <i class="bi bi-info-circle me-1"></i> {{ $application->rejection_reason }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Right column: Supporting Documents & Decision Controls ─── --}}
        <div class="col-xl-5">
            {{-- Supporting Documents Section --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                        <div>
                            <h2 class="h5 sf-heading mb-1">Supporting Documents</h2>
                            <p class="small text-secondary mb-0">Proof files uploaded by the applicant</p>
                        </div>
                        <i class="bi bi-file-earmark-check fs-3" style="color:#0F2942;"></i>
                    </div>

                    @forelse ($application->documents as $document)
                        @php
                            $typeLabel = $document->document_type instanceof \BackedEnum
                                ? $document->document_type->label()
                                : \Illuminate\Support\Str::headline(str_replace('_', ' ', (string) $document->document_type));
                        @endphp
                        <div class="doc-row">
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <div class="p-2 rounded bg-success-subtle text-success">
                                    <i class="bi bi-file-earmark-check fs-4"></i>
                                </div>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-semibold small">{{ $typeLabel }}</div>
                                    <div class="small text-secondary text-truncate">{{ $document->file_name }}</div>
                                </div>
                                <span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    Uploaded
                                </span>
                            </div>
                            <div class="d-flex gap-2 ms-5 ps-1">
                                <a href="{{ route('fassg.applications.documents.download', [$application, $document]) }}"
                                    target="_blank" rel="noopener"
                                    class="btn btn-sm btn-outline-primary py-1 px-2">
                                    <i class="bi bi-eye me-1"></i> Preview
                                </a>
                                <a href="{{ route('fassg.applications.documents.download', [$application, $document]) }}"
                                    download
                                    class="btn btn-sm btn-outline-secondary py-1 px-2">
                                    <i class="bi bi-download me-1"></i> Download
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-secondary py-4">
                            <i class="bi bi-file-earmark-x display-6 d-block mb-2"></i>
                            No supporting documents uploaded yet.
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Decision Controls --}}
            <div class="card border-0 shadow-sm bg-light action-card">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <h2 class="h5 sf-heading mb-0">Decision Controls</h2>
                            <p class="small text-secondary mb-0">Review decision and verification attestation</p>
                        </div>
                        <span class="badge {{ $program->available_slots > 0 ? 'bg-success-subtle text-success-emphasis' : 'bg-danger-subtle text-danger-emphasis' }} border px-2 py-1">
                            {{ $program->available_slots }} slot{{ $program->available_slots !== 1 ? 's' : '' }} left
                        </span>
                    </div>

                    @if ($isPending)
                        {{-- ── 1. VERIFY APPLICATION ACTION ── --}}
                        <form method="POST" action="{{ route('fassg.applications.verify', $application) }}"
                            onsubmit="return confirm('Mark this application as Verified? This forwards nothing to the sponsor yet but confirms your review.');">
                            @csrf
                            @method('PATCH')

                            <div class="card card-body p-4 shadow-sm mb-3 border-0">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <i class="bi bi-patch-check" style="color:#0F2942;"></i>
                                    <span class="small fw-semibold text-dark">Verification Attestation</span>
                                </div>
                                <p class="small text-secondary mb-3">
                                    Check each box to confirm you have reviewed the corresponding document.
                                </p>

                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="confirm_gwa" value="1"
                                        id="confirm_gwa" required>
                                    <label class="form-check-label small" for="confirm_gwa">
                                        Confirm that the grade slip matches the submitted GWA.
                                    </label>
                                </div>

                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="confirm_address" value="1"
                                        id="confirm_address" required>
                                    <label class="form-check-label small" for="confirm_address">
                                        Confirm that the proof of residence and barangay certificate match the submitted address.
                                    </label>
                                </div>

                                @if ($needsCorAttest)
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="confirm_cor" value="1"
                                            id="confirm_cor" required>
                                        <label class="form-check-label small" for="confirm_cor">
                                            Confirm that the Certificate of Registration (COR) matches current enrollment.
                                        </label>
                                    </div>
                                @endif

                                @if ($needsKinshipAttest)
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="confirm_kinship" value="1"
                                            id="confirm_kinship" required>
                                        <label class="form-check-label small" for="confirm_kinship">
                                            Confirm that the Employee ID / Proof of Kinship matches the submitted employee dependency details.
                                        </label>
                                    </div>
                                @endif

                                @error('confirm_gwa')
                                    <div class="text-danger small mt-2"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>
                                @enderror
                                @error('confirm_address')
                                    <div class="text-danger small mt-2"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>
                                @enderror
                                @error('confirm_cor')
                                    <div class="text-danger small mt-2"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>
                                @enderror
                                @error('confirm_kinship')
                                    <div class="text-danger small mt-2"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>
                                @enderror
                            </div>

                            <button type="submit" class="btn btn-success fw-semibold w-100 py-2">
                                <i class="bi bi-check-circle me-1"></i>Mark as Verified
                            </button>
                        </form>

                        <p class="small text-muted mb-0 mt-2">
                            <i class="bi bi-shield-check me-1 text-success"></i>
                            Marks the application as Verified and moves it into the verified review set.
                        </p>

                        <hr class="my-3">

                        {{-- ── 2. REJECT APPLICATION ACTION (Mandatory Reason) ── --}}
                        <div>
                            <h3 class="h6 fw-bold text-danger mb-2">
                                <i class="bi bi-x-circle me-1"></i> Reject Application
                            </h3>
                            <p class="small text-secondary mb-3">
                                Provide a reason so the student understands why their application was rejected.
                            </p>

                            <form method="POST" action="{{ route('fassg.applications.reject', $application) }}"
                                onsubmit="return confirm('Are you sure you want to reject this application? This action cannot be undone.');">
                                @csrf
                                @method('PATCH')

                                <div class="input-group">
                                    <textarea class="form-control" name="reason" rows="1"
                                        placeholder="Rejection reason (required)" required maxlength="500"
                                        aria-label="Rejection reason">{{ old('reason') }}</textarea>
                                    <button type="submit" class="btn btn-outline-danger fw-semibold">
                                        <i class="bi bi-x-octagon me-1"></i> Reject
                                    </button>
                                </div>
                                <div class="form-text small text-secondary">
                                    Mandatory. The student will be notified of this reason.
                                </div>
                            </form>
                        </div>

                        <hr class="my-3">

                        {{-- ── 3. REQUEST RESUBMISSION / CORRECTION ACTION ── --}}
                        <div>
                            <h3 class="h6 fw-bold mb-2" style="color:#9a6b00;">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> Request Resubmission / Correction
                            </h3>
                            <p class="small text-secondary mb-3">
                                Ask the student to upload a corrected or clearer copy of a document. A mandatory note is attached so the student knows exactly what to fix.
                            </p>

                            <form method="POST" action="{{ route('fassg.applications.request-resubmission', $application) }}"
                                onsubmit="return confirm('Send this application back to the student for document resubmission?');">
                                @csrf
                                @method('PATCH')
                                @php
                                    $resubReqCanon = static fn ($type) => \App\Enums\DocumentType::canonicalValue($type);
                                    $resubReqTypes = array_values(array_unique(array_merge(
                                        $program->requiredDocumentCanonicalValues(),
                                        $application->documents->map(fn ($d) => $resubReqCanon($d->document_type))->all(),
                                    )));
                                    $resubReqLabels = [];
                                    foreach ($resubReqTypes as $canon) {
                                        $label = $canon;
                                        foreach (\App\Enums\DocumentType::cases() as $type) {
                                            if ($resubReqCanon($type) === $canon) {
                                                $label = $type->label();
                                                break;
                                            }
                                        }
                                        $resubReqLabels[$canon] = $label;
                                    }
                                @endphp
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-dark">
                                        Documents to Re-upload <span class="text-danger">*</span>
                                    </label>
                                    <div class="border rounded-3 bg-white p-3" style="max-height: 220px; overflow-y: auto;">
                                        @foreach ($resubReqLabels as $resubReqCanonValue => $resubReqLabel)
                                            <label class="d-flex align-items-start gap-2 small mb-2">
                                                <input type="checkbox" class="form-check-input mt-1"
                                                    name="requested_documents[]" value="{{ $resubReqCanonValue }}"
                                                    @checked(in_array($resubReqCanonValue, (array) old('requested_documents', []), true))>
                                                <span>{{ $resubReqLabel }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    @error('requested_documents')
                                        <div class="text-danger small mt-1"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>
                                    @enderror
                                    <div class="form-text small text-secondary">
                                        Select at least one document you want the student to replace or provide a clearer copy of.
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-dark" for="resubmission_notes">
                                        Resubmission Notes <span class="text-danger">*</span>
                                    </label>
                                    <textarea class="form-control" id="resubmission_notes" name="resubmission_notes" rows="3"
                                        placeholder="e.g., Uploaded COR is unreadable. Please upload a clear copy."
                                        required minlength="5" maxlength="1000">{{ old('resubmission_notes') }}</textarea>
                                    <div class="form-text small text-secondary">
                                        Mandatory. Displayed to the student on their application page.
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-warning w-100 fw-semibold text-dark">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i> Request Document Resubmission
                                </button>
                            </form>
                        </div>
                    @elseif ($isVerified)
                        {{-- Application Verified (read-only) --}}
                        <div class="p-4 text-center">
                            <div class="mb-3">
                                <i class="bi bi-patch-check-fill display-4" style="color:#1a7a4a;"></i>
                            </div>
                            <h3 class="h5 sf-heading mb-2">Application Verified</h3>
                            <p class="small text-secondary mb-0">
                                This application was verified on
                                <strong>{{ $application->verified_at?->format('F j, Y') ?? '—' }}</strong>
                                and no further review decisions can be submitted.
                            </p>
                        </div>
                    @else
                        {{-- Application Already Finalized --}}
                        <div class="alert {{ $application->status === \App\Enums\ApplicationStatus::Approved ? 'alert-success' : 'alert-secondary' }} mb-0">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <i class="bi {{ $application->status === \App\Enums\ApplicationStatus::Approved ? 'bi-check-circle-fill text-success' : 'bi-info-circle' }} fs-5"></i>
                                <strong>Status: {{ $application->status->value }}</strong>
                            </div>
                            <p class="small mb-0">
                                This application is marked as <strong>{{ $application->status->value }}</strong> and no further review decisions can be submitted.
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection