@props(['title', 'value', 'icon', 'color' => 'emerald'])

@php
    $colors = [
        'emerald' => 'bg-emerald-50 text-emerald-700',
        'amber' => 'bg-cream text-slate-900',
        'sky' => 'bg-cyan-50 text-cyan-700',
        'rose' => 'bg-red-50 text-red-700',
        'slate' => 'bg-indigo-50 text-indigo-700',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'sf-stat-card card border-0 shadow-sm h-100']) }}>
    <div class="card-body p-4 d-flex align-items-start justify-content-between gap-3">
        <div>
            <p class="sf-eyebrow mb-1 text-uppercase">{{ $title }}</p>
            <p class="fw-bold text-slate-900 mb-0" style="font-size: 1.75rem;">{{ $value }}</p>
        </div>
        <span class="sf-stat-icon {{ $colors[$color] ?? $colors['slate'] }}">
            <i class="bi {{ $icon }}"></i>
        </span>
    </div>
</div>