@extends('layouts.app')

@section('title', 'Approvals Queue')
@section('eyebrow', 'Sponsor Portal · Approvals')
@section('page-title', 'Approvals Queue')
@section('subtitle', 'Review submitted beneficiary batches from the FASSG Application Queue and external fixed lists.')

@section('header-actions')
    <span class="badge bg-primary-subtle text-primary-emphasis px-3 py-2">{{ $generatedBatches->count() }}
        generated batches</span>
    <span class="badge bg-info-subtle text-info-emphasis px-3 py-2">{{ $externalLists->count() }}
        external lists</span>
@endsection

@push('styles')
    <style>

        /* Custom Navy Styles for Bootstrap Nav Tabs */
        .nav-tabs-navy {
            border-bottom: 1px solid #e5e7eb;
        }

        .nav-tabs-navy .nav-link {
            color: #4b5563 !important;
            font-weight: 500;
            border: 1px solid transparent;
            border-top-left-radius: 0.375rem;
            border-top-right-radius: 0.375rem;
            padding: 0.6rem 1.25rem;
            background-color: transparent;
        }

        .nav-tabs-navy .nav-link:hover {
            color: #0F2942 !important;
            border-color: #e5e7eb #e5e7eb #f3f4f6;
        }

        .nav-tabs-navy .nav-link.active {
            color: #0F2942 !important;
            font-weight: 700;
            background-color: #ffffff !important;
            border-color: #e5e7eb #e5e7eb #ffffff !important;
        }
    </style>
@endpush

@section('content')
    <div class="card sf-card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('sponsor.approvals.index') }}" class="row g-2">
                <div class="col-md-4">
                    <select name="sponsorship_program_id" class="form-select" onchange="this.form.submit()">
                        <option value="">All sponsorship programs</option>
                        @foreach ($programs as $program)
                            <option value="{{ $program->id }}" @selected((int) request('sponsorship_program_id') === (int) $program->id)>
                                {{ $program->program_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('sponsor.approvals.index') }}" class="btn btn-outline-secondary w-100">Reset Filters</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Functional Bootstrap Nav Tabs --}}
    <ul class="nav nav-tabs nav-tabs-navy mb-4" id="approvalQueueTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="generated-batches-tab" data-bs-toggle="tab"
                data-bs-target="#generated-batches-pane" type="button" role="tab"
                aria-controls="generated-batches-pane" aria-selected="true">
                Generated Batches
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="external-lists-tab" data-bs-toggle="tab" data-bs-target="#external-lists-pane"
                type="button" role="tab" aria-controls="external-lists-pane" aria-selected="false">
                External Fixed Lists
            </button>
        </li>
    </ul>

    {{-- Tab Content Container --}}
    <div class="tab-content" id="approvalQueueTabContent">

        {{-- Pane 1: Generated Batches (from FASSG Application Queue) --}}
        <div class="tab-pane fade show active" id="generated-batches-pane" role="tabpanel"
            aria-labelledby="generated-batches-tab" tabindex="0">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                    <h3 class="h6 sf-heading mb-3 fw-bold">Generated Batches</h3>
                    <p class="small text-secondary mb-0">Batch lists generated from the FASSG Application Queue, each
                        linked to verified applications.</p>
                </div>
                <i class="bi bi-people fs-3" style="color: #0F2942;"></i>
            </div>

            <div class="card sf-card border-0 shadow-sm mb-4">
                <div class="table-responsive">
                    <table class="table sf-table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">Batch / Program</th>
                                <th>Beneficiaries</th>
                                <th>Status</th>
                                <th>Signed Document</th>
                                <th class="text-end pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($generatedBatches as $list)
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-semibold">{{ $list->batch_name }}</div>
                                        <div class="small text-secondary">{{ $list->sponsorshipProgram->program_name }}
                                        </div>
                                    </td>
                                    <td>{{ $list->total_names }} {{ Str::plural('student', $list->total_names) }}</td>
                                    <td><x-status-badge :status="'Submitted'" /></td>
                                    <td>
                                        @if (!empty($list->approval_document_path))
                                            <span
                                                class="badge bg-emerald-50 text-emerald-700 border border-emerald-200 px-2 py-1">
                                                <i class="bi bi-file-check me-1"></i>Uploaded
                                            </span>
                                        @else
                                            <span class="text-secondary small">
                                                <i class="bi bi-dash-circle me-1"></i>Pending Upload
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="d-inline-flex flex-nowrap gap-2 align-items-center justify-content-end">
                                            <a href="{{ route('sponsor.lists.show', $list) }}"
                                                class="btn btn-sm btn-sf-navy">
                                                <i class="bi bi-check2-circle me-1"></i>Review &amp; Confirm
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-secondary py-5">No generated batches awaiting
                                        approval.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white border-top px-4 py-3 d-flex flex-wrap align-items-center justify-content-between gap-2 no-print">
                    <span class="small text-secondary">
                        <i class="bi bi-people me-1"></i><strong>{{ $generatedBatches->count() }}</strong>
                        generated batch{{ $generatedBatches->count() === 1 ? '' : 'es' }} awaiting approval
                    </span>
                    <span class="small text-secondary">Upload the signed approval document to confirm each list.</span>
                </div>
            </div>
        </div>

        {{-- Pane 2: External Fixed Lists (Manual / CSV uploaded) --}}
        <div class="tab-pane fade" id="external-lists-pane" role="tabpanel" aria-labelledby="external-lists-tab"
            tabindex="0">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                    <h3 class="h6 sf-heading mb-3 fw-bold">External Fixed Lists</h3>
                    <p class="small text-secondary mb-0">Manually encoded or CSV-uploaded beneficiary lists.</p>
                </div>
                <i class="bi bi-file-earmark-text fs-3" style="color: #0F2942;"></i>
            </div>

            <div class="card sf-card border-0 shadow-sm mb-4">
                <div class="table-responsive">
                    <table class="table sf-table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">Batch / Program</th>
                                <th>Beneficiaries</th>
                                <th>Status</th>
                                <th>Signed Document</th>
                                <th class="text-end pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($externalLists as $list)
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-semibold">{{ $list->batch_name }}</div>
                                        <div class="small text-secondary">{{ $list->sponsorshipProgram->program_name }}
                                        </div>
                                    </td>
                                    <td>{{ $list->total_names }} {{ Str::plural('student', $list->total_names) }}</td>
                                    <td><x-status-badge :status="'Submitted'" /></td>
                                    <td>
                                        @if (!empty($list->approval_document_path))
                                            <span
                                                class="badge bg-emerald-50 text-emerald-700 border border-emerald-200 px-2 py-1">
                                                <i class="bi bi-file-check me-1"></i>Uploaded
                                            </span>
                                        @else
                                            <span class="text-secondary small">
                                                <i class="bi bi-dash-circle me-1"></i>Pending Upload
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="d-inline-flex flex-nowrap gap-2 align-items-center justify-content-end">
                                            <a href="{{ route('sponsor.lists.show', $list) }}"
                                                class="btn btn-sm btn-sf-navy">
                                                <i class="bi bi-check2-circle me-1"></i>Review &amp; Confirm
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-secondary py-5">No external fixed lists
                                        awaiting approval.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white border-top px-4 py-3 d-flex flex-wrap align-items-center justify-content-between gap-2 no-print">
                    <span class="small text-secondary">
                        <i class="bi bi-file-earmark-text me-1"></i><strong>{{ $externalLists->count() }}</strong>
                        external list{{ $externalLists->count() === 1 ? '' : 's' }} awaiting approval
                    </span>
                    <span class="small text-secondary">Review each external list before uploading the signed document.</span>
                </div>
            </div>
        </div>

    </div>
@endsection
