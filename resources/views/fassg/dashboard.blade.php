@extends('layouts.app')

@section('title', 'FASSG Dashboard')
@section('eyebrow', 'FASSG OFFICE')
@section('page-title', 'Dashboard')

@push('styles')
    @include('fassg.partials.dashboard-styles')
@endpush

@section('content')
    <div class="fassg-dashboard">
        @php
            // Presentation metadata only; metric values remain supplied by the controller.
            $metrics = [
                [
                    'key' => 'total_applicants',
                    'title' => 'Total Applicants',
                    'icon' => 'bi-people',
                    'description' => 'Across all active programs',
                ],
                [
                    'key' => 'verified_sle_fhe',
                    'title' => 'Verified SLE-FHE',
                    'icon' => 'bi-shield-check',
                    'description' => 'Institutional registry verified',
                ],
                [
                    'key' => 'active_programs',
                    'title' => 'Active Programs',
                    'icon' => 'bi-briefcase',
                    'description' => 'Open and accepting applications',
                ],
                [
                    'key' => 'confirmed_beneficiaries',
                    'title' => 'Confirmed Beneficiaries',
                    'icon' => 'bi-file-earmark-check',
                    'description' => 'Confirmed batch members',
                ],
            ];
        @endphp

        {{-- 1. Four Summary Metric Cards --}}
        <div class="fassg-dashboard-metrics">
            @foreach ($metrics as $metric)
                <section class="fassg-dashboard-card fassg-dashboard-metric" aria-labelledby="metric-{{ $metric['key'] }}">
                    <div class="fassg-dashboard-metric-top">
                        <h3 class="fassg-dashboard-metric-label" id="metric-{{ $metric['key'] }}">{{ $metric['title'] }}</h3>
                        <span @class(['fassg-dashboard-icon', 'fassg-dashboard-icon-amber' => $metric['key'] === 'active_programs']) aria-hidden="true">
                            <i class="bi {{ $metric['icon'] }}"></i>
                        </span>
                    </div>
                    <div class="fassg-dashboard-metric-body">
                        <p class="fassg-dashboard-metric-value">{{ number_format($stats[$metric['key']] ?? 0) }}</p>
                        <p class="fassg-dashboard-metric-description">{{ $metric['description'] }}</p>
                    </div>
                </section>
            @endforeach
        </div>

        {{-- 2. Lower Dashboard Panels: Application Progress (Left) and Operations (Right) --}}
        <div class="fassg-dashboard-panels">
            {{-- Left Panel: Application Progress This Term --}}
            <section class="fassg-dashboard-card fassg-dashboard-panel" aria-labelledby="application-progress-heading">
                <div class="fassg-dashboard-panel-heading">
                    <h3 id="application-progress-heading" class="fassg-dashboard-panel-title">Application Progress This Term</h3>
                    <span class="fassg-dashboard-panel-subtitle">By status</span>
                </div>

                @php
                    $breakdown = $applicationStatusBreakdown ?? [];
                    $max = max(array_values($breakdown) + [1]);
                @endphp

                <div class="fassg-dashboard-progress-list">
                    @forelse ($breakdown as $status => $count)
                        <div class="fassg-dashboard-progress-item">
                            <div class="fassg-dashboard-progress-label">
                                <span class="fassg-dashboard-status-name">{{ $status }}</span>
                                <span class="fassg-dashboard-status-count">{{ number_format($count) }}</span>
                            </div>
                            <div class="progress fassg-dashboard-progress">
                                <div class="progress-bar" role="progressbar"
                                    aria-label="{{ $status }} applications"
                                    aria-valuemin="0"
                                    aria-valuemax="{{ $max }}"
                                    aria-valuenow="{{ $count }}"
                                    style="width: {{ $max ? ($count / $max) * 100 : 0 }}%;">
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="sf-empty-state py-4">
                            <i class="bi bi-bar-chart" aria-hidden="true"></i>
                            <div class="small">No application data yet this term.</div>
                        </div>
                    @endforelse
                </div>
            </section>

            {{-- Right Panel: Pending SLE-FHE Verifications & Recent Program Updates --}}
            <div class="fassg-dashboard-card fassg-dashboard-panel">
                {{-- Top Section: Pending SLE-FHE Verifications --}}
                <section aria-labelledby="pending-verifications-heading" class="fassg-dashboard-pending-section">
                    <h3 id="pending-verifications-heading" class="fassg-dashboard-panel-title">Pending SLE-FHE Verifications</h3>
                    <a href="{{ route('fassg.sle-fhe.index') }}" class="fassg-dashboard-pending-link" aria-label="Review {{ $pendingVerificationCount ?? 0 }} pending SLE-FHE student verifications">
                        <div class="fassg-dashboard-icon fassg-dashboard-icon-amber" aria-hidden="true">
                            <i class="bi bi-clock"></i>
                        </div>
                        <div class="fassg-dashboard-pending-details">
                            <span class="fassg-dashboard-pending-count">{{ number_format($pendingVerificationCount ?? 0) }}</span>
                            <span class="fassg-dashboard-pending-description">Student profiles awaiting FASSG masterlist verification</span>
                        </div>
                    </a>
                </section>

                <div class="fassg-dashboard-divider" role="separator" aria-hidden="true"></div>

                {{-- Bottom Section: Recent Program Updates --}}
                <section aria-labelledby="recent-programs-heading" class="fassg-dashboard-programs-section">
                    <h3 id="recent-programs-heading" class="fassg-dashboard-panel-title">Recent Program Updates</h3>
                    <div class="fassg-dashboard-programs">
                        @forelse ($recentPrograms as $program)
                            <div class="fassg-dashboard-program">
                                <span class="fassg-dashboard-program-name" title="{{ $program->program_name }}">{{ $program->program_name }}</span>
                                <x-status-badge :status="$program->effective_status" class="fassg-dashboard-program-status" />
                            </div>
                        @empty
                            <div class="sf-empty-state py-4">
                                <i class="bi bi-briefcase" aria-hidden="true"></i>
                                <div class="small">No program updates yet.</div>
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </div>
@endsection
