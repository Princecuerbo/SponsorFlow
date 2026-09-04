@extends('layouts.app')

@section('title', 'Student Dashboard')

@section('content')
    @php
        $isVerified = (bool) ($studentProfile?->is_sle_fhe_verified ?? false);
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
                            <div class="fw-bold text-dark fs-4 lh-1 mt-1">{{ $activeSponsorships ?? 0 }}</div>
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
                                        style="background-color: #fef3c7; color: #b45309; font-size: 0.72rem;">Pending
                                        Review</span>
                                @endif
                            </div>
                        </div>
                    </div>
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
                            @forelse ($applications as $app)
                                <tr>
                                    <td class="fw-semibold text-dark">
                                        {{ $app->sponsorshipProgram?->program_name ?? 'Unknown Program' }}</td>
                                    <td class="text-secondary small">{{ $app->submitted_at?->format('M d, Y') ?? '—' }}</td>
                                    <td>
                                        @php $statusVal = $app->status?->value ?? $app->status; @endphp
                                        @if (in_array($statusVal, ['Approved', 'Confirmed'], true))
                                            <span
                                                class="badge bg-secondary bg-opacity-10 text-secondary px-2.5 py-1 rounded-pill fw-semibold"
                                                style="font-size: 0.72rem;">Approved</span>
                                        @elseif (in_array($statusVal, ['Pending', 'pending'], true))
                                            <span
                                                class="badge bg-secondary bg-opacity-10 text-secondary px-2.5 py-1 rounded-pill fw-semibold"
                                                style="font-size: 0.72rem;">Pending</span>
                                        @else
                                            <span
                                                class="badge bg-secondary bg-opacity-10 text-secondary px-2.5 py-1 rounded-pill fw-semibold"
                                                style="font-size: 0.72rem;">{{ $statusVal }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('student.applications.show', $app) }}"
                                            class="btn btn-light btn-sm rounded-2 border px-2.5 py-1 small">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted small">No applications submitted
                                        yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
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
