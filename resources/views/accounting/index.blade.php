@extends('layouts.app')

@section('title', 'Beneficiary Reference')
@section('eyebrow', 'Accounting Office')
@section('page-title', 'Approved Beneficiaries')

@push('styles')
    <style>
        @media print {

            .sf-navbar,
            .no-print {
                display: none !important;
            }

            .sf-content {
                padding: 0 !important;
            }

            .sf-card {
                box-shadow: none !important;
                border: 1px solid #dee2e6 !important;
            }

            .accounting-reference {
                font-size: 10pt;
            }
        }
    </style>
@endpush

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-4 accounting-reference gap-3">
        <div><span class="text-uppercase fw-bold text-muted extra-small tracking-wider d-block mb-1">Tuition Adjustment
                Reference</span>
            <h3 class="fw-bold mb-0">Approved Beneficiaries</h3>
            <p class="text-muted small mb-0">Read-only sponsor-confirmed and approved SLE-FHE beneficiary records.</p>
        </div>
        <div class="d-flex align-items-center gap-2 no-print"><button type="button" onclick="window.print()"
                class="btn btn-outline-secondary fw-semibold d-inline-flex align-items-center gap-2"><i
                    class="bi bi-printer"></i>Print Master List</button><a
                href="{{ route('accounting.beneficiaries.export') }}"
                class="btn btn-warning fw-semibold d-inline-flex align-items-center gap-2"><i
                    class="bi bi-download"></i>Export CSV / Excel</a>
            <div class="sf-stat-card px-3 py-2">
                <div class="h4 mb-0 sf-heading">{{ number_format($totalApproved) }}</div>
                <div class="small text-secondary">Approved records</div>
            </div>
        </div>
    </div>

    <div class="sf-readonly-banner d-flex align-items-center gap-3 mb-4 no-print"><i class="bi bi-lock fs-5"></i>
        <div class="small fw-semibold">Accounting access is strictly read-only. Approval, editing, and deletion are handled
            by FASSG and sponsors.</div>
    </div>

    <div class="card sf-card mb-4 no-print">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('accounting.beneficiaries.index') }}" class="row g-2">
                <div class="col-md-12">
                    <div class="input-group"><span class="input-group-text bg-white border-end-0"><i
                                class="bi bi-search text-secondary"></i></span><input
                            class="form-control border-start-0 ps-0" type="search" name="q"
                            value="{{ request('q') }}" placeholder="Search student, ID, course, program, or sponsor"
                            oninput="clearTimeout(window.searchTimer); window.searchTimer = setTimeout(() => this.form.submit(), 600)">
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card sf-card accounting-reference">
        <div class="table-responsive">
            <table class="table sf-table mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Student ID Number</th>
                        <th>Full Name</th>
                        <th>Course &amp; Year</th>
                        <th>Program &amp; Category</th>
                        <th>Sponsor / Organization</th>
                        <th>GWA / GPA</th>
                        <th>Address &amp; Rurality</th>
                        <th>Date Approved</th>
                        <th class="text-end pe-4 no-print">Reference</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($beneficiaries as $beneficiary)
                        <tr>
                            <td class="ps-4 sf-mono">{{ $beneficiary['student_id_number'] }}</td>
                            <td class="fw-semibold">{{ $beneficiary['student_name'] }}</td>
                            <td>{{ $beneficiary['course'] }}<div class="small text-secondary">Year
                                    {{ $beneficiary['year_level'] }}</div>
                            </td>
                            <td>{{ $beneficiary['program'] }}<div class="small text-secondary">
                                    {{ $beneficiary['category'] }}</div>
                            </td>
                            <td>{{ $beneficiary['sponsor'] }}<div class="small text-secondary">
                                    {{ $beneficiary['billing_contact'] ?: 'Billing contact unavailable' }}</div>
                            </td>
                            <td>{{ $beneficiary['gwa'] !== null ? number_format((float) $beneficiary['gwa'], 2) : '—' }}
                            </td>
                            <td>{{ $beneficiary['address'] ?: 'Fixed-list record' }}@if ($beneficiary['rurality'])
                                    <div><span
                                            class="badge {{ $beneficiary['rurality'] === 'Rural' ? 'bg-info-subtle text-info-emphasis' : 'bg-secondary-subtle text-secondary-emphasis' }}">{{ $beneficiary['rurality'] }}</span>
                                    </div>
                                @endif
                            </td>
                            <td>{{ $beneficiary['approved_at']?->format('M d, Y') ?? '—' }}</td>
                            <td class="text-end pe-4 no-print">
                                @if ($beneficiary['application_id'])
                                    <a href="{{ route('accounting.beneficiaries.show', $beneficiary['application_id']) }}"
                                        class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye me-1"></i>View
                                    Reference</a>@else<span class="small text-secondary">Fixed list</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-secondary py-5"><i
                                        class="bi bi-inbox fs-2 d-block mb-2"></i>No approved beneficiaries found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endsection
