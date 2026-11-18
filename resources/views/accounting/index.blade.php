@extends('layouts.app')

@section('title', 'Forwarded Approved Beneficiaries')
@section('eyebrow', 'Accounting Office · Beneficiary Reference')
@section('page-title', 'Forwarded Approved Beneficiaries')
@section('subtitle')
    <span class="accounting-original-copy">Read-only reference for SLE-FHE-verified, individually Approved/Ongoing beneficiaries with Sponsor confirmation and explicit FASSG forwarding.</span>
    <span class="accounting-desktop-copy d-none">View SLE-FHE-verified, Sponsor-approved beneficiaries explicitly forwarded by FASSG.</span>
@endsection

@section('header-actions')
    <button type="button" onclick="window.print()"
        class="btn btn-outline-secondary fw-semibold d-inline-flex align-items-center gap-2 no-print accounting-print">
        <i class="bi bi-printer" aria-hidden="true"></i> Print Master List
    </button>
    <a href="{{ route('accounting.beneficiaries.export', request()->only(['q', 'sponsorship_program_id', 'academic_program_id'])) }}"
        class="btn btn-sf-navy fw-semibold px-3 d-inline-flex align-items-center gap-2 no-print accounting-export">
        <i class="bi bi-file-earmark-spreadsheet me-1 accounting-original-copy" aria-hidden="true"></i><i class="bi bi-download accounting-desktop-copy d-none" aria-hidden="true"></i> Export CSV
    </a>
    <div class="sf-stat-card px-3 py-2 ms-2 accounting-record-count">
        <div class="h4 mb-0 sf-heading fw-bold">{{ number_format($totalApproved) }}</div>
        <div class="small text-secondary accounting-original-copy">Matching forwarded records</div>
        <span class="accounting-desktop-copy d-none">matching {{ $totalApproved === 1 ? 'record' : 'records' }}</span>
    </div>
@endsection

@push('styles')
    @include('accounting.partials.desktop-styles')
    <style>
        .dropdown-item.active-filter {
            background-color: #ffffff !important;
            color: var(--sf-navy) !important;
            font-weight: 600;
        }

        .dropdown-item:hover,
        .dropdown-item:focus {
            background-color: rgba(15, 37, 55, 0.08) !important;
            color: var(--sf-navy) !important;
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

            .accounting-reference {
                font-size: 10pt;
            }
        }
    </style>
@endpush

@section('content')
    <div class="sf-readonly-banner d-flex align-items-center gap-3 mb-4 no-print accounting-notice">
        <i class="bi bi-lock fs-5 accounting-original-copy" aria-hidden="true"></i>
        <div class="small fw-semibold accounting-original-copy">Accounting can only view, print, and export eligible forwarded records.
            Sponsors decide on submitted batches. FASSG records Sponsor decisions and explicitly forwards eligible approved beneficiaries.</div>
        <i class="bi bi-shield-lock accounting-desktop-copy d-none" aria-hidden="true"></i>
        <span class="accounting-desktop-copy d-none">Only eligible, Sponsor-approved beneficiaries explicitly forwarded by FASSG are shown. Accounting may view, print, and export these records.</span>
    </div>

    <div class="card sf-card mb-4 no-print accounting-filters">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('accounting.beneficiaries.index') }}" class="row g-2">
                <div class="col-md-3">
                    <label for="accounting-program-filter" class="accounting-filter-label d-none">Sponsorship Program</label>
                    <select id="accounting-program-filter" name="sponsorship_program_id" class="form-select" onchange="this.form.submit()">
                        <option value="">All Sponsorship Programs</option>
                        @foreach ($programs as $program)
                            <option value="{{ $program->id }}" @selected((int) request('sponsorship_program_id') === (int) $program->id)>
                                {{ $program->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="accounting-academic-filter" class="accounting-filter-label d-none">Academic Program</label>
                    <select id="accounting-academic-filter" name="academic_program_id" class="form-select" onchange="this.form.submit()">
                        <option value="">All Academic Programs</option>
                        @foreach ($academicPrograms as $academicProgram)
                            <option value="{{ $academicProgram->program_id }}" @selected((int) request('academic_program_id') === (int) $academicProgram->program_id)>
                                {{ $academicProgram->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="accounting-search" class="accounting-filter-label d-none">Search beneficiaries</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="bi bi-search text-secondary" aria-hidden="true"></i>
                        </span>
                        <input id="accounting-search" class="form-control border-start-0 ps-0" type="search" name="q"
                            value="{{ request('q') }}" placeholder="Search student, ID, academic program, program, or sponsor"
                            oninput="clearTimeout(window.searchTimer); window.searchTimer = setTimeout(() => this.form.submit(), 600)">
                    </div>
                </div>
                <div class="col-md-2 text-end">
                    <a href="{{ route('accounting.beneficiaries.index') }}"
                        class="sf-btn-reset-filters accounting-btn-reset w-100">
                        <i class="bi bi-arrow-counterclockwise"></i>
                        <span>Reset Filters</span>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card sf-card accounting-reference">
        <div class="table-responsive accounting-table-scroll" tabindex="0" role="region" aria-label="Forwarded approved beneficiaries table; scroll horizontally to view all columns">
            <table class="table sf-table table-hover align-middle mb-0 {{ count($beneficiaries) > 0 ? 'accounting-populated' : '' }}">
                <thead>
                    <tr>
                        <th class="ps-4 text-nowrap">Student ID Number</th>
                        <th class="text-nowrap">Full Name</th>
                        <th>Academic Program</th>
                        <th>Year Level</th>
                        <th>Campus</th>
                        <th>Program &amp; Category</th>
                        <th>Sponsor / Organization</th>
                        <th>GWA / GPA</th>
                        <th>Address &amp; Rurality</th>
                        <th class="text-nowrap">Date Approved</th>
                        <th class="text-end pe-4 no-print">Reference</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($beneficiaries as $beneficiary)
                        <tr>
                            <td class="ps-4 sf-mono text-nowrap">{{ $beneficiary['student_id_number'] }}</td>
                            <td class="fw-semibold">{{ $beneficiary['student_name'] }}</td>
                            <td>{{ $beneficiary['course'] }}</td>
                            <td>
                                @if (!empty($beneficiary['year_level']))
                                    Year {{ $beneficiary['year_level'] }}
                                @else
                                    <span class="text-secondary">—</span>
                                @endif
                            </td>
                            <td>{{ $beneficiary['campus'] ?: 'N/A' }}</td>
                            <td>{{ $beneficiary['program'] }}<div class="small text-secondary">
                                    {{ $beneficiary['category'] }}</div>
                            </td>
                            <td>{{ $beneficiary['sponsor'] }}<div class="small text-secondary">
                                    {{ $beneficiary['billing_contact'] ?: 'Sponsor contact not recorded' }}</div>
                            </td>
                            <td>{{ $beneficiary['gwa'] !== null ? number_format((float) $beneficiary['gwa'], 2) : '—' }}
                            </td>
                            <td>
                                <div>{{ $beneficiary['address'] ?: 'Not recorded' }}</div>
                                @if ($beneficiary['rurality'])
                                    <div class="mt-1"><span
                                            class="badge {{ $beneficiary['rurality'] === 'Rural' ? 'bg-info-subtle text-info-emphasis' : 'bg-secondary-subtle text-secondary-emphasis' }}">{{ $beneficiary['rurality'] }}</span>
                                    </div>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                @if ($beneficiary['approved_at'])
                                    <div>{{ $beneficiary['approved_at']->format('M d, Y') }}</div>
                                    <div class="small text-secondary">{{ $beneficiary['approved_at']->format('h:i A') }}</div>
                                @else
                                    <span class="text-secondary">—</span>
                                @endif
                            </td>
                            <td class="text-end pe-4 no-print">
                                <x-status-badge
                                    :status="$beneficiary['reference_label'] ?? (!empty($beneficiary['application_id']) ? 'Application Batch' : 'Fixed List')" />
                                @if (!empty($beneficiary['fixed_list_id']))
                                    <a href="{{ route('accounting.beneficiaries.reference', $beneficiary['fixed_list_id']) }}"
                                        class="btn btn-sm btn-outline-secondary mt-1"><i class="bi bi-eye me-1"></i>View
                                        Reference</a>
                                @elseif (!empty($beneficiary['application_id']))
                                    <a href="{{ route('accounting.beneficiaries.show', $beneficiary['application_id']) }}"
                                        class="btn btn-sm btn-outline-secondary mt-1"><i class="bi bi-eye me-1"></i>View
                                        Reference</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center text-secondary py-5 accounting-empty-cell">
                                <div class="accounting-original-copy"><i class="bi bi-inbox fs-2 d-block mb-2"></i>No eligible, FASSG-forwarded beneficiaries match the current filters.</div>
                                <div class="accounting-empty accounting-desktop-copy d-none">
                                    <i class="bi bi-file-earmark-text" aria-hidden="true"></i>
                                    @if (request()->filled('q') || request()->filled('sponsorship_program_id') || request()->filled('academic_program_id'))
                                        <h3>No matching beneficiaries</h3>
                                        <p>Try adjusting the selected filters or search terms.</p>
                                        <div class="mt-2">
                                            <a href="{{ route('accounting.beneficiaries.index') }}" class="sf-btn-reset-filters accounting-btn-reset">
                                                <i class="bi bi-arrow-counterclockwise"></i>
                                                <span>Reset Filters</span>
                                            </a>
                                        </div>
                                    @else
                                        <h3>No forwarded beneficiaries yet</h3>
                                        <p>Eligible beneficiaries will appear here after FASSG explicitly forwards their approved records.</p>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
