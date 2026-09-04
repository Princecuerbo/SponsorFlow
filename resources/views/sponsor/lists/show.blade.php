@extends('layouts.app')

@section('title', $list->batch_name)
@section('eyebrow', 'Sponsor Portal · Forwarded Applicants')
@section('page-title', $list->batch_name)

@section('content')

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card sf-card">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h2 class="h6 sf-heading mb-1">{{ $list->sponsorshipProgram->program_name }}</h2>
                                <div class="small text-secondary">{{ $list->total_names }} names in this batch</div>
                        </div>
                        <x-status-badge :status="$list->status" />
                    </div>

                    <div class="table-responsive">
                        <table class="table sf-table mb-0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Student ID</th>
                                    <th>Course</th>
                                    <th>Year</th>
                                    <th>Eligibility</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($list->items as $item)
                                    <tr>
                                        <td>{{ $item->student_name }}</td>
                                        <td class="sf-mono text-secondary">{{ $item->student_id_number }}</td>
                                        <td class="text-secondary">{{ $item->course }}</td>
                                        <td class="text-secondary">{{ $item->year_level }}</td>
                                        <td><x-status-badge :status="$item->status" /></td>
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
            <div class="card sf-card mb-4">
                <div class="card-body p-4">
                    <h2 class="h6 sf-heading mb-3">Signed Approval Document</h2>

                    @if ($list->latestApproval?->approval_document_path)
                        <div class="d-flex align-items-center gap-2 mb-3 p-3 border rounded-3 bg-light">
                            <i class="bi bi-file-earmark-check text-success fs-5"></i>
                            <div class="flex-grow-1 small">Document uploaded</div>
                            <a href="{{ route('sponsor.approvals.download', $list->latestApproval) }}" class="btn btn-sm btn-outline-secondary">View</a>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('sponsor.approvals.store', $list) }}" enctype="multipart/form-data">
                        @csrf
                        <label for="approval_document" class="d-block border border-2 rounded-3 text-center p-4 bg-light mb-3"
                               style="cursor:pointer; border-style:dashed !important;">
                            <i class="bi bi-cloud-upload fs-3 text-secondary d-block mb-2"></i>
                            <span class="small fw-semibold d-block">Click to upload signed approval</span>
                            <span class="small text-secondary" id="approvalFileName">PDF, JPG, or PNG</span>
                            <input type="file" name="approval_document" id="approval_document" class="d-none" accept=".pdf,.jpg,.jpeg,.png"
                                   onchange="document.getElementById('approvalFileName').textContent = this.files[0]?.name || 'PDF, JPG, or PNG'">
                        </label>
                        <button type="submit" class="btn btn-sf-navy w-100"><i class="bi bi-upload me-1"></i>Upload Document</button>
                    </form>
                </div>
            </div>

            {{-- Confirm list --}}
            <div class="card sf-card">
                <div class="card-body p-4">
                    <h2 class="h6 sf-heading mb-2">Confirm Beneficiary List</h2>
                    <p class="small text-secondary">Confirming finalizes this batch as approved beneficiaries. Accounting will be able to view them for tuition adjustment.</p>

                    <form method="POST" action="{{ route('sponsor.approvals.confirm', $list) }}">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn btn-sf-gold w-100" {{ $list->status->value === 'Approved' ? 'disabled' : '' }}>
                            <i class="bi bi-check-circle me-1"></i>
                            {{ $list->status->value === 'Approved' ? 'Already Confirmed' : 'Confirm Final List' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection
