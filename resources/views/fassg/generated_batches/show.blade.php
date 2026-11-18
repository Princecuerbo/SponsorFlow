@extends('layouts.app')

@php
    $batchTitle = $list->batch_name ?: ('Generated Batch #' . $list->id);
    $programName = $list->sponsorshipProgram->program_name ?? null;
    $candidateCount = $list->items->count();
    $candidateText = $candidateCount . ' ' . \Illuminate\Support\Str::plural('candidate', $candidateCount);

    // Avoid repeating identical text if batch_name is already the program name
    if ($programName && strcasecmp(trim($batchTitle), trim($programName)) !== 0) {
        $batchSubtitle = $programName . ' · ' . $candidateText;
    } else {
        $batchSubtitle = 'Batch #' . $list->id . ' · ' . $candidateText;
    }

    $listStatus = $list->status->value ?? (string) $list->status;
@endphp

@section('title', 'Generated Batch')
@section('eyebrow', 'FASSG Office · Generated Batches')
@section('page-title', $batchTitle)
@section('subtitle', $batchSubtitle)

@section('header-actions')
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('fassg.generated-batches.index') }}"
            class="btn btn-outline-secondary btn-sm fw-semibold d-inline-flex align-items-center gap-2">
            <i class="bi bi-arrow-left"></i>Back to Generated Batches
        </a>
        <x-status-badge :status="$list->status" class="sf-batch-status-badge sf-batch-status-{{ strtolower($listStatus) }}" />
    </div>
@endsection

@push('styles')
    @include('fassg.partials.generated-batches-styles')
    @include('fassg.partials.modal-styles')
@endpush

