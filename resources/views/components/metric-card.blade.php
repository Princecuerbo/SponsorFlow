@props(['title', 'value', 'icon', 'color' => 'emerald'])

@php
    $colors = [
        'emerald' => 'bg-emerald-100 text-emerald-700',
        'amber' => 'bg-amber-100 text-amber-700',
        'sky' => 'bg-sky-100 text-sky-700',
        'rose' => 'bg-rose-100 text-rose-700',
        'slate' => 'bg-slate-100 text-slate-700',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-slate-200 bg-white p-5 shadow-sm']) }}>
    <div class="flex items-start justify-between gap-4">
        <div>
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $title }}</p>
            <p class="text-3xl font-bold text-slate-900">{{ $value }}</p>
        </div>
        <span
            class="inline-flex h-11 w-11 items-center justify-center rounded-xl {{ $colors[$color] ?? $colors['slate'] }}">
            <i class="bi {{ $icon }} text-lg"></i>
        </span>
    </div>
</div>
