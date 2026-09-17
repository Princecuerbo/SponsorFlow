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
            button,
            canvas {
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

    {{-- Page Header --}}
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
            <a href="{{ route('fassg.reports.export-pdf', request()->query()) }}"
                class="btn fw-semibold d-inline-flex align-items-center gap-2 text-white"
                style="background-color: #0F2942; border-color: #0F2942;">
                <i class="bi bi-file-earmark-pdf"></i> Export PDF
            </a>
            <a href="{{ route('fassg.reports.export-csv', request()->query()) }}"
                class="btn btn-outline-success fw-semibold d-inline-flex align-items-center gap-2">
                <i class="bi bi-file-earmark-spreadsheet"></i> Export Excel / CSV
            </a>
        </div>
    </div>

    {{-- Analytical Filter Bar --}}
    @php
        $filterYear = $filters['academic_year'] ?? '';
        $filterSemester = $filters['semester'] ?? '';
        $filterCampus = $filters['campus'] ?? '';
        $filterProgram = $filters['sponsorship_program_id'] ?? 0;
        $yearOptions = collect($academicYears ?? [])
            ->push(sprintf('%d-%d', now()->year, now()->year + 1))
            ->unique()
            ->values()
            ->all();
    @endphp
    <div class="card filter-card mb-4 rounded-3 no-print">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('fassg.reports.index') }}" class="row g-2 align-items-end">
                {{-- Sponsorship Program --}}
                <div class="col-md-2">
                    <label class="form-label small text-secondary fw-semibold mb-1">Sponsorship Program</label>
                    <select name="sponsorship_program_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Programs</option>
                        @foreach (($programs ?? []) as $program)
                            <option value="{{ $program->id }}" @selected($filterProgram == $program->id)>{{ $program->program_name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Academic Year --}}
                <div class="col-md-2">
                    <label class="form-label small text-secondary fw-semibold mb-1">Academic Year</label>
                    <select name="academic_year" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All academic years</option>
                        @foreach ($yearOptions as $yearOption)
                            <option value="{{ $yearOption }}" @selected($filterYear === $yearOption)>{{ $yearOption }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Semester --}}
                <div class="col-md-2">
                    <label class="form-label small text-secondary fw-semibold mb-1">Semester</label>
                    <select name="semester" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All semesters</option>
                        <option value="First" @selected($filterSemester === 'First')>First Semester</option>
                        <option value="Second" @selected($filterSemester === 'Second')>Second Semester</option>
                    </select>
                </div>

                {{-- Campus --}}
                <div class="col-md-3">
                    <label class="form-label small text-secondary fw-semibold mb-1">Campus</label>
                    <select name="campus" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Campuses</option>
                        @foreach (['Main Campus (City of Mati)', 'Baganga Campus', 'Banaybanay Campus', 'Cateel Campus', 'San Isidro Campus', 'Tarragona Campus'] as $campusOpt)
                            <option value="{{ $campusOpt }}" @selected($filterCampus === $campusOpt)>{{ $campusOpt }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Reset --}}
                <div class="col-md-3">
                    <a href="{{ route('fassg.reports.index') }}"
                        class="btn btn-outline-secondary btn-sm w-100">
                        Reset Filters
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- KPI Summary Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="sf-stat-card p-3">
                <div class="sf-eyebrow mb-1">Overall Slot Utilization</div>
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
                <div class="small text-secondary">Approved/Ongoing applications: {{ $approvedBeneficiaries ?? 0 }}</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="sf-stat-card p-3">
                <div class="sf-eyebrow mb-1">Rural Applicants Rate</div>
                <div class="h4 sf-heading mb-1">{{ $report['rural_pct'] ?? 0 }}%</div>
                <div class="small text-secondary">Of total verified applicants</div>
            </div>
        </div>
    </div>

    @php
        $trendsAvailable = ! empty($chartTrends['labels'] ?? []);
    @endphp

    {{-- Monthly Trends Chart --}}
    <div class="row g-4">
        <div class="col-12">
            <div class="card sf-card">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h6 sf-heading mb-0">Monthly Applicant Trends</h2>
                        <span class="small text-secondary">Applications vs. approvals per month</span>
                    </div>
                    @if ($trendsAvailable)
                        <div style="position: relative; height: 320px;">
                            <canvas id="monthlyTrendsChart"></canvas>
                        </div>
                    @else
                        <div class="sf-empty-state py-4">
                            <i class="bi bi-bar-chart"></i>
                            <div class="small">No submission data for the selected period.</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Demographic Charts --}}
    <div class="row g-4 mt-0">
        <div class="col-lg-4">
            <div class="card sf-card h-100">
                <div class="card-body p-4">
                    <h2 class="h6 sf-heading mb-3">Residency Distribution</h2>
                    @if (! empty($chartRuralUrban['data'] ?? []) && array_sum($chartRuralUrban['data']) > 0)
                        <div style="position: relative; height: 280px;">
                            <canvas id="ruralUrbanChart"></canvas>
                        </div>
                    @else
                        <div class="sf-empty-state py-4">
                            <i class="bi bi-house"></i>
                            <div class="small">No residency data available.</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card sf-card h-100">
                <div class="card-body p-4">
                    <h2 class="h6 sf-heading mb-3">Gender Distribution</h2>
                    @if (! empty($chartGender['data'] ?? []) && array_sum($chartGender['data']) > 0)
                        <div style="position: relative; height: 280px;">
                            <canvas id="genderChart"></canvas>
                        </div>
                    @else
                        <div class="sf-empty-state py-4">
                            <i class="bi bi-gender-ambiguous"></i>
                            <div class="small">No gender data available.</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card sf-card h-100">
                <div class="card-body p-4">
                    <h2 class="h6 sf-heading mb-3">Applicants by Campus</h2>
                    @if (! empty($chartCampus['data'] ?? []) && array_sum($chartCampus['data']) > 0)
                        <div style="position: relative; height: 280px;">
                            <canvas id="campusChart"></canvas>
                        </div>
                    @else
                        <div class="sf-empty-state py-4">
                            <i class="bi bi-buildings"></i>
                            <div class="small">No campus data available.</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-0">
        <div class="col-lg-7">
            <div class="card sf-card h-100">
                <div class="card-body p-4">
                    <h2 class="h6 sf-heading mb-3">Applicants by Course</h2>
                    @if (! empty($chartCourse['data'] ?? []) && array_sum($chartCourse['data']) > 0)
                        <div style="position: relative; height: 300px;">
                            <canvas id="courseChart"></canvas>
                        </div>
                    @else
                        <div class="sf-empty-state py-4">
                            <i class="bi bi-book"></i>
                            <div class="small">No course data available.</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-5">
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
    </div>

    {{-- Slot Utilization --}}
    <div class="row g-4 mt-0">
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
                                        $pct = min(100, $program->utilization);
                                        $barClass = $pct >= 75 ? 'bg-success' : ($pct >= 40 ? 'bg-warning' : 'bg-danger');
                                    @endphp
                                    <tr>
                                        <td>{{ $program->program_name }}</td>
                                        <td style="min-width: 220px;">
                                            <div class="d-flex justify-content-between small mb-1">
                                                <span class="fw-semibold">{{ $program->utilization }}%</span>
                                                <span
                                                    class="text-secondary">{{ $program->filled_slots }}/{{ $program->total_slots }}</span>
                                            </div>
                                            <div class="progress" style="height: 8px; background-color: #e9ecef;">
                                                <div class="progress-bar {{ $barClass }}"
                                                    style="width: {{ $pct }}%">
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-end">{{ $program->filled_slots }}</td>
                                        <td class="text-end">{{ $program->available_slots }}</td>
                                        <td class="text-end fw-semibold">{{ $program->total_slots }}</td>
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
    </div>

@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    @php
        $chartData = [
            'trends' => $chartTrends ?? [],
            'ruralUrban' => $chartRuralUrban ?? [],
            'gender' => $chartGender ?? [],
            'campus' => $chartCampus ?? [],
            'course' => $chartCourse ?? [],
        ];
    @endphp
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof Chart === 'undefined') return;

            const chartData = @json($chartData);

            const nonEmpty = d => Array.isArray(d.labels) && d.labels.length > 0;
            const hasCounts = d => Array.isArray(d.data) && d.data.reduce((a, b) => a + b, 0) > 0;

            const navy = '#0F2942';
            const baseOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: true, ticks: { precision: 0 } },
                },
            };

            if (document.getElementById('monthlyTrendsChart') && nonEmpty(chartData.trends)) {
                new Chart(document.getElementById('monthlyTrendsChart'), {
                    type: 'line',
                    data: {
                        labels: chartData.trends.labels,
                        datasets: [
                            {
                                label: 'Applications',
                                data: chartData.trends.applications,
                                borderColor: navy,
                                backgroundColor: 'rgba(15, 41, 66, 0.10)',
                                tension: 0.35,
                                fill: true,
                                pointRadius: 3,
                            },
                            {
                                label: 'Approvals',
                                data: chartData.trends.approvals,
                                borderColor: '#198754',
                                backgroundColor: 'rgba(25, 135, 84, 0.10)',
                                tension: 0.35,
                                fill: true,
                                pointRadius: 3,
                            },
                        ],
                    },
                    options: baseOptions,
                });
            }

            if (document.getElementById('ruralUrbanChart') && hasCounts(chartData.ruralUrban)) {
                new Chart(document.getElementById('ruralUrbanChart'), {
                    type: 'doughnut',
                    data: {
                        labels: chartData.ruralUrban.labels,
                        datasets: [{
                            data: chartData.ruralUrban.data,
                            backgroundColor: ['#ffc107', navy],
                            borderWidth: 2,
                        }],
                    },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } },
                });
            }

            if (document.getElementById('genderChart') && hasCounts(chartData.gender)) {
                new Chart(document.getElementById('genderChart'), {
                    type: 'pie',
                    data: {
                        labels: chartData.gender.labels,
                        datasets: [{
                            data: chartData.gender.data,
                            backgroundColor: ['#0d6efd', '#d63384', '#6f42c1', '#fd7e14', '#20c997'],
                            borderWidth: 2,
                        }],
                    },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } },
                });
            }

            const horizontalBase = {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { beginAtZero: true, ticks: { precision: 0 } },
                    y: { grid: { display: false } },
                },
            };

            if (document.getElementById('campusChart') && hasCounts(chartData.campus)) {
                new Chart(document.getElementById('campusChart'), {
                    type: 'bar',
                    data: {
                        labels: chartData.campus.labels,
                        datasets: [{
                            label: 'Applicants',
                            data: chartData.campus.data,
                            backgroundColor: 'rgba(15, 41, 66, 0.75)',
                            borderRadius: 4,
                        }],
                    },
                    options: horizontalBase,
                });
            }

            if (document.getElementById('courseChart') && hasCounts(chartData.course)) {
                new Chart(document.getElementById('courseChart'), {
                    type: 'bar',
                    data: {
                        labels: chartData.course.labels,
                        datasets: [{
                            label: 'Applicants',
                            data: chartData.course.data,
                            backgroundColor: 'rgba(13, 110, 253, 0.7)',
                            borderRadius: 4,
                        }],
                    },
                    options: horizontalBase,
                });
            }
        });
    </script>
@endpush