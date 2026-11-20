@extends('layouts.app')

@php
    $userName = auth()->user()->first_name ?? explode(' ', auth()->user()->name ?? 'Student')[0];
@endphp

@section('title', 'Student Dashboard')
@section('eyebrow', 'Student Portal · Dashboard')
@section('page-title', 'Welcome back, ' . $userName)
@section('subtitle', 'Your SLE-FHE sponsorship overview at Davao Oriental State University.')

@push('styles')
    <style>
        /* Scoped to the student dashboard; no global Bootstrap overrides. */
        .sf-dash-metric .card-body {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 132px;
        }

        .sf-dash-table thead th {
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            color: #64748B;
            vertical-align: middle;
        }

        .sf-dash-table tbody td {
            vertical-align: middle;
            padding-top: 0.85rem;
            padding-bottom: 0.85rem;
        }

        .sf-dash-table .sf-dash-empty {
            padding: 2.5rem 1rem;
        }

        .sf-dash-empty a.btn:focus-visible {
            outline: 3px solid #0F2942;
            outline-offset: 2px;
        }

        @media (max-width: 575.98px) {
            .sf-dash-table .sf-dash-empty {
                padding: 2rem 0.75rem;
            }
        }
    </style>
@endpush

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
        $sleFheStatus = $studentProfile?->sle_fhe_status ?? 'Unverified';
        $isVerified = $sleFheStatus === 'Verified';
        $isPending = $sleFheStatus === 'Pending Review';
        $isRejected = $sleFheStatus === 'Rejected';
        $sleFheLabel = $isVerified ? 'Verified' : ($isPending ? 'Pending' : 'Unverified');
        $sleFheIcon = $isVerified ? 'bi-shield-check' : ($isPending ? 'bi-clock' : 'bi-shield');
        $sleFhePalette = match ($sleFheStatus) {
            'Verified' => ['bg' => '#ECFDF5', 'text' => '#047857', 'border' => '#A7F3D0'],
            'Pending Review' => ['bg' => '#FFF7E6', 'text' => '#A16207', 'border' => '#F5D58B'],
            default => ['bg' => '#F1F5F9', 'text' => '#475569', 'border' => '#CBD5E1'],
        };
        $sleFheSupport = match (true) {
            $isVerified => 'Your SLE-FHE eligibility has been verified by FASSG.',
            $isPending => 'Your verification request is awaiting FASSG review.',
            $isRejected => 'Previous verification request not verified. Submit a new request for FASSG review.',
            default => 'Submit a verification request to have your eligibility reviewed by FASSG.',
        };
    @endphp

    <div>

        <!-- Top Metric Cards Grid -->
        <div class="row g-4 mb-4">
            <!-- Total Applications -->
            <div class="col-12 col-md-4">
                <x-metric-card title="Total Applications" value="{{ $totalApplications ?? 0 }}" icon="bi-file-earmark-text"
                    color="slate" class="sf-dash-metric" />
            </div>

            <!-- Active Sponsorships -->
            <div class="col-12 col-md-4">
                <x-metric-card title="Active Sponsorships" value="{{ $activeSponsorships ?? 0 }}" icon="bi-award"
                    color="emerald" class="sf-dash-metric" />
            </div>

            <!-- SLE-FHE Verification -->
            <div class="col-12 col-md-4">
                <div class="sf-stat-card card border-0 shadow-sm h-100 sf-dash-metric">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-start justify-content-between gap-3 mb-2">
                            <span class="sf-eyebrow d-block">SLE-FHE Verification</span>
                            <span class="sf-stat-icon" style="background-color: {{ $sleFhePalette['bg'] }}; color: {{ $sleFhePalette['text'] }}; border: 1px solid {{ $sleFhePalette['border'] }};">
                                <i class="bi {{ $sleFheIcon }}" aria-hidden="true"></i>
                            </span>
                        </div>
                        <div role="status" aria-label="SLE-FHE verification status: {{ $sleFheLabel }}">
                            <h3 class="sf-heading mb-1" style="font-size: 1.75rem; color: {{ $sleFhePalette['text'] }}; font-weight: 700;">
                                {{ $sleFheLabel }}
                            </h3>
                            <p class="text-secondary small mb-0" style="font-size: 0.775rem; line-height: 1.4;">
                                {{ $sleFheSupport }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Grant Awarded Alert --}}
        @if ($activeGrant)
            <div class="alert border-0 shadow-sm rounded-3 p-3 mb-4 d-flex align-items-center gap-3"
                style="background-color: #ECFDF5; border: 1px solid #A7F3D0 !important;">
                <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                    style="width: 36px; height: 36px; background-color: #D1FAE5; color: #047857;">
                    <i class="bi bi-award fs-5"></i>
                </div>
                <div>
                    <strong class="text-dark d-block">Grant Awarded</strong>
                    <span class="text-secondary small">{{ $activeGrant->sponsorshipProgram?->program_name }}</span>
                </div>
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
                    <table class="table sf-table sf-dash-table table-hover align-middle mb-0">
                        <thead class="text-uppercase border-bottom" style="background-color: #F8FAFC;">
                            <tr>
                                <th scope="col" class="ps-4 py-3 border-0" style="width: 38%;">Program</th>
                                <th scope="col" class="py-3 border-0 text-nowrap" style="width: 24%;">Submitted</th>
                                <th scope="col" class="py-3 border-0 text-nowrap" style="width: 22%;">Status</th>
                                <th scope="col" class="py-3 border-0 text-end pe-4" style="width: 16%;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($applications as $app)
                                <tr>
                                    <td class="ps-4 py-3 fw-semibold text-dark">
                                        {{ $app->sponsorshipProgram?->program_name ?? 'Unknown Program' }}</td>
                                    <td class="text-secondary small text-nowrap py-3">{{ $app->submitted_at?->format('M d, Y, h:i A') ?? '—' }}</td>
                                    <td class="text-nowrap py-3">
                                        <x-student-application-status-badge :status="$app->status" />
                                    </td>
                                    <td class="text-end pe-4 py-3">
                                        <a href="{{ route('student.applications.show', $app) }}"
                                            class="sf-btn-student-secondary"
                                            aria-label="View details for application to {{ $app->sponsorshipProgram?->program_name ?? 'program' }}">
                                            <i class="bi bi-eye" aria-hidden="true"></i> View Details
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted small sf-dash-empty">
                                        <div>
                                            <i class="bi bi-file-earmark-text display-6 text-secondary opacity-50 d-block mb-2"
                                                aria-hidden="true"></i>
                                            <span class="text-dark fw-semibold d-block mb-1">No applications submitted yet</span>
                                            <p class="text-secondary small mb-3 opacity-75">Apply for open sponsorship programs to track your progress here.</p>
                                            <a href="{{ route('student.programs.index') }}"
                                                class="btn btn-sm px-3 py-2 fw-semibold rounded-3 text-white"
                                                style="background-color: #0F2942; border: none; font-size: 0.8125rem;">
                                                <i class="bi bi-search me-1" aria-hidden="true"></i> Browse Sponsorship Opportunities
                                            </a>
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
                    @php
                        $steps = [
                            ['label' => 'Application Submitted', 'done' => $isStep1Done, 'status' => $isStep1Done ? optional($activeApplication->created_at)->format('M d, Y, h:i A') : 'Pending', 'icon' => 'bi-send'],
                            ['label' => 'FASSG Application Review', 'done' => $isStep2Done, 'status' => $isStep2Done ? 'Completed' : ($isStep1Done ? 'Awaiting Review' : 'Pending'), 'icon' => 'bi-search'],
                            ['label' => 'Sponsor Batch Decision', 'done' => $isStep3Done, 'status' => $isStep3Done ? 'Completed' : ($isStep2Done ? 'Awaiting Decision' : 'Pending'), 'icon' => 'bi-clipboard-check'],
                            ['label' => 'Ongoing', 'done' => $isStep4Done, 'status' => $isStep4Done ? 'Approved & Confirmed' : 'Pending', 'icon' => 'bi-award'],
                        ];
                        $currentStepIndex = null;
                        foreach ($steps as $idx => $s) {
                            if (!$s['done']) {
                                $currentStepIndex = $idx;
                                break;
                            }
                        }
                    @endphp
                    <div class="sf-app-timeline">
                        <div class="sf-app-timeline-track" aria-hidden="true"></div>
                        @foreach ($steps as $i => $step)
                            @php
                                if ($step['done']) {
                                    $stepState = 'completed';
                                } elseif ($i === $currentStepIndex) {
                                    $stepState = 'current';
                                } else {
                                    $stepState = 'upcoming';
                                }
                            @endphp
                            <div class="sf-app-timeline-step">
                                <div class="sf-app-timeline-circle is-{{ $stepState }}"
                                    aria-label="Stage {{ $i + 1 }}: {{ $step['label'] }} ({{ ucfirst($stepState) }})">
                                    <i class="bi {{ $stepState === 'completed' ? 'bi-check-lg' : $step['icon'] }}" aria-hidden="true"></i>
                                </div>
                                <p class="sf-app-timeline-label is-{{ $stepState }}">
                                    {{ $step['label'] }}
                                    @if ($step['label'] === 'Ongoing')
                                        <span class="visually-hidden">Final Approval</span>
                                    @endif
                                </p>
                                @if (
                                    $step['label'] === 'Ongoing' &&
                                        strtolower((string) ($activeApplication->status?->value ?? $activeApplication->status)) === 'approved')
                                    <div class="sf-app-timeline-status fw-bold text-success">Approved &amp; Confirmed</div>
                                    <small class="text-muted d-block" style="font-size: 0.72rem;">{{ $activeApplication->updated_at?->format('M d, Y, h:i A') ?? '—' }}</small>
                                @else
                                    <p class="sf-app-timeline-status {{ $stepState === 'completed' ? 'text-success fw-medium' : ($stepState === 'current' ? 'text-primary fw-medium' : 'text-muted') }}">
                                        {{ $step['status'] }}
                                    </p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <!-- FASSG Announcements & Policy Section -->
        <div class="row">
            <div class="col-12">
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
                        <div class="row g-4 align-items-center">
                            <div class="col-12 col-lg-6">
                                <div class="text-uppercase fw-bold text-secondary mb-2"
                                    style="font-size: 0.68rem; letter-spacing: 0.05em; color: #64748B;">INSTITUTIONAL ELIGIBILITY</div>
                                <p class="text-secondary small mb-0" style="font-size: 0.85rem; line-height: 1.6; color: #475569;">
                                    Verified SLE-FHE students are eligible to apply for open group, individual, and employee-based sponsorships managed by the FASSG office. Application submission initiates institutional document review; approval remains subject to committee evaluation and program slot availability.
                                </p>
                            </div>
                            <div class="col-12 col-lg-6">
                                <div class="p-3 rounded-3 border small d-flex align-items-start gap-2.5"
                                    style="font-size: 0.825rem; background-color: #FFFDF5; border-color: #F6DDA1 !important; color: #78350F; line-height: 1.55;">
                                    <i class="bi bi-info-circle flex-shrink-0 mt-0.5" style="color: #D97706; font-size: 1rem;"></i>
                                    <div>
                                        <strong>One Active Sponsorship Policy:</strong> Students may hold only one active university sponsorship grant at a time. If your previous sponsorship has concluded or expired, you may apply for newly opened program cycles.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection
