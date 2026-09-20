@extends('layouts.app')

@section('title', 'Sponsorship Programs')
@section('eyebrow', 'FASSG Office')
@section('page-title', 'Sponsorship Programs')

@push('styles')
    <style>
        /* Active Filter Pill Tab Accent */
        .sf-filter-pill-active {
            background-color: #0F2942 !important;
            border-color: #0F2942 !important;
            color: #ffffff !important;
        }
    </style>
@endpush

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div>
            <span class="sf-eyebrow d-block mb-1">FASSG Office · Programs</span>
            <h2 class="h2 sf-heading mb-1 fw-bold">Sponsorship Programs</h2>
            <p class="text-secondary mb-0">Manage open, closed, and expired sponsorship programs across the portal.</p>
        </div>
    </div>

    <div class="d-flex align-items-center justify-content-between mb-4 gap-3">
        <div class="d-flex align-items-center gap-2 p-1 bg-light rounded-3 border no-print" role="group">
            <a href="{{ route('fassg.programs.index') }}"
                class="btn btn-sm fw-semibold px-3 py-1 rounded-2 {{ !request('status') ? 'sf-filter-pill-active shadow-sm' : 'text-secondary btn-link text-decoration-none' }}">
                All
            </a>
            <a href="{{ route('fassg.programs.index', ['status' => \App\Enums\ProgramStatus::Open->value]) }}"
                class="btn btn-sm fw-semibold px-3 py-1 rounded-2 {{ request('status') === \App\Enums\ProgramStatus::Open->value ? 'sf-filter-pill-active shadow-sm' : 'text-secondary btn-link text-decoration-none' }}">
                Open
            </a>
            <a href="{{ route('fassg.programs.index', ['status' => \App\Enums\ProgramStatus::Closed->value]) }}"
                class="btn btn-sm fw-semibold px-3 py-1 rounded-2 {{ request('status') === \App\Enums\ProgramStatus::Closed->value ? 'sf-filter-pill-active shadow-sm' : 'text-secondary btn-link text-decoration-none' }}">
                Closed
            </a>
            <a href="{{ route('fassg.programs.index', ['status' => \App\Enums\ProgramStatus::Expired->value]) }}"
                class="btn btn-sm fw-semibold px-3 py-1 rounded-2 {{ request('status') === \App\Enums\ProgramStatus::Expired->value ? 'sf-filter-pill-active shadow-sm' : 'text-secondary btn-link text-decoration-none' }}">
                Expired
            </a>
        </div>
        <a href="{{ route('fassg.programs.create') }}"
            class="btn btn-sf-navy fw-semibold d-inline-flex align-items-center gap-2 px-3">
            <i class="bi bi-plus-lg"></i>Create Program
        </a>
    </div>

    @if ($programs->isEmpty())
        <div class="card sf-card">
            <div class="sf-empty-state">
                <i class="bi bi-briefcase"></i>
                <div class="fw-semibold">No sponsorship programs yet</div>
                <div class="small mb-3">Create your first program to start accepting student applications.</div>
                <a href="{{ route('fassg.programs.create') }}" class="btn btn-sf-navy btn-sm px-3">Create Program</a>
            </div>
        </div>
    @else
        <div class="card sf-card">
            <div class="table-responsive">
                <table class="table sf-table mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Program</th>
                            <th>Sponsor</th>
                            <th>Category</th>
                            <th>Slots</th>
                            <th>Min. GPA</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($programs as $program)
                            <tr>
                                <td class="ps-4 fw-semibold">
                                    <div>{{ $program->program_name }}</div>
                                    @if ($program->requires_relative_verification)
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle mt-1"
                                            style="font-weight: 500;">
                                            <i class="bi bi-person-badge me-1"></i>Relative worker verification
                                        </span>
                                    @endif
                                </td>
                                <td class="text-secondary">{{ $program->sponsor->company_organization_name }}</td>
                                <td>
                                    <span class="badge rounded-2 px-2 py-1 fw-medium"
                                        style="background-color: rgba(15, 41, 66, 0.08) !important; color: #0F2942 !important;">
                                        {{ $program->category?->value ?? $program->category }}
                                    </span>
                                </td>
                                <td>
                                    <span class="fw-semibold">{{ $program->available_slots }}</span>
                                    <span class="text-secondary">/</span>
                                    <span class="text-secondary">{{ $program->total_slots }}</span>
                                </td>
                                <td>{{ $program->min_gpa ? number_format($program->min_gpa, 2) : '—' }}</td>
                                <td>
                                    @php
                                        $status = $program->status?->value ?? $program->status;
                                        $statusLower = strtolower($program->effective_status->value);
                                    @endphp
                                    <x-status-badge :status="$program->effective_status" />
                                </td>
                                <td class="text-end pe-4">
                                    @if ($statusLower === 'open')
                                        <form method="POST" action="{{ route('fassg.programs.toggle-status', $program) }}"
                                            class="d-inline">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-outline-warning"
                                                data-bs-toggle="tooltip" title="Close this program">
                                                <i class="bi bi-lock"></i>
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('fassg.programs.reopen', $program) }}"
                                            class="d-inline">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-outline-success"
                                                data-bs-toggle="tooltip" title="Reopen this program">
                                                <i class="bi bi-unlock"></i>
                                            </button>
                                        </form>
                                    @endif
                                    @if ($statusLower !== 'expired')
                                        <form method="POST" action="{{ route('fassg.programs.expire', $program) }}"
                                            class="d-inline">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                                data-bs-toggle="tooltip" title="Expire Program &amp; Release Beneficiaries"
                                                onclick="return confirm('Expiring this program will mark all active student grants under this program as expired, allowing enrolled students to apply for new active programs. Proceed?')">
                                                <i class="bi bi-clock-history"></i>
                                            </button>
                                        </form>
                                    @endif
                                    <a href="{{ route('fassg.programs.edit', $program) }}"
                                        class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip"
                                        title="Edit program criteria">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

@endsection
