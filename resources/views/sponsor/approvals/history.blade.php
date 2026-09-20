@extends('layouts.app')

@section('title', 'Approval History')
@section('eyebrow', 'Sponsor Portal')
@section('page-title', 'Approval History')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div>
            <p class="text-uppercase small fw-semibold text-secondary mb-2">Sponsor records</p>
            <h1 class="h2 sf-heading mb-1 fw-bold">Approval History</h1>
            <p class="text-secondary mb-0">Previously finalized applications and beneficiary lists.</p>
        </div>
        <span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200 px-3 py-2">
            {{ $approvals->count() }} {{ Str::plural('finalized record', $approvals->count()) }}
        </span>
    </div>

    <section>
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h2 class="h5 sf-heading mb-1 fw-bold">Confirmed Beneficiary Batches</h2>
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
                                    <div class="small text-secondary">{{ $approval->fixedList->items->reject(fn($item) => $item->application?->status->value === 'Rejected')->count() }} beneficiary(ies)</div>
                                </td>
                                <td>{{ $approval->sponsorshipProgram->program_name }}</td>
                                <td><x-status-badge :status="'Confirmed'" /></td>
                                <td>{{ $approval->updated_at?->format('M d, Y, h:i A') ?? '—' }}</td>
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
                                <td colspan="5" class="text-center text-secondary py-5">No confirmed fixed lists yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white border-top px-4 py-3 d-flex flex-wrap align-items-center justify-content-between gap-2 no-print">
                <span class="small text-secondary">
                    <i class="bi bi-archive me-1"></i><strong>{{ $approvals->count() }}</strong>
                    finalized record{{ $approvals->count() === 1 ? '' : 's' }}
                </span>
                <span class="small text-secondary">Download the signed approval document for each confirmed batch.</span>
            </div>
        </div>
    </section>
@endsection
