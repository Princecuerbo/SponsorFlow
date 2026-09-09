@props(['status'])

@php
    $label = $status instanceof \BackedEnum ? $status->value : (string) $status;
    $map = [
        // Emerald / Green — final positive resolution
        'Approved'  => ['bg-emerald-50 text-emerald-700 border border-emerald-200', 'bi-check-circle'],
        'Active'    => ['bg-success-subtle text-success border border-success-subtle', 'bi-check-circle'],
        // Cyan / Sky — FASSG-verified, awaiting next step
        'Verified'  => ['bg-cyan-50 text-cyan-700 border border-cyan-200', 'bi-patch-check'],
        'Completed' => ['bg-success-subtle text-success border border-success-subtle', 'bi-check2-circle'],
        'Pending'   => ['bg-warning-subtle text-warning-emphasis border border-warning-subtle', 'bi-hourglass-split'],
        'Under Review' => ['bg-warning-subtle text-warning-emphasis border border-warning-subtle', 'bi-search'],
        'Submitted' => ['bg-warning-subtle text-warning-emphasis border border-warning-subtle', 'bi-send'],
        'Draft'     => ['bg-warning-subtle text-warning-emphasis border border-warning-subtle', 'bi-pencil'],
        'Eligible'  => ['bg-success-subtle text-success border border-success-subtle', 'bi-patch-check'],
        'Ineligible' => ['bg-danger-subtle text-danger border border-danger-subtle', 'bi-x-circle'],
        'Rejected'  => ['bg-danger-subtle text-danger border border-danger-subtle', 'bi-x-circle'],
        'Declined'  => ['bg-danger-subtle text-danger border border-danger-subtle', 'bi-x-circle'],
        'Inactive'  => ['bg-danger-subtle text-danger border border-danger-subtle', 'bi-slash-circle'],
        'Forwarded' => ['bg-info-subtle text-info-emphasis border border-info-subtle', 'bi-arrow-right-circle'],
        'In Progress' => ['bg-info-subtle text-info-emphasis border border-info-subtle', 'bi-arrow-repeat'],
        'Open'      => ['bg-success-subtle text-success border border-success-subtle', 'bi-check-circle-fill'],
        'Closed'    => ['bg-secondary-subtle text-secondary border border-secondary-subtle', 'bi-slash-circle'],
        'Expired'   => ['bg-danger-subtle text-danger border border-danger-subtle', 'bi-clock-history'],
        'Ongoing'   => ['bg-success-subtle text-success border border-success-subtle', 'bi-arrow-repeat'],
        'Resubmission Requested' => ['bg-warning-subtle text-warning-emphasis border border-warning-subtle', 'bi-arrow-counterclockwise'],
    ];

    [$colors, $icon] = $map[$label] ?? ['bg-secondary-subtle text-secondary border border-secondary-subtle', 'bi-question-circle'];
@endphp

<span
    {{ $attributes->merge(['class' => "badge px-2 py-1 fw-medium $colors"]) }}>
    <i class="bi {{ $icon }} me-1"></i>{{ $label }}
</span>
