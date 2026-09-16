@extends('layouts.app')

@section('title', 'Generated Batches')
@section('eyebrow', 'FASSG Office')
@section('page-title', 'Generated Batches')

@push('styles')
    <style>
        .badge-info-custom {
            background-color: #e0f2fe !important;
            color: #0369a1 !important;
            border: 1px solid #bae6fd !important;
        }
    </style>
@endpush

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-4 gap-3">
        <div>
            <h1 class="h3 fw-bold mb-1">Generated Batches</h1>
            <p class="text-secondary small mb-0">Batch beneficiary lists generated directly from the ranked Application Queue.
            </p>
        </div>
        <a href="{{ route('fassg.applications.index') }}" class="btn btn-primary fw-semibold d-inline-flex align-items-center gap-2 px-3">
            <i class="bi bi-inboxes me-1"></i>Open Application Queue
        </a>
    </div>

    @if ($fixedLists->isEmpty())
        <div class="card sf-card border-0 shadow-sm">
            <div class="sf-empty-state text-center p-5">
                <i class="bi bi-boxes text-secondary fs-1 d-block mb-3"></i>
                <div class="fw-semibold">No generated batches yet</div>
                <div class="small text-secondary mb-3">Select applicants from the Application Queue and create a batch list to see it here.
                </div>
                <a href="{{ route('fassg.applications.index') }}" class="btn btn-primary btn-sm px-3">
                    <i class="bi bi-inboxes me-1"></i>Open Application Queue
                </a>
            </div>
        </div>
    @else
        <div class="accordion" id="generatedBatchesAccordion">
            @foreach ($fixedLists as $list)
                <div class="card sf-card mb-3 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h2 class="h6 sf-heading mb-1 fw-bold">
                                    <a href="{{ route('fassg.generated-batches.show', $list) }}"
                                        class="text-decoration-none text-dark">
                                        {{ $list->batch_name ?: 'Generated Batch #' . $list->id . ' - ' . ($list->sponsorshipProgram->program_name ?? 'Unassigned Program') }}
                                    </a>
                                </h2>
                                <div class="small text-secondary">
                                    {{ $list->sponsorshipProgram->program_name }} · {{ $list->total_names }}
                                    {{ Str::plural('name', $list->total_names) }}
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                @php($listStatus = strtolower($list->status->value ?? (string) $list->status))
                                @if ($listStatus === 'saved')
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-2 fw-semibold d-inline-flex align-items-center gap-1">
                                        <i class="bi bi-pencil-square"></i>Reviewing
                                    </span>
                                @elseif ($listStatus === 'submitted')
                                    <span class="badge badge-info-custom px-2 py-2 fw-semibold d-inline-flex align-items-center gap-1">
                                        <i class="bi bi-send me-1"></i>Forwarded to Sponsor
                                    </span>
                                @else
                                    <span class="badge bg-light text-secondary border px-2 py-2 fw-semibold">
                                        {{ ucfirst($listStatus) }}
                                    </span>
                                @endif

                                <a href="{{ route('fassg.generated-batches.show', $list) }}" class="btn btn-outline-primary btn-sm"
                                    title="View Generated Batch" aria-label="View Generated Batch">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection