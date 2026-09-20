@extends('layouts.app')

@section('title', 'Generated Batch')
@section('eyebrow', 'FASSG Office')
@section('page-title', 'Generated Batch')

@push('styles')
    <style>

        .btn-outline-navy {
            color: #0F2942 !important;
            border-color: #0F2942 !important;
            background-color: transparent !important;
            font-weight: 600;
            transition: all 0.2s ease-in-out;
        }

        .btn-outline-navy:hover,
        .btn-outline-navy:focus {
            color: #ffffff !important;
            background-color: #0F2942 !important;
            border-color: #0F2942 !important;
        }
    </style>
@endpush

@section('content')
    @php
        $blockingStatuses = [
            'Pending',
            'pending',
            'Resubmission Requested',
            'resubmission_requested',
            'resubmission',
        ];
        $hasPending = $list->items->contains(
            fn ($item) => in_array($item->application?->status?->value, $blockingStatuses, true)
        );
    @endphp
    <div class="row g-4 mb-4">
        <div class="col-12 col-lg-9">
            <div class="card sf-card mb-4 border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3 gap-3">
                        <a href="{{ route('fassg.generated-batches.index') }}"
                            class="btn btn-outline-secondary btn-sm fw-semibold d-inline-flex align-items-center gap-2">
                            <i class="bi bi-arrow-left"></i>Back to Generated Batches
                        </a>
                        <span class="badge bg-secondary-subtle text-secondary px-3 py-2 fw-semibold border border-secondary-subtle">
                            {{ ucfirst($list->status->value ?? $list->status) }} Status
                        </span>
                    </div>
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <span class="badge bg-light text-dark mb-2 border">{{ $list->sponsorshipProgram->program_name }}</span>
                            <h2 class="h4 fw-bold mb-1">
                                {{ $list->batch_name ?: 'Generated Batch #' . $list->id . ' - ' . ($list->sponsorshipProgram->program_name ?? 'Unassigned Program') }}
                            </h2>
                            <p class="text-secondary small mb-0">Total Names: {{ $list->items->count() }}</p>
                        </div>
                    </div>

                    <div class="alert alert-info border-0 mb-0 d-flex align-items-start gap-2 py-2 small">
                        <i class="bi bi-list-check text-primary mt-1"></i>
                        <div>
                            <strong>Generated from Application Queue</strong> — all candidates below were selected from the ranked
                            FASSG applicant queue and are linked to their source applications. Ranked by GWA (best first).
                        </div>
                    </div>
                </div>
            </div>

            <div class="card sf-card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <h3 class="h6 fw-bold mb-0">Ranked Applicants</h3>
                    <span class="small text-secondary"><i class="bi bi-sort-numeric-down me-1"></i>Ranked by GWA (best first)</span>
                </div>
                <div class="table-responsive">
                    <table class="table sf-table mb-0 align-middle" id="generatedBatchTable">
                        <thead>
                            <tr>
                                <th class="ps-4" style="width: 48px;">Rank</th>
                                <th style="white-space: nowrap;">Student ID</th>
                                <th>Student Name</th>
                                <th>Academic Program</th>
                                <th>Year Level</th>
                                <th>Campus</th>
                                <th>GWA</th>
                                <th>Application Status</th>
                                <th>SLE-FHE Status</th>
                                <th class="text-end pe-4" style="white-space: nowrap;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($list->items as $item)
                                <tr class="{{ $item->is_manually_endorsed ? 'table-success' : '' }}">
                                    <td class="ps-4">
                                        <span
                                            class="d-inline-flex align-items-center justify-content-center rounded-circle bg-cyan-50 text-cyan-700 border border-cyan-200 fw-bold"
                                            style="width: 30px; height: 30px;">{{ $loop->iteration }}</span>
                                    </td>
                                    <td class="sf-mono text-secondary fw-semibold" style="white-space: nowrap;">{{ $item->student_id_number ?: 'N/A' }}</td>
                                    <td class="fw-semibold">
                                        {{ $item->student_name }}
                                    </td>
                                    <td>{{ $item->course ?: '—' }}</td>
                                    <td>{{ $item->year_level ? "Year {$item->year_level}" : '—' }}</td>
                                    <td>{{ $item->campus ?: 'N/A' }}</td>
                                    <td class="fw-semibold">
                                        {{ $item->application?->gpa_submitted !== null ? number_format((float) $item->application->gpa_submitted, 2) : '—' }}
                                    </td>
                                    <td>
                                        @if ($item->application)
                                            <x-status-badge :status="$item->application->status" />
                                        @else
                                            <span class="badge bg-light text-secondary border">No Application</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $item->is_sle_fhe_verified ? 'bg-cyan-50 text-cyan-700 border border-cyan-200' : 'bg-warning-subtle text-warning-emphasis border border-warning-subtle' }}">
                                            {{ $item->is_sle_fhe_verified ? 'Verified' : 'Pending Check' }}
                                        </span>
                                    </td>
                                    <td class="text-end pe-4" style="white-space: nowrap;">
                                        @if ($item->application_id)
                                            <a href="{{ route('fassg.applications.show', $item->application_id) }}"
                                                class="btn btn-sm text-white fw-semibold d-inline-flex align-items-center gap-1"
                                                style="background-color: #0F2537; border-color: #0F2537; white-space: nowrap;"
                                                title="View Source Application">
                                                <i class="bi bi-file-earmark-text text-white"></i>View Application
                                            </a>
                                        @endif
                                        @if (!$item->is_sle_fhe_verified && $item->status !== \App\Enums\FixedListItemStatus::Endorsed)
                                            <form method="POST"
                                                action="{{ route('fassg.fixed-lists.items.verify', [$list, $item]) }}"
                                                class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-outline-navy btn-sm">Verify</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center text-secondary py-4">No applicants in this generated batch yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                </div>
        </div>

        <div class="col-12 col-lg-3">
            @if (in_array($list->status, [\App\Enums\FixedListStatus::Draft, \App\Enums\FixedListStatus::Rejected, \App\Enums\FixedListStatus::Saved], true))
                <div class="card sf-card border-0 shadow-sm">
                    <div class="card-body p-4 text-center">
                        <h3 class="h6 fw-bold mb-2"><i class="bi bi-send me-1"></i>Submit to Sponsor</h3>
                        <p class="text-secondary small mb-3">Once submitted, this batch will be forwarded to the sponsor for
                            review.</p>

                        @if ($hasPending)
                            <div class="alert alert-warning text-xs mb-3">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                <strong>Action Required:</strong> All applicants in this batch must be fully verified before
                                submitting to the sponsor. One or more applicants are currently pending or requested for
                                resubmission. Use the
                                <span class="fw-semibold">View Application</span> link on each affected row below to review
                                their documents before submitting this batch.
                            </div>
                        @endif

                        <form method="POST" action="{{ route('fassg.fixed-lists.submit', $list) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-sf-navy w-100 py-2"
                                @disabled($list->items->isEmpty() || $hasPending)
                                onClick="if ({{ $list->items->where('is_sle_fhe_verified', true)->count() }} !== {{ $list->items->count() }}) { alert('Verify all students before forwarding this batch to the sponsor.'); return false; } return confirm('All students are verified. Forward this batch to the sponsor?');">
                                <i class="bi bi-send me-1"></i>Submit Batch
                            </button>
                        </form>

                        <div class="border-top pt-3 mt-3">
                            <p class="small text-secondary mb-2"><i
                                    class="bi bi-arrow-counterclockwise me-1"></i>Deleting this batch unlinks its
                                applicants and returns them to the Application Queue.</p>
                            <button type="button"
                                class="btn btn-outline-danger w-100 py-2 fw-semibold d-inline-flex align-items-center justify-content-center gap-2"
                                data-bs-toggle="modal" data-bs-target="#deleteBatchModal">
                                <i class="bi bi-trash"></i>Delete Batch
                            </button>
                        </div>
                    </div>
                </div>
            @elseif ($list->status === \App\Enums\FixedListStatus::Submitted)
                <div class="card sf-card border-0 shadow-sm">
                    <div class="card-body p-4 text-center">
                        <h3 class="h6 fw-bold mb-2"><i class="bi bi-hourglass-split me-1 text-info"></i>Submitted to Sponsor</h3>
                        <p class="text-secondary small mb-3">This batch is awaiting sponsor review and approval.</p>
                        <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-3 py-2 fw-semibold">
                            <i class="bi bi-send me-1"></i>Submitted to Sponsor (Awaiting Sponsor Approval)
                        </span>
                    </div>
                </div>
            @elseif ($list->status === \App\Enums\FixedListStatus::Approved)
                <div class="card sf-card border-0 shadow-sm">
                    <div class="card-body p-4 text-center">
                        <h3 class="h6 fw-bold mb-2 text-success"><i class="bi bi-check-circle-fill me-1"></i>Approved by Sponsor</h3>
                        <p class="text-secondary small mb-3">The sponsor has confirmed this batch. Verified beneficiaries
                            have been forwarded to Accounting automatically.</p>
                        <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle px-3 py-2 fw-semibold">
                            <i class="bi bi-patch-check me-1"></i>Approved by Sponsor
                        </span>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Delete Batch Confirmation Modal --}}
    <div class="modal fade" id="deleteBatchModal" tabindex="-1" aria-labelledby="deleteBatchModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('fassg.generated-batches.destroy', $list) }}">
                @csrf
                @method('DELETE')
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-danger" id="deleteBatchModalLabel">
                            <i class="bi bi-trash3 me-1"></i>Delete Batch
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-2">Are you sure you want to delete this batch?</p>
                        <p class="small text-secondary mb-0">All linked applications will be unbatched and returned to
                            the queue. This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary fw-semibold"
                            data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger fw-semibold">
                            <i class="bi bi-trash me-1"></i>Delete Batch
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection