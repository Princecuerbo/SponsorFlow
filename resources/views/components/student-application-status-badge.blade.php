@props(['status'])

@php
    $label = $status instanceof \BackedEnum ? $status->value : (string) $status;

    $tones = [
        'Pending' => ['bg' => '#FFF8EA', 'color' => '#986515', 'border' => '#F6DDA1', 'icon' => 'bi-hourglass-split', 'description' => 'Awaiting FASSG review'],
        'Draft' => ['bg' => '#FFF8EA', 'color' => '#986515', 'border' => '#F6DDA1', 'icon' => 'bi-hourglass-split', 'description' => 'Not yet submitted'],
        'Submitted' => ['bg' => '#FFF8EA', 'color' => '#986515', 'border' => '#F6DDA1', 'icon' => 'bi-hourglass-split', 'description' => 'Received, awaiting review'],
        'Under Review' => ['bg' => '#FFF8EA', 'color' => '#986515', 'border' => '#F6DDA1', 'icon' => 'bi-hourglass-split', 'description' => 'Under FASSG review'],
        'Resubmission Requested' => ['bg' => '#FFF8EA', 'color' => '#986515', 'border' => '#F6DDA1', 'icon' => 'bi-hourglass-split', 'description' => 'Additional documents requested'],
        'Approved' => ['bg' => '#ECFDF5', 'color' => '#047857', 'border' => '#A7F3D0', 'icon' => 'bi-check-circle', 'description' => 'Sponsor approval recorded'],
        'Ongoing' => ['bg' => '#ECFDF5', 'color' => '#047857', 'border' => '#A7F3D0', 'icon' => 'bi-check-circle', 'description' => 'Sponsorship in progress'],
        'Confirmed' => ['bg' => '#ECFDF5', 'color' => '#047857', 'border' => '#A7F3D0', 'icon' => 'bi-check-circle', 'description' => 'Sponsorship confirmed'],
        'Completed' => ['bg' => '#ECFDF5', 'color' => '#047857', 'border' => '#A7F3D0', 'icon' => 'bi-check-circle', 'description' => 'Completed'],
        'Rejected' => ['bg' => '#FEF2F2', 'color' => '#B91C1C', 'border' => '#FECACA', 'icon' => 'bi-x-circle', 'description' => 'Not approved'],
        'Declined' => ['bg' => '#FEF2F2', 'color' => '#B91C1C', 'border' => '#FECACA', 'icon' => 'bi-x-circle', 'description' => 'Not approved'],
        'Expired' => ['bg' => '#FEF2F2', 'color' => '#B91C1C', 'border' => '#FECACA', 'icon' => 'bi-clock', 'description' => 'Application cycle closed'],
    ];
@endphp

@if (isset($tones[$label]))
    <span class="badge rounded-pill d-inline-flex align-items-center gap-1 fw-semibold"
        style="font-size: 0.75rem; padding: 0.25rem 0.65rem; background-color: {{ $tones[$label]['bg'] }}; color: {{ $tones[$label]['color'] }}; border: 1px solid {{ $tones[$label]['border'] }};"
        title="Application status: {{ $label }} — {{ $tones[$label]['description'] }}">
        <i class="bi {{ $tones[$label]['icon'] }}" aria-hidden="true"></i>{{ $label }}
        <span class="visually-hidden"> — {{ $tones[$label]['description'] }}</span>
    </span>
@else
    <x-status-badge :status="$status" />
@endif
