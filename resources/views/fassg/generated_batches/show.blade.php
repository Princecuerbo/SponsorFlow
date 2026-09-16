@extends('layouts.app')

@section('title', 'Generated Batch')
@section('eyebrow', 'FASSG Office')
@section('page-title', 'Generated Batch')

@push('styles')
    <style>
        .btn-navy-primary,
        a.btn-navy-primary,
        button.btn-navy-primary {
            background-color: #0F2942 !important;
            border-color: #0F2942 !important;
            color: #ffffff !important;
            font-weight: 600;
            box-shadow: none !important;
            transition: all 0.2s ease-in-out;
        }

        .btn-navy-primary:hover,
        .btn-navy-primary:focus,
        a.btn-navy-primary:hover,
        a.btn-navy-primary:focus,
        button.btn-navy-primary:hover,
        button.btn-navy-primary:focus {
            background-color: #0A1E31 !important;
            border-color: #0A1E31 !important;
            color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(15, 41, 66, 0.15) !important;
        }

        .btn-outline-navy {
            color: #0F2942 !important;
            border-color: #0F2942 !important;
            background-color: transparent !important;
            font-weight: 600;
            transition: all 0.2s ease-in-out;
        }

        .btn-outline-navy:hover,
        .btn-outline-navy:focus {
            color: #ffffff !important;
            background-color: #0F2942 !important;
            border-color: #0F2942 !important;
        }
    </style>
@endpush

@section('content')
    <div class="row g-4 mb-4">
        <div class="col-md-8">
            <div class="card sf-card mb-4 border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3 gap-3">
                        <a href="{{ route('fassg.generated-batches.index') }}"
                            class="btn btn-outline-secondary btn-sm fw-semibold d-inline-flex align-items-center gap-2">
                            <i class="bi bi-arrow-left"></i>Back to Generated Batches
                        </a>
                        <span class="badge bg-secondary-subtle text-secondary px-3 py-2 fw-semibold border border-secondary-subtle">
                            {{ ucfirst($list->status->value ?? $list->status) }} Status
                        </span>
                    </div>
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <span class="badge bg-light text-dark mb-2 border">{{ $list->sponsorshipProgram->program_name }}</span>
                            <h2 class="h4 fw-bold mb-1">
                                {{ $list->batch_name ?: 'Generated Batch #' . $list->id . ' - ' . ($list->sponsorshipProgram->program_name ?? 'Unassigned Program') }}
                            </h2>
                            <p class="text-secondary small mb-0">Total Names: {{ $list->items->count() }}</p>
                        </div>
                    </div>

                    <div class="alert alert-info border-0 mb-0 d-flex align-items-start gap-2 py-2 small">
                        <i class="bi bi-list-check text-primary mt-1"></i>
                        <div>
                            <strong>Generated from Application Queue</strong> — all candidates below were selected from the ranked
                            FASSG applicant queue and are linked to their source applications. Ranked by GWA (best first).
                        </div>
                    </div>
                </div>
            </div>

            <div class="card sf-card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <h3 class="h6 fw-bold mb-0">Ranked Applicants</h3>
                    <span class="small text-secondary"><i class="bi bi-sort-numeric-down me-1"></i>Ranked by GWA (best first)</span>
                </div>
                <div class="table-responsive">
                    <table class="table sf-table mb-0 align-middle" id="generatedBatchTable">
                        <thead>
                            <tr>
                                <th class="ps-4" style="width: 48px;">Rank</th>
                                <th>Student Name</th>
                                <th>Student ID</th>
                                <th>Course &amp; Year</th>
                                <th>Campus</th>
                                <th>GWA</th>
                                <th>SLE-FHE Status</th>
                                <th>Endorsed</th>
                                <th class="text-end pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($list->items as $item)
                                <tr class="{{ $item->is_manually_endorsed ? 'table-success' : '' }}">
                                    <td class="ps-4">
                                        <span
                                            class="d-inline-flex align-items-center justify-content-center rounded-circle bg-cyan-50 text-cyan-700 border border-cyan-200 fw-bold"
                                            style="width: 30px; height: 30px;">{{ $loop->iteration }}</span>
                                    </td>
                                    <td class="fw-semibold">
                                        {{ $item->student_name }}
                                        @if ($item->application_id)
                                            <a href="{{ route('fassg.applications.show', $item->application_id) }}"
                                                class="d-block small text-primary text-decoration-none fw-normal">
                                                <i class="bi bi-arrow-right-circle me-1"></i>View source application
                                            </a>
                                        @endif
                                    </td>
                                    <td class="sf-mono">{{ $item->student_id_number ?: 'N/A' }}</td>
                                    <td>{{ $item->course }} {{ $item->year_level ? "Year {$item->year_level}" : '' }}</td>
                                    <td>{{ $item->campus ?: 'N/A' }}</td>
                                    <td class="fw-semibold">
                                        {{ $item->application?->gpa_submitted !== null ? number_format((float) $item->application->gpa_submitted, 2) : '—' }}
                                    </td>
                                    <td>
                                        <span class="badge {{ $item->is_sle_fhe_verified ? 'bg-cyan-50 text-cyan-700 border border-cyan-200' : 'bg-warning-subtle text-warning-emphasis border border-warning-subtle' }}">
                                            {{ $item->is_sle_fhe_verified ? 'Verified' : 'Pending Check' }}
                                        </span>
                                    </td>
                                    <td>
                                        @if ($item->status === \App\Enums\FixedListItemStatus::Endorsed)
                                            <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle">
                                                Endorsed
                                            </span>
                                        @else
                                            <button type="submit" form="endorse-{{ $item->id }}"
                                                class="btn btn-outline-success btn-sm">
                                                <i class="bi bi-check-lg me-1"></i>Endorse
                                            </button>
                                        @endif
                                    </td>
                                    <td class="text-end pe-4">
                                        @if (!$item->is_sle_fhe_verified && $item->status !== \App\Enums\FixedListItemStatus::Endorsed)
                                            <form method="POST"
                                                action="{{ route('fassg.fixed-lists.items.verify', [$list, $item]) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-outline-navy btn-sm">Verify</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-secondary py-4">No applicants in this generated batch yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @foreach ($list->items as $item)
                    <form method="POST" id="endorse-{{ $item->id }}"
                        action="{{ route('fassg.fixed-lists.items.endorse', [$list, $item]) }}"
                        class="d-none">
                        @csrf
                        @method('PATCH')
                    </form>
                @endforeach
            </div>
        </div>

        <div class="col-md-4">
            @if (in_array($list->status, [\App\Enums\FixedListStatus::Draft, \App\Enums\FixedListStatus::Rejected, \App\Enums\FixedListStatus::Saved], true))
                <div class="card sf-card border-0 shadow-sm">
                    <div class="card-body p-4 text-center">
                        <h3 class="h6 fw-bold mb-2"><i class="bi bi-send me-1"></i>Submit to Sponsor</h3>
                        <p class="text-secondary small mb-3">Once submitted, this batch will be forwarded to the sponsor for
                            review.</p>
                        <form method="POST" action="{{ route('fassg.fixed-lists.submit', $list) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-navy-primary w-100 py-2" @disabled($list->items->isEmpty())
                                onClick="if ({{ $list->items->where('is_sle_fhe_verified', true)->count() }} !== {{ $list->items->count() }}) { alert('Verify all students before forwarding this batch to the sponsor.'); return false; } return confirm('All students are verified. Forward this batch to the sponsor?');">
                                <i class="bi bi-send me-1"></i>Submit Batch
                            </button>
                        </form>
                    </div>
                </div>
            @endif
            @if ($list->status === \App\Enums\FixedListStatus::Approved && blank($list->fassg_assigned_at))
                <div class="card sf-card border-0 shadow-sm">
                    <div class="card-body p-4 text-center">
                        <h3 class="h6 fw-bold mb-2"><i class="bi bi-lock-fill me-1 text-success"></i>Complete FASSG Assignment</h3>
                        <p class="text-secondary small mb-3">Record this batch as FASSG-assigned before Accounting can view its
                            beneficiary payout rows.</p>
                        <form method="POST" action="{{ route('fassg.fixed-lists.assign-fassg', $list) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-success w-100 py-2"
                                onClick="return confirm('Record this batch as FASSG-assigned? Verified students become visible to Accounting.');">
                                <i class="bi bi-check-lg me-1"></i>Complete FASSG Assignment
                            </button>
                        </form>
                    </div>
                </div>
            @elseif ($list->status === \App\Enums\FixedListStatus::Approved && $list->fassg_assigned_at !== null)
                <div class="card sf-card border-0 shadow-sm">
                    <div class="card-body p-4 text-center">
                        <h3 class="h6 fw-bold mb-2 text-success"><i class="bi bi-lock-fill me-1"></i>FASSG Assigned</h3>
                        <p class="text-secondary small mb-0">Assigned {{ $list->fassg_assigned_at?->format('M d, Y h:i A') }}.
                            Verified students are visible to Accounting.</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection