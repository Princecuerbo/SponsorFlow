@extends('layouts.app')

@section('title', 'System Administration')
@section('eyebrow')
    <span class="admin-original-copy">System Administrator</span>
    <span class="admin-desktop-copy d-none">System Administrator · Overview</span>
@endsection
@section('page-title')
    <span class="admin-original-copy">Users & Security</span>
    <span class="admin-desktop-copy d-none">Administration Overview</span>
@endsection
@section('subtitle')
    <span class="admin-desktop-copy d-none">Monitor user accounts, security activity, and database backups.</span>
@endsection

@push('styles')
    @include('admin.partials.desktop-styles')
@endpush

@include('admin.partials.audit-action-helpers')

@section('content')
    {{-- Mobile metric cards --}}
    <div class="row g-3 mb-4 admin-original-copy">
        @php
            $kpiCards = [
                ['title' => 'TOTAL ACCOUNTS', 'value' => $metrics['totalUsers'], 'icon' => 'bi-people', 'color' => 'slate'],
                ['title' => 'ACTIVE ACCOUNTS', 'value' => $metrics['activeUsers'], 'icon' => 'bi-person-check', 'color' => 'emerald'],
                ['title' => "TODAY'S AUDIT LOGS", 'value' => $metrics['todayLogs'], 'icon' => 'bi-shield-check', 'color' => 'sky'],
                ['title' => 'LAST COMPLETED BACKUP', 'value' => $metrics['lastBackup']?->created_at?->format('M d, Y h:i A') ?? 'None recorded', 'icon' => 'bi-database-check', 'color' => 'amber'],
            ];
        @endphp

        @foreach ($kpiCards as $card)
            <div class="col-sm-6 col-xl-3">
                <x-metric-card :title="$card['title']" :value="$card['value']" :icon="$card['icon']"
                    :color="$card['color']" />
            </div>
        @endforeach
    </div>

    {{-- Desktop metric cards --}}
    <div class="admin-desktop-metrics d-none">
        {{-- 1. Total Accounts --}}
        <div class="admin-metric">
            <span class="admin-metric-label">Total Accounts</span>
            <span class="admin-metric-icon admin-icon-navy"><i class="bi bi-people" aria-hidden="true"></i></span>
            <strong class="admin-metric-value">{{ number_format($metrics['totalUsers']) }}</strong>
        </div>

        {{-- 2. Active Accounts --}}
        <div class="admin-metric">
            <span class="admin-metric-label">Active Accounts</span>
            <span class="admin-metric-icon admin-icon-emerald"><i class="bi bi-person-check" aria-hidden="true"></i></span>
            <strong class="admin-metric-value">{{ number_format($metrics['activeUsers']) }}</strong>
        </div>

        {{-- 3. Today's Audit Logs --}}
        <div class="admin-metric">
            <span class="admin-metric-label">Today's Audit Logs</span>
            <span class="admin-metric-icon admin-icon-sky"><i class="bi bi-clipboard-data" aria-hidden="true"></i></span>
            <strong class="admin-metric-value">{{ number_format($metrics['todayLogs']) }}</strong>
        </div>

        {{-- 4. Last Completed Backup --}}
        <div class="admin-metric">
            <span class="admin-metric-label">Last Completed Backup</span>
            <span class="admin-metric-icon admin-icon-amber"><i class="bi bi-database-check" aria-hidden="true"></i></span>
            @if ($metrics['lastBackup'])
                <strong class="admin-metric-value" style="font-size: 16px; font-weight: 700; line-height: 1.35; padding-top: 4px;">
                    {{ $metrics['lastBackup']->created_at?->format('M d, Y · h:i A') }}
                </strong>
            @else
                <span class="admin-metric-empty">No backup recorded</span>
            @endif
        </div>
    </div>

    {{-- Content Panels --}}
    <div class="row g-4">
        {{-- Recent System Activity --}}
        <div class="col-lg-7">
            {{-- Mobile presentation --}}
            <div class="card sf-card h-100 admin-original-copy">
                <div class="card-body p-4">
                    <h3 class="h6 sf-heading mb-3">Recent system activity</h3>
                    <div class="list-group list-group-flush">
                        @forelse($recentLogs as $log)
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between">
                                    <strong class="small">{{ $log->action }}</strong>
                                    <span class="small" style="color: #475569;">{{ $log->created_at?->format('M d, Y, h:i A') }}</span>
                                </div>
                                <div class="small" style="color: #475569;">{{ $log->user?->name ?? 'System' }} · {{ $log->target_module }} · {{ $log->ip_address ?? '—' }}</div>
                            </div>
                        @empty
                            <div class="text-secondary py-3">No recent system activity.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Desktop presentation --}}
            <div class="admin-card h-100 admin-desktop-copy d-none">
                <div class="p-4 border-bottom bg-white">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h3 class="admin-section-title mb-1">Recent System Activity</h3>
                            <p class="admin-section-desc">Audit events, authentications, and administrative actions.</p>
                        </div>
                        <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-sm btn-outline-sf-navy px-3 py-1.5" style="font-size: 12px; border-radius: 6px;">
                            <i class="bi bi-journal-text me-1"></i>View All Logs
                        </a>
                    </div>
                </div>
                <div class="p-0">
                    @forelse($recentLogs as $log)
                        @php $meta = resolveAuditActionMeta($log->action); @endphp
                        <div class="admin-activity-item">
                            <span class="admin-activity-icon">
                                <i class="bi {{ $meta['icon'] }}" aria-hidden="true"></i>
                            </span>
                            <div class="admin-activity-content">
                                <div class="admin-activity-title">{{ $meta['label'] }}</div>
                                <div class="admin-activity-meta">
                                    <span class="fw-medium text-dark">{{ $log->user?->name ?? 'System' }}</span>
                                    <span class="text-secondary">·</span>
                                    <span class="badge bg-light text-secondary border px-2 py-0.5" style="font-size: 11px;">{{ $log->target_module }}</span>
                                    <span class="text-secondary">·</span>
                                    <span class="font-monospace text-muted" style="font-size: 11.5px;">{{ $log->ip_address ?? '—' }}</span>
                                    <span class="text-secondary">·</span>
                                    <code class="text-muted" style="font-size: 11px; background: rgba(15, 41, 66, 0.05); padding: 1px 4px; border-radius: 4px;">{{ $log->action }}</code>
                                </div>
                            </div>
                            <div class="admin-activity-time">
                                {{ $log->created_at?->format('M d, Y · h:i A') }}
                            </div>
                        </div>
                    @empty
                        <div class="admin-empty-state">
                            <span class="admin-empty-icon"><i class="bi bi-journal-text"></i></span>
                            <h4 class="admin-empty-title">No Recent Activity</h4>
                            <p class="admin-empty-desc">System activity and audit events will appear here once recorded.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Active Accounts by Role --}}
        <div class="col-lg-5">
            {{-- Mobile presentation --}}
            <div class="card sf-card h-100 admin-original-copy">
                <div class="card-body p-4">
                    <h3 class="h6 sf-heading mb-3">Active accounts by role</h3>
                    @foreach (\App\Enums\UserRole::cases() as $role)
                        <div class="d-flex justify-content-between border-bottom py-2">
                            <span>{{ $role->label() }}</span>
                            <strong>{{ $roleCounts[$role->value] ?? 0 }}</strong>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Desktop presentation --}}
            <div class="admin-card h-100 admin-desktop-copy d-none">
                <div class="p-4 border-bottom bg-white">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h3 class="admin-section-title mb-1">Active Accounts by Role</h3>
                            <p class="admin-section-desc">Active users categorized by system portal permissions.</p>
                        </div>
                        <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-outline-sf-navy px-3 py-1.5" style="font-size: 12px; border-radius: 6px;">
                            <i class="bi bi-people me-1"></i>Manage Users
                        </a>
                    </div>
                </div>
                <div class="p-0">
                    @php
                        $roleIcons = [
                            'student' => 'bi-mortarboard',
                            'fassg' => 'bi-award',
                            'sponsor' => 'bi-briefcase',
                            'accounting' => 'bi-calculator',
                            'admin' => 'bi-shield-lock',
                        ];
                    @endphp
                    @foreach (\App\Enums\UserRole::cases() as $role)
                        <div class="admin-role-row">
                            <span class="admin-role-label">
                                <i class="bi {{ $roleIcons[$role->value] ?? 'bi-person' }} text-secondary" style="font-size: 15px;" aria-hidden="true"></i>
                                {{ $role->label() }}
                            </span>
                            <span class="admin-role-count">
                                {{ number_format($roleCounts[$role->value] ?? 0) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection
