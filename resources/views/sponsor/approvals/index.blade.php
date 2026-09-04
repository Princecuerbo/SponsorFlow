@extends('layouts.app')

@section('title', 'Approval History')
@section('eyebrow', 'Sponsor Portal')
@section('page-title', 'Approval History')

@section('content')
    <div class="mb-4">
        <p class="text-uppercase small fw-semibold text-success mb-2">Sponsor records</p>
        <h1 class="h2 sf-heading mb-1">Approval History</h1>
        <p class="text-secondary mb-0">Confirmed applications and fixed-list approvals submitted by your organization.</p>
    </div>

    <section class="mb-5">
        <h2 class="h5 sf-heading mb-3">Confirmed Applications</h2>
        <div class="card sf-card">
            <div class="table-responsive">
                <table class="table sf-table mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Student</th>
                            <th>Program</th>
                            <th>Status</th>
                            <th>Confirmed</th>
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
                                <td><x-status-badge :status="$application->status" /></td>
                                <td>
                                    @if ($application->status === \App\Enums\ApplicationStatus::Approved)
                                        <div>
                                            {{ $application->updated_at?->format('M d, Y') ?? ($application->approved_at?->format('M d, Y') ?? '—') }}
                                        </div>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    @if ($application->sponsor_approval_path)
                                        <a href="{{ route('sponsor.applicants.approval-document', $application) }}"
                                            target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary"><i
                                                class="bi bi-eye me-1"></i>View File</a>
                                    @else
                                        <span class="small text-secondary">Not available</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-secondary py-5">No individual application
                                    approvals yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section>
        <h2 class="h5 sf-heading mb-3">Fixed-List Approvals</h2>
        <div class="card sf-card">
            <div class="table-responsive">
                <table class="table sf-table mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Batch</th>
                            <th>Program</th>
                            <th>Status</th>
                            <th>Submitted</th>
                            <th class="text-end pe-4">Document</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($approvals as $approval)
                            <tr>
                                <td class="ps-4 fw-semibold">{{ $approval->fixedList->batch_name }}</td>
                                <td>{{ $approval->sponsorshipProgram->program_name }}</td>
                                <td><x-status-badge :status="$approval->confirmation_status" /></td>
                                <td>{{ $approval->created_at?->format('M d, Y') ?? '—' }}</td>
                                <td class="text-end pe-4"><a href="{{ route('sponsor.approvals.download', $approval) }}"
                                        target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary"><i
                                            class="bi bi-eye me-1"></i>View File</a></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-secondary py-5">No fixed-list approvals yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection
