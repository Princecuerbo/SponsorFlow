@extends('layouts.app')

@section('title', 'Student Dashboard')

@section('content')
    @php
        $activeApplication = auth()
            ->user()
            ->studentProfile?->applications()
            ->whereNotIn('status', [
                \App\Enums\ApplicationStatus::Expired->value,
                \App\Enums\ApplicationStatus::Rejected->value,
            ])
            ->with('documents')
            ->latest()
            ->first();
        $timelineStatus = $activeApplication?->status?->value ?? $activeApplication?->status;
        $isActiveApplication = $activeApplication !== null;
        $isStep1Done = $isActiveApplication;
        $isStep2Done = in_array(
            $timelineStatus,
            ['Verified', 'FASSG Verified', 'Sponsor Reviewed', 'Approved', 'Confirmed'],
            true,
        );
        $isStep3Done = in_array($timelineStatus, ['Sponsor Reviewed', 'Approved', 'Confirmed'], true);
        $isStep4Done = in_array($timelineStatus, ['Approved', 'Confirmed'], true);
        $latestApp = auth()->user()->studentProfile?->applications()->with('documents')->latest()->first();
        $latestStatus = strtolower((string) ($latestApp?->status?->value ?? ($latestApp?->status ?? '')));
        $isActiveApplication = in_array(
            $latestStatus,
            ['pending', 'under_review', 'verified', 'approved', 'ongoing'],
            true,
        );
        $hasDocument = static fn(string $type): bool => $isActiveApplication &&
            ($latestApp?->documents ?? collect())->contains(
                static fn($document): bool => $document->document_type?->value === $type,
            );
        $docs = [
            ['label' => 'Certificate of Grades (CG / Grade Slip)', 'type' => 'certificate_of_grades'],
            ['label' => 'Proof of Residence', 'type' => 'proof_of_residence'],
            ['label' => 'Barangay Certification', 'type' => 'barangay_cert'],
        ];
        $isVerified = (bool) ($studentProfile?->is_sle_fhe_verified ?? false);
        $featured = $activeGrant ?? $activeApplication ?? $latestApp;
        $userName = auth()->user()->first_name ?? explode(' ', auth()->user()->name ?? 'Student')[0];
    @endphp

    <div class="container-fluid px-3 px-md-4 py-4" style="min-height: calc(100vh - 60px);">

        <!-- Welcome Title -->
        <div class="mb-4">
            <h3 class="fw-bold mb-1" style="color: #0f172a; font-size: 1.65rem;">
                Welcome back, {{ $userName }}
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
                        <div class="rounded-3 p-2.5 d-flex align-items-center justify-content-center"
                            style="background-color: #eff6ff; color: #2563eb; width: 44px; height: 44px; flex-shrink: 0;">
                            <i class="bi bi-journal-text fs-5"></i>
                        </div>
                        <div>
                            <div class="text-secondary fw-semibold text-uppercase extra-small"
                                style="font-size: 0.72rem; letter-spacing: 0.05em;">Total Applications</div>
                            <div class="fw-bold text-dark fs-4 lh-1 mt-1">{{ $totalApplications ?? 0 }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Sponsorships -->
            <div class="col-12 col-md-4">
                <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 p-2.5 d-flex align-items-center justify-content-center"
                            style="background-color: #ecfdf5; color: #059669; width: 44px; height: 44px; flex-shrink: 0;">
                            <i class="bi bi-award fs-5"></i>
                        </div>
                        <div>
                            <div class="text-secondary fw-semibold text-uppercase extra-small"
                                style="font-size: 0.72rem; letter-spacing: 0.05em;">Active Sponsorships</div>
                            <div class="fw-bold text-dark fs-4 lh-1 mt-1">{{ $activeGrant ? 1 : 0 }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SLE-FHE Verification -->
            <div class="col-12 col-md-4">
                <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-3 p-2.5 d-flex align-items-center justify-content-center"
                                style="background-color: #fffbeb; color: #d97706; width: 44px; height: 44px; flex-shrink: 0;">
                                <i class="bi bi-shield-check fs-5"></i>
                            </div>
                            <div>
                                <div class="text-secondary fw-semibold text-uppercase extra-small mb-1"
                                    style="font-size: 0.72rem; letter-spacing: 0.05em;">SLE-FHE Verification</div>
                                @if ($isVerified)
                                    <span class="badge rounded-pill fw-semibold px-2.5 py-1"
                                        style="background-color: #dcfce7; color: #166534; font-size: 0.72rem;">Verified</span>
                                @else
                                    <span class="badge rounded-pill fw-semibold px-2.5 py-1"
                                        style="background-color: #fef3c7; color: #b45309; font-size: 0.72rem;">Pending Review</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Verification Action Banner --}}
        @if ($studentProfile && !$isVerified)
            <div class="card border-0 rounded-3 mb-4 p-3.5"
                style="background-color: #fef9c3; border-left: 4px solid #eab308 !important;">
                <div class="d-flex align-items-start gap-3">
                    <div class="fs-4 lh-1 mt-0.5" style="color: #ca8a04;">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1" style="color: #713f12; font-size: 0.925rem;">Complete your SLE-FHE verification setup.</h6>
                        <p class="mb-2.5 small" style="color: #854d0e; font-size: 0.85rem;">Update your verification details before applying for sponsorship programs.</p>
                        <a href="{{ route('student.verification.show') }}"
                            class="btn btn-sm fw-semibold shadow-sm px-3 py-1.5"
                            style="background-color: #eab308; color: #422006; border: none; border-radius: 6px; font-size: 0.8rem;">
                            Go to SLE-FHE Verification
                        </a>
                    </div>
                </div>
            </div>
        @endif

        {{-- Grant Awarded Alert --}}
        @if ($activeGrant)
            <div class="alert alert-light border small p-3 rounded-3 mb-4 d-flex align-items-center gap-2 shadow-sm"
                style="background:#f8fafc; border-color:#e2e8f0 !important;">
                <i class="bi bi-patch-check-fill" style="color:#059669;"></i>
                <span><strong class="text-dark">Grant Awarded:</strong>
                    {{ $activeGrant->sponsorshipProgram?->program_name }}</span>
            </div>
        @endif

        <!-- My Applications Table Section -->
        <div class="card border-0 shadow-sm rounded-3 bg-white mb-4">
            <div class="card-header bg-white border-0 pt-3.5 px-4 pb-0">
                <h6 class="fw-bold text-dark mb-0" style="font-size: 0.95rem;">My Applications</h6>
            </div>
            <div class="card-body px-4 pt-2 pb-4">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="text-secondary extra-small text-uppercase border-bottom"
                            style="font-size: 0.72rem;">
                            <tr>
                                <th class="py-2.5 fw-bold border-0 text-secondary" style="width: 40%;">PROGRAM</th>
                                <th class="py-2.5 fw-bold border-0 text-secondary" style="width: 25%;">SUBMITTED</th>
                                <th class="py-2.5 fw-bold border-0 text-secondary" style="width: 20%;">STATUS</th>
                                <th class="py-2.5 fw-bold border-0 text-secondary text-end" style="width: 15%;">VIEW</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($applications as $app)
                                <tr>
                                    <td class="fw-semibold text-dark">{{ $app->sponsorshipProgram?->program_name ?? 'Unknown Program' }}</td>
                                    <td class="text-secondary small">{{ $app->submitted_at?->format('M d, Y') ?? '—' }}</td>
                                    <td>
                                        @php $statusVal = $app->status?->value ?? $app->status; @endphp
                                        @if (in_array($statusVal, ['Approved', 'Confirmed'], true))
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary px-2.5 py-1 rounded-pill fw-semibold" style="font-size: 0.72rem;">Approved</span>
                                        @elseif (in_array($statusVal, ['Pending', 'pending'], true))
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary px-2.5 py-1 rounded-pill fw-semibold" style="font-size: 0.72rem;">Pending</span>
                                        @else
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary px-2.5 py-1 rounded-pill fw-semibold" style="font-size: 0.72rem;">{{ $statusVal }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('student.applications.show', $app) }}"
                                            class="btn btn-light btn-sm rounded-2 border px-2.5 py-1 small">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted small">No applications submitted yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Application Status Timeline --}}
        @if ($activeApplication)
            <div class="card border-0 shadow-sm rounded-3 bg-white mb-4">
                <div class="card-header bg-white border-0 pt-3.5 px-4 pb-2.5">
                    <h6 class="fw-bold text-dark mb-0" style="font-size: 0.95rem;">
                        <i class="bi bi-clock-history me-2 text-primary"></i>Application Status Timeline
                    </h6>
                </div>
                <div class="card-body px-4 py-3">
                    <div class="d-flex justify-content-between position-relative my-2">
                        <div class="position-absolute top-50 start-0 end-0 translate-middle-y bg-light" style="height: 2px;"></div>
                        @foreach ([
                            ['label' => 'Submitted', 'done' => $isStep1Done, 'status' => $isStep1Done ? optional($activeApplication->created_at)->format('M d, Y') : 'Pending', 'icon' => 'bi-send'],
                            ['label' => 'FASSG Review', 'done' => $isStep2Done, 'status' => $isStep2Done ? 'Completed' : ($isStep1Done ? 'In Progress' : 'Pending'), 'icon' => 'bi-search'],
                            ['label' => 'Sponsor Review', 'done' => $isStep3Done, 'status' => $isStep3Done ? 'Completed' : ($isStep2Done ? 'In Progress' : 'Pending'), 'icon' => 'bi-building'],
                            ['label' => 'Final Approval', 'done' => $isStep4Done, 'status' => $isStep4Done ? 'Approved & Confirmed' : 'Pending', 'icon' => 'bi-award'],
                        ] as $step)
                            <div class="text-center position-relative z-1 bg-white px-1" style="flex: 1;">
                                <span class="btn btn-sm {{ $step['done'] ? 'btn-primary' : 'btn-secondary' }} rounded-circle mb-2"
                                    style="width: 36px; height: 36px;">
                                    <i class="bi {{ $step['done'] ? 'bi-check-lg' : $step['icon'] }}"></i>
                                </span>
                                <p class="small {{ $step['done'] ? 'fw-bold' : 'text-muted' }} mb-0">{{ $step['label'] }}</p>
                                @if (
                                    $step['label'] === 'Final Approval' &&
                                        strtolower((string) ($activeApplication->status?->value ?? $activeApplication->status)) === 'approved')
                                    <div class="fw-bold text-success">Approved &amp; Confirmed</div>
                                    <small class="text-muted">{{ $activeApplication->updated_at?->format('M d, Y') ?? '—' }}</small>
                                @else
                                    <small class="{{ $step['done'] ? 'text-success' : 'text-muted' }}">{{ $step['status'] }}</small>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <!-- Document Verification Check Section -->
        <div class="card border-0 shadow-sm rounded-3 bg-white mb-4">
            <div class="card-header bg-white border-0 pt-3.5 px-4 pb-2">
                <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2" style="font-size: 0.95rem;">
                    <i class="bi bi-file-earmark-text text-secondary"></i> Document Verification Check
                </h6>
            </div>
            <div class="card-body px-4 pt-0 pb-3">
                <div class="small mb-3" style="color: #64748b; font-size: 0.75rem;">
                    <i class="bi bi-info-circle me-1 text-primary"></i>
                    Accepted formats: PDF, JPG, PNG <span class="fw-semibold" style="color: #475569;">(Max: 5MB)</span>
                </div>
                <div class="list-group list-group-flush">
                    @foreach ($docs as $document)
                        @php
                            $uploaded = $hasDocument($document['type']);
                        @endphp
                        <div class="list-group-item px-0 py-2.5 d-flex align-items-center justify-content-between border-bottom">
                            <span class="small fw-medium text-dark" style="font-size: 0.875rem;">{{ $document['label'] }}</span>
                            <div class="d-flex align-items-center gap-2">
                                @if ($latestStatus === 'needs_resubmission')
                                    <span class="badge px-2.5 py-1 rounded-2 fw-normal"
                                        style="font-size: 0.7rem; background-color: #fef3c7; color: #b45309;">Re-upload Required</span>
                                @elseif ($uploaded)
                                    <span class="badge px-2.5 py-1 rounded-2 fw-normal"
                                        style="font-size: 0.7rem; background-color: #dcfce7; color: #166534;">
                                        <i class="bi bi-check-lg me-1"></i>Uploaded
                                    </span>
                                @else
                                    <span class="badge bg-secondary px-2.5 py-1 rounded-2 fw-normal"
                                        style="font-size: 0.7rem; background-color: #64748b !important;">Not Uploaded</span>
                                @endif
                                <a href="{{ route('student.verification.show') }}"
                                    class="btn btn-sm btn-outline-primary rounded-2 px-2.5 py-1 fw-medium" style="font-size: 0.72rem;">
                                    <i class="bi bi-upload me-1"></i>Upload
                                </a>
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
                        <div class="text-uppercase fw-bold text-secondary mb-1"
                            style="font-size: 0.7rem; letter-spacing: 0.05em;">ELIGIBILITY &amp; POLICY NOTICE</div>
                        <p class="text-secondary small mb-3" style="font-size: 0.825rem; line-height: 1.5;">
                            Verified SLE-FHE students are eligible to apply for open group, individual, and
                            employee-based sponsorships managed by FASSG.
                        </p>
                        <div class="p-3 rounded-2 border bg-light small" style="font-size: 0.8rem; color: #475569;">
                            <i class="bi bi-exclamation-circle text-warning me-1"></i>
                            <strong>Note:</strong> Multiple active sponsorships are automatically restricted. If
                            your previous sponsorship has expired, you may apply for newly opened programs.
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection
