@extends('layouts.app')

@section('title', 'Sponsorship Programs')
@section('eyebrow', 'Sponsor Portal · Programs')
@section('page-title', 'Connected Programs')
@section('subtitle', 'Programs created for your organization and their current review activity.')

@section('content')
    @if ($programs->isEmpty())
        <div class="card sf-card border-0 shadow-sm">
            <div class="sf-empty-state text-center p-5">
                <i class="bi bi-briefcase text-secondary fs-1 d-block mb-3"></i>
                <div class="fw-semibold">No connected programs</div>
                <div class="small text-secondary">Programs assigned to your organization will appear here.</div>
            </div>
        </div>
    @else
        <div class="card sf-card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table sf-table mb-0 align-middle">
                    <thead>
                        <tr>
                            <th class="ps-4">Program</th>
                            <th>Category</th>
                            <th>Slots</th>
                            <th>Guidelines</th>
                            <th>Status</th>
                            <th>Applications</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($programs as $program)
                            <tr>
                                <td class="ps-4 fw-semibold">{{ $program->program_name }}</td>
                                <td>{{ $program->category->value }}</td>
                                <td>{{ $program->available_slots }}</td>
                                <td class="text-secondary">
                                    {{ $program->address_requirement ?: 'No additional address requirement' }}
                                    @if ($program->target_course)
                                        <div class="small">Academic Program: {{ $program->target_course }}</div>
                                    @endif
                                </td>
                                <td><x-status-badge :status="$program->status" /></td>
                                <td>{{ $program->applications_count }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
