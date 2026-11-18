@extends('layouts.app')

@section('title', 'Accounting Dashboard')
@section('eyebrow', 'Accounting Office · Dashboard')
@section('page-title', 'Accounting Dashboard')
@section('subtitle')
    <span class="accounting-original-copy">Read-only reference for SLE-FHE-verified, Sponsor-approved beneficiaries explicitly forwarded by FASSG.</span>
    <span class="accounting-desktop-copy d-none">View approved beneficiaries officially forwarded by FASSG.</span>
@endsection

@push('styles')
    @include('accounting.partials.desktop-styles')
@endpush

@section('header-actions')
    <a href="{{ route('accounting.beneficiaries.index') }}" class="btn btn-sf-navy accounting-primary-link d-inline-flex align-items-center gap-2"><i class="bi bi-table" aria-hidden="true"></i><span class="accounting-original-copy">View Full
        Master List</span><span class="accounting-desktop-copy d-none">View Beneficiary Master List</span></a>
@endsection

@section('content')
    <div class="alert border-0 border-start border-4 border-primary rounded-3 p-3 mb-4 accounting-notice"
        style="background-color: rgba(15, 41, 66, 0.05); color: #0F2942; border-color: #0F2942 !important;">
        <span class="accounting-original-copy"><i class="bi bi-lock-fill me-2"></i> Accounting personnel can view, print, and export references only. No approval, editing,
        or deletion actions are available.</span>
        <i class="bi bi-shield-lock accounting-desktop-copy d-none" aria-hidden="true"></i>
        <span class="accounting-desktop-copy d-none"><strong>Read-only access:</strong> Accounting personnel may view, print, and export beneficiary records. Approval and record modification are not available.</span>
    </div>
    <div class="row g-3 mb-4 accounting-original-copy">
        <div class="col-sm-6 col-xl-3">
            <x-metric-card title="Forwarded approved beneficiaries" value="{{ number_format($totalApproved) }}" icon="bi-person-check"
                color="emerald" />
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-metric-card title="Sponsors represented" value="{{ number_format($activeSponsors) }}"
                icon="bi-buildings" color="slate" />
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-metric-card title="Grant program categories" value="{{ number_format($programBreakdown->count()) }}"
                icon="bi-diagram-3" color="sky" />
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-metric-card title="Beneficiaries approved in the last 30 days" value="{{ number_format($recentApprovalCount) }}"
                icon="bi-clock-history" color="amber" />
        </div>
    </div>
    <div class="accounting-desktop-metrics d-none">
        @foreach ([
            ['label' => 'Forwarded approved beneficiaries', 'value' => $totalApproved, 'icon' => 'bi-person-check'],
            ['label' => 'Sponsors represented', 'value' => $activeSponsors, 'icon' => 'bi-building'],
            ['label' => 'Program categories', 'value' => $programBreakdown->count(), 'icon' => 'bi-diagram-3'],
            ['label' => 'Approved in the last 30 days', 'value' => $recentApprovalCount, 'icon' => 'bi-calendar-check'],
        ] as $metric)
            <div class="accounting-metric">
                <span class="accounting-metric-label">{{ $metric['label'] }}</span>
                <span class="accounting-metric-icon"><i class="bi {{ $metric['icon'] }}" aria-hidden="true"></i></span>
                <strong class="accounting-metric-value">{{ number_format($metric['value']) }}</strong>
            </div>
        @endforeach
    </div>
    <p class="small text-secondary mb-4 accounting-metric-note">Counts include only eligible beneficiaries explicitly forwarded by FASSG.
        The 30-day count uses the Sponsor approval date.</p>
    <div class="row g-4 accounting-summary-panels">
        <div class="col-lg-7">
            <div class="card sf-card h-100">
                <div class="card-body p-4">
                    <h3 class="h6 sf-heading mb-3">Beneficiaries by Sponsor</h3>
                    <div class="table-responsive">
                        <table class="table sf-table table-hover align-middle mb-0 accounting-sponsor-table">
                            <thead class="table-light text-muted small text-uppercase">
                                <tr>
                                    <th class="ps-4">Sponsor / Organization</th>
                                    <th>Programs</th>
                                    <th class="text-end pe-4">Beneficiaries</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($sponsorAllocation as $allocation)
                                    <tr>
                                        <td class="ps-4">{{ $allocation['sponsor'] }}</td>
                                        <td>{{ $allocation['programs'] }}</td>
                                        <td class="text-end fw-semibold">{{ $allocation['beneficiaries'] }}</td>
                                </tr>@empty<tr>
                                        <td colspan="3" class="text-center py-4 accounting-empty-cell">
                                            <div class="accounting-original-copy">
                                            <i class="bi bi-folder-x display-6 text-muted opacity-50 mb-2 d-block"></i>
                                            <span class="text-muted">No eligible, FASSG-forwarded beneficiaries to summarize.</span>
                                            </div>
                                            <div class="accounting-empty accounting-desktop-copy d-none">
                                                <i class="bi bi-people" aria-hidden="true"></i>
                                                <h4>No forwarded beneficiaries yet</h4>
                                                <p>Sponsor summaries will appear once eligible beneficiaries have been forwarded by FASSG.</p>
                                            </div>
                                        </td>
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
                    <h3 class="h6 sf-heading mb-3">Program Category Breakdown</h3>
                    @forelse ($programBreakdown as $breakdown)
                        <div class="d-flex justify-content-between py-2 border-bottom accounting-category-row">
                            <span>{{ $breakdown['category'] }}</span><strong>{{ $breakdown['beneficiaries'] }}</strong>
                    </div>@empty
                        <div class="text-secondary small accounting-original-copy">No program categories with eligible, FASSG-forwarded beneficiaries.</div>
                        <div class="accounting-empty accounting-category-empty accounting-desktop-copy d-none">
                            <i class="bi bi-diagram-3" aria-hidden="true"></i>
                            <h4>No category data available</h4>
                            <p>Program category totals will appear when eligible forwarded beneficiaries are available.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
