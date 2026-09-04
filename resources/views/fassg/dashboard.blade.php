@extends('layouts.app')

@section('title', 'FASSG Dashboard')
@section('eyebrow', 'FASSG Office')
@section('page-title', 'Dashboard')

@section('content')

    {{-- Stat cards --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="sf-stat-card p-3 d-flex align-items-center gap-3">
                <div class="sf-stat-icon bg-primary-subtle text-primary"><i class="bi bi-people"></i></div>
                <div>
                    <div class="h4 mb-0 sf-heading">{{ number_format($stats['total_applicants'] ?? 0) }}</div>
                    <div class="small text-secondary">Total Applicants</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="sf-stat-card p-3 d-flex align-items-center gap-3">
                <div class="sf-stat-icon bg-info-subtle text-info"><i class="bi bi-patch-check"></i></div>
                <div>
                    <div class="h4 mb-0 sf-heading">{{ number_format($stats['verified_sle_fhe'] ?? 0) }}</div>
                    <div class="small text-secondary">Verified SLE-FHE</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="sf-stat-card p-3 d-flex align-items-center gap-3">
                <div class="sf-stat-icon bg-warning-subtle text-warning"><i class="bi bi-briefcase"></i></div>
                <div>
                    <div class="h4 mb-0 sf-heading">{{ number_format($stats['active_programs'] ?? 0) }}</div>
                    <div class="small text-secondary">Active Programs</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="sf-stat-card p-3 d-flex align-items-center gap-3">
                <div class="sf-stat-icon bg-success-subtle text-success"><i class="bi bi-award"></i></div>
                <div>
                    <div class="h4 mb-0 sf-heading">{{ number_format($stats['confirmed_beneficiaries'] ?? 0) }}</div>
                    <div class="small text-secondary">Confirmed Beneficiaries</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- Chart placeholder --}}
        <div class="col-lg-7">
            <div class="card sf-card h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h6 sf-heading mb-0">Application Progress This Term</h2>
                        <span class="small text-secondary">By status</span>
                    </div>

                    @php
                        $breakdown = $applicationStatusBreakdown ?? [];
                        $max = max(array_values($breakdown) + [1]);
                    @endphp

                    <div class="d-flex flex-column gap-2">
                        @forelse ($breakdown as $status => $count)
                            <div>
                                <div class="d-flex justify-content-between small mb-1">
                                    <span>{{ $status }}</span>
                                    <span class="fw-semibold">{{ $count }}</span>
                                </div>
                                <div class="progress" style="height:8px; border-radius:6px;">
                                    <div class="progress-bar" role="progressbar"
                                        style="width: {{ $max ? ($count / $max) * 100 : 0 }}%; background: var(--sf-navy);">
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="sf-empty-state py-4">
                                <i class="bi bi-bar-chart"></i>
                                <div class="small">No application data yet this term.</div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- Operational summaries --}}
        <div class="col-lg-5">
            <div class="card sf-card h-100">
                <div class="card-body p-4">
                    <h2 class="h6 sf-heading mb-3">Pending Verification Queue</h2>
                    <div class="d-flex align-items-center gap-3 border-bottom pb-3 mb-3">
                        <div class="sf-stat-icon bg-warning-subtle text-warning">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                        <div>
                            <div class="h4 mb-0 sf-heading">{{ number_format($pendingVerificationCount ?? 0) }}</div>
                            <div class="small text-secondary">Applications awaiting FASSG or sponsor review</div>
                        </div>
                    </div>
                    <h3 class="h6 sf-heading mb-2">Recent Program Updates</h3>
                    <div class="list-group list-group-flush">
                        @forelse ($recentPrograms as $program)
                            <div class="list-group-item px-0 d-flex justify-content-between align-items-center gap-3">
                                <div class="small fw-semibold">{{ $program->program_name }}</div>
                                <x-status-badge :status="$program->effective_status" />
                            </div>
                        @empty
                            <div class="sf-empty-state py-4">
                                <i class="bi bi-briefcase"></i>
                                <div class="small">No program updates yet.</div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
