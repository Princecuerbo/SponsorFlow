@extends('layouts.app')

@section('title', 'Reports')
@section('eyebrow', 'FASSG Office')
@section('page-title', 'Sponsorship Reports')

@push('styles')
    <style>
        @media print {

            .no-print,
            nav,
            header,
            sidebar,
            .navbar,
            button {
                display: none !important;
            }

            body {
                background-color: #fff !important;
                padding: 0 !important;
            }

            .card,
            .sf-stat-card {
                box-shadow: none !important;
                border: 1px solid #dee2e6 !important;
            }
        }
    </style>
@endpush

@section('content')

    <div class="d-flex align-items-center justify-content-between mb-4 gap-3">
        <div>
            <h3 class="fw-bold mb-0">Sponsorship Reports</h3>
            <p class="text-muted small mb-0">Institutional analytics and slot utilization breakdown.</p>
        </div>
        <div class="d-flex align-items-center gap-2 no-print">
            <button type="button" onclick="window.print()"
                class="btn btn-outline-secondary fw-semibold d-inline-flex align-items-center gap-2">
                <i class="bi bi-printer"></i> Print Report
            </button>
            <a href="{{ route('fassg.reports.export-pdf') }}"
                class="btn fw-semibold d-inline-flex align-items-center gap-2 text-white"
                style="background-color: #0F2942; border-color: #0F2942;">
                <i class="bi bi-file-earmark-pdf"></i> Export PDF
            </a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="sf-stat-card p-3">
                <div class="sf-eyebrow mb-1">Slot Utilization</div>
                <div class="h4 sf-heading mb-1">{{ $report['slot_utilization_pct'] ?? 0 }}%</div>
                <div class="small text-secondary">{{ $report['slots_filled'] ?? 0 }} of {{ $report['slots_total'] ?? 0 }}
                    slots filled</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="sf-stat-card p-3">
                <div class="sf-eyebrow mb-1">Total Applicants</div>
                <div class="h4 sf-heading mb-1">{{ $report['total_applicants'] ?? 0 }}</div>
                <div class="small text-secondary">Across all open &amp; closed programs</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="sf-stat-card p-3">
                <div class="sf-eyebrow mb-1">Confirmed Beneficiaries</div>
                <div class="h4 sf-heading mb-1">{{ $report['confirmed_beneficiaries'] ?? 0 }}</div>
                <div class="small text-secondary">Approved applications: {{ $approvedBeneficiaries ?? 0 }}</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="sf-stat-card p-3">
                <div class="sf-eyebrow mb-1">Rural Applicants</div>
                <div class="h4 sf-heading mb-1">{{ $report['rural_pct'] ?? 0 }}%</div>
                <div class="small text-secondary">Of total verified applicants</div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12">
            <div class="card sf-card">
                <div class="card-body p-4">
                    <h2 class="h6 sf-heading mb-3">Monthly Applicant Trends</h2>
                    <div class="table-responsive">
                        <table class="table sf-table mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th class="text-end">Applications</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($applicantTrends ?? [] as $month => $total)
                                    <tr>
                                        <td>
                                            @if ($month && strlen((string) $month) === 7)
                                                {{ \Carbon\Carbon::parse($month . '-01')->format('F Y') }}
                                            @else
                                                {{ $month }}
                                            @endif
                                        </td>
                                        <td class="text-end fw-semibold">{{ $total }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-secondary text-center py-3">No submitted applications
                                            yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card sf-card">
                <div class="card-body p-4">
                    <h2 class="h6 sf-heading mb-3">Slot Utilization by Program</h2>
                    <div class="table-responsive">
                        <table class="table sf-table mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th>Program</th>
                                    <th>Utilization</th>
                                    <th class="text-end">Filled</th>
                                    <th class="text-end">Available</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($slotUtilization ?? [] as $program)
                                    @php
                                        $filled = $program['filled_slots'] ?? 0;
                                        $available = $program['available_slots'] ?? 0;
                                        $totalSlots =
                                            $program['total_slots'] > 0
                                                ? $program['total_slots']
                                                : $filled + $available;
                                        $pct = $totalSlots > 0 ? round(($filled / $totalSlots) * 100) : 0;
                                    @endphp
                                    <tr>
                                        <td>{{ $program['program_name'] }}</td>
                                        <td style="min-width: 220px;">
                                            <div class="d-flex justify-content-between small mb-1">
                                                <span>{{ $pct }}%</span>
                                                <span
                                                    class="text-secondary">{{ $filled }}/{{ $totalSlots }}</span>
                                            </div>
                                            <div class="progress" style="height: 6px;">
                                                <div class="progress-bar bg-primary" style="width: {{ min(100, $pct) }}%">
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-end">{{ $filled }}</td>
                                        <td class="text-end">{{ $available }}</td>
                                        <td class="text-end fw-semibold">{{ $totalSlots }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-secondary text-center py-3">No programs available.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card sf-card h-100">
                <div class="card-body p-4">
                    <h2 class="h6 sf-heading mb-3">Category Breakdown</h2>
                    <table class="table sf-table mb-0">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th class="text-end">Programs</th>
                                <th class="text-end">Applicants</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($categoryBreakdown ?? [] as $row)
                                <tr>
                                    <td>{{ $row['category'] }}</td>
                                    <td class="text-end">{{ $row['programs'] }}</td>
                                    <td class="text-end">{{ $row['applicants'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-secondary text-center py-3">No data available.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card sf-card h-100">
                <div class="card-body p-4">
                    <h2 class="h6 sf-heading mb-3">Demographic Distribution</h2>
                    <div class="mb-4">
                        <div class="small fw-semibold mb-2">Gender</div>
                        @forelse ($genderDistribution ?? [] as $label => $count)
                            <div class="d-flex justify-content-between small py-1 border-bottom">
                                <span>{{ $label }}</span><span class="fw-semibold">{{ $count }}</span>
                            </div>
                        @empty
                            <div class="text-secondary small">Not tracked.</div>
                        @endforelse
                    </div>
                    <div>
                        <div class="small fw-semibold mb-2">Rurality</div>
                        @forelse ($ruralityDistribution ?? [] as $label => $count)
                            <div class="d-flex justify-content-between small py-1 border-bottom">
                                <span>{{ $label }}</span><span class="fw-semibold">{{ $count }}</span>
                            </div>
                        @empty
                            <div class="text-secondary small">No data available.</div>
                        @endforelse
                    </div>
                    <div class="mt-4">
                        <div class="small fw-semibold mb-2">Course</div>
                        @forelse (($demographics['by_course'] ?? []) as $course => $count)
                            <div class="d-flex justify-content-between small py-1 border-bottom">
                                <span>{{ $course }}</span><span class="fw-semibold">{{ $count }}</span>
                            </div>
                        @empty
                            <div class="text-secondary small">No data available.</div>
                        @endforelse
                    </div>
                    <div class="mt-4">
                        <div class="small fw-semibold mb-2">Year Level</div>
                        @forelse (($demographics['by_year_level'] ?? []) as $yearLevel => $count)
                            <div class="d-flex justify-content-between small py-1 border-bottom">
                                <span>Year {{ $yearLevel }}</span><span class="fw-semibold">{{ $count }}</span>
                            </div>
                        @empty
                            <div class="text-secondary small">No data available.</div>
                        @endforelse
                    </div>
                    <div class="mt-4">
                        <div class="small fw-semibold mb-2">Barangay / Municipality</div>
                        @forelse (($demographics['by_barangay'] ?? []) as $barangay => $count)
                            <div class="d-flex justify-content-between small py-1 border-bottom">
                                <span>{{ $barangay }}</span><span class="fw-semibold">{{ $count }}</span>
                            </div>
                        @empty
                            <div class="text-secondary small">No data available.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
