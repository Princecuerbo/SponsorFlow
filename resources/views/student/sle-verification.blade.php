@extends('layouts.app')

@section('title', 'SLE-FHE Verification')

@section('content')
    <div class="container-fluid px-3 px-md-4 py-4" style="background-color: #f8fafc; min-height: calc(100vh - 70px);">

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
            <div>
                <h3 class="fw-bold mb-1" style="color: #0f172a; font-size: 1.6rem;">SLE-FHE Student Verification</h3>
                <p class="text-secondary small mb-0" style="font-size: 0.875rem;">
                    Submit and manage your verification records for Davao Oriental State University sponsorship eligibility.
                </p>
            </div>
            <div>
                <a href="{{ route('student.dashboard') }}" class="btn btn-light btn-sm rounded-3 border fw-medium px-3">
                    <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
                </a>
            </div>
        </div>

        @if (session('status'))
            <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4" role="alert" style="font-size: 0.875rem;">
                <i class="bi bi-check-circle me-1"></i> {{ session('status') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4" role="alert" style="font-size: 0.875rem;">
                <i class="bi bi-exclamation-triangle me-1"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="card border-0 rounded-3 mb-4 p-3.5 shadow-sm" style="background-color: #fef9c3; border-left: 4px solid #eab308 !important;">
            <div class="d-flex align-items-start gap-3">
                <div class="text-warning fs-3 lh-1 mt-0.5">
                    <i class="bi bi-clock-history" style="color: #ca8a04;"></i>
                </div>
                <div class="w-100">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-1">
                        <h6 class="fw-bold mb-0" style="color: #713f12; font-size: 0.95rem;">Verification Status: {{ $profile?->is_sle_fhe_verified ? 'Verified' : 'Pending Review' }}</h6>
                        <span class="badge rounded-pill fw-semibold px-3 py-1" style="background-color: {{ $profile?->is_sle_fhe_verified ? '#dcfce7' : '#fef3c7' }}; color: {{ $profile?->is_sle_fhe_verified ? '#166534' : '#b45309' }}; font-size: 0.75rem;">
                            {{ $profile?->is_sle_fhe_verified ? 'VERIFIED' : 'IN PROGRESS' }}
                        </span>
                    </div>
                    <p class="mb-0 small" style="color: #854d0e; font-size: 0.85rem;">
                        @if ($profile?->is_sle_fhe_verified)
                            Your SLE-FHE status has been verified. You are eligible to apply for open sponsorship programs.
                        @else
                            Your institutional verification records are currently being evaluated by the FASSG Admin team. Please make sure all required document requirements below are uploaded.
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <div class="row g-4">

            <div class="col-lg-5">
                <div class="card border-0 shadow-sm rounded-3 bg-white h-100">
                    <div class="card-header bg-white border-bottom pt-3.5 px-4 pb-3">
                        <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2" style="font-size: 0.95rem;">
                            <i class="bi bi-person-badge text-primary"></i> Academic Profile Details
                        </h6>
                    </div>
                    <div class="card-body p-4">                        <div class="row g-3">
                            <div class="col-12 pb-2 border-bottom">
                                <span class="text-secondary extra-small text-uppercase d-block mb-1" style="font-size: 0.7rem; letter-spacing: 0.05em;">Full Name</span>
                                <span class="fw-bold text-dark" style="font-size: 0.925rem;">{{ $user->first_name ?? '' }} {{ $user->middle_name ?? '' }} {{ $user->last_name ?? '' }}</span>
                            </div>

                            <div class="col-6 pb-2 border-bottom">
                                <span class="text-secondary extra-small text-uppercase d-block mb-1" style="font-size: 0.7rem; letter-spacing: 0.05em;">Student ID</span>
                                <span class="fw-semibold text-dark font-monospace" style="font-size: 0.875rem;">{{ $profile?->student_id_number ?? '—' }}</span>
                            </div>

                            <div class="col-6 pb-2 border-bottom">
                                <span class="text-secondary extra-small text-uppercase d-block mb-1" style="font-size: 0.7rem; letter-spacing: 0.05em;">Year Level</span>
                                <span class="fw-semibold text-dark" style="font-size: 0.875rem;">{{ $profile?->year_level ? $profile->year_level . ' Year' : '—' }}</span>
                            </div>

                            <div class="col-6 pb-2 border-bottom">
                                <span class="text-secondary extra-small text-uppercase d-block mb-1" style="font-size: 0.7rem; letter-spacing: 0.05em;">Course</span>
                                <span class="fw-semibold text-dark" style="font-size: 0.875rem;">{{ $profile?->course ?? '—' }}</span>
                            </div>

                            <div class="col-6 pb-2 border-bottom">
                                <span class="text-secondary extra-small text-uppercase d-block mb-1" style="font-size: 0.7rem; letter-spacing: 0.05em;">Residency</span>
                                @if ($profile?->is_rural)
                                    <span class="badge bg-info-subtle text-info-emphasis rounded-2 fw-medium" style="font-size: 0.75rem;"><i class="bi bi-tree me-1"></i>Rural</span>
                                @elseif ($profile)
                                    <span class="badge bg-secondary-subtle text-secondary-emphasis rounded-2 fw-medium" style="font-size: 0.75rem;"><i class="bi bi-building me-1"></i>Urban</span>
                                @else
                                    <span class="fw-semibold text-dark" style="font-size: 0.875rem;">—</span>
                                @endif
                            </div>

                            <div class="col-12 pt-1">
                                <span class="text-secondary extra-small text-uppercase d-block mb-1" style="font-size: 0.7rem; letter-spacing: 0.05em;">SLE-FHE Status</span>
                                @if ($profile?->is_sle_fhe_verified)
                                    <span class="badge bg-success-subtle text-success-emphasis rounded-2 fw-semibold px-2.5 py-1" style="font-size: 0.75rem;">
                                        <i class="bi bi-patch-check-fill me-1"></i> Verified
                                    </span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning-emphasis rounded-2 fw-semibold px-2.5 py-1" style="font-size: 0.75rem;">
                                        <i class="bi bi-hourglass-split me-1"></i> Pending
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-3 bg-white mt-4">
                    <div class="card-header bg-white border-bottom pt-3.5 px-4 pb-3">
                        <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2" style="font-size: 0.95rem;">
                            <i class="bi bi-pencil-square text-primary"></i> Update Program &amp; Profile
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" action="{{ route('student.verification.update') }}" class="row g-3">
                            @csrf
                            @method('PUT')

                            <div class="col-12">
                                <label for="sle_academic_program_id" class="form-label small fw-semibold text-secondary mb-1">Academic Program / Course *</label>
                                <select id="sle_academic_program_id" name="academic_program_id" class="form-select form-select-md @error('academic_program_id') is-invalid @enderror" required style="font-size: 0.875rem; border-radius: 8px;">
                                    <option value="" disabled @selected(!$profile?->academic_program_id)>-- Select Your Course/Program --</option>
                                    @foreach ($programs as $program)
                                        <option value="{{ $program->program_id }}" @selected((int) old('academic_program_id', $profile?->academic_program_id) === (int) $program->program_id)>
                                            {{ $program->code }} - {{ $program->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('academic_program_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-6">
                                <label for="sle_year_level" class="form-label small fw-semibold text-secondary mb-1">Year Level</label>
                                <input type="number" id="sle_year_level" name="year_level" class="form-control form-control-md" min="1" max="5" value="{{ old('year_level', $profile?->year_level) }}" required style="font-size: 0.875rem; border-radius: 8px;">
                            </div>
                            <div class="col-6">
                                <label for="sle_student_id_number" class="form-label small fw-semibold text-secondary mb-1">Student ID</label>
                                <input type="text" id="sle_student_id_number" name="student_id_number" class="form-control form-control-md" value="{{ old('student_id_number', $profile?->student_id_number) }}" required style="font-size: 0.875rem; border-radius: 8px;">
                            </div>
                            <div class="col-12">
                                <label for="sle_birthdate" class="form-label small fw-semibold text-secondary mb-1">Birthdate</label>
                                <input type="date" id="sle_birthdate" name="birthdate" class="form-control form-control-md" value="{{ old('birthdate', optional($profile?->birthdate)->format('Y-m-d')) }}" style="font-size: 0.875rem; border-radius: 8px;">
                            </div>
                            <div class="col-12">
                                <label for="sle_address" class="form-label small fw-semibold text-secondary mb-1">Address</label>
                                <input type="text" id="sle_address" name="address" class="form-control form-control-md" value="{{ old('address', $profile?->address) }}" required style="font-size: 0.875rem; border-radius: 8px;">
                            </div>
                            <div class="col-8">
                                <label for="sle_barangay" class="form-label small fw-semibold text-secondary mb-1">Barangay</label>
                                <input type="text" id="sle_barangay" name="barangay" class="form-control form-control-md" value="{{ old('barangay', $profile?->barangay) }}" required style="font-size: 0.875rem; border-radius: 8px;">
                            </div>
                            <div class="col-4 d-flex align-items-end">
                                <div class="form-check mb-1">
                                    <input class="form-check-input" type="checkbox" id="sle_is_rural" name="is_rural" value="1" @checked((bool) old('is_rural', $profile?->is_rural))>
                                    <label class="form-check-label small text-secondary fw-medium" for="sle_is_rural">Rural</label>
                                </div>
                            </div>
                            <div class="col-12 pt-1">
                                <button type="submit" class="btn btn-primary w-100 fw-semibold" style="background-color: #0f294a; border: none; border-radius: 8px; font-size: 0.875rem;">
                                    <i class="bi bi-save me-1"></i> Save Academic Profile
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card border-0 shadow-sm rounded-3 bg-white h-100">
                    <div class="card-header bg-white border-bottom pt-3.5 px-4 pb-3">
                        <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2" style="font-size: 0.95rem;">
                            <i class="bi bi-file-earmark-arrow-up text-primary"></i> Required Documents
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <p class="text-secondary small mb-3" style="font-size: 0.825rem;">
                            Upload the following documents to complete your SLE-FHE verification. Accepted formats: PDF, JPG, PNG (max 5MB).
                        </p>

                        @php
                            $docTypes = [
                                [
                                    'label' => 'Certificate of Grades (CG / Grade Slip)',
                                    'type' => 'certificate_of_grades',
                                    'path' => $profile?->sle_fhe_cg_path,
                                ],
                                [
                                    'label' => 'Proof of Residence',
                                    'type' => 'proof_of_residence',
                                    'path' => $profile?->sle_fhe_residence_path,
                                ],
                                [
                                    'label' => 'Barangay Certification',
                                    'type' => 'barangay_cert',
                                    'path' => $profile?->sle_fhe_barangay_path,
                                ],
                            ];
                        @endphp

                        <div class="d-flex flex-column gap-3">
                            @foreach ($docTypes as $doc)
                                <div class="border rounded-3 p-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="rounded-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background-color: {{ $doc['path'] ? '#dcfce7' : '#f1f5f9' }}; color: {{ $doc['path'] ? '#16a34a' : '#64748b' }};">
                                            <i class="bi {{ $doc['path'] ? 'bi-check-circle-fill' : 'bi-file-earmark' }} fs-5"></i>
                                        </div>
                                        <div>
                                            <div class="fw-semibold text-dark" style="font-size: 0.875rem;">{{ $doc['label'] }}</div>
                                            <div class="text-secondary" style="font-size: 0.75rem;">
                                                @if ($doc['path'])
                                                    <i class="bi bi-check-lg text-success me-1"></i>Uploaded
                                                @else
                                                    Not yet uploaded
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <form action="{{ route('student.sle-fhe') }}" method="POST" enctype="multipart/form-data" class="d-flex align-items-center gap-2">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="document_type" value="{{ $doc['type'] }}">
                                        <label class="btn btn-sm {{ $doc['path'] ? 'btn-outline-success' : 'btn-outline-primary' }} rounded-2 px-3 py-1.5 fw-medium mb-0" style="font-size: 0.78rem; cursor: pointer;">
                                            <i class="bi {{ $doc['path'] ? 'bi-arrow-repeat' : 'bi-upload' }} me-1"></i>{{ $doc['path'] ? 'Replace' : 'Upload' }}
                                            <input type="file" name="document_file" class="d-none" accept=".pdf,.jpg,.jpeg,.png" onchange="this.form.submit()">
                                        </label>
                                    </form>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-4 p-3 rounded-3 border bg-light" style="font-size: 0.8rem; color: #475569;">
                            <i class="bi bi-info-circle text-primary me-1"></i>
                            <strong>Reminder:</strong> All documents must be clear and legible. FASSG will review your uploads within 3–5 working days.
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div class="card border-0 shadow-sm rounded-3 bg-white mt-4">
            <div class="card-header bg-white border-bottom pt-3.5 px-4 pb-2.5">
                <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2" style="font-size: 0.95rem;">
                    <i class="bi bi-info-circle text-primary"></i> How Verification Works
                </h6>
            </div>
            <div class="card-body p-4">
                <div class="row g-4">
                    <div class="col-md-3 text-center">
                        <div class="rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background-color: #eff6ff; color: #2563eb;">
                            <i class="bi bi-1-circle-fill fs-5"></i>
                        </div>
                        <div class="fw-semibold text-dark small mb-1">Upload Documents</div>
                        <div class="text-secondary" style="font-size: 0.78rem;">Submit your academic and residency documents above.</div>
                    </div>
                    <div class="col-md-3 text-center">
                        <div class="rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background-color: #fef3c7; color: #d97706;">
                            <i class="bi bi-2-circle-fill fs-5"></i>
                        </div>
                        <div class="fw-semibold text-dark small mb-1">FASSG Review</div>
                        <div class="text-secondary" style="font-size: 0.78rem;">Admin team reviews and cross-checks your records.</div>
                    </div>
                    <div class="col-md-3 text-center">
                        <div class="rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background-color: #ecfdf5; color: #059669;">
                            <i class="bi bi-3-circle-fill fs-5"></i>
                        </div>
                        <div class="fw-semibold text-dark small mb-1">Fixed List Match</div>
                        <div class="text-secondary" style="font-size: 0.78rem;">Your student ID is matched against the SLE-FHE fixed list.</div>
                    </div>
                    <div class="col-md-3 text-center">
                        <div class="rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background-color: #f0fdf4; color: #16a34a;">
                            <i class="bi bi-4-circle-fill fs-5"></i>
                        </div>
                        <div class="fw-semibold text-dark small mb-1">Eligibility Unlocked</div>
                        <div class="text-secondary" style="font-size: 0.78rem;">Browse and apply to open sponsorship programs.</div>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection
