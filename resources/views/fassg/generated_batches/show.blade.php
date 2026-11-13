@extends('layouts.app')

@section('title', 'Generated Batch')
@section('eyebrow', 'FASSG Office · Generated Batches')
@section('page-title', $list->batch_name ?: 'Generated Batch #' . $list->id . ' - ' . ($list->sponsorshipProgram->program_name ?? 'Unassigned Program'))
@section('subtitle', $list->sponsorshipProgram->program_name . ' · Total Names: ' . $list->items->count())

@section('header-actions')
    <a href="{{ route('fassg.generated-batches.index') }}"
        class="btn btn-outline-secondary btn-sm fw-semibold d-inline-flex align-items-center gap-2">
        <i class="bi bi-arrow-left"></i>Back to Generated Batches
    </a>
    <x-status-badge :status="$list->status" />
@endsection

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
        $hasPending = $list->items->contains(
            fn ($item) => $item->application?->status !== \App\Enums\ApplicationStatus::Verified
                || $item->application?->verified_at === null || ! $item->is_sle_fhe_verified
        );
    @endphp
    <div class="row g-4 mb-4">
        <div class="col-12 col-lg-9">
            <div class="card sf-card mb-4 border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="alert alert-info border-0 mb-0 d-flex align-items-start gap-2 py-2 small">
                        <i class="bi bi-list-check text-primary mt-1"></i>
                        <div>
                            <strong>Selected by FASSG</strong> — candidates may come from verified fixed lists,
                            ordinary applications, or both. Display order is by Student ID and does not indicate priority.
                            Saving a draft does not verify or approve anyone. Review each member before submission.
                        </div>
                    </div>
                </div>
            </div>

            <div class="card sf-card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <h3 class="h6 fw-bold mb-0">Selected Candidates</h3>
                    <span class="small text-secondary">Ordered by Student ID</span>
                </div>
                <div class="table-responsive">
                    <table class="table sf-table table-hover align-middle mb-0" id="generatedBatchTable">
                        <thead>
                            <tr>
                                <th class="ps-4" style="width: 48px;">No.</th>
                                <th>Candidate Source</th>
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
                                <tr>
                                    <td class="ps-4">
                                        <span
                                            class="d-inline-flex align-items-center justify-content-center rounded-circle bg-cyan-50 text-cyan-700 border border-cyan-200 fw-bold"
                                            style="width: 30px; height: 30px;">{{ $loop->iteration }}</span>
                                    </td>
                                    <td>
                                        @if ($item->is_fixed_list)
                                            <span class="badge bg-warning-subtle text-warning-emphasis fw-bold">Fixed List</span>
                                        @else
                                            <span class="badge bg-info-subtle text-info fw-bold">Ordinary Applicant</span>
                                        @endif
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
                                        <x-status-badge :status="$item->is_sle_fhe_verified ? 'Verified' : 'Pending'" />
                                    </td>
                                    <td class="text-end pe-4" style="white-space: nowrap;">
                                        @if ($list->status === \App\Enums\GeneratedBatchStatus::Saved && $list->submission_version === 0
                                            && $list->submitted_roster === null && $list->fassg_assigned_at === null
                                            && $list->fassg_assigned_by_id === null && $list->sponsorApprovals->isEmpty())
                                            <form method="POST" class="d-inline"
                                                action="{{ route('fassg.generated-batches.items.destroy', [$list, $item]) }}"
                                                onsubmit="return confirm('Remove this candidate from the unsubmitted draft? The original Fixed List, application and verification evidence will be retained.');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Remove from Draft</button>
                                            </form>
                                        @endif
                                        @if ($item->application_id)
                                            <a href="{{ route('fassg.generated-batches.applications.show', [$list, $item->application_id]) }}"
                                                class="btn btn-sm text-white fw-semibold d-inline-flex align-items-center gap-1"
                                                style="background-color: #0F2537; border-color: #0F2537; white-space: nowrap;"
                                                title="Review this batch member and supporting documents">
                                                <i class="bi bi-file-earmark-text text-white"></i>Review Member
                                            </a>
                                        @endif
                                        @if (!$item->is_sle_fhe_verified)
                                            <span class="badge bg-light text-secondary border">Awaiting SLE-FHE verification</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center text-secondary py-4">No applicants in this generated batch yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                </div>
        </div>

        <div class="col-12 col-lg-3">
            @if ($list->submission_version === 0 && $list->status !== \App\Enums\GeneratedBatchStatus::Saved)
                <div class="alert alert-warning">Legacy evidence has not been reconciled. Original records are preserved; no past forwarding is inferred.</div>
            @endif
            @if ($currentApproval?->decided_at)
                <div class="card sf-card mb-3"><div class="card-body">
                    <p>Sponsor decision: {{ $currentApproval->confirmation_status->value }} · {{ $currentApproval->decidedBy?->name ?? 'Officer not recorded' }}<br>{{ $currentApproval->decided_at->format('M d, Y H:i') }}</p>
                    @if (!$currentApproval->fassg_recorded_at)
                        <form method="POST" action="{{ route('fassg.generated-batches.record-decision', $list) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="approval_id" value="{{ $currentApproval->id }}">
                            <input type="hidden" name="submission_version" value="{{ $list->submission_version }}">
                            <button type="submit" class="btn btn-outline-primary">Record Receipt of Sponsor Decision</button>
                        </form>
                    @else
                        <p>Received by FASSG: {{ $currentApproval->recordedBy?->name ?? 'Officer not recorded' }} · {{ $currentApproval->fassg_recorded_at->format('M d, Y H:i') }}</p>
                        @if ($currentApproval->isConfirmed() && !$currentApproval->accounting_forwarded_at)
                            <form method="POST" action="{{ route('fassg.generated-batches.forward-accounting', $list) }}" onsubmit="return confirm('Forward the complete eligible approved batch to Accounting?');">
                                @csrf @method('PATCH')
                                <input type="hidden" name="approval_id" value="{{ $currentApproval->id }}">
                                <input type="hidden" name="submission_version" value="{{ $list->submission_version }}">
                                <button type="submit" class="btn btn-success">Forward Approved Batch to Accounting</button>
                            </form>
                        @endif
                    @endif
                    @if ($currentApproval->accounting_forwarded_at)
                        <p>Forwarded by {{ $currentApproval->forwardedBy?->name }} on {{ $currentApproval->forwardedBy?->name ?? 'Officer not recorded' }} · {{ $currentApproval->accounting_forwarded_at->format('M d, Y H:i') }}</p>
                    @endif
                </div></div>
            @endif
            @foreach ($list->sponsorApprovals as $evidence)
                <div class="small border rounded p-2 mb-2">
                    {{ $evidence->confirmation_status->value }} · submission {{ $evidence->submission_version ?? 'legacy' }}
                    @if ($evidence->approval_document_path)
                        <a href="{{ route('fassg.generated-batches.approval-document', [$list, $evidence]) }}" target="_blank" rel="noopener">View original evidence</a>
                    @endif
                </div>
            @endforeach
            @if (in_array($list->status, [\App\Enums\GeneratedBatchStatus::Saved, \App\Enums\GeneratedBatchStatus::Rejected], true))
                <div class="card sf-card border-0 shadow-sm">
                    <div class="card-body p-4 text-center">
                        <h3 class="h6 fw-bold mb-2"><i class="bi bi-send me-1"></i>Submit to Sponsor</h3>
                        <p class="text-secondary small mb-3">Once submitted, this batch will be forwarded to the sponsor for
                            review.</p>

                        @if ($hasPending)
                            <div class="alert alert-warning small mb-3">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                <strong>Action Required:</strong> All applicants in this batch must be fully verified before
                                submitting to the sponsor. One or more members lack the required verification. Use the
                                <span class="fw-semibold">Review Member</span> link on each affected row to review
                                their documents before submitting this batch.
                            </div>
                        @endif

                        <form method="POST" action="{{ route('fassg.generated-batches.submit', $list) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-sf-navy w-100 py-2"
                                @disabled($list->items->isEmpty() || $hasPending || ($list->status === \App\Enums\GeneratedBatchStatus::Rejected && !$currentApproval?->fassg_recorded_at))
                                onClick="if ({{ $list->items->where('is_sle_fhe_verified', true)->count() }} !== {{ $list->items->count() }}) { alert('Verify all students before forwarding this batch to the sponsor.'); return false; } return confirm('All students are verified. Forward this batch to the sponsor?');">
                                <i class="bi bi-send me-1"></i>Submit Batch
                            </button>
                        </form>

                        @if ($list->status === \App\Enums\GeneratedBatchStatus::Saved && $list->submission_version === 0)
                        <div class="border-top pt-3 mt-3">
                            <p class="small text-secondary mb-2"><i
                                    class="bi bi-arrow-counterclockwise me-1"></i>Deleting this batch unlinks its
                                candidates. Source applications and fixed-list processing records remain available for manual selection.</p>
                            <button type="button"
                                class="btn btn-outline-danger w-100 py-2 fw-semibold d-inline-flex align-items-center justify-content-center gap-2"
                                data-bs-toggle="modal" data-bs-target="#deleteBatchModal">
                                <i class="bi bi-trash"></i>Delete Batch
                            </button>
                        </div>
                        @endif
                    </div>
                </div>
            @elseif ($list->status === \App\Enums\GeneratedBatchStatus::Submitted)
                <div class="card sf-card border-0 shadow-sm">
                    <div class="card-body p-4 text-center">
                        <h3 class="h6 fw-bold mb-2"><i class="bi bi-hourglass-split me-1 text-info"></i>Submitted to Sponsor</h3>
                        <p class="text-secondary small mb-3">This batch is awaiting sponsor review and approval.</p>
                        <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-3 py-2 fw-semibold">
                            <i class="bi bi-send me-1"></i>Submitted to Sponsor (Awaiting Sponsor Approval)
                        </span>
                    </div>
                </div>
            @elseif ($list->status === \App\Enums\GeneratedBatchStatus::Approved)
                <div class="card sf-card border-0 shadow-sm">
                    <div class="card-body p-4 text-center">
                        <h3 class="h6 fw-bold mb-2 text-success"><i class="bi bi-check-circle-fill me-1"></i>Approved by Sponsor</h3>
                        <p class="text-secondary small mb-3">The Sponsor has approved this batch. FASSG must record receipt and explicitly forward the eligible beneficiaries to Accounting.</p>
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
                        <p class="small text-secondary mb-0">Source applications and fixed-list processing records are retained and may be selected again manually. This action cannot be undone.</p>
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
