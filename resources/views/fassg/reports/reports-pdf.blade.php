<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Sponsorship Summary Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #333;
            margin: 0;
            padding: 10px;
        }

        h1 {
            font-size: 18px;
            color: #0F2942;
            margin-bottom: 2px;
        }

        .subtitle {
            font-size: 10px;
            color: #666;
            margin-bottom: 20px;
        }

        .stats-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .stats-table td {
            border: 1px solid #ccc;
            padding: 10px;
            background-color: #f9f9f9;
        }

        .stat-label {
            font-size: 9px;
            text-transform: uppercase;
            color: #666;
        }

        .stat-value {
            font-size: 16px;
            font-weight: bold;
            color: #0F2942;
            margin: 4px 0;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .data-table th,
        .data-table td {
            border: 1px solid #ddd;
            padding: 6px 8px;
            text-align: left;
        }

        .data-table th {
            background-color: #0F2942;
            color: #ffffff;
            font-size: 10px;
        }

        .text-end {
            text-align: right;
        }

        .section-title {
            color: #0F2942;
            font-size: 12px;
            margin-bottom: 8px;
            margin-top: 15px;
        }

        .columns-table {
            width: 100%;
            border-collapse: collapse;
        }

        .columns-table td {
            vertical-align: top;
            padding: 0;
        }
    </style>
</head>

<body>

    <h1>Sponsorship Reports Summary</h1>
    <div class="subtitle">Generated on {{ now()->format('F d, Y h:i A') }}</div>

    <table class="stats-table">
        <tr>
            <td width="25%">
                <div class="stat-label">Slot Utilization</div>
                <div class="stat-value">{{ $report['slot_utilization_pct'] ?? 0 }}%</div>
                <small>{{ $report['slots_filled'] ?? 0 }} of {{ $report['slots_total'] ?? 0 }} filled</small>
            </td>
            <td width="25%">
                <div class="stat-label">Total Applicants</div>
                <div class="stat-value">{{ $report['total_applicants'] ?? 0 }}</div>
                <small>Across all programs</small>
            </td>
            <td width="25%">
                <div class="stat-label">Confirmed Beneficiaries</div>
                <div class="stat-value">{{ $report['confirmed_beneficiaries'] ?? 0 }}</div>
                <small>Approved applications</small>
            </td>
            <td width="25%">
                <div class="stat-label">Rural Applicants</div>
                <div class="stat-value">{{ $report['rural_pct'] ?? 0 }}%</div>
                <small>Verified applicants</small>
            </td>
        </tr>
    </table>

    <!-- Monthly Applicant Trends -->
    <h3 class="section-title">Monthly Applicant Trends</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th>Month</th>
                <th class="text-end">Applications</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($applicantTrends ?? [] as $month => $total)
                <tr>
                    <td>{{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y') }}</td>
                    <td class="text-end">{{ $total }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="2" style="text-align: center;">No applicant trends available.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <h3 class="section-title">Slot Utilization by Program</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th>Program Name</th>
                <th class="text-end">Filled</th>
                <th class="text-end">Available</th>
                <th class="text-end">Total Slots</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($slotUtilization ?? [] as $program)
                <tr>
                    <td>{{ $program->program_name ?? 'N/A' }}</td>
                    <td class="text-end">{{ $program->filled_slots }}</td>
                    <td class="text-end">{{ $program->available_slots }}</td>
                    <td class="text-end">{{ $program->total_slots }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align: center;">No data available.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="columns-table">
        <tr>
            <td width="48%">
                <h3 class="section-title">Category Breakdown</h3>
                <table class="data-table">
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
                                <td colspan="3" style="text-align: center;">No data available.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </td>
            <td width="4%"></td>
            <td width="48%">
                <h3 class="section-title">Demographic Distribution</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Metric</th>
                            <th class="text-end">Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Gender -->
                        <tr>
                            <td colspan="2" style="background-color: #eee; font-weight: bold;">Gender</td>
                        </tr>
                        @php
                            $genderDistribution = $genderDistribution ?? $demographics['by_gender'] ?? [];
                            $maleCount = (int) ($genderDistribution['Male'] ?? $genderDistribution['male'] ?? 0);
                            $femaleCount = (int) ($genderDistribution['Female'] ?? $genderDistribution['female'] ?? 0);
                        @endphp
                        @if ($maleCount > 0 || $femaleCount > 0)
                            <tr>
                                <td>Male</td>
                                <td class="text-end">{{ $maleCount }}</td>
                            </tr>
                            <tr>
                                <td>Female</td>
                                <td class="text-end">{{ $femaleCount }}</td>
                            </tr>
                        @else
                            <tr>
                                <td colspan="2" style="text-align: center; color: #777;">No data available.</td>
                            </tr>
                        @endif

                        <!-- Campus -->
                        <tr>
                            <td colspan="2" style="background-color: #eee; font-weight: bold;">Campus</td>
                        </tr>
                        @forelse ($campusDistribution ?? $demographics['by_campus'] ?? [] as $label => $count)
                            <tr>
                                <td>{{ $label }}</td>
                                <td class="text-end">{{ $count }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" style="text-align: center; color: #777;">No data available.</td>
                            </tr>
                        @endforelse

                        <!-- Rurality -->
                        <tr>
                            <td colspan="2" style="background-color: #eee; font-weight: bold;">Rurality</td>
                        </tr>
                        @forelse ($ruralityDistribution ?? [] as $label => $count)
                            <tr>
                                <td>{{ $label }}</td>
                                <td class="text-end">{{ $count }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" style="text-align: center; color: #777;">No data available.</td>
                            </tr>
                        @endforelse

                        <!-- Course -->
                        <tr>
                            <td colspan="2" style="background-color: #eee; font-weight: bold;">Course</td>
                        </tr>
                        @forelse ($demographics['by_course'] ?? [] as $label => $count)
                            <tr>
                                <td>{{ $label }}</td>
                                <td class="text-end">{{ $count }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" style="text-align: center; color: #777;">No data available.</td>
                            </tr>
                        @endforelse

                        <!-- Year Level -->
                        <tr>
                            <td colspan="2" style="background-color: #eee; font-weight: bold;">Year Level</td>
                        </tr>
                        @forelse ($demographics['by_year_level'] ?? [] as $label => $count)
                            <tr>
                                <td>{{ $label }}</td>
                                <td class="text-end">{{ $count }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" style="text-align: center; color: #777;">No data available.</td>
                            </tr>
                        @endforelse

                        <!-- Barangay / Municipality -->
                        <tr>
                            <td colspan="2" style="background-color: #eee; font-weight: bold;">Barangay /
                                Municipality</td>
                        </tr>
                        @forelse (($demographics['by_municipality'] ?? $demographics['by_barangay'] ?? $municipalityDistribution ?? []) as $label => $count)
                            <tr>
                                <td>{{ $label }}</td>
                                <td class="text-end">{{ $count }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" style="text-align: center; color: #777;">No data available.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

</body>

</html>
