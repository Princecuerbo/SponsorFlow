@extends('layouts.app')

@section('title', 'Beneficiary Batch Reference')
@section('eyebrow', 'Accounting Office')
@section('page-title', 'Beneficiary Batch Reference')

@push('styles')
    <style>
        .badge-pill-outline {
            border: 1px solid rgba(30, 58, 138, 0.25);
            background-color: rgba(30, 58, 138, 0.06);
            color: var(--sf-navy, #1e3a8a);
            font-weight: 600;
        }

        @media print {

            .sf-navbar,
            .no-print {
                display: none !important;
            }

            .sf-content {
                padding: 0 !important;
            }

            .sf-card {
                box-shadow: none !important;
                border: 1px solid #dee2e6 !important;
            }
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid px-4 py-4">
        <div class="mb-4 no-print">
            <a href="{{ route('accounting.beneficiaries.index') }}" class="btn btn-sm btn-outline-secondary mb-3">
                <i class="bi bi-arrow-left me-1"></i>Back to Master List
            </a>
            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                <span class="text-uppercase fw-bold text-muted extra-small tracking-wider d-block">
                    Tuition Adjustment Reference
                </span>
                <span class="badge bg-success-subtle text-success-emphasis fw-semibold">
                    {{ $fixedList->status->value }}
                </span>
                <span class="badge bg-primary-subtle text-primary-emphasis fw-semibold">
                    Sponsor Confirmed
                </span>
            </div>
            <h1 class="h2 fw-bold text-dark mb-1">{{ $fixedList->batch_name }}</h1>
            <p class="text-muted small mb-0">
                {{ $fixedList->sponsorshipProgram?->program_name ?? 'Unspecified Program' }} ·
                {{ $fixedList->total_names }} {!! \Illuminate\Support\Str::plural('beneficiary', $fixedList->total_names) !!} ·
                Confirmed {{ $approval?->created_at?->format('M d, Y, h:i A') ?? '—' }}
            </p>
        </div>

        <div class="alert border-0 border-start border-4 rounded-3 p-3 mb-4 no-print d-flex align-items-center gap-2"
            style="background-color: rgba(30, 58, 138, 0.05); color: #1e3a8a; border-color: #1e3a8a !important;">
            <i class="bi bi-lock-fill fs-5"></i>
            <span class="small fw-semibold">Accounting access is strictly read-only. Approval, editing, and deletion are
                handled by FASSG and sponsors.</span>
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card sf-card border-0 shadow-sm rounded-3">
                    <div class="card-body p-4">
                        <h2 class="h5 fw-bold text-dark mb-4">Approved Batch Details</h2>
                        <dl class="row mb-0">
                            <dt class="col-sm-4 text-secondary fw-normal py-2">Batch Name</dt>
                            <dd class="col-sm-8 fw-semibold text-dark py-2 mb-0">{{ $fixedList->batch_name }}</dd>
                            <dt class="col-sm-4 text-secondary fw-normal py-2 border-top">Program</dt>
                            <dd class="col-sm-8 py-2 mb-0 border-top">
                                {{ $fixedList->sponsorshipProgram?->program_name ?? '—' }}
                                @if ($fixedList->sponsorshipProgram?->category)
                                    <div class="small text-secondary">{{ $fixedList->sponsorshipProgram->category->value }}</div>
                                @endif
                            </dd>
                            <dt class="col-sm-4 text-secondary fw-normal py-2 border-top">Sponsor / Organization</dt>
                            <dd class="col-sm-8 py-2 mb-0 border-top">
                                {{ $fixedList->sponsorshipProgram?->sponsor?->company_organization_name ?? '—' }}
                            </dd>
                            <dt class="col-sm-4 text-secondary fw-normal py-2 border-top">Billing Contact</dt>
                            <dd class="col-sm-8 py-2 mb-0 border-top">
                                {{ $fixedList->sponsorshipProgram?->sponsor?->contact_person ?: 'Not provided' }}
                            </dd>
                            <dt class="col-sm-4 text-secondary fw-normal py-2 border-top">Total Names</dt>
                            <dd class="col-sm-8 py-2 mb-0 border-top">{{ $fixedList->total_names }}</dd>
                            @if ($fixedList->fassg_assigned_at)
                                <dt class="col-sm-4 text-secondary fw-normal py-2 border-top">FASSG Assigned</dt>
                                <dd class="col-sm-8 py-2 mb-0 border-top">
                                    {{ $fixedList->fassg_assigned_at->format('M d, Y, h:i A') }}</dd>
                            @endif
                            @if ($approval?->created_at)
                                <dt class="col-sm-4 text-secondary fw-normal py-2 border-top">Sponsor Confirmed</dt>
                                <dd class="col-sm-8 py-2 mb-0 border-top">
                                    {{ $approval->created_at->format('M d, Y, h:i A') }}</dd>
                            @endif
                        </dl>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card sf-card border-0 shadow-sm rounded-3 h-100">
                    <div class="card-body p-4">
                        <h2 class="h5 fw-bold text-dark mb-4">Signed Approval Document</h2>
                        @if ($approval?->approval_document_path)
                            <a href="{{ route('accounting.fixed-lists.document', $fixedList) }}" target="_blank"
                                rel="noopener" class="btn btn-sf-navy w-100 fw-semibold">
                                <i class="bi bi-file-earmark-check me-1"></i>View Signed Approval
                            </a>
                            <div class="small text-secondary mt-3 d-flex align-items-center gap-2">
                                <i class="bi bi-file-earmark-pdf"></i>
                                Sponsor-signed beneficiary list
                                {{ $approval->created_at?->format('M d, Y, h:i A') ? '· uploaded '.$approval->created_at->format('M d, Y, h:i A') : '' }}
                            </div>
                        @else
                            <div class="text-secondary small">No signed approval document has been uploaded for this
                                batch.</div>
                        @endif
                        @if ($approval?->uploadedBySponsor)
                            <div class="border-top mt-3 pt-3 small text-secondary">
                                Uploaded by {{ $approval->uploadedBySponsor->name }} ·
                                {{ $approval->confirmation_status->value }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="card sf-card border-0 shadow-sm rounded-3 mt-4">
            <div class="card-header bg-white border-bottom p-3 d-flex align-items-center justify-content-between">
                <div>
                    <h2 class="h5 fw-bold text-dark mb-0">Beneficiary Roster</h2>
                    <div class="small text-secondary">{{ $fixedList->items->count() }} record(s) in this batch</div>
                </div>
                <button type="button" onclick="window.print()" class="btn btn-outline-secondary btn-sm fw-semibold no-print">
                    <i class="bi bi-printer me-1"></i>Print
                </button>
            </div>
            <div class="table-responsive">
                <table class="table sf-table table-hover align-middle mb-0">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-4">Student ID Number</th>
                            <th>Full Name</th>
                            <th>Course &amp; Year</th>
                            <th>GWA / GPA</th>
                            <th>Campus</th>
                            <th>Source</th>
                            <th>Rurality</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        @forelse ($fixedList->items as $item)
                            <tr>
                                <td class="ps-4 font-monospace small" style="color: #475569;">{{ $item->student_id_number }}</td>
                                <td class="fw-semibold text-dark">{{ $item->student_name }}</td>
                                <td>
                                    {{ $item->course ?: '—' }}
                                    @if ($item->year_level)
                                        <div class="small text-secondary">Year {{ $item->year_level }}</div>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $gwa = $item->application?->gpa_submitted ?? $item->gwa;
                                    @endphp
                                    @if ($gwa !== null)
                                        {{ number_format((float) $gwa, 2) }}
                                    @else
                                        <span class="text-secondary">—</span>
                                    @endif
                                </td>
                                <td>{{ $item->campus ?: 'N/A' }}</td>
                                <td>
                                    @if ($item->application_id)
                                        <span class="badge bg-primary-subtle text-primary-emphasis border border-primary-subtle fw-semibold">Application Batch</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle fw-semibold">Fixed List</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $app = $item->application;
                                        $rurality = $app?->is_rural_submitted
                                            ? 'Rural'
                                            : ($app?->studentProfile?->is_rural ? 'Rural' : null);
                                    @endphp
                                    @if ($rurality)
                                        <span class="badge {{ $rurality === 'Rural' ? 'bg-info-subtle text-info-emphasis' : 'bg-secondary-subtle text-secondary-emphasis' }}">
                                            {{ $rurality }}
                                        </span>
                                    @else
                                        <span class="text-secondary">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($item->is_sle_fhe_verified)
                                        <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle fw-semibold">SLE-FHE</span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle fw-semibold">Unverified</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-secondary py-5">
                                    <div class="fw-semibold text-dark">No beneficiary records in this batch.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection