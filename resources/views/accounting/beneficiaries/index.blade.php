@extends('layouts.app')

@section('title', 'Confirmed Beneficiaries')
@section('eyebrow', 'Accounting Office')
@section('page-title', 'Confirmed Beneficiaries')

@section('content')

    <div class="d-flex align-items-center justify-content-between mb-4 gap-3">
        <div>
            <span class="text-uppercase fw-bold text-muted extra-small tracking-wider d-block mb-1">Tuition Adjustment
                Reference</span>
            <h3 class="fw-bold mb-0">Approved Beneficiaries</h3>
            <p class="text-muted small mb-0">Read-only sponsor-confirmed and approved SLE-FHE beneficiary records.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" onclick="window.print()"
                class="btn btn-outline-secondary fw-semibold d-inline-flex align-items-center gap-2 no-print">
                <i class="bi bi-printer"></i>Print Master List
            </button>
            <a href="{{ route('accounting.beneficiaries.export') }}"
                class="btn btn-warning fw-semibold d-inline-flex align-items-center gap-2 no-print">
                <i class="bi bi-download"></i>Export CSV / Excel
            </a>
        </div>
    </div>

    <div class="sf-readonly-banner d-flex align-items-center gap-3 mb-4">
        <i class="bi bi-eye fs-5"></i>
        <div class="small fw-semibold">
            Read-Only View: Confirmed &amp; Approved Beneficiaries for Tuition Adjustment Reference
        </div>
    </div>

    <div class="card sf-card mb-4 no-print">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('accounting.beneficiaries.index') }}" class="row g-2">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i
                                class="bi bi-search text-secondary"></i></span>
                        <input type="text" name="q" value="{{ request('q') }}"
                            class="form-control border-start-0 ps-0" placeholder="Search by student ID or name…"
                            oninput="clearTimeout(window.searchTimer); window.searchTimer = setTimeout(() => this.form.submit(), 600)">
                    </div>
                </div>
                <div class="col-md-7"></div>
            </form>
        </div>
    </div>

    @if ($beneficiaries->isEmpty())
        <div class="card sf-card">
            <div class="sf-empty-state">
                <i class="bi bi-cash-coin"></i>
                <div class="fw-semibold">No confirmed beneficiaries yet</div>
                <div class="small">This list populates automatically once sponsors confirm their beneficiary lists.</div>
            </div>
        </div>
    @else
        <div class="card sf-card">
            <div class="table-responsive">
                <table class="table sf-table mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Student ID</th>
                            <th>Full Name</th>
                            <th>Program</th>
                            <th>Sponsor</th>
                            <th>Date Approved</th>
                            <th class="pe-4">Billing Status</th>
                            <th class="pe-4">Reference</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($beneficiaries as $b)
                            <tr>
                                <td class="ps-4 sf-mono">{{ $b['student_id_number'] }}</td>
                                <td class="fw-semibold">{{ $b['student_name'] }}</td>
                                <td class="text-secondary">{{ $b['program'] }}</td>
                                <td class="text-secondary">{{ $b['sponsor'] }}</td>
                                <td class="text-secondary">{{ $b['approved_at']?->format('M d, Y') ?? '—' }}</td>
                                <td class="pe-4">
                                    <span class="badge bg-success-subtle text-success-emphasis">
                                        {{ $b['billing_status'] }}
                                    </span>
                                </td>
                                <td class="pe-4">
                                    @if ($b['document_url'] ?? null)
                                        <a href="{{ $b['document_url'] }}" target="_blank" rel="noopener"
                                            class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye me-1"></i>View Reference
                                        </a>
                                    @else
                                        <span class="text-secondary small">No document</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    @endif

@endsection
