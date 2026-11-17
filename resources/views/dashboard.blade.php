@extends('layouts.app')

@php
    $userName = auth()->user()->first_name ?? explode(' ', auth()->user()->name ?? 'Student')[0];
@endphp

@section('title', 'Student Dashboard')
@section('eyebrow', 'Student Portal · Dashboard')
@section('page-title', 'Welcome back, ' . $userName)
@section('subtitle', 'Your SLE-FHE sponsorship overview at Davao Oriental State University.')

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
        $isVerified = ($studentProfile?->sle_fhe_status ?? null) === 'Verified';
    @endphp

    <div>

        <!-- Top Metric Cards Grid -->
        <div class="row g-4 mb-4">
            <!-- Total Applications -->
            <div class="col-12 col-md-4">
                <x-metric-card title="Total Applications" value="{{ $totalApplications ?? 0 }}" icon="bi-file-earmark-text"
                    color="slate" />
            </div>

            <!-- Active Sponsorships -->
            <div class="col-12 col-md-4">
                <x-metric-card title="Active Sponsorships" value="{{ $activeSponsorships ?? 0 }}" icon="bi-mortarboard"
                    color="emerald" />
            </div>

            <!-- SLE-FHE Verification -->
            <div class="col-12 col-md-4">
                <div class="sf-stat-card card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-start justify-content-between gap-3 mb-2">
                            <span class="sf-eyebrow d-block">SLE-FHE Verification</span>
                            @if ($isVerified)
                                <span class="sf-stat-icon" style="background-color: #EEF3F7; color: #173B57;">
                                    <i class="bi bi-patch-check"></i>
                                </span>
                            @else
                                <span class="sf-stat-icon" style="background-color: #FFF7E6; color: #B88219; border: 1px solid #F6DDA1;">
                                    <i class="bi bi-clock"></i>
                                </span>
                            @endif
                        </div>
                        <div class="d-flex align-items-center" style="min-height: 2.25rem;">
                            @if ($isVerified)
                                <x-status-badge :status="'Verified'" />
                            @else
                                <span class="badge rounded-pill d-inline-flex align-items-center gap-1 fw-semibold"
                                    style="font-size: 0.75rem; padding: 0.2rem 0.65rem; background-color: #FFF8EA; color: #986515; border: 1px solid #F6DDA1;">
                                    <span style="width: 7px; height: 7px; border-radius: 50%; background-color: #D4A332; display: inline-block;"></span>
                                    Pending Review
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

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
        <div class="card border-0 shadow-sm rounded-4 bg-white mb-4" style="border: 1px solid var(--sf-border-color, #E1E8EC) !important;">
            <div class="card-header bg-white border-bottom px-4 py-3">
                <h3 class="h6 sf-heading mb-0 fw-bold" style="color: #0F2942;">My Applications</h3>
                <p class="text-secondary small mb-0 mt-0.5" style="font-size: 0.775rem;">Status and history of your submitted sponsorship applications</p>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table sf-table table-hover align-middle mb-0">
                        <thead class="text-secondary extra-small text-uppercase border-bottom" style="font-size: 0.72rem; background-color: #F8FAFC;">
                            <tr>
                                <th class="ps-4 py-3 fw-bold border-0 text-secondary" style="width: 40%;">PROGRAM</th>
                                <th class="py-3 fw-bold border-0 text-secondary text-nowrap" style="width: 25%;">SUBMITTED</th>
                                <th class="py-3 fw-bold border-0 text-secondary text-nowrap" style="width: 20%;">STATUS</th>
                                <th class="py-3 fw-bold border-0 text-secondary text-end pe-4" style="width: 15%;">VIEW</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($applications as $app)
                                <tr>
                                    <td class="ps-4 py-3 fw-semibold text-dark">
                                        {{ $app->sponsorshipProgram?->program_name ?? 'Unknown Program' }}</td>
                                    <td class="text-secondary small text-nowrap py-3">{{ $app->submitted_at?->format('M d, Y, h:i A') ?? '—' }}</td>
                                    <td class="text-nowrap py-3">
                                        <x-status-badge :status="$app->status" />
                                    </td>
                                    <td class="text-end pe-4 py-3">
                                        <a href="{{ route('student.applications.show', $app) }}"
                                            class="btn btn-light btn-sm rounded-2 border px-2.5 py-1 small fw-semibold"
                                            style="font-size: 0.8rem; background-color: #F8FAFC; border-color: #E2E8F0; color: #0F2942;">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted small">
                                        <div class="py-2">
                                            <i class="bi bi-folder2-open display-6 text-secondary opacity-50 d-block mb-2"></i>
                                            <span class="text-secondary fw-semibold">No applications submitted yet.</span>
                                            <p class="text-secondary small mb-0 mt-1 opacity-75">Apply for open sponsorship programs to track your progress here.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Application Status Timeline --}}
        @if ($activeApplication)
            <div class="card border-0 shadow-sm rounded-4 bg-white mb-4" style="border: 1px solid var(--sf-border-color, #E1E8EC) !important;">
                <div class="card-header bg-white border-bottom px-4 py-3">
                    <div class="d-flex align-items-center gap-3">
                        <span class="rounded-3 d-inline-flex align-items-center justify-content-center flex-shrink-0"
                            style="width: 40px; height: 40px; background-color: #E8F0F6; color: #0F2942;">
                            <i class="bi bi-clock-history fs-5" aria-hidden="true"></i>
                        </span>
                        <div>
                            <h3 class="h6 sf-heading mb-0 fw-bold" style="color: #0F2942;">Application Status Timeline</h3>
                            <p class="text-secondary small mb-0 mt-0.5" style="font-size: 0.775rem;">Current progress of your active sponsorship application</p>
                        </div>
                    </div>
                </div>
                <div class="card-body px-4 py-4">
                    <div class="d-flex justify-content-between position-relative my-2">
                        <div class="position-absolute top-50 start-0 end-0 translate-middle-y bg-light" style="height: 2px;"></div>
                        @foreach ([
                            ['label' => 'Submitted', 'done' => $isStep1Done, 'status' => $isStep1Done ? optional($activeApplication->created_at)->format('M d, Y, h:i A') : 'Pending', 'icon' => 'bi-send'],
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
                                    <small class="text-muted">{{ $activeApplication->updated_at?->format('M d, Y, h:i A') ?? '—' }}</small>
                                @else
                                    <small class="{{ $step['done'] ? 'text-success' : 'text-muted' }}">{{ $step['status'] }}</small>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <!-- FASSG Announcements & Policy Section -->
        <div class="row">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 bg-white" style="border: 1px solid var(--sf-border-color, #E1E8EC) !important;">
                    <div class="card-header bg-white border-bottom px-4 py-3">
                        <div class="d-flex align-items-center gap-3">
                            <span class="rounded-3 d-inline-flex align-items-center justify-content-center flex-shrink-0"
                                style="width: 40px; height: 40px; background-color: #E8F0F6; color: #0F2942;">
                                <i class="bi bi-info-circle fs-5" aria-hidden="true"></i>
                            </span>
                            <div>
                                <h3 class="h6 sf-heading mb-0 fw-bold" style="color: #0F2942;">FASSG Announcements &amp; Policy</h3>
                                <p class="text-secondary small mb-0 mt-0.5" style="font-size: 0.775rem;">Official guidelines and eligibility policies</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="text-uppercase fw-bold text-secondary mb-2"
                            style="font-size: 0.68rem; letter-spacing: 0.05em; color: #64748B;">ELIGIBILITY &amp; POLICY NOTICE</div>
                        <p class="text-secondary small mb-3" style="font-size: 0.85rem; line-height: 1.55; color: #475569;">
                            Verified SLE-FHE students are eligible to apply for open group, individual, and
                            employee-based sponsorships managed by FASSG.
                        </p>
                        <div class="p-3 rounded-3 border small d-flex align-items-start gap-2.5"
                            style="font-size: 0.825rem; background-color: #FFFDF5; border-color: #F6DDA1 !important; color: #78350F; line-height: 1.5;">
                            <i class="bi bi-exclamation-circle-fill flex-shrink-0 mt-0.5" style="color: #D97706; font-size: 0.95rem;"></i>
                            <div>
                                <strong>Note:</strong> Multiple active sponsorships are automatically restricted. If
                                your previous sponsorship has expired, you may apply for newly opened programs.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection
