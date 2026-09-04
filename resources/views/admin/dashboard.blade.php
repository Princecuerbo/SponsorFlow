@extends('layouts.app')

@section('title', 'System Administration')
@section('eyebrow', 'System Administrator')
@section('page-title', 'Users & Security')

@section('content')
    <div class="row g-3 mb-4">
        @php
            $kpiCards = [
                ['label' => 'TOTAL ACCOUNTS', 'value' => $metrics['totalUsers'], 'icon' => 'bi-people', 'color' => '#0f294a', 'bg' => '#e0e7ff'],
                ['label' => 'ACTIVE ACCOUNTS', 'value' => $metrics['activeUsers'], 'icon' => 'bi-person-check', 'color' => '#047857', 'bg' => '#d1fae5'],
                ['label' => "TODAY'S AUDIT LOGS", 'value' => $metrics['todayLogs'], 'icon' => 'bi-shield-check', 'color' => '#7c3aed', 'bg' => '#ede9fe'],
                ['label' => 'LAST BACKUP', 'value' => $metrics['lastBackup']?->status ?? 'None', 'icon' => 'bi-database-check', 'color' => '#b45309', 'bg' => '#fef3c7'],
            ];
        @endphp

        @foreach ($kpiCards as $card)
            <div class="col-sm-6 col-xl-3">
                <div class="sf-stat-card p-4 h-100" style="border-radius: 14px;">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="sf-eyebrow" style="font-size: 0.7rem; color: #64748b;">{{ $card['label'] }}</div>
                        <div style="width: 40px; height: 40px; border-radius: 10px; background: {{ $card['bg'] }}; display: flex; align-items: center; justify-content: center;">
                            <i class="bi {{ $card['icon'] }}" style="font-size: 1.1rem; color: {{ $card['color'] }};"></i>
                        </div>
                    </div>
                    <div style="font-size: 1.75rem; font-weight: 700; color: #0f172a; line-height: 1;">{{ $card['value'] }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card sf-card h-100">
                <div class="card-body p-4">
                    <h2 class="h6 sf-heading mb-3">Recent system access</h2>
                    <div class="list-group list-group-flush">
                        @forelse($recentLogs as $log)
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between">
                                    <strong class="small">{{ $log->action }}</strong>
                                    <span class="small" style="color: #475569;">{{ $log->created_at?->diffForHumans() }}</span>
                                </div>
                                <div class="small" style="color: #475569;">{{ $log->user?->name ?? 'System' }} · {{ $log->target_module }} · {{ $log->ip_address ?? '—' }}</div>
                            </div>
                        @empty
                            <div class="text-secondary py-3">No recent access activity.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card sf-card h-100">
                <div class="card-body p-4">
                    <h2 class="h6 sf-heading mb-3">Active role access summary</h2>
                    @foreach (\App\Enums\UserRole::cases() as $role)
                        <div class="d-flex justify-content-between border-bottom py-2">
                            <span>{{ $role->label() }}</span>
                            <strong>{{ $roleCounts[$role->value] ?? 0 }}</strong>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection
