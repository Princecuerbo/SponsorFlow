@extends('layouts.app')

@section('title', 'Sponsor Approvals')
@section('eyebrow', 'Sponsor Portal')
@section('page-title', 'Review Beneficiary Lists')

@push('styles')
    <style>
        .btn-navy-primary,
        button.btn-navy-primary {
            background-color: #0F2942 !important;
            border-color: #0F2942 !important;
            color: #ffffff !important;
            font-weight: 600;
        }

        .btn-navy-primary:hover,
        button.btn-navy-primary:hover {
            background-color: #0A1E31 !important;
            border-color: #0A1E31 !important;
            color: #ffffff !important;
        }
    </style>
@endpush

@section('content')
    <div class="mb-4">
        <h1 class="h3 fw-bold mb-1">Sponsor Review &amp; Document Upload Portal</h1>
        <p class="text-secondary mb-0">Upload signed endorsement files to confirm beneficiary lists.</p>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-bottom">
            <h2 class="h5 mb-0 fw-bold">Forwarded Applicant Lists for Approval</h2>
        </div>
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>List / Program</th>
                            <th>Total Students</th>
                            <th>Submitted Status</th>
                            <th>Upload Confirmation File</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($lists as $list)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $list->batch_name }}</div>
                                    <div class="small text-secondary">{{ $list->sponsorshipProgram->program_name }}</div>
                                </td>
                                <td>{{ $list->total_names }} {{ Str::plural('Student', $list->total_names) }}</td>
                                <td><x-status-badge :status="$list->status" /></td>
                                <td>
                                    <form id="upload-form-{{ $list->id }}" method="POST"
                                        action="{{ route('sponsor.approvals.store', $list) }}"
                                        enctype="multipart/form-data">
                                        @csrf
                                        <input type="file" name="approval_document" class="form-control form-control-sm"
                                            accept=".pdf,.jpg,.jpeg,.png" required>
                                    </form>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        <button type="submit" form="upload-form-{{ $list->id }}"
                                            class="btn btn-navy-primary btn-sm">
                                            Upload
                                        </button>
                                        <a href="{{ route('sponsor.lists.show', $list) }}"
                                            class="btn btn-outline-secondary btn-sm">
                                            Review
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-secondary py-4">
                                    No forwarded lists awaiting approval.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
