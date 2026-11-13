@extends('layouts.app')

@section('title', 'Approval History')
@section('eyebrow', 'Sponsor Portal · Records')
@section('page-title', 'Approval History')
@section('subtitle', 'Recorded Sponsor decisions, confirmation evidence, and retained historical approvals.')

@section('header-actions')
    <span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200 px-3 py-2">
        {{ $approvals->count() }} {{ Str::plural('decision record', $approvals->count()) }}
    </span>
@endsection

@section('content')
    <section>
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h3 class="h6 sf-heading mb-3 fw-bold">Sponsor Batch Decisions</h3>
                <p class="small text-secondary mb-0">Whole-batch approvals and rejections, with original confirmation documents retained.</p>
                <p class="small text-secondary mt-2 mb-0">Sponsor confirmation alone does not grant Accounting access. FASSG must record receipt and separately forward eligible approved beneficiaries.</p>
            </div>
            <i class="bi bi-people fs-3" style="color: #0F2942;"></i>
        </div>

        <div class="card sf-card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table sf-table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Batch</th>
                            <th>Program</th>
                            <th>Sponsor Decision</th>
                            <th>Decision Date</th>
                            <th class="text-end pe-4">Document</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($approvals as $approval)
                            <tr>
                                <td class="ps-4">
                                    @php($approvalBatch = $approval->generatedBatch ?? $approval->fixedList)
                                    <div class="fw-semibold">{{ $approvalBatch?->batch_name }} · {{ $approval->submission_version ? 'Submission '.$approval->submission_version : 'Legacy record' }}</div>
                                    <div class="small text-secondary">{{ $approvalBatch?->items?->count() ?? 0 }} roster member(s)</div>
                                </td>
                                <td>{{ $approval->sponsorshipProgram->program_name }}</td>
                                <td>
                                    @if ($approval->confirmation_status === \App\Enums\ConfirmationStatus::Confirmed)
                                        <span class="badge px-2 py-1 fw-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <i class="bi bi-check-circle-fill me-1" aria-hidden="true"></i>Sponsor Confirmed
                                        </span>
                                    @else
                                        <x-status-badge :status="$approval->confirmation_status" />
                                    @endif
                                </td>
                                <td>{{ $approval->decided_at?->format('M d, Y, h:i A') ?? '—' }}</td>
                                <td class="text-end pe-4">
                                    <div class="d-inline-flex flex-nowrap gap-2 align-items-center justify-content-end">
                                        @if ($approval->approval_document_path)
                                            <a href="{{ route('sponsor.approvals.download', $approval) }}"
                                                target="_blank" rel="noopener" class="btn btn-sm btn-sf-navy">
                                                <i class="bi bi-file-earmark-text me-1"></i>View File
                                            </a>
                                        @else
                                            <span class="small text-secondary">Not available</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-secondary py-5">No Sponsor decisions recorded.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white border-top px-4 py-3 d-flex flex-wrap align-items-center justify-content-between gap-2 no-print">
                <span class="small text-secondary">
                    <i class="bi bi-archive me-1"></i><strong>{{ $approvals->count() }}</strong>
                    decision record{{ $approvals->count() === 1 ? '' : 's' }}
                </span>
                <span class="small text-secondary">View the original confirmation document where available.</span>
            </div>
        </div>
    </section>
    @php($legacyApplications = $approvedApplications->filter(fn ($application) => filled($application->sponsor_approval_path)))
    @if ($legacyApplications->isNotEmpty())
        <section class="card sf-card mt-4">
            <div class="card-body">
                <h3 class="h6">Historical Individual Approvals</h3>
                <p class="small text-secondary">Original individual approval records and documents are retained. They do not establish a new FASSG Accounting handoff.</p>
                @foreach ($legacyApplications as $application)
                    <div class="d-flex justify-content-between border-top py-2 gap-3">
                        <span>{{ $application->studentProfile?->user?->name }} · {{ $application->sponsorshipProgram?->program_name }} · {{ $application->status?->value }}</span>
                        <a href="{{ route('sponsor.applicants.approval-document', $application) }}" target="_blank" rel="noopener">Original approval document</a>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
@endsection
