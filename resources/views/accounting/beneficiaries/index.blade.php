@extends('layouts.app')

@section('title', 'Confirmed Beneficiaries')
@section('eyebrow', 'Accounting Office')
@section('page-title', 'Confirmed Beneficiaries')

@push('styles')
    <style>
        .dropdown-item.active-filter {
            background-color: #ffffff !important;
            color: var(--sf-navy, #1e3a8a) !important;
            font-weight: 600;
        }

        .dropdown-item:hover,
        .dropdown-item:focus {
            background-color: rgba(30, 58, 138, 0.08) !important;
            color: var(--sf-navy, #1e3a8a) !important;
        }

        .pagination {
            margin-bottom: 0 !important;
            justify-content: center;
        }

        .pagination .page-item.active .page-link {
            background-color: var(--sf-navy, #1e3a8a) !important;
            border-color: var(--sf-navy, #1e3a8a) !important;
            color: #ffffff !important;
        }

        .pagination .page-link {
            color: var(--sf-navy, #1e3a8a);
        }

        .pagination .page-link:hover {
            color: var(--sf-navy-deep, #172554);
            background-color: rgba(30, 58, 138, 0.08);
        }

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
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid px-4 py-4">
        {{-- Top Header Action Bar --}}
        <div class="d-flex align-items-center justify-content-between mb-4 gap-3">
            <div>
                <span class="text-uppercase fw-bold text-muted extra-small tracking-wider d-block mb-1">
                    Tuition Adjustment Reference
                </span>
                <h3 class="fw-bold mb-0 text-dark">Approved Beneficiaries</h3>
                <p class="text-muted small mb-0">Read-only sponsor-confirmed and approved SLE-FHE beneficiary records.</p>
            </div>
            <div class="d-flex align-items-center gap-2 no-print">
                <button type="button" onclick="window.print()"
                    class="btn btn-outline-secondary fw-semibold d-inline-flex align-items-center gap-2">
                    <i class="bi bi-printer"></i> Print Master List
                </button>
                <a href="{{ route('accounting.beneficiaries.export') }}"
                    class="btn btn-sf-navy fw-semibold px-3 d-inline-flex align-items-center gap-2 no-print">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export CSV / Excel
                </a>
                <div class="sf-stat-card px-3 py-2 bg-white rounded-3 border shadow-sm ms-2">
                    <div class="h4 mb-0 sf-heading fw-bold text-dark">
                        {{ number_format($totalApproved ?? (is_countable($beneficiaries) ? count($beneficiaries) : 0)) }}
                    </div>
                    <div class="extra-small text-secondary">Approved records</div>
                </div>
            </div>
        </div>

        {{-- Notice Banner --}}
        <div class="alert border-0 border-start border-4 rounded-3 p-3 mb-4 no-print d-flex align-items-center gap-2"
            style="background-color: rgba(30, 58, 138, 0.05); color: #1e3a8a; border-color: #1e3a8a !important;">
            <i class="bi bi-lock-fill fs-5"></i>
            <span class="small fw-semibold">Accounting access is strictly read-only. Approval, editing, and deletion are
                handled by FASSG and sponsors.</span>
        </div>

        {{-- Filter Toolbar & Table Container Card --}}
        <div class="card sf-card border-0 shadow-sm rounded-3 overflow-hidden mb-4">
            <div class="card-header bg-white border-bottom p-3 no-print">
                <form id="beneficiaryFilterForm" method="GET" action="{{ route('accounting.beneficiaries.index') }}"
                    class="row g-2 align-items-center">
                    {{-- Custom Program Filter Dropdown --}}
                    <div class="col-md-3">
                        <div class="dropdown program-filter-dropdown">
                            <button type="button"
                                class="form-select bg-white text-start d-flex align-items-center justify-content-between"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <span>
                                    @if (request('academic_program_id') && isset($academicPrograms))
                                        {{ $academicPrograms->firstWhere('program_id', request('academic_program_id'))->name ?? 'All academic programs' }}
                                    @else
                                        All academic programs
                                    @endif
                                </span>
                            </button>
                            <ul class="dropdown-menu shadow-sm border w-100 mt-1 p-1">
                                <li>
                                    <button type="button"
                                        class="dropdown-item rounded py-2 {{ !request('academic_program_id') ? 'active-filter' : '' }}"
                                        onclick="setProgramFilter('', 'All academic programs')">
                                        All academic programs
                                    </button>
                                </li>
                                @if (isset($academicPrograms))
                                    @foreach ($academicPrograms as $academicProgram)
                                        <li>
                                            <button type="button"
                                                class="dropdown-item rounded py-2 {{ (int) request('academic_program_id') === (int) $academicProgram->program_id ? 'active-filter' : '' }}"
                                                onclick="setProgramFilter('{{ $academicProgram->program_id }}', '{{ $academicProgram->name }}')">
                                                {{ $academicProgram->name }}
                                            </button>
                                        </li>
                                    @endforeach
                                @endif
                            </ul>
                            <input type="hidden" name="academic_program_id" id="academicProgramInput"
                                value="{{ request('academic_program_id') }}">
                        </div>
                    </div>

                    {{-- Search Input --}}
                    <div class="col-md-8">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i
                                    class="bi bi-search text-secondary"></i></span>
                            <input type="search" name="q" value="{{ request('q') }}"
                                class="form-control border-start-0 ps-0"
                                placeholder="Search student, ID, course, program, or sponsor"
                                oninput="clearTimeout(window.searchTimer); window.searchTimer = setTimeout(() => this.form.submit(), 600)">
                        </div>
                    </div>

                    <div class="col-md-1 text-end">
                        <a href="{{ route('accounting.beneficiaries.index') }}" class="btn btn-outline-secondary w-100"
                            title="Reset Filters">Reset</a>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table sf-table table-hover align-middle mb-0">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-4">Student ID Number</th>
                            <th>Full Name</th>
                            <th>Course & Year</th>
                            <th>Program & Category</th>
                            <th>Sponsor / Organization</th>
                            <th>GWA / GPA</th>
                            <th>Address & Rurality</th>
                            <th>Date Approved</th>
                            <th class="text-end pe-4 no-print">Reference</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        @forelse ($beneficiaries as $beneficiary)
                            <tr>
                                <td class="ps-4 font-monospace small" style="color: #475569;">
                                    {{ $beneficiary['student_id_number'] ?? $beneficiary['student_id'] }}
                                </td>
                                <td class="fw-semibold text-dark">
                                    {{ $beneficiary['student_name'] ?? $beneficiary['name'] }}
                                </td>
                                <td>
                                    {{ $beneficiary['course'] ?? '—' }}
                                    @if (isset($beneficiary['year_level']))
                                        <div class="small text-secondary">Year {{ $beneficiary['year_level'] }}</div>
                                    @endif
                                </td>
                                <td>
                                    {{ $beneficiary['program'] ?? '—' }}
                                    @if (isset($beneficiary['category']))
                                        <div class="small text-secondary">{{ $beneficiary['category'] }}</div>
                                    @endif
                                </td>
                                <td>
                                    {{ $beneficiary['sponsor'] ?? '—' }}
                                    <div class="small text-secondary">
                                        {{ !empty($beneficiary['billing_contact']) ? $beneficiary['billing_contact'] : 'Billing contact unavailable' }}
                                    </div>
                                </td>
                                <td>
                                    {{ isset($beneficiary['gwa']) && $beneficiary['gwa'] !== null ? number_format((float) $beneficiary['gwa'], 2) : '—' }}
                                </td>
                                <td>
                                    {{ $beneficiary['address'] ?: 'Fixed-list record' }}
                                    @if (!empty($beneficiary['rurality']))
                                        <div>
                                            <span
                                                class="badge {{ $beneficiary['rurality'] === 'Rural' ? 'bg-info-subtle text-info-emphasis' : 'bg-secondary-subtle text-secondary-emphasis' }}">
                                                {{ $beneficiary['rurality'] }}
                                            </span>
                                        </div>
                                    @endif
                                </td>
                                <td class="text-secondary small">
                                    {{ isset($beneficiary['approved_at']) && $beneficiary['approved_at'] ? \Carbon\Carbon::parse($beneficiary['approved_at'])->format('M d, Y') : '—' }}
                                </td>
                                <td class="text-end pe-4 no-print">
                                    @if (!empty($beneficiary['application_id']))
                                        <a href="{{ route('accounting.beneficiaries.show', $beneficiary['application_id']) }}"
                                            class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-eye me-1"></i>View Reference
                                        </a>
                                    @else
                                        <span class="small text-secondary">Fixed list</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-secondary py-5">
                                    <i class="bi bi-inbox display-6 d-block mb-2 opacity-50"></i>
                                    <div class="fw-semibold text-dark">No approved beneficiaries found.</div>
                                    <div class="small text-muted">This list populates automatically once sponsors confirm
                                        their beneficiary lists.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if (method_exists($beneficiaries, 'links'))
                <div
                    class="card-footer bg-white border-top d-flex flex-column align-items-center justify-content-center py-3 px-4 gap-2 text-center no-print">
                    <div class="pagination-sm mb-1">
                        {{ $beneficiaries->links('pagination::bootstrap-4') }}
                    </div>
                    <div class="small text-muted">
                        Showing <span class="fw-semibold text-dark">{{ $beneficiaries->firstItem() ?? 0 }}</span> to
                        <span class="fw-semibold text-dark">{{ $beneficiaries->lastItem() ?? 0 }}</span> of
                        <span class="fw-semibold text-dark">{{ $beneficiaries->total() }}</span> results
                    </div>
                </div>
            @endif
        </div>
    </div>

    <script>
        function setProgramFilter(value, label) {
            document.getElementById('academicProgramInput').value = value;
            document.getElementById('beneficiaryFilterForm').submit();
        }
    </script>
@endsection
