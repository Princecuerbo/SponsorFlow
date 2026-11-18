@extends('layouts.app')

@section('title', 'Approvals Queue')
@section('eyebrow', 'Sponsor Portal · Approvals')
@section('page-title', 'Approvals Queue')
@section('subtitle')
    <span class="sponsor-original-copy">Review FASSG-submitted Generated Batches and approve or reject each entire batch.</span>
    <span class="sponsor-desktop-copy d-none">Review FASSG-submitted batches and record a decision for each entire batch.</span>
@endsection

@section('header-actions')
    <span class="sponsor-original-copy badge bg-primary-subtle text-primary-emphasis px-3 py-2">{{ $generatedBatches->count() }}
        generated batches</span>
    <span class="sponsor-desktop-copy d-none sponsor-pending-count">
        <i class="bi bi-clock" aria-hidden="true"></i>
        <strong>{{ $generatedBatches->count() }}</strong>
        {{ $generatedBatches->count() === 1 ? 'batch' : 'batches' }} awaiting decision
    </span>
@endsection

@push('styles')
    @include('sponsor.partials.desktop-styles')
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
    <div class="card sf-card border-0 shadow-sm mb-4 sponsor-filters">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('sponsor.approvals.index') }}" class="row g-2">
                <div class="col-md-4">
                    <label for="sponsor-program-filter" class="sponsor-filter-label d-none">Sponsorship Program</label>
                    <select id="sponsor-program-filter" name="sponsorship_program_id" class="form-select" onchange="this.form.submit()">
                        <option value="">All sponsorship programs</option>
                        @foreach ($programs as $program)
                            <option value="{{ $program->id }}" @selected((int) request('sponsorship_program_id') === (int) $program->id)>
                                {{ $program->program_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('sponsor.approvals.index') }}" class="sf-btn-reset-filters sponsor-btn-reset w-100">
                        <i class="bi bi-arrow-counterclockwise"></i>
                        <span>Reset Filters</span>
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Mobile: Functional Bootstrap Nav Tabs --}}
    <div class="sponsor-original-copy">
        <ul class="nav nav-tabs nav-tabs-navy mb-4" id="approvalQueueTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="generated-batches-tab" data-bs-toggle="tab"
                    data-bs-target="#generated-batches-pane" type="button" role="tab"
                    aria-controls="generated-batches-pane" aria-selected="true">
                    Generated Batches
                </button>
            </li>
        </ul>
    </div>

    {{-- Tab Content Container --}}
    <div class="tab-content" id="approvalQueueTabContent">

        {{-- Submitted Generated Batches from either candidate source --}}
        <div class="tab-pane fade show active" id="generated-batches-pane" role="tabpanel"
            aria-labelledby="generated-batches-tab" tabindex="0">

            <div class="d-flex align-items-center justify-content-between mb-3 sponsor-section-header">
                <div>
                    <h3 class="h6 sf-heading mb-3 fw-bold sponsor-section-title">
                        <span class="sponsor-original-copy">Generated Batches</span>
                        <span class="sponsor-desktop-copy d-none">Submitted Batches</span>
                    </h3>
                    <p class="small text-secondary mb-0 sponsor-section-desc">
                        <span class="sponsor-original-copy">FASSG prepares and verifies each batch from ordinary applicants, finalized Fixed List candidates, or both.</span>
                        <span class="sponsor-desktop-copy d-none">Batches submitted by FASSG for Sponsor review and whole-batch decisions.</span>
                    </p>
                </div>
                <i class="bi bi-people fs-3 sponsor-original-copy" style="color: #0F2942;"></i>
            </div>

            <div class="card sf-card border-0 shadow-sm mb-4 sponsor-card">
                <div class="table-responsive">
                    <table class="table sf-table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">Batch / Program</th>
                                <th>Batch Members</th>
                                <th>Status</th>
                                <th>Confirmation Evidence</th>
                                <th class="text-end pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($generatedBatches as $list)
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-semibold">
                                            <i class="bi bi-collection text-secondary me-1 sponsor-desktop-copy d-none" aria-hidden="true"></i>{{ $list->batch_name }}
                                        </div>
                                        <div class="small text-secondary">{{ $list->sponsorshipProgram->program_name }}</div>
                                    </td>
                                    <td>
                                        <span class="sponsor-original-copy">{{ $list->total_slots }} {{ Str::plural('student', $list->total_slots) }}</span>
                                        <span class="sponsor-desktop-copy d-none">
                                            <i class="bi bi-people text-secondary me-1" aria-hidden="true"></i>{{ $list->total_slots }} {{ Str::plural('member', $list->total_slots) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="sponsor-original-copy"><x-status-badge :status="'Submitted'" /></span>
                                        <span class="sponsor-desktop-copy d-none badge px-2 py-1 fw-medium" style="background: #FFF7E6; color: #7B5B10; border: 1px solid #F6DDA1;">
                                            <i class="bi bi-clock me-1" aria-hidden="true"></i>Awaiting Decision
                                        </span>
                                    </td>
                                    <td>
                                        @if ($list->currentApproval()?->approval_document_path)
                                            <span class="sponsor-original-copy badge bg-emerald-50 text-emerald-700 border border-emerald-200 px-2 py-1">
                                                <i class="bi bi-file-check me-1"></i>Uploaded
                                            </span>
                                            <span class="sponsor-desktop-copy d-none badge px-2 py-1 fw-medium" style="background: #EEF3F7; color: #173B57; border: 1px solid #D6E1E9;">
                                                <i class="bi bi-file-earmark-check me-1" aria-hidden="true"></i>Evidence Uploaded
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
                                                <i class="bi bi-eye me-1" aria-hidden="true"></i>Review Batch
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-secondary py-5 sponsor-empty-cell">
                                        <div class="sponsor-original-copy">No generated batches awaiting a Sponsor decision.</div>
                                        <div class="sponsor-empty sponsor-desktop-copy d-none">
                                            <i class="bi bi-inbox" aria-hidden="true"></i>
                                            @if (request()->filled('sponsorship_program_id'))
                                                <h3>No matching batches</h3>
                                                <p>Try selecting another sponsorship program or resetting the filters.</p>
                                                <div class="mt-2">
                                                    <a href="{{ route('sponsor.approvals.index') }}" class="sf-btn-reset-filters sponsor-btn-reset">
                                                        <i class="bi bi-arrow-counterclockwise"></i>
                                                        <span>Reset Filters</span>
                                                    </a>
                                                </div>
                                            @else
                                                <h3>No batches awaiting review</h3>
                                                <p>Submitted batches that require your decision will appear here.</p>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white border-top px-4 py-3 d-flex flex-wrap align-items-center justify-content-between gap-2 no-print">
                    <span class="small text-secondary sponsor-original-copy">
                        <i class="bi bi-people me-1"></i><strong>{{ $generatedBatches->count() }}</strong>
                        generated batch{{ $generatedBatches->count() === 1 ? '' : 'es' }} awaiting a Sponsor decision
                    </span>
                    <span class="small text-secondary sponsor-desktop-copy d-none">
                        Approval requires confirmation evidence and an explicit whole-batch decision.
                    </span>
                    <span class="small text-secondary sponsor-original-copy">Approval requires confirmation evidence and an explicit whole-batch decision.</span>
                </div>
            </div>
        </div>

    </div>
@endsection
