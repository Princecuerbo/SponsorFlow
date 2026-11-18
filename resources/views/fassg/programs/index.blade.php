@extends('layouts.app')

@section('title', 'Sponsorship Programs')
@section('eyebrow', 'FASSG Office · Programs')
@section('page-title', 'Sponsorship Programs')
@section('subtitle', 'Manage open, closed, and expired sponsorship programs across the portal.')

@push('styles')
    @include('fassg.partials.programs-styles')
@endpush

@section('content')
    <div class="sf-programs-page">
        {{-- Filter Toolbar --}}
        <div class="sfp-toolbar no-print">
            <div class="sfp-filter-group" role="group" aria-label="Filter programs by status">
                <a href="{{ route('fassg.programs.index') }}"
                    class="sfp-filter-pill {{ !request('status') ? 'active sfp-filter-pill-active' : '' }}">
                    All
                </a>
                <a href="{{ route('fassg.programs.index', ['status' => \App\Enums\ProgramStatus::Open->value]) }}"
                    class="sfp-filter-pill {{ request('status') === \App\Enums\ProgramStatus::Open->value ? 'active sfp-filter-pill-active' : '' }}">
                    Open
                </a>
                <a href="{{ route('fassg.programs.index', ['status' => \App\Enums\ProgramStatus::Closed->value]) }}"
                    class="sfp-filter-pill {{ request('status') === \App\Enums\ProgramStatus::Closed->value ? 'active sfp-filter-pill-active' : '' }}">
                    Closed
                </a>
                <a href="{{ route('fassg.programs.index', ['status' => \App\Enums\ProgramStatus::Expired->value]) }}"
                    class="sfp-filter-pill {{ request('status') === \App\Enums\ProgramStatus::Expired->value ? 'active sfp-filter-pill-active' : '' }}">
                    Expired
                </a>
            </div>
            <a href="{{ route('fassg.programs.create') }}"
                class="btn btn-sf-navy sfp-btn-create">
                <i class="bi bi-plus-lg"></i><span>Create Program</span>
            </a>
        </div>

        @if ($programs->isEmpty())
            <div class="card sf-card sfp-table-card">
                <div class="sfp-empty-box">
                    <i class="bi bi-briefcase"></i>
                    <div class="sfp-empty-title">No sponsorship programs yet</div>
                    <div class="sfp-empty-text">Create your first program to start accepting student applications.</div>
                    <a href="{{ route('fassg.programs.create') }}" class="btn btn-sf-navy btn-sm px-3 sfp-btn-create">Create Program</a>
                </div>
            </div>
        @else
            <div class="card sf-card sfp-table-card">
                <div class="table-responsive">
                    <table class="table sfp-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="sfp-col-program">Program</th>
                                <th>Sponsor</th>
                                <th>Category</th>
                                <th>Slots</th>
                                <th>Min. GPA</th>
                                <th>Target Location</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($programs as $program)
                                <tr>
                                    <td class="sfp-col-program">
                                        <div class="sfp-program-name">{{ $program->program_name }}</div>
                                        @if ($program->requires_relative_verification)
                                            <span class="sfp-relative-badge">
                                                <i class="bi bi-person-badge"></i>Relative worker verification
                                            </span>
                                        @endif
                                    </td>
                                    <td class="sfp-sponsor-name">{{ $program->sponsor->company_organization_name }}</td>
                                    <td>
                                        <span class="sfp-category-badge">
                                            {{ $program->category?->value ?? $program->category }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="sfp-slots-avail">{{ $program->available_slots }}</span>
                                        <span class="sfp-slots-slash">/</span>
                                        <span class="sfp-slots-total">{{ $program->total_slots }}</span>
                                    </td>
                                    <td>
                                        @if ($program->min_gpa)
                                            <span class="sfp-gpa-val">{{ number_format($program->min_gpa, 2) }}</span>
                                        @else
                                            <span class="sfp-gpa-empty">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($program->target_municipality && $program->target_province)
                                            <span class="sfp-location-val">{{ $program->target_municipality }}</span>,
                                            <span class="sfp-location-val">{{ $program->target_province }}</span>
                                        @elseif ($program->target_province)
                                            <span class="sfp-location-val">{{ $program->target_province }}</span>
                                        @elseif ($program->target_municipality)
                                            <span class="sfp-location-val">{{ $program->target_municipality }}</span>
                                        @else
                                            <span class="sfp-location-empty">All / No Preference</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $status = $program->status?->value ?? $program->status;
                                            $statusLower = strtolower($program->effective_status->value);
                                        @endphp
                                        <x-status-badge :status="$program->effective_status" />
                                    </td>
                                    <td>
                                        <div class="sfp-actions-group">
                                            @if ($statusLower === 'open')
                                                <form method="POST" action="{{ route('fassg.programs.toggle-status', $program) }}"
                                                    class="d-inline">
                                                    @csrf @method('PATCH')
                                                    <button type="submit" class="btn btn-sm btn-outline-warning sfp-action-btn"
                                                        data-bs-toggle="tooltip" title="Close this program">
                                                        <i class="bi bi-lock"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ route('fassg.programs.reopen', $program) }}"
                                                    class="d-inline">
                                                    @csrf @method('PATCH')
                                                    <button type="submit" class="btn btn-sm btn-outline-success sfp-action-btn"
                                                        data-bs-toggle="tooltip" title="Reopen this program">
                                                        <i class="bi bi-unlock"></i>
                                                    </button>
                                                </form>
                                            @endif
                                            @if ($statusLower !== 'expired')
                                                <form method="POST" action="{{ route('fassg.programs.expire', $program) }}"
                                                    class="d-inline">
                                                    @csrf @method('PATCH')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger sfp-action-btn"
                                                        data-bs-toggle="tooltip" title="Expire Program &amp; Release Beneficiaries"
                                                        onclick="return confirm('Expiring this program will mark all active student grants under this program as expired, allowing enrolled students to apply for new active programs. Proceed?')">
                                                        <i class="bi bi-clock-history"></i>
                                                    </button>
                                                </form>
                                            @endif
                                            <a href="{{ route('fassg.programs.edit', $program) }}"
                                                class="btn btn-sm btn-outline-primary sfp-action-btn sfp-action-btn-edit" data-bs-toggle="tooltip"
                                                title="Edit program criteria">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
@endsection
