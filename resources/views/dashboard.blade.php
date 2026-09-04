@extends('layouts.app')

@section('title', 'Student Dashboard')

@section('content')
    <div class="container-fluid px-3 px-md-4 py-4" style="background-color: #f8fafc; min-height: calc(100vh - 60px);">

        <!-- Welcome Title -->
        <div class="mb-4">
            <h3 class="fw-bold mb-1" style="color: #0f172a; font-size: 1.65rem;">
                Welcome back, {{ auth()->user()->first_name ?? 'Maria' }} {{ auth()->user()->last_name ?? 'Santos' }}
            </h3>
            <p class="text-secondary small mb-0" style="font-size: 0.875rem;">
                Your SLE-FHE sponsorship overview at Davao Oriental State University.
            </p>
        </div>

        <!-- Top Metric Cards Grid -->
        <div class="row g-3 mb-4">
            <!-- Total Applications -->
            <div class="col-12 col-md-4">
                <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 p-2.5 d-flex align-items-center justify-content-center" style="background-color: #eff6ff; color: #2563eb; width: 44px; height: 44px; flex-shrink: 0;">
                            <i class="bi bi-journal-text fs-5"></i>
                        </div>
                        <div>
                            <div class="text-secondary fw-semibold text-uppercase extra-small" style="font-size: 0.72rem; letter-spacing: 0.05em;">Total Applications</div>
                            <div class="fw-bold text-dark fs-4 lh-1 mt-1">{{ $totalApplications ?? 0 }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Sponsorships -->
            <div class="col-12 col-md-4">
                <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 p-2.5 d-flex align-items-center justify-content-center" style="background-color: #ecfdf5; color: #059669; width: 44px; height: 44px; flex-shrink: 0;">
                            <i class="bi bi-award fs-5"></i>
                        </div>
                        <div>
                            <div class="text-secondary fw-semibold text-uppercase extra-small" style="font-size: 0.72rem; letter-spacing: 0.05em;">Active Sponsorships</div>
                            <div class="fw-bold text-dark fs-4 lh-1 mt-1">{{ $activeSponsorships ?? 0 }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SLE-FHE Verification -->
            <div class="col-12 col-md-4">
                <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 p-2.5 d-flex align-items-center justify-content-center" style="background-color: #fffbeb; color: #d97706; width: 44px; height: 44px; flex-shrink: 0;">
                            <i class="bi bi-shield-check fs-5"></i>
                        </div>
                        <div>
                            <div class="text-secondary fw-semibold text-uppercase extra-small mb-1" style="font-size: 0.72rem; letter-spacing: 0.05em;">SLE-FHE Verification</div>
                            <span class="badge rounded-pill fw-semibold px-2.5 py-1" style="background-color: #fef3c7; color: #b45309; font-size: 0.72rem;">
                                {{ auth()->user()->sle_status ?? 'Pending Review' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Yellow SLE-FHE Setup Alert Banner -->
        <div class="card border-0 rounded-3 mb-4 p-3.5" style="background-color: #fef9c3; border-left: 4px solid #eab308 !important;">
            <div class="d-flex align-items-start gap-3">
                <div class="fs-4 lh-1 mt-0.5" style="color: #ca8a04;">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
                <div>
                    <h6 class="fw-bold mb-1" style="color: #713f12; font-size: 0.925rem;">Complete your SLE-FHE verification setup.</h6>
                    <p class="mb-2.5 small" style="color: #854d0e; font-size: 0.85rem;">Update your verification details before applying for sponsorship programs.</p>
                    <a href="{{ route('student.sle-fhe') }}" class="btn btn-sm fw-semibold shadow-sm px-3 py-1.5" style="background-color: #eab308; color: #422006; border: none; border-radius: 6px; font-size: 0.8rem;">
                        Go to SLE-FHE Verification
                    </a>
                </div>
            </div>
        </div>

        <!-- My Applications Table Section -->
        <div class="card border-0 shadow-sm rounded-3 bg-white mb-4">
            <div class="card-header bg-white border-0 pt-3.5 px-4 pb-0">
                <h6 class="fw-bold text-dark mb-0" style="font-size: 0.95rem;">My Applications</h6>
            </div>
            <div class="card-body px-4 pt-2 pb-4">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="text-secondary extra-small text-uppercase border-bottom" style="font-size: 0.72rem;">
                            <tr>
                                <th class="py-2.5 fw-bold border-0 text-secondary" style="width: 40%;">PROGRAM</th>
                                <th class="py-2.5 fw-bold border-0 text-secondary" style="width: 25%;">SUBMITTED</th>
                                <th class="py-2.5 fw-bold border-0 text-secondary" style="width: 20%;">STATUS</th>
                                <th class="py-2.5 fw-bold border-0 text-secondary text-end" style="width: 15%;">VIEW</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($applications as $application)
                                <tr>
                                    <td class="fw-semibold text-dark">{{ $application->sponsorshipProgram->program_name }}</td>
                                    <td class="text-secondary small">{{ optional($application->submitted_at ?? $application->created_at)->format('M d, Y') }}</td>
                                    <td>
                                        @php
                                            $status = $application->status?->value ?? $application->status;
                                            $statusClass = match($status) {
                                                'approved', 'confirmed' => 'bg-success bg-opacity-10 text-success',
                                                'verified', 'fassg_verified', 'sponsor_reviewed', 'ongoing' => 'bg-primary bg-opacity-10 text-primary',
                                                'pending', 'under_review' => 'bg-warning bg-opacity-10 text-warning',
                                                'rejected' => 'bg-danger bg-opacity-10 text-danger',
                                                default => 'bg-secondary bg-opacity-10 text-secondary',
                                            };
                                            $statusLabel = match($status) {
                                                'pending' => 'Under Review',
                                                'verified' => 'Verified',
                                                'fassg_verified' => 'FASSG Verified',
                                                'sponsor_reviewed' => 'Sponsor Reviewed',
                                                'approved' => 'Approved',
                                                'confirmed' => 'Confirmed',
                                                'ongoing' => 'Ongoing',
                                                'rejected' => 'Rejected',
                                                'expired' => 'Expired',
                                                default => ucfirst(str_replace('_', ' ', $status)),
                                            };
                                        @endphp
                                        <span class="badge {{ $statusClass }} px-2.5 py-1 rounded-pill fw-semibold" style="font-size: 0.72rem;">
                                            {{ $statusLabel }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('student.applications.show', $application) }}" class="btn btn-light btn-sm rounded-2 border px-2.5 py-1 small">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-5">
                                        <i class="bi bi-inbox fs-3 text-muted d-block mb-2"></i>
                                        <p class="text-muted small mb-2" style="font-size: 0.875rem;">No applications submitted yet.</p>
                                        <a href="{{ route('student.programs.index') }}" class="small fw-medium text-primary text-decoration-none" style="font-size: 0.825rem;">
                                            Explore Sponsorship Opportunities <i class="bi bi-arrow-right ms-1"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Document Verification Check Section -->
        <div class="card border-0 shadow-sm rounded-3 bg-white mb-4">
            <div class="card-header bg-white border-0 pt-3.5 px-4 pb-2">
                <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2" style="font-size: 0.95rem;">
                    <i class="bi bi-file-earmark-text text-secondary"></i> Document Verification Check
                </h6>
            </div>
            <div class="card-body px-4 pt-0 pb-3">
                <div class="list-group list-group-flush">
                    @php
                        $docTypes = [
                            ['label' => 'Certificate of Grades (CG / Grade Slip)', 'type' => 'certificate_of_grades'],
                            ['label' => 'Proof of Residence', 'type' => 'proof_of_residence'],
                            ['label' => 'Barangay Certification', 'type' => 'barangay_cert'],
                        ];
                        $latestApp = $applications->first();
                        $uploadedDocs = $latestApp?->documents?->pluck('document_type')?->map(fn($d) => $d->value ?? $d)?->toArray() ?? [];
                    @endphp

                    @foreach ($docTypes as $doc)
                        <div class="list-group-item px-0 py-2.5 d-flex align-items-center justify-content-between border-bottom">
                            <span class="small fw-medium text-dark" style="font-size: 0.875rem;">{{ $doc['label'] }}</span>
                            <div class="d-flex align-items-center gap-2">
                                @if (in_array($doc['type'], $uploadedDocs))
                                    <span class="badge px-2.5 py-1 rounded-2 fw-normal" style="font-size: 0.7rem; background-color: #dcfce7; color: #166534;">
                                        <i class="bi bi-check-lg me-1"></i>Uploaded
                                    </span>
                                @else
                                    <span class="badge bg-secondary px-2.5 py-1 rounded-2 fw-normal" style="font-size: 0.7rem; background-color: #64748b !important;">Not Uploaded</span>
                                    <a href="{{ route('student.sle-fhe') }}" class="btn btn-sm btn-outline-primary rounded-2 px-2.5 py-1 fw-medium" style="font-size: 0.72rem;">
                                        <i class="bi bi-upload me-1"></i>Upload
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- FASSG Announcements & Policy Section -->
        <div class="row">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-3 bg-white">
                    <div class="card-header bg-white border-bottom pt-3.5 px-4 pb-2.5">
                        <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2" style="font-size: 0.95rem;">
                            <i class="bi bi-info-circle text-primary"></i> FASSG Announcements &amp; Policy
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="text-uppercase fw-bold text-secondary mb-1" style="font-size: 0.7rem; letter-spacing: 0.05em;">
                            ELIGIBILITY &amp; POLICY NOTICE
                        </div>
                        <p class="text-secondary small mb-3" style="font-size: 0.825rem; line-height: 1.5;">
                            Verified SLE-FHE students are eligible to apply for open group, individual, and employee-based sponsorships opened by FASSG.
                        </p>
                        <div class="p-3 rounded-2 border bg-light small" style="font-size: 0.8rem; color: #475569;">
                            <i class="bi bi-exclamation-circle text-warning me-1"></i> <strong>Note:</strong> Multiple active sponsorships are automatically restricted. If your previous sponsorship has expired, you may apply for newly opened programs.
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection
