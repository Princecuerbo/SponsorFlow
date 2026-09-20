@extends('layouts.app')

@section('title', 'Generated Batches')
@section('eyebrow', 'FASSG Office')
@section('page-title', 'Generated Batches')

@push('styles')
@endpush

@section('content')
    <div class="mb-4">
        <h1 class="h3 fw-bold mb-1">Generated Batches</h1>
        <p class="text-secondary small mb-0">Batch beneficiary lists generated directly from the ranked Application Queue.</p>
    </div>

    {{-- Filter Bar --}}
    <div class="card filter-card mb-4 rounded-3 border-0 shadow-sm">
        <div class="card-body p-3">
            <form method="GET" action="{{ url()->current() }}" class="row g-2 align-items-end">
                <div class="col-md-5 col-lg-4">
                    <label class="form-label small text-secondary fw-semibold mb-1" for="sponsorship_program_id">Filter by Program</label>
                    <select name="sponsorship_program_id" id="sponsorship_program_id" class="form-select form-select-sm" onchange="this.form.submit()">
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
                        class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
                        Reset Filters
                    </a>
                </div>
            </form>
        </div>
    </div>

    @if ($fixedLists->isEmpty())
        <div class="card sf-card border-0 shadow-sm">
            <div class="sf-empty-state text-center p-5">
                <i class="bi bi-boxes text-secondary fs-1 d-block mb-3"></i>
                <div class="fw-semibold">No generated batches yet</div>
                <div class="small text-secondary mb-0">Select applicants from the Application Queue and create a batch list to see it here.
                </div>
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
                                @php($listStatus = $list->status->value ?? (string) $list->status)
                                <x-status-badge :status="$listStatus" />

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