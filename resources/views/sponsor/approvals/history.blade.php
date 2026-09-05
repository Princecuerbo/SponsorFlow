@extends('layouts.app')

@section('title', 'Approval History')
@section('eyebrow', 'Sponsor Portal')
@section('page-title', 'Approval History')

@push('styles')
    <style>
        .btn-navy-primary,
        a.btn-navy-primary {
            background-color: #0F2942 !important;
            border-color: #0F2942 !important;
            color: #fff !important;
            font-weight: 600;
        }

        .btn-navy-primary:hover,
        .btn-navy-primary:focus {
            background-color: #0A1E31 !important;
            border-color: #0A1E31 !important;
            color: #fff !important;
        }
    </style>
@endpush

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div>
            <p class="text-uppercase small fw-semibold text-secondary mb-2">Sponsor records</p>
            <h1 class="h2 sf-heading mb-1 fw-bold">Approval History</h1>
            <p class="text-secondary mb-0">Previously finalized applications and beneficiary lists.</p>
        </div>
        <span class="badge bg-success-subtle text-success-emphasis px-3 py-2">
            {{ $applications->count() + $approvals->count() }} finalized records
        </span>
    </div>

    <section class="mb-5">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h2 class="h5 sf-heading mb-1 fw-bold">Approved Individual Applications</h2>
                <p class="small text-secondary mb-0">FASSG-verified students confirmed by your organization.</p>
            </div>
            <i class="bi bi-person-check fs-3" style="color: #0F2942;"></i>
        </div>

        <div class="card sf-card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table sf-table mb-0 align-middle">
                    <thead>
                        <tr>
                            <th class="ps-4">Student</th>
                            <th>Program</th>
                            <th>Status</th>
                            <th>Approved</th>
                            <th class="text-end pe-4">Endorsement</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($applications as $application)
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-semibold">{{ $application->studentProfile->user->name }}</div>
                                    <div class="small text-secondary sf-mono">
                                        {{ $application->studentProfile->student_id_number }}</div>
                                </td>
                                <td>{{ $application->sponsorshipProgram->program_name }}</td>
                                <td><span class="badge bg-success-subtle text-success-emphasis">Approved</span></td>
                                <td>{{ $application->approved_at?->format('M d, Y') ?? ($application->updated_at?->format('M d, Y') ?? '—') }}
                                </td>
                                <td class="text-end pe-4">
                                    @if ($application->sponsor_approval_path)
                                        <a href="{{ route('sponsor.applicants.approval-document', $application) }}"
                                            target="_blank" rel="noopener" class="btn btn-sm btn-navy-primary">
                                            <i class="bi bi-file-earmark-text me-1"></i>View File
                                        </a>
                                    @else
                                        <span class="small text-secondary">Not available</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-secondary py-5">No approved individual
                                    applications yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section>
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h2 class="h5 sf-heading mb-1 fw-bold">Confirmed Fixed Lists</h2>
                <p class="small text-secondary mb-0">Beneficiary lists confirmed with sponsor endorsement documents.</p>
            </div>
            <i class="bi bi-people fs-3" style="color: #0F2942;"></i>
        </div>

        <div class="card sf-card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table sf-table mb-0 align-middle">
                    <thead>
                        <tr>
                            <th class="ps-4">Batch</th>
                            <th>Program</th>
                            <th>Status</th>
                            <th>Confirmed</th>
                            <th class="text-end pe-4">Document</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($approvals as $approval)
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-semibold">{{ $approval->fixedList->batch_name }}</div>
                                    <div class="small text-secondary">{{ $approval->fixedList->total_names }} beneficiaries
                                    </div>
                                </td>
                                <td>{{ $approval->sponsorshipProgram->program_name }}</td>
                                <td><span class="badge bg-success-subtle text-success-emphasis">Confirmed</span></td>
                                <td>{{ $approval->updated_at?->format('M d, Y') ?? '—' }}</td>
                                <td class="text-end pe-4">
                                    @if ($approval->approval_document_path)
                                        <a href="{{ route('sponsor.approvals.download', $approval) }}" target="_blank"
                                            rel="noopener" class="btn btn-sm btn-navy-primary">
                                            <i class="bi bi-file-earmark-text me-1"></i>View File
                                        </a>
                                    @else
                                        <span class="small text-secondary">Not available</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-secondary py-5">No confirmed fixed lists yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection
