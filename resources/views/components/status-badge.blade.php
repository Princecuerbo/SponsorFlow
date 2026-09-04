@props(['status'])

@php
    $label = $status instanceof \BackedEnum ? $status->value : (string) $status;
    $map = [
        'Approved' => ['bg-emerald-100 text-emerald-800', 'bi-check-circle'],
        'Active' => ['bg-emerald-100 text-emerald-800', 'bi-check-circle'],
        'Verified' => ['bg-emerald-100 text-emerald-800', 'bi-patch-check'],
        'Completed' => ['bg-emerald-100 text-emerald-800', 'bi-check2-circle'],
        'Pending' => ['bg-amber-100 text-amber-800', 'bi-hourglass-split'],
        'Under Review' => ['bg-amber-100 text-amber-800', 'bi-search'],
        'Submitted' => ['bg-amber-100 text-amber-800', 'bi-send'],
        'Rejected' => ['bg-rose-100 text-rose-800', 'bi-x-circle'],
        'Declined' => ['bg-rose-100 text-rose-800', 'bi-x-circle'],
        'Inactive' => ['bg-rose-100 text-rose-800', 'bi-slash-circle'],
        'Forwarded' => ['bg-sky-100 text-sky-800', 'bi-arrow-right-circle'],
        'In Progress' => ['bg-sky-100 text-sky-800', 'bi-arrow-repeat'],
        'Open' => ['bg-emerald-50 text-emerald-700', 'bi-check-circle-fill'],
        'Closed' => ['bg-slate-100 text-slate-700', 'bi-slash-circle'],
        'Expired' => ['bg-amber-50 text-amber-700', 'bi-clock-history'],
        'Ongoing' => ['bg-emerald-100 text-emerald-800', 'bi-arrow-repeat'],
    ];

    [$colors, $icon] = $map[$label] ?? ['bg-slate-100 text-slate-700', 'bi-question-circle'];
@endphp

<span
    {{ $attributes->merge(['class' => "inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold $colors"]) }}>
    <i class="bi {{ $icon }}"></i>{{ $label }}
</span>
