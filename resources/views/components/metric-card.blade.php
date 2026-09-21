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
    <div class="card-body p-4">
        <div class="d-flex align-items-start justify-content-between gap-3 mb-2">
            <span class="sf-eyebrow d-block">{{ $title }}</span>
            <span class="sf-stat-icon {{ $colors[$color] ?? $colors['slate'] }}">
                <i class="bi {{ $icon }}"></i>
            </span>
        </div>
        <h3 class="sf-heading mb-0" style="font-size: 1.75rem; color: #0F2537;">{{ $value }}</h3>
    </div>
</div>