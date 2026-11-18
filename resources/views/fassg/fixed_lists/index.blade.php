@extends('layouts.app')

@section('title', 'Fixed Lists')
@section('eyebrow', 'FASSG Office')
@section('page-title', 'Sponsor-Provided Fixed Lists')
@section('subtitle', 'Manage sponsor-provided student lists, review entries, and prepare finalized lists for batch selection.')

@section('header-actions')
    <button type="button" class="btn btn-sf-navy fw-semibold d-inline-flex align-items-center gap-2 px-3"
        data-bs-toggle="modal" data-bs-target="#newFixedListModal">
        <i class="bi bi-plus-lg"></i>Create Fixed List
    </button>
@endsection

@push('styles')
    @include('fassg.partials.fixed-lists-styles')
    @include('fassg.partials.modal-styles')
@endpush

@section('content')
<div class="sf-fixed-lists-page">
    @if ($fixedLists->isEmpty())
        <div class="card sf-card border-0 shadow-sm">
            <div class="sf-empty-state text-center p-5">
                <i class="bi bi-list-check text-secondary fs-1 d-block mb-3"></i>
                <div class="fw-semibold fs-6 mb-1 text-dark">No fixed lists yet</div>
                <div class="small text-secondary mb-3">Upload a sponsor-provided beneficiary list, or encode names manually.</div>
                <button type="button" class="btn btn-sf-navy btn-sm px-3" data-bs-toggle="modal"
                    data-bs-target="#newFixedListModal">
                    <i class="bi bi-plus-lg me-1"></i>Create Fixed List
                </button>
            </div>
        </div>
    @else
        <div class="sf-fixed-lists-list" id="fixedListsAccordion">
            @foreach ($fixedLists as $list)
                @php
                    $progName = $list->sponsorshipProgram?->program_name ?? 'Unassigned Program';
                    $listTitle = $list->batch_name ?: ('Batch #' . $list->id . ' - ' . $progName);
                    $count = $list->items_count ?? $list->items->count() ?? $list->total_names;
                    $countLabel = $count . ' ' . \Illuminate\Support\Str::plural('student', $count);
                    $listStatus = $list->status->value ?? (string) $list->status;
                @endphp
                <div class="card sf-card sf-fixed-list-card mb-3 border-0 shadow-sm">
                    <div class="card-body sf-fixed-list-card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h3 class="h6 sf-heading mb-1 fw-bold">
                                    <a href="{{ route('fassg.fixed-lists.show', $list) }}"
                                        class="sf-fixed-list-title text-decoration-none">
                                        {{ $listTitle }}
                                    </a>
                                </h3>
                                <div class="sf-fixed-list-meta">
                                    <span>{{ $progName }}</span>
                                    <span class="sf-meta-dot">·</span>
                                    <span><i class="bi bi-people me-1"></i>{{ $countLabel }}</span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <x-status-badge :status="$listStatus" class="sf-fixed-list-status-badge sf-fixed-list-status-{{ strtolower($listStatus) }}" />

                                <a href="{{ route('fassg.fixed-lists.show', $list) }}" class="sf-btn-view-list"
                                    title="View &amp; Manage Fixed List" aria-label="View &amp; Manage Fixed List">
                                    <i class="bi bi-eye"></i><span>View List</span>
                                </a>

                                @if (in_array($list->status, [\App\Enums\FixedListStatus::Draft, \App\Enums\FixedListStatus::Rejected], true))
                                    <a href="{{ route('fassg.fixed-lists.edit', $list) }}"
                                        class="btn btn-outline-secondary btn-sm" title="Rename Fixed List"
                                        aria-label="Rename Fixed List">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="POST" action="{{ route('fassg.fixed-lists.destroy', $list) }}"
                                        class="d-inline" data-sf-archive-form data-list-name="{{ $listTitle }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm"
                                            title="Archive Fixed List" aria-label="Archive Fixed List">
                                            <i class="bi bi-archive"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table sf-table sf-fixed-list-table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 25%;">Student Name</th>
                                        <th style="width: 15%;">Student ID</th>
                                        <th>Academic Program</th>
                                        <th style="width: 10%;">Year</th>
                                        <th style="width: 15%;">SLE-FHE Check</th>
                                        <th style="width: 15%;">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($list->items as $item)
                                        <tr>
                                            <td>
                                                @if (filled($item->student_name))
                                                    <span class="sf-student-name">{{ $item->student_name }}</span>
                                                @else
                                                    <span class="text-secondary fst-italic">Name not recorded</span>
                                                @endif
                                            </td>
                                            <td class="sf-mono text-secondary">{{ $item->student_id_number ?: 'N/A' }}</td>
                                            <td class="text-secondary">{{ $item->course ?: '—' }}</td>
                                            <td class="text-secondary">{{ $item->year_level ? "Year {$item->year_level}" : '—' }}</td>
                                            <td>
                                                @php
                                                    $isVerified = $item->is_sle_fhe_verified || ($item->studentProfile && $item->studentProfile->isSleFheVerified());
                                                @endphp
                                                @if ($isVerified)
                                                    <span class="sf-status-badge-inline sf-status-verified">
                                                        <i class="bi bi-shield-check"></i>Verified
                                                    </span>
                                                @else
                                                    <span class="sf-status-badge-inline sf-status-pending">
                                                        <i class="bi bi-clock"></i>Pending
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                @php
                                                    $itemStatus = $item->status->value ?? (string) $item->status;
                                                @endphp
                                                <x-status-badge :status="$itemStatus" class="sf-status-badge-inline sf-status-{{ strtolower($itemStatus) }}" />
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-secondary py-3 small">
                                                No student records encoded in this list yet.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Upload / encode modal --}}
    <div class="modal fade" id="newFixedListModal" tabindex="-1" aria-labelledby="newFixedListModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" style="max-width: 800px;">
            <div class="modal-content">
                <form method="POST" action="{{ route('fassg.fixed-lists.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title sf-heading fw-bold" id="newFixedListModalLabel">
                            <i class="bi bi-file-earmark-plus me-1 text-primary"></i>New Fixed List
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="sponsorship_program_id">
                                    Sponsorship Program <span class="text-danger">*</span>
                                </label>
                                <select name="sponsorship_program_id" id="sponsorship_program_id" class="form-select" required>
                                    <option value="">Select Sponsorship Program…</option>
                                    @foreach ($programs as $program)
                                        <option value="{{ $program->id }}">{{ $program->program_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="batch_name">
                                    Fixed List Title <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="batch_name" id="batch_name" class="form-control"
                                    placeholder="e.g. Governor Endorsed Candidates 2026" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="file">Upload CSV/Excel of Names</label>
                                <input type="file" name="file" id="file" class="form-control" accept=".csv,.txt">
                                <div class="form-text">Upload a CSV/Excel file, or leave this blank to encode students manually after creating the list.</div>
                            </div>

                            <div class="col-12 pt-2">
                                <hr class="my-2">
                                <div class="sf-form-section-title">
                                    <i class="bi bi-funnel"></i>Optional Import Filters
                                    <span class="sf-form-section-desc">(applied when importing rows from the uploaded file)</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="criteria_course">Academic Program</label>
                                <input type="text" name="criteria_course" id="criteria_course" class="form-control"
                                    placeholder="e.g. BSIT or BSCS" value="{{ old('criteria_course') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="criteria_campus">Campus</label>
                                <select name="criteria_campus" id="criteria_campus" class="form-select">
                                    <option value="">All Campuses</option>
                                    @foreach (['Main Campus (City of Mati)', 'Baganga Campus', 'Banaybanay Campus', 'Cateel Campus', 'San Isidro Campus', 'Tarragona Campus'] as $campusOpt)
                                        <option value="{{ $campusOpt }}" @selected(old('criteria_campus') === $campusOpt)>{{ $campusOpt }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="criteria_gpa">Maximum GWA (Import Filter)</label>
                                <input type="number" step="0.01" min="0" max="5" name="criteria_gpa" id="criteria_gpa"
                                    class="form-control" placeholder="e.g. 1.75" value="{{ old('criteria_gpa') }}">
                                <div class="form-text">Rows with a GWA higher than this number are skipped.</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary btn-sm fw-semibold px-3" data-bs-dismiss="modal">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-sf-navy btn-sm fw-semibold px-3 d-inline-flex align-items-center gap-1.5">
                            <i class="bi bi-plus-lg"></i>Create Fixed List
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Archive Fixed List Confirmation Modal --}}
    <div class="modal fade sf-confirm-modal" id="sfArchiveModal" tabindex="-1"
        aria-labelledby="sfArchiveModalTitle" aria-hidden="true"
        data-bs-keyboard="true" data-bs-backdrop="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="sf-confirm-header">
                    <div class="sf-confirm-header-left">
                        <div class="sf-confirm-icon-box amber">
                            <i class="bi bi-archive"></i>
                        </div>
                        <div class="sf-confirm-title-area">
                            <h3 class="sf-confirm-title" id="sfArchiveModalTitle">Archive Fixed List?</h3>
                            <p class="sf-confirm-subtitle" id="sfArchiveListName"></p>
                        </div>
                    </div>
                    <button type="button" class="sf-confirm-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="sf-confirm-body">
                    <p class="sf-confirm-desc">Are you sure you want to archive this fixed list?</p>
                    <div class="sf-confirm-notice amber">
                        <i class="bi bi-info-circle"></i>
                        <div>Archiving will hide this list from active management. Its student entries and supporting evidence will be preserved. Lists with historical references cannot be archived.</div>
                    </div>
                </div>
                <div class="sf-confirm-footer">
                    <button type="button" class="sf-modal-btn sf-modal-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="sf-modal-btn sf-modal-btn-amber" id="sfArchiveConfirmBtn">
                        <i class="bi bi-archive"></i>Archive Fixed List
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
    var modalEl = document.getElementById('sfArchiveModal');
    if (!modalEl) return;

    var forms = Array.from(document.querySelectorAll('form[data-sf-archive-form]'));
    if (forms.length === 0) return;

    var listNameEl = document.getElementById('sfArchiveListName');
    var confirmBtn = document.getElementById('sfArchiveConfirmBtn');
    var bsModal = new bootstrap.Modal(modalEl);
    var activeForm = null;
    var submitting = false;

    var MOBILE_FALLBACK = 'Archive this obsolete list? Its members and evidence will be retained. Lists with historical references cannot be archived.';

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
            listNameEl.textContent = form.dataset.listName || '';
            submitting = false;
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = '<i class="bi bi-archive"></i>Archive Fixed List';
            bsModal.show();
        });
    });

    confirmBtn.addEventListener('click', function () {
        if (!activeForm || submitting) return;
        submitting = true;
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<i class="bi bi-hourglass-split"></i>Archiving\u2026';
        activeForm.dataset.sfConfirmed = '1';
        try {
            activeForm.requestSubmit();
        } catch (err) {
            submitting = false;
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = '<i class="bi bi-archive"></i>Archive Fixed List';
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
                confirmBtn.innerHTML = '<i class="bi bi-archive"></i>Archive Fixed List';
            }
        }
    });
})();
</script>
@endpush
