@extends('layouts.app')

@section('title', 'Generated Batches')
@section('eyebrow', 'FASSG Office')
@section('page-title', 'Generated Batches')
@section('subtitle', 'Manually selected batches, Sponsor decisions, FASSG receipt, and Accounting forwarding.')

@push('styles')
    @include('fassg.partials.generated-batches-styles')
@endpush

@section('content')
<div class="sf-batches-page">
    {{-- Filter Bar --}}
    <div class="card sf-batches-filter-card mb-4 border-0 shadow-sm">
        <form method="GET" action="{{ url()->current() }}" class="row g-3 align-items-end">
            <div class="col-md-6 col-lg-5">
                <label class="form-label" for="sponsorship_program_id">Filter by Program</label>
                <select name="sponsorship_program_id" id="sponsorship_program_id" class="form-select" onchange="this.form.submit()">
                    <option value="">All Programs</option>
                    @foreach ($programs as $prog)
                        <option value="{{ $prog->id }}" @selected((int) request('sponsorship_program_id', $selectedProgramId ?? 0) === $prog->id)>
                            {{ $prog->program_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <a href="{{ url()->current() }}"
                    class="btn btn-reset d-inline-flex align-items-center">
                    <i class="bi bi-arrow-counterclockwise"></i>
                    <span>Reset Filters</span>
                </a>
            </div>
        </form>
    </div>

    @if ($fixedLists->isEmpty())
        <div class="card sf-card border-0 shadow-sm">
            <div class="sf-batch-empty-card">
                <div class="sf-batch-empty-icon">
                    <i class="bi bi-boxes"></i>
                </div>
                <div class="sf-batch-empty-title">No generated batches yet</div>
                <p class="sf-batch-empty-text">Select applicants from the Application Queue and create a batch list to see it here.</p>
            </div>
        </div>
    @else
        <div class="sf-batches-list">
            @foreach ($fixedLists as $list)
                @php
                    $progName = $list->sponsorshipProgram->program_name ?? 'Unassigned Program';
                    $batchTitle = $list->batch_name ?: ('Generated Batch #' . $list->id . ' - ' . $progName);
                    $count = $list->items_count ?? $list->total_slots;
                    $candidateLabel = $count . ' ' . \Illuminate\Support\Str::plural('candidate', $count);

                    // If batch title is identical to program name, show Batch #ID instead of repeating program name
                    $isDuplicate = (strcasecmp(trim($batchTitle), trim($progName)) === 0);
                    $metaSubtitle = $isDuplicate ? ('Batch #' . $list->id) : $progName;

                    $listStatus = $list->status->value ?? (string) $list->status;
                @endphp
                <div class="card sf-batch-card">
                    <div class="card-body sf-batch-card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                        <div>
                            <h3 class="h6 sf-heading mb-1 fw-bold">
                                <a href="{{ route('fassg.generated-batches.show', $list) }}"
                                    class="sf-batch-title">
                                    {{ $batchTitle }}
                                </a>
                            </h3>
                            <div class="sf-batch-meta">
                                <span>{{ $metaSubtitle }}</span>
                                <span class="sf-batch-meta-dot">·</span>
                                <span><i class="bi bi-people me-1"></i>{{ $candidateLabel }}</span>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <x-status-badge :status="$listStatus" class="sf-batch-status-badge sf-batch-status-{{ strtolower($listStatus) }}" />

                            <a href="{{ route('fassg.generated-batches.show', $list) }}" class="sf-batch-btn-view"
                                title="View Generated Batch" aria-label="View Generated Batch">
                                <span>View Batch</span>
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
