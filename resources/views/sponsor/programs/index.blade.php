@extends('layouts.app')

@section('title', 'Sponsorship Programs')
@section('eyebrow', 'Sponsor Portal')
@section('page-title', 'Connected Programs')

@section('content')
    <div class="mb-4"><p class="text-uppercase small fw-semibold text-success mb-2">Sponsor programs</p><h1 class="h2 sf-heading mb-1">Connected Programs</h1><p class="text-secondary mb-0">Programs created for your organization and their current review activity.</p></div>
    @if ($programs->isEmpty())
        <div class="card sf-card"><div class="sf-empty-state"><i class="bi bi-briefcase"></i><div class="fw-semibold">No connected programs</div><div class="small">Programs assigned to your organization will appear here.</div></div></div>
    @else
        <div class="card sf-card"><div class="table-responsive"><table class="table sf-table mb-0"><thead><tr><th class="ps-4">Program</th><th>Category</th><th>Slots</th><th>Guidelines</th><th>Status</th><th>Applications</th></tr></thead><tbody>@foreach ($programs as $program)<tr><td class="ps-4 fw-semibold">{{ $program->program_name }}</td><td>{{ $program->category->value }}</td><td>{{ $program->available_slots }}</td><td class="text-secondary">{{ $program->address_requirement ?: 'No additional address requirement' }}@if ($program->target_course)<div class="small">Course: {{ $program->target_course }}</div>@endif</td><td><x-status-badge :status="$program->status" /></td><td>{{ $program->applications_count }}</td></tr>@endforeach</tbody></table></div></div>
    @endif
@endsection
