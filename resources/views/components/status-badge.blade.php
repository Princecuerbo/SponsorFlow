@props(['status'])

@php
    $label = $status instanceof \BackedEnum ? $status->value : (string) $status;
    $map = [
        // Cream / Gold — pending review
        'Pending'    => ['bg-cream text-slate-900 border border-cream-gold', 'bi-hourglass-split'],
        'Draft'      => ['bg-cream text-slate-900 border border-cream-gold', 'bi-hourglass-split'],
        'Submitted'  => ['bg-cream text-slate-900 border border-cream-gold', 'bi-hourglass-split'],
        'Under Review' => ['bg-cream text-slate-900 border border-cream-gold', 'bi-hourglass-split'],
        'Resubmission Requested' => ['bg-cream text-slate-900 border border-cream-gold', 'bi-hourglass-split'],
        'Saved'      => ['bg-cream text-slate-900 border border-cream-gold', 'bi-hourglass-split'],
        // Cyan — FASSG-verified, awaiting next step
        'Verified'   => ['bg-cyan-50 text-cyan-700 border border-cyan-200', 'bi-patch-check-fill'],
        // Emerald — final positive resolution
        'Approved'   => ['bg-emerald-50 text-emerald-700 border border-emerald-200', 'bi-check-circle-fill'],
        'Completed'  => ['bg-emerald-50 text-emerald-700 border border-emerald-200', 'bi-check-circle-fill'],
        'Active'     => ['bg-emerald-50 text-emerald-700 border border-emerald-200', 'bi-check-circle-fill'],
        'active'     => ['bg-emerald-50 text-emerald-700 border border-emerald-200', 'bi-check-circle-fill'],
        'Eligible'   => ['bg-emerald-50 text-emerald-700 border border-emerald-200', 'bi-check-circle-fill'],
        'Open'       => ['bg-emerald-50 text-emerald-700 border border-emerald-200', 'bi-check-circle-fill'],
        'Confirmed'  => ['bg-emerald-50 text-emerald-700 border border-emerald-200', 'bi-check-circle-fill'],
        // Red — rejection / closure
        'Rejected'   => ['bg-red-50 text-red-700 border border-red-200', 'bi-x-circle-fill'],
        'Declined'   => ['bg-red-50 text-red-700 border border-red-200', 'bi-x-circle-fill'],
        'Ineligible' => ['bg-red-50 text-red-700 border border-red-200', 'bi-x-circle-fill'],
        'Expired'    => ['bg-red-50 text-red-700 border border-red-200', 'bi-x-circle-fill'],
        'Closed'     => ['bg-red-50 text-red-700 border border-red-200', 'bi-x-circle-fill'],
        'Inactive'   => ['bg-red-50 text-red-700 border border-red-200', 'bi-x-circle-fill'],
        'inactive'   => ['bg-red-50 text-red-700 border border-red-200', 'bi-x-circle-fill'],
        'suspended'  => ['bg-red-50 text-red-700 border border-red-200', 'bi-x-circle-fill'],
        // Indigo — in-flight / forwarded states
        'Forwarded'   => ['bg-indigo-50 text-indigo-700 border border-indigo-200', 'bi-arrows-move'],
        'In Progress' => ['bg-indigo-50 text-indigo-700 border border-indigo-200', 'bi-arrows-move'],
        'Ongoing'     => ['bg-indigo-50 text-indigo-700 border border-indigo-200', 'bi-arrows-move'],
        'Endorsed'    => ['bg-indigo-50 text-indigo-700 border border-indigo-200', 'bi-arrows-move'],
        'SLE-FHE'     => ['bg-indigo-50 text-indigo-700 border border-indigo-200', 'bi-arrows-move'],
    ];

    [$colors, $icon] = $map[$label] ?? ['bg-secondary-subtle text-secondary border border-secondary-subtle', 'bi-question-circle'];
@endphp

<span
    {{ $attributes->merge(['class' => "badge px-2 py-1 fw-medium $colors"]) }}>
    <i class="bi {{ $icon }} me-1"></i>{{ $label }}
</span>