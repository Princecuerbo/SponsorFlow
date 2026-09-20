@extends('layouts.app')

@section('title', $list->batch_name)
@section('eyebrow', 'Sponsor Portal · Forwarded Applicants')
@section('page-title', $list->batch_name)

@section('content')
    <div class="row g-4">
        <div class="col-lg-7">
            @php
                $eligibleItems = $list->items
                    ->filter(static fn ($item) => $item->application?->status !== \App\Enums\ApplicationStatus::Rejected)
                    ->values();
                $excludedCount = $list->items->count() - $eligibleItems->count();
            @endphp

            <div class="card sf-card border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h2 class="h6 sf-heading mb-1 fw-bold">{{ $list->sponsorshipProgram->program_name }}</h2>
                            <div class="small text-secondary">{{ $eligibleItems->count() }} {{ Str::plural('name', $eligibleItems->count()) }} in this batch
                                @if ($excludedCount > 0)
                                    <span class="text-danger ms-1">({{ $excludedCount }} rejected excluded)</span>
                                @endif
                            </div>
                        </div>
                        <x-status-badge :status="$list->status" />
                    </div>

                    <div class="table-responsive">
                        <table class="table sf-table mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th class="ps-4">Student ID</th>
                                    <th>Student Name</th>
                                    <th>Academic Program</th>
                                    <th>Year Level</th>
                                    <th>Verification Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($eligibleItems as $item)
                                    <tr>
                                        <td class="ps-4 sf-mono text-secondary">{{ $item->student_id_number }}</td>
                                        <td class="fw-semibold">{{ $item->student_name }}</td>
                                        <td class="text-secondary">{{ $item->course }}</td>
                                        <td class="text-secondary">{{ $item->year_level ? "Year {$item->year_level}" : '—' }}</td>
                                        <td>
                                            @if ($item->application?->status === \App\Enums\ApplicationStatus::Verified)
                                                <span class="badge bg-success-subtle text-success">Verified</span>
                                            @elseif ($item->application?->status === \App\Enums\ApplicationStatus::Rejected)
                                                <span class="badge bg-danger-subtle text-danger">Rejected</span>
                                            @elseif ($item->application?->status === \App\Enums\ApplicationStatus::ResubmissionRequested)
                                                <span class="badge bg-warning-subtle text-warning">Resubmission</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">{{ $item->application?->status?->value ?? '—' }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            {{-- Upload approval document --}}
            <div class="card sf-card mb-4 border-0 shadow-sm">
                <div class="card-body p-4">
                    <h2 class="h6 sf-heading mb-3 fw-bold">Signed Approval Document</h2>

                    @if ($list->latestApproval?->approval_document_path)
                        <div class="d-flex align-items-center gap-2 mb-3 p-3 border rounded-3 bg-light">
                            <i class="bi bi-file-earmark-check text-success fs-5"></i>
                            <div class="flex-grow-1 small">Document uploaded</div>
                            <a href="{{ route('sponsor.approvals.download', $list->latestApproval) }}"
                                class="btn btn-sm btn-outline-secondary">View</a>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('sponsor.approvals.store', $list) }}"
                        enctype="multipart/form-data">
                        @csrf
                        <div onclick="document.getElementById('approval_document').click()"
                            class="d-block border border-2 rounded-3 text-center p-4 bg-light mb-3"
                            style="cursor:pointer; border-style:dashed !important;">
                            <i class="bi bi-cloud-upload fs-3 text-secondary d-block mb-2"></i>
                            <span class="small fw-semibold d-block">Click to select and upload signed approval</span>
                            <span class="small text-secondary" id="approvalFileName">PDF, JPG, or PNG</span>
                            <input type="file" name="approval_document" id="approval_document" class="d-none"
                                accept=".pdf,.jpg,.jpeg,.png" onchange="this.form.submit()">
                        </div>
                    </form>
                </div>
            </div>

            {{-- Confirm list --}}
            <div class="card sf-card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h2 class="h6 sf-heading mb-2 fw-bold">Confirm Beneficiary List</h2>
                    <p class="small text-secondary">Confirming finalizes this batch as approved beneficiaries. Accounting
                        will be able to view them for tuition adjustment.</p>

                    @if (!$list->latestApproval?->approval_document_path)
                        <div class="alert alert-warning small py-2 mb-3">
                            <i class="bi bi-exclamation-triangle me-1"></i> Please upload and save a signed approval
                            document above before you can confirm this list.
                        </div>
                    @endif

                    <form method="POST" action="{{ route('sponsor.approvals.confirm', $list) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-sf-navy w-100 py-2"
                            {{ !$list->latestApproval?->approval_document_path || $list->status->value === 'Approved' ? 'disabled' : '' }}>
                            <i class="bi bi-check-circle me-1"></i>
                            {{ $list->status->value === 'Approved' ? 'Already Confirmed' : 'Confirm Final List' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
