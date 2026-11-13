@extends('layouts.app')

@section('title', $list->batch_name)
@section('eyebrow', 'Sponsor Portal · Forwarded Applicants')
@section('page-title', $list->batch_name)

@section('content')
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    @if (session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    <div class="row g-4">
        <div class="col-lg-7">
            @php
                $eligibleItems = $list->items->values();
                $excludedCount = 0;
                $canDecide = $list->status === \App\Enums\GeneratedBatchStatus::Submitted && $list->hasIntactSubmittedRoster();
            @endphp

            <div class="card sf-card border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h3 class="h6 sf-heading mb-3 fw-bold">{{ $list->sponsorshipProgram->program_name }}</h3>
                            <div class="small text-secondary">{{ $eligibleItems->count() }} {{ Str::plural('name', $eligibleItems->count()) }} in this batch
                                @if ($excludedCount > 0)
                                    <span class="text-danger ms-1">({{ $excludedCount }} rejected excluded)</span>
                                @endif
                            </div>
                        </div>
                        <x-status-badge :status="$list->status" />
                    </div>

                    <div class="table-responsive">
                        <table class="table sf-table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">Student ID</th>
                                    <th>Student Name</th>
                                    <th>Academic Program</th>
                                    <th>Year Level</th>
                                    <th>Candidate Source</th>
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
                                            @if ($item->is_fixed_list)
                                                <span class="badge bg-purple-subtle text-purple fw-bold">★ Sponsor-proposed fixed list</span>
                                            @else
                                                <span class="badge bg-info-subtle text-info fw-bold">Ordinary applicant</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($item->application)
                                                <x-status-badge :status="$item->application->status" />
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">—</span>
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
                    <h3 class="h6 sf-heading mb-3 fw-bold">Signed Approval Document</h3>

                    @if ($currentApproval?->approval_document_path)
                        <div class="d-flex align-items-center gap-2 mb-3 p-3 border rounded-3 bg-light">
                            <i class="bi bi-file-earmark-check text-success fs-5"></i>
                            <div class="flex-grow-1 small">Document uploaded</div>
                            <a href="{{ route('sponsor.approvals.download', $currentApproval) }}"
                                class="btn btn-sm btn-outline-secondary">View</a>
                        </div>
                    @endif

                    @if ($canDecide)
                    <form method="POST" action="{{ route('sponsor.approvals.store', $list) }}"
                        enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="submission_version" value="{{ $list->submission_version }}">
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
                    @endif
                </div>
            </div>

            @if ($list->sponsorApprovals->isNotEmpty())
                <div class="card sf-card mb-4"><div class="card-body">
                    <h3 class="h6">Original Evidence History</h3>
                    @foreach ($list->sponsorApprovals as $evidence)
                        <div class="small border-top py-2">
                            {{ $evidence->confirmation_status->value }} · submission {{ $evidence->submission_version ?? 'legacy' }}
                            @if ($evidence->approval_document_path)
                                <a href="{{ route('sponsor.approvals.download', $evidence) }}" target="_blank" rel="noopener">View original document</a>
                            @endif
                        </div>
                    @endforeach
                </div></div>
            @endif

            {{-- Confirm list --}}
            <div class="card sf-card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h3 class="h6 sf-heading mb-3 fw-bold">Confirm Beneficiary List</h3>
                    <p class="small text-secondary">Approve or reject every member displayed. Approval returns to FASSG for receipt and explicit Accounting forwarding.</p>

                    @if (!$currentApproval?->approval_document_path)
                        <div class="alert alert-warning small py-2 mb-3">
                            <i class="bi bi-exclamation-triangle me-1"></i> Please upload and save a signed approval
                            document above before you can confirm this list.
                        </div>
                    @endif

                    <form method="POST" action="{{ route('sponsor.approvals.confirm', $list) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="submission_version" value="{{ $list->submission_version }}">
                        <input type="hidden" name="approval_id" value="{{ $currentApproval?->id }}">
                        <button type="submit" class="btn btn-sf-navy w-100 py-2"
                            @disabled(!$canDecide || !$currentApproval?->approval_document_path)>
                            <i class="bi bi-check-circle me-1"></i>
                            {{ $list->status->value === 'Approved' ? 'Already Confirmed' : 'Approve Entire Displayed Batch' }}
                        </button>
                    </form>
                    @if ($canDecide)
                        <form method="POST" action="{{ route('sponsor.approvals.reject', $list) }}" class="mt-3" onsubmit="return confirm('Reject the entire displayed batch?');">
                            @csrf @method('PATCH')
                            <input type="hidden" name="submission_version" value="{{ $list->submission_version }}">
                            <button class="btn btn-outline-danger w-100" type="submit">Reject Entire Displayed Batch</button>
                        </form>
                    @else
                        <p class="small text-secondary mt-3">This record is read-only. Legacy submissions require evidence review before a new decision.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
