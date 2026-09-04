@extends('layouts.app')

@section('title', 'Accounting Dashboard')
@section('eyebrow', 'Accounting Office')
@section('page-title', 'Dashboard')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div>
            <p class="text-uppercase small fw-semibold text-warning mb-2">Accounting workspace</p>
            <h1 class="h2 sf-heading mb-1">Accounting Dashboard</h1>
            <p class="text-secondary mb-0">Read-only financial reference overview for sponsor-confirmed beneficiaries.</p>
        </div><a href="{{ route('accounting.beneficiaries.index') }}" class="btn btn-sf-navy"><i
                class="bi bi-table me-1"></i>View Full Master List</a>
    </div>
    <div class="sf-readonly-banner d-flex align-items-center gap-3 mb-4"><i class="bi bi-lock fs-5"></i><span
            class="small fw-semibold">Accounting personnel can view and export references only. No approval, editing, or
            deletion actions are available.</span></div>
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="sf-stat-card p-4">
                <div class="sf-stat-icon bg-success-subtle text-success mb-3"><i class="bi bi-person-check"></i></div>
                <div class="h3 sf-heading mb-1">{{ number_format($totalApproved) }}</div>
                <div class="small text-secondary">Approved beneficiaries</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="sf-stat-card p-4">
                <div class="sf-stat-icon bg-primary-subtle text-primary mb-3"><i class="bi bi-buildings"></i></div>
                <div class="h3 sf-heading mb-1">{{ number_format($activeSponsors) }}</div>
                <div class="small text-secondary">Active sponsors represented</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="sf-stat-card p-4">
                <div class="sf-stat-icon bg-warning-subtle text-warning mb-3"><i class="bi bi-diagram-3"></i></div>
                <div class="h3 sf-heading mb-1">{{ number_format($programBreakdown->count()) }}</div>
                <div class="small text-secondary">Grant program categories</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="sf-stat-card p-4">
                <div class="sf-stat-icon bg-info-subtle text-info mb-3"><i class="bi bi-clock-history"></i></div>
                <div class="h3 sf-heading mb-1">{{ number_format($recentApprovals->count()) }}</div>
                <div class="small text-secondary">Recent approvals shown</div>
            </div>
        </div>
    </div>
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card sf-card h-100">
                <div class="card-body p-4">
                    <h2 class="h5 sf-heading mb-3">Sponsor Allocation Summary</h2>
                    <div class="table-responsive">
                        <table class="table sf-table mb-0">
                            <thead>
                                <tr>
                                    <th>Sponsor / Organization</th>
                                    <th>Programs</th>
                                    <th class="text-end">Beneficiaries</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($sponsorAllocation as $allocation)
                                    <tr>
                                        <td>{{ $allocation['sponsor'] }}</td>
                                        <td>{{ $allocation['programs'] }}</td>
                                        <td class="text-end fw-semibold">{{ $allocation['beneficiaries'] }}</td>
                                </tr>@empty<tr>
                                        <td colspan="3" class="text-center text-secondary py-4">No sponsor allocations
                                            yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card sf-card h-100">
                <div class="card-body p-4">
                    <h2 class="h5 sf-heading mb-3">Program Category Breakdown</h2>
                    @forelse ($programBreakdown as $breakdown)
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span>{{ $breakdown['category'] }}</span><strong>{{ $breakdown['beneficiaries'] }}</strong>
                    </div>@empty<div class="text-secondary small">No approved program data yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
    <div class="card sf-card mt-4">
        <div class="card-body p-4">
            <h2 class="h5 sf-heading mb-3">Recent Beneficiary Approvals</h2>
            <div class="table-responsive">
                <table class="table sf-table mb-0">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Program</th>
                            <th>Sponsor</th>
                            <th>Date Approved</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentApprovals as $beneficiary)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $beneficiary['student_name'] }}</div>
                                    <div class="small text-secondary sf-mono">{{ $beneficiary['student_id'] }}</div>
                                </td>
                                <td>{{ $beneficiary['program_name'] }}</td>
                                <td>{{ $beneficiary['sponsor_name'] }}</td>
                                <td>{{ $beneficiary['date_approved']?->format('M d, Y') ?? '—' }}</td>
                        </tr>@empty<tr>
                                <td colspan="4" class="text-center text-secondary py-4">No beneficiary approvals yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
