@props(['title', 'value', 'icon', 'color' => 'emerald'])

@php
    $colors = [
        'emerald' => ['bg' => '#EEF3F7', 'text' => '#173B57'],
        'amber' => ['bg' => '#FFF7E6', 'text' => '#B88219'],
        'sky' => ['bg' => '#EEF3F7', 'text' => '#173B57'],
        'rose' => ['bg' => '#FEF2F2', 'text' => '#B91C1C'],
        'slate' => ['bg' => '#EEF3F7', 'text' => '#173B57'],
    ];
    $c = $colors[$color] ?? $colors['slate'];
@endphp

<div {{ $attributes->merge(['class' => 'sf-stat-card card border-0 shadow-sm h-100']) }}>
    <div class="card-body p-4">
        <div class="d-flex align-items-start justify-content-between gap-3 mb-2">
            <span class="sf-eyebrow d-block">{{ $title }}</span>
            <span class="sf-stat-icon" style="background-color: {{ $c['bg'] }}; color: {{ $c['text'] }};">
                <i class="bi {{ $icon }}"></i>
            </span>
        </div>
        <h3 class="sf-heading mb-0" style="font-size: 1.75rem; color: #0F2537;">{{ $value }}</h3>
    </div>
</div>