@section('content')
<div class="sf-batch-detail-page">
    @php
        $hasPending = $list->items->contains(
            fn ($item) => $item->application?->status !== \App\Enums\ApplicationStatus::Verified
                || $item->application?->verified_at === null || ! $item->is_sle_fhe_verified
        );
    @endphp

    {{-- Institutional Notice Card --}}
    <div class="sf-batch-notice-card">
        <i class="bi bi-info-circle"></i>
        <div class="sf-notice-text">
            <strong class="sf-notice-title d-block mb-1">Manual Selection &amp; Verification</strong>
            Candidates are manually selected by FASSG from ordinary applications or finalized fixed lists. Saving a draft does not verify or approve candidates. Each member must be reviewed before Sponsor submission.
        </div>
    </div>

    @if ($list->submission_version === 0 && $list->status !== \App\Enums\GeneratedBatchStatus::Saved)
        <div class="sf-legacy-alert mb-4">
            <i class="bi bi-exclamation-triangle me-1"></i>
            Historical evidence is incomplete. Original records are preserved, and no past approval or Accounting forwarding is assumed.
        </div>
    @endif

    {{-- Selected Candidates Table / Empty State Card --}}
    <div class="card sf-table-card border-0 shadow-sm mb-4">
        <div class="sf-table-card-header">
            <h3 class="sf-table-title">
                <i class="bi bi-people-fill text-secondary"></i>Selected Candidates
            </h3>
            <span class="sf-table-subtitle">
                <i class="bi bi-sort-numeric-down me-1"></i>Ordered by Student ID
            </span>
        </div>

        @if ($list->items->isEmpty())
            {{-- Dedicated Status-Aware Empty State --}}
            <div class="sf-batch-empty-card">
                <div class="sf-batch-empty-icon">
                    <i class="bi bi-people"></i>
                </div>
                <div class="sf-batch-empty-title">No candidates in this generated batch</div>
                @if ($list->status === \App\Enums\GeneratedBatchStatus::Saved)
                    <p class="sf-batch-empty-text">This draft batch does not currently contain any candidates. Select applicants from the Application Queue to populate this batch.</p>
                @elseif ($list->status === \App\Enums\GeneratedBatchStatus::Submitted)
                    <p class="sf-batch-empty-text">No candidate records are available for this submitted batch. Its recorded status has been preserved; no past candidate selections are inferred.</p>
                @elseif ($list->status === \App\Enums\GeneratedBatchStatus::Approved)
                    <p class="sf-batch-empty-text">No candidate records are available for this approved batch. Its recorded status has been preserved.</p>
                @elseif ($list->status === \App\Enums\GeneratedBatchStatus::Rejected)
                    <p class="sf-batch-empty-text">No candidate records are available for this rejected batch. Its recorded status has been preserved.</p>
                @else
                    <p class="sf-batch-empty-text">No candidate records are associated with this batch.</p>
                @endif
            </div>
        @else
            {{-- Populated Candidates Table --}}
            <div class="table-responsive">
                <table class="table sf-batch-table align-middle" id="generatedBatchTable">
                    <thead>
                        <tr>
                            <th class="ps-3" style="width: 38px;">No.</th>
                            <th style="width: 92px;">Source</th>
                            <th style="width: 95px;">Student ID</th>
                            <th style="width: 130px;">Student Name</th>
                            <th>Academic Program</th>
                            <th style="width: 115px;">Year &amp; Campus</th>
                            <th style="width: 50px;">GWA</th>
                            <th style="width: 95px;">App Status</th>
                            <th style="width: 88px;">SLE-FHE</th>
                            <th class="text-end pe-3" style="width: 140px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($list->items as $item)
                            <tr>
                                <td class="ps-3">
                                    <span class="sf-index-badge">{{ $loop->iteration }}</span>
                                </td>
                                <td>
                                    @if ($item->is_fixed_list)
                                        <span class="sf-source-badge-fixed" title="Fixed List Candidate">
                                            <i class="bi bi-pin-angle-fill"></i>Fixed List
                                        </span>
                                    @else
                                        <span class="sf-source-badge-ordinary" title="Ordinary Applicant">
                                            <i class="bi bi-person-fill"></i>Ordinary
                                        </span>
                                    @endif
                                </td>
                                <td class="sf-student-id">{{ $item->student_id_number ?: 'N/A' }}</td>
                                <td>
                                    <div class="sf-student-name">{{ $item->student_name }}</div>
                                </td>
                                <td>
                                    <div class="sf-course-cell">{{ $item->course ?: '—' }}</div>
                                </td>
                                <td>
                                    <div class="sf-year-campus-cell">
                                        <span class="sf-year-text">{{ $item->year_level ? "Year {$item->year_level}" : '—' }}</span>
                                        <span class="sf-campus-text">
                                            <i class="bi bi-geo-alt me-1"></i>{{ $item->campus ?: 'N/A' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="fw-bold sf-gwa-text">
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
                                <td class="text-end pe-3">
                                    <div class="sf-action-group">
                                        @if ($list->status === \App\Enums\GeneratedBatchStatus::Saved && $list->submission_version === 0
                                            && $list->submitted_roster === null && $list->fassg_assigned_at === null
                                            && $list->fassg_assigned_by_id === null && $list->sponsorApprovals->isEmpty())
                                            <form method="POST" class="d-inline"
                                                action="{{ route('fassg.generated-batches.items.destroy', [$list, $item]) }}"
                                                data-sf-remove-candidate-form
                                                data-candidate-name="{{ $item->student_name }}"
                                                data-candidate-id="{{ $item->student_id_number ?: 'N/A' }}">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="sf-btn-remove-draft" title="Remove candidate from draft">
                                                    <i class="bi bi-x-circle"></i>Remove
                                                </button>
                                            </form>
                                        @endif
                                        @if ($item->application_id)
                                            <a href="{{ route('fassg.generated-batches.applications.show', [$list, $item->application_id]) }}"
                                                class="sf-btn-review-member"
                                                title="Review this batch member and supporting documents">
                                                <i class="bi bi-file-earmark-text"></i>Review Member
                                            </a>
                                        @endif
                                    </div>
                                    @if (!$item->is_sle_fhe_verified)
                                        <div class="text-end mt-1">
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle sf-awaiting-badge">
                                                Awaiting SLE-FHE
                                            </span>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Workflow, Decisions & Submission Section --}}
    @if ($currentApproval?->decided_at)
        <div class="card sf-workflow-card shadow-sm border-0 mb-4">
            <div class="card-body p-4">
                <div class="row align-items-center g-3">
                    <div class="col-12 col-md-7">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1 fw-semibold">
                                Sponsor Decision
                            </span>
                            <span class="fw-bold text-dark">{{ $currentApproval->confirmation_status->value }}</span>
                        </div>
                        <p class="small text-secondary mb-0">
                            Decided by: <strong class="text-dark">{{ $currentApproval->decidedBy?->name ?? 'Officer not recorded' }}</strong>
                            · {{ $currentApproval->decided_at->format('M d, Y H:i') }}
                        </p>
                        @if ($currentApproval->fassg_recorded_at)
                            <p class="small text-secondary border-top pt-2 mt-2 mb-0">
                                <i class="bi bi-check2-circle text-primary me-1"></i>Received by: {{ $currentApproval->recordedBy?->name ?? 'Officer not recorded' }}
                                · {{ $currentApproval->fassg_recorded_at->format('M d, Y H:i') }}
                            </p>
                        @endif
                        @if ($currentApproval->accounting_forwarded_at)
                            <p class="small text-success border-top pt-2 mt-2 mb-0">
                                <i class="bi bi-send-check me-1"></i>Forwarded to Accounting by {{ $currentApproval->forwardedBy?->name ?? 'Officer' }}
                                · {{ $currentApproval->accounting_forwarded_at->format('M d, Y H:i') }}
                            </p>
                        @endif
                    </div>
                    <div class="col-12 col-md-5 text-md-end">
                        @if (!$currentApproval->fassg_recorded_at)
                            <form method="POST" action="{{ route('fassg.generated-batches.record-decision', $list) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="approval_id" value="{{ $currentApproval->id }}">
                                <input type="hidden" name="submission_version" value="{{ $list->submission_version }}">
                                <button type="submit" class="btn btn-outline-primary btn-sm fw-semibold px-3">
                                    <i class="bi bi-check2-circle me-1"></i>Record Receipt of Decision
                                </button>
                            </form>
                        @elseif ($currentApproval->isConfirmed() && !$currentApproval->accounting_forwarded_at)
                            <form method="POST" action="{{ route('fassg.generated-batches.forward-accounting', $list) }}"
                                onsubmit="return confirm('Forward the complete eligible approved batch to Accounting?');">
                                @csrf @method('PATCH')
                                <input type="hidden" name="approval_id" value="{{ $currentApproval->id }}">
                                <input type="hidden" name="submission_version" value="{{ $list->submission_version }}">
                                <button type="submit" class="btn btn-success btn-sm fw-semibold px-3">
                                    <i class="bi bi-send-check me-1"></i>Forward to Accounting
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if ($list->sponsorApprovals->isNotEmpty())
        <div class="d-flex flex-wrap gap-2 mb-4">
            @foreach ($list->sponsorApprovals as $evidence)
                <div class="card sf-evidence-card p-2.5 px-3 d-inline-flex flex-row align-items-center gap-3">
                    <span class="small fw-semibold text-dark">{{ $evidence->confirmation_status->value }}</span>
                    <span class="badge bg-light text-secondary border" style="font-size: 0.6875rem;">v{{ $evidence->submission_version ?? 'legacy' }}</span>
                    @if ($evidence->approval_document_path)
                        <a href="{{ route('fassg.generated-batches.approval-document', [$list, $evidence]) }}"
                            target="_blank" rel="noopener" class="small text-primary text-decoration-none d-inline-flex align-items-center gap-1">
                            <i class="bi bi-file-earmark-pdf"></i>View official evidence
                        </a>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    {{-- Main Workflow Status & Action Panel --}}
    @if (in_array($list->status, [\App\Enums\GeneratedBatchStatus::Saved, \App\Enums\GeneratedBatchStatus::Rejected], true))
        {{-- Submission Panel for Saved / Rejected Batches --}}
        <div class="card sf-workflow-card shadow-sm border-0 mb-4">
            <div class="card-body p-4">
                <div class="row align-items-center g-4">
                    <div class="col-12 col-lg-7 col-xl-8">
                        <div class="d-flex align-items-start gap-3">
                            <div class="sf-workflow-icon-badge">
                                <i class="bi bi-send text-primary"></i>
                            </div>
                            <div>
                                <h3 class="sf-workflow-title mb-1">Submit to Sponsor</h3>
                                <p class="sf-workflow-subtitle mb-0">
                                    Send the verified candidate roster for the Sponsor's formal decision.
                                </p>
                            </div>
                        </div>

                        @if ($hasPending)
                            <div class="sf-action-required-alert mt-3">
                                <div class="d-flex align-items-center gap-1.5 fw-bold mb-1">
                                    <i class="bi bi-exclamation-triangle me-1"></i>Action Required
                                </div>
                                <div>
                                    Some candidates still require individual FASSG review. Select <strong>Review Member</strong> to verify their documents and qualifications before submission.
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="col-12 col-lg-5 col-xl-4 text-lg-end">
                        <div class="d-flex flex-column gap-2 justify-content-end">
                            <form method="POST" action="{{ route('fassg.generated-batches.submit', $list) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="sf-btn-submit-batch w-100"
                                    @disabled($list->items->isEmpty() || $hasPending || ($list->status === \App\Enums\GeneratedBatchStatus::Rejected && !$currentApproval?->fassg_recorded_at))
                                    onClick="if ({{ $list->items->where('is_sle_fhe_verified', true)->count() }} !== {{ $list->items->count() }}) { alert('Verify all students before forwarding this batch to the sponsor.'); return false; } return confirm('All students are verified. Forward this batch to the sponsor?');">
                                    <i class="bi bi-send me-1"></i>Submit Batch
                                </button>
                            </form>

                            @if ($list->status === \App\Enums\GeneratedBatchStatus::Saved && $list->submission_version === 0)
                                <div class="sf-delete-section">
                                    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2">
                                        <span class="sf-delete-note">
                                            Deleting this draft does not delete source applications or fixed lists.
                                        </span>
                                        <button type="button" class="sf-btn-delete-batch-outlined"
                                            data-bs-toggle="modal" data-bs-target="#deleteBatchModal">
                                            <i class="bi bi-trash3 me-1"></i>Delete Batch
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @elseif ($list->status === \App\Enums\GeneratedBatchStatus::Submitted)
        {{-- Status Panel for Submitted Batches (Amber semantic tone) --}}
        <div class="card sf-workflow-card shadow-sm border-0 mb-4">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="sf-workflow-icon-badge" style="background-color: #FFF7E6; color: #B88219;">
                            <i class="bi bi-clock fs-4"></i>
                        </div>
                        <div>
                            <h3 class="sf-workflow-title mb-1" style="color: #986515;">Submitted to Sponsor</h3>
                            <p class="sf-workflow-subtitle mb-0">
                                This batch is awaiting the Sponsor's formal decision.
                            </p>
                        </div>
                    </div>
                    <div>
                        <div class="sf-submitted-status-badge">
                            <i class="bi bi-clock me-1"></i>Awaiting Sponsor Decision
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @elseif ($list->status === \App\Enums\GeneratedBatchStatus::Approved)
        {{-- Status Panel for Approved Batches (Emerald semantic tone) --}}
        <div class="card sf-workflow-card shadow-sm border-0 mb-4">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="sf-workflow-icon-badge" style="background-color: #ECFDF5; color: #065F46;">
                            <i class="bi bi-check-circle-fill fs-4"></i>
                        </div>
                        <div>
                            <h3 class="sf-workflow-title text-success mb-1">Approved by Sponsor</h3>
                            <p class="sf-workflow-subtitle mb-0">
                                The Sponsor has approved this batch. FASSG must record receipt and explicitly forward the eligible beneficiaries to Accounting.
                            </p>
                        </div>
                    </div>
                    <div>
                        <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle px-3 py-2 fw-semibold">
                            <i class="bi bi-patch-check me-1"></i>Approved by Sponsor
                        </span>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Delete Batch Confirmation Modal --}}
    <div class="modal fade" id="deleteBatchModal" tabindex="-1" aria-labelledby="deleteBatchModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('fassg.generated-batches.destroy', $list) }}">
                @csrf
                @method('DELETE')
                <div class="modal-content border-0 shadow" style="border-radius: 14px; overflow: hidden;">
                    <div class="modal-header border-bottom py-3 px-4" style="background-color: #FEF2F2;">
                        <h5 class="modal-title fw-bold text-danger fs-6 d-flex align-items-center gap-2" id="deleteBatchModalLabel">
                            <i class="bi bi-trash3"></i>Delete Unsubmitted Batch
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <p class="mb-2 fw-semibold text-dark">Are you sure you want to delete this unsubmitted draft?</p>
                        <p class="small text-secondary mb-0">
                            Source applications and fixed-list processing records will be unlinked and retained, making them available for manual selection again. This action cannot be undone.
                        </p>
                    </div>
                    <div class="modal-footer border-top bg-light py-2.5 px-4 d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm fw-semibold px-3"
                            data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger btn-sm fw-semibold px-3 d-inline-flex align-items-center gap-1.5">
                            <i class="bi bi-trash3"></i>Confirm Delete
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Remove Candidate from Draft Confirmation Modal --}}
    <div class="modal fade sf-confirm-modal" id="sfRemoveCandidateModal" tabindex="-1"
        aria-labelledby="sfRemoveCandidateModalTitle" aria-hidden="true"
        data-bs-keyboard="true" data-bs-backdrop="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="sf-confirm-header">
                    <div class="sf-confirm-header-left">
                        <div class="sf-confirm-icon-box red">
                            <i class="bi bi-person-dash"></i>
                        </div>
                        <div class="sf-confirm-title-area">
                            <h3 class="sf-confirm-title" id="sfRemoveCandidateModalTitle">Remove Candidate from Draft?</h3>
                            <p class="sf-confirm-subtitle" id="sfRemoveCandidateIdentity"></p>
                        </div>
                    </div>
                    <button type="button" class="sf-confirm-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="sf-confirm-body">
                    <p class="sf-confirm-desc">Remove this candidate from the current generated batch?</p>
                    <div class="sf-confirm-notice neutral">
                        <i class="bi bi-info-circle"></i>
                        <div>Only the candidate's membership in this saved draft will be removed. Their original application, fixed-list records, and verification evidence will be preserved.</div>
                    </div>
                </div>
                <div class="sf-confirm-footer">
                    <button type="button" class="sf-modal-btn sf-modal-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="sf-modal-btn sf-modal-btn-danger" id="sfRemoveCandidateConfirmBtn">
                        <i class="bi bi-person-dash"></i>Remove Candidate
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';
    var modalEl = document.getElementById('sfRemoveCandidateModal');
    if (!modalEl) return;

    var forms = Array.from(document.querySelectorAll('form[data-sf-remove-candidate-form]'));
    if (forms.length === 0) return;

    var identityEl = document.getElementById('sfRemoveCandidateIdentity');
    var confirmBtn = document.getElementById('sfRemoveCandidateConfirmBtn');
    var bsModal = new bootstrap.Modal(modalEl);
    var activeForm = null;
    var submitting = false;

    var MOBILE_FALLBACK = 'Remove this candidate from the unsubmitted draft? The original Fixed List, application and verification evidence will be retained.';

    forms.forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (form.dataset.sfConfirmed === '1') {
                form.dataset.sfConfirmed = '';
                return;
            }
            e.preventDefault();
            if (window.innerWidth < 992) {
                if (confirm(MOBILE_FALLBACK)) {
                    form.dataset.sfConfirmed = '1';
                    form.requestSubmit();
                }
                return;
            }
            activeForm = form;
            var name = form.dataset.candidateName || 'this candidate';
            var id = form.dataset.candidateId || '';
            identityEl.textContent = id && id !== 'N/A' ? name + ' (' + id + ')' : name;
            submitting = false;
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = '<i class="bi bi-person-dash"></i>Remove Candidate';
            bsModal.show();
        });
    });

    confirmBtn.addEventListener('click', function () {
        if (!activeForm || submitting) return;
        submitting = true;
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<i class="bi bi-hourglass-split"></i>Removing\u2026';
        activeForm.dataset.sfConfirmed = '1';
        try {
            activeForm.requestSubmit();
        } catch (err) {
            submitting = false;
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = '<i class="bi bi-person-dash"></i>Remove Candidate';
            activeForm.dataset.sfConfirmed = '';
        }
    });

    modalEl.addEventListener('hidden.bs.modal', function () {
        if (!submitting) activeForm = null;
    });

    window.addEventListener('pageshow', function (e) {
        if (e.persisted) {
            submitting = false;
            if (confirmBtn) {
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = '<i class="bi bi-person-dash"></i>Remove Candidate';
            }
        }
    });
})();
</script>
@endpush
