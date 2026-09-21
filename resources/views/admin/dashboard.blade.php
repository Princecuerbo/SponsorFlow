@extends('layouts.app')

@section('title', 'System Administration')
@section('eyebrow', 'System Administrator')
@section('page-title', 'Users & Security')

@section('content')
    <div class="row g-3 mb-4">
        @php
            $kpiCards = [
                ['title' => 'TOTAL ACCOUNTS', 'value' => $metrics['totalUsers'], 'icon' => 'bi-people', 'color' => 'slate'],
                ['title' => 'ACTIVE ACCOUNTS', 'value' => $metrics['activeUsers'], 'icon' => 'bi-person-check', 'color' => 'emerald'],
                ['title' => "TODAY'S AUDIT LOGS", 'value' => $metrics['todayLogs'], 'icon' => 'bi-shield-check', 'color' => 'sky'],
                ['title' => 'LAST BACKUP', 'value' => $metrics['lastBackup']?->status ?? 'None', 'icon' => 'bi-database-check', 'color' => 'amber'],
            ];
        @endphp

        @foreach ($kpiCards as $card)
            <div class="col-sm-6 col-xl-3">
                <x-metric-card title="{{ $card['title'] }}" value="{{ $card['value'] }}" icon="{{ $card['icon'] }}"
                    color="{{ $card['color'] }}" />
            </div>
        @endforeach
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card sf-card h-100">
                <div class="card-body p-4">
                    <h3 class="h6 sf-heading mb-3">Recent system access</h3>
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
                            <div class="text-secondary py-3">No recent access activity.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card sf-card h-100">
                <div class="card-body p-4">
                    <h3 class="h6 sf-heading mb-3">Active role access summary</h3>
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
