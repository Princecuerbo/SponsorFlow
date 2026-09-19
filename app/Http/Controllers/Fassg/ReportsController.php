<?php

namespace App\Http\Controllers\Fassg;

use App\Enums\ApplicationStatus;
use App\Enums\ConfirmationStatus;
use App\Enums\ProgramCategory;
use App\Http\Controllers\Concerns\ResolvesModuleContext;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\FixedList;
use App\Models\FixedListItem;
use App\Models\SponsorshipProgram;
use App\Models\StudentProfile;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportsController extends Controller
{
    use ResolvesModuleContext;

    public function index(Request $request): View
    {
        return view('fassg.reports.index', $this->getReportData($request));
    }

    public function exportPdf(Request $request)
    {
        $data = $this->getReportData($request);

        $pdf = Pdf::loadView('fassg.reports.reports-pdf', $data)
            ->setPaper('a4', 'portrait');

        return $pdf->download('sponsorship-report-'.now()->format('Y-m-d').'.pdf');
    }

    public function exportCsv(Request $request): Response
    {
        $data = $this->getReportData($request);

        $stream = fopen('php://temp', 'w+');
        fputcsv($stream, ['Program', 'Utilization (%)', 'Filled', 'Available', 'Total Slots']);
        fputcsv($stream, ['', '', '', '', '']);
        fputcsv($stream, ['Overall Slot Utilization', $data['report']['slot_utilization_pct'].'%', $data['report']['slots_filled'], $data['report']['slots_total'] - $data['report']['slots_filled'], $data['report']['slots_total']]);
        fputcsv($stream, ['Total Applicants', $data['report']['total_applicants']]);
        fputcsv($stream, ['Confirmed Beneficiaries', $data['report']['confirmed_beneficiaries']]);
        fputcsv($stream, ['Rural Applicants Rate', $data['report']['rural_pct'].'%']);
        fputcsv($stream, ['', '', '', '', '']);

        foreach ($data['slotUtilization'] as $program) {
            fputcsv($stream, [
                $program->program_name,
                $program->utilization,
                $program->filled_slots,
                $program->available_slots,
                $program->total_slots,
            ]);
        }

        rewind($stream);
        $content = stream_get_contents($stream);
        fclose($stream);

        return response($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="sponsorship-report-'.now()->format('Y-m-d').'.csv"',
        ]);
    }

    private function getReportData(Request $request): array
    {
        $driver = DB::getDriverName();

        $dateFormat = match ($driver) {
            'sqlite' => "strftime('%Y-%m', submitted_at)",
            'pgsql' => "TO_CHAR(submitted_at, 'YYYY-MM')",
            default => "DATE_FORMAT(submitted_at, '%Y-%m')",
        };

        $campus = $request->string('campus')->trim()->toString();
        $academicYear = $request->string('academic_year')->trim()->toString();
        $semester = $request->string('semester')->trim()->toString();
        $sponsorshipProgramId = $request->integer('sponsorship_program_id', 0);

        $term = $this->resolveTerm($academicYear, $semester);

        $academicYears = Application::query()
            ->whereNotNull('submitted_at')
            ->pluck('submitted_at')
            ->map(fn ($stamp) => (int) substr((string) $stamp, 0, 4))
            ->unique()
            ->sort()
            ->values()
            ->map(fn (int $year): string => sprintf('%d-%d', $year, $year + 1))
            ->values()
            ->all();

        $termScope = function ($query) use ($term): void {
            $query->whereBetween('submitted_at', $term);
        };
        $campusScope = function ($query) use ($campus): void {
            $query->whereHas('studentProfile', fn ($pq) => $pq->where('campus', $campus));
        };
        $programScope = function ($query) use ($sponsorshipProgramId): void {
            $query->when($sponsorshipProgramId > 0, fn ($q) => $q->where('sponsorship_program_id', $sponsorshipProgramId));
        };

        $applicantTrends = Application::query()
            ->whereNotNull('submitted_at')
            ->when($term !== null, $termScope)
            ->when($campus !== '', $campusScope)
            ->when($sponsorshipProgramId > 0, $programScope)
            ->selectRaw("{$dateFormat} as month, COUNT(*) as total")
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month')
            ->all();

        $approvalTrends = Application::query()
            ->whereNotNull('approved_at')
            ->when($term !== null, $termScope)
            ->when($campus !== '', $campusScope)
            ->when($sponsorshipProgramId > 0, $programScope)
            ->selectRaw("{$dateFormat} as month, COUNT(*) as total")
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month')
            ->all();

        $applicantCounts = Application::query()
            ->when($term !== null, $termScope)
            ->when($campus !== '', $campusScope)
            ->when($sponsorshipProgramId > 0, $programScope)
            ->select('applications.status', DB::raw('count(*) as total'))
            ->groupBy('applications.status')
            ->pluck('total', 'status')
            ->all();

        $rejectedApplicationsCount = Application::query()
            ->where('status', ApplicationStatus::Rejected)
            ->when($term !== null, $termScope)
            ->when($campus !== '', $campusScope)
            ->when($sponsorshipProgramId > 0, $programScope)
            ->count();

        $approvedBeneficiaries = Application::query()
            ->previouslyApprovedBeneficiaries()
            ->when($term !== null, $termScope)
            ->when($campus !== '', $campusScope)
            ->when($sponsorshipProgramId > 0, $programScope)
            ->distinct('student_profile_id')
            ->count('student_profile_id');

        $confirmedLists = FixedList::query()
            ->whereNotNull('fassg_assigned_at')
            ->whereHas('latestApproval', fn ($query) => $query->where('confirmation_status', ConfirmationStatus::Confirmed))
            ->when($sponsorshipProgramId > 0, fn ($q) => $q->where('sponsorship_program_id', $sponsorshipProgramId))
            ->when($term !== null, fn ($q) => $q->whereBetween('fassg_assigned_at', $term))
            ->count();

        $confirmedListItems = FixedListItem::query()
            ->whereHas('fixedList', function ($q) use ($sponsorshipProgramId, $term): void {
                $q->whereNotNull('fassg_assigned_at');
                if ($sponsorshipProgramId > 0) {
                    $q->where('sponsorship_program_id', $sponsorshipProgramId);
                }
                if ($term !== null) {
                    $q->whereBetween('fassg_assigned_at', $term);
                }
            })
            ->whereDoesntHave('application', fn ($q) => $q->where('status', ApplicationStatus::Rejected))
            ->when($campus !== '', function ($q) use ($campus): void {
                $q->where(function ($sub) use ($campus): void {
                    $sub->where('fixed_list_items.campus', $campus)
                        ->orWhereHas('application.studentProfile', fn ($sq) => $sq->where('campus', $campus))
                        ->orWhereHas('studentProfile', fn ($sq) => $sq->where('campus', $campus));
                });
            })
            ->get(['id', 'application_id']);

        $linkedConfirmedAppIds = $confirmedListItems->pluck('application_id')->filter()->all();

        $standaloneApprovedCount = Application::query()
            ->previouslyApprovedBeneficiaries()
            ->when($term !== null, $termScope)
            ->when($campus !== '', $campusScope)
            ->when($sponsorshipProgramId > 0, $programScope)
            ->when(! empty($linkedConfirmedAppIds), fn ($q) => $q->whereNotIn('id', $linkedConfirmedAppIds))
            ->distinct('student_profile_id')
            ->count('student_profile_id');

        $confirmedListNames = $confirmedListItems->count() + $standaloneApprovedCount;

        $categoryBreakdown = SponsorshipProgram::query()
            ->when($sponsorshipProgramId > 0, fn ($q) => $q->where('id', $sponsorshipProgramId))
            ->select('category', DB::raw('count(*) as programs'))
            ->groupBy('category')
            ->pluck('programs', 'category')
            ->all();

        $applicantsByCategory = Application::query()
            ->when($term !== null, $termScope)
            ->when($campus !== '', $campusScope)
            ->when($sponsorshipProgramId > 0, $programScope)
            ->join('sponsorship_programs', 'applications.sponsorship_program_id', '=', 'sponsorship_programs.id')
            ->select('sponsorship_programs.category', DB::raw('count(*) as total'))
            ->groupBy('sponsorship_programs.category')
            ->pluck('total', 'category')
            ->all();

        $approvedByCategory = Application::query()
            ->previouslyApprovedBeneficiaries()
            ->when($term !== null, $termScope)
            ->when($campus !== '', $campusScope)
            ->when($sponsorshipProgramId > 0, $programScope)
            ->join('sponsorship_programs', 'applications.sponsorship_program_id', '=', 'sponsorship_programs.id')
            ->select('sponsorship_programs.category', DB::raw('count(*) as total'))
            ->groupBy('sponsorship_programs.category')
            ->pluck('total', 'category')
            ->all();

        // Gather student profiles from both individual applications and confirmed fixed lists
        $applicantProfileIds = Application::query()
            ->when($term !== null, $termScope)
            ->when($campus !== '', $campusScope)
            ->when($sponsorshipProgramId > 0, $programScope)
            ->pluck('student_profile_id');

        $confirmedFixedListStudentIds = FixedListItem::query()
            ->whereHas('fixedList', function ($q) use ($sponsorshipProgramId, $term): void {
                $q->whereNotNull('fassg_assigned_at');
                if ($sponsorshipProgramId > 0) {
                    $q->where('sponsorship_program_id', $sponsorshipProgramId);
                }
                if ($term !== null) {
                    $q->whereBetween('fassg_assigned_at', $term);
                }
            })
            ->whereDoesntHave('application', fn ($q) => $q->where('status', ApplicationStatus::Rejected))
            ->when($campus !== '', function ($q) use ($campus): void {
                $q->where(function ($sub) use ($campus): void {
                    $sub->where('fixed_list_items.campus', $campus)
                        ->orWhereHas('application.studentProfile', fn ($sq) => $sq->where('campus', $campus))
                        ->orWhereHas('studentProfile', fn ($sq) => $sq->where('campus', $campus));
                });
            })
            ->pluck('student_id_number');

        $fixedListProfileIds = StudentProfile::query()
            ->whereIn('student_id_number', $confirmedFixedListStudentIds)
            ->when($campus !== '', fn ($q) => $q->where('campus', $campus))
            ->pluck('id');

        $allProfileIds = $applicantProfileIds->merge($fixedListProfileIds)->unique();

        $baseProfileQuery = fn (): Builder => StudentProfile::query()
            ->whereIn('id', $allProfileIds)
            ->when($campus !== '', fn ($q) => $q->where('campus', $campus));

        $genderDistribution = $baseProfileQuery()
            ->selectRaw("COALESCE(gender, 'Unassigned') as label")
            ->selectRaw('COUNT(*) as total')
            ->groupByRaw("COALESCE(gender, 'Unassigned')")
            ->get()
            ->pluck('total', 'label')
            ->all();

        $byMunicipality = $baseProfileQuery()
            ->get(['id', 'province', 'municipality', 'barangay', 'home_address'])
            ->map(function (StudentProfile $profile): string {
                $municipality = trim((string) ($profile->municipality ?? ''));
                if ($municipality !== '') {
                    return $municipality;
                }

                $barangay = trim((string) ($profile->barangay ?? ''));
                if ($barangay !== '') {
                    return $barangay;
                }

                $address = trim((string) $profile->full_address);
                if ($address !== '') {
                    $knownMunicipalities = [
                        'Mati City',
                        'Baganga',
                        'Banaybanay',
                        'Boston',
                        'Caraga',
                        'Cateel',
                        'Governor Generoso',
                        'Lupon',
                        'Manay',
                        'San Isidro',
                        'Tarragona',
                    ];

                    foreach ($knownMunicipalities as $known) {
                        if (stripos($address, $known) !== false) {
                            return $known;
                        }
                    }

                    if (preg_match('/(?:Barangay|Brgy\.?)\s+([^,]+)/i', $address, $matches)) {
                        $parsed = trim($matches[1]);
                        if ($parsed !== '') {
                            return $parsed;
                        }
                    }
                }

                return 'Unspecified';
            })
            ->filter()
            ->countBy()
            ->sortDesc()
            ->take(10)
            ->all();

        $demographics = [
            'rural' => $baseProfileQuery()->where('is_rural', true)->count(),
            'urban' => $baseProfileQuery()->where('is_rural', false)->count(),
            'sle_fhe_verified' => $baseProfileQuery()->where('is_sle_fhe_verified', true)->count(),
            'by_gender' => $genderDistribution,
            'by_campus' => $baseProfileQuery()
                ->selectRaw("COALESCE(campus, 'Unassigned') as label")
                ->selectRaw('COUNT(*) as total')
                ->groupByRaw("COALESCE(campus, 'Unassigned')")
                ->orderByDesc('total')
                ->get()
                ->pluck('total', 'label')
                ->all(),
            'by_year_level' => $baseProfileQuery()
                ->select('year_level', DB::raw('count(*) as total'))
                ->groupBy('year_level')
                ->orderBy('year_level')
                ->pluck('total', 'year_level')
                ->all(),
            'by_course' => $baseProfileQuery()
                ->selectRaw("COALESCE(course, 'Unassigned') as label")
                ->selectRaw('COUNT(*) as total')
                ->groupByRaw("COALESCE(course, 'Unassigned')")
                ->orderByDesc('total')
                ->limit(10)
                ->get()
                ->pluck('total', 'label')
                ->all(),
            'by_barangay' => $baseProfileQuery()
                ->whereNotNull('barangay')
                ->where('barangay', '!=', '')
                ->selectRaw('barangay as label, count(*) as total')
                ->groupBy('barangay')
                ->orderByDesc('total')
                ->limit(10)
                ->pluck('total', 'label')
                ->all(),
            'by_municipality' => $byMunicipality,
        ];

        $slotUtilization = SponsorshipProgram::query()
            ->when($sponsorshipProgramId > 0, fn ($q) => $q->where('id', $sponsorshipProgramId))
            ->select('id', 'program_name', 'total_slots', 'available_slots', 'status')
            ->orderBy('program_name')
            ->get()
            ->map(function (SponsorshipProgram $program) use ($campus, $term, $termScope, $campusScope): SponsorshipProgram {
                $flItems = FixedListItem::query()
                    ->whereHas('fixedList', function ($q) use ($program, $term): void {
                        $q->where('sponsorship_program_id', $program->id)
                            ->whereNotNull('fassg_assigned_at');
                        if ($term !== null) {
                            $q->whereBetween('fassg_assigned_at', $term);
                        }
                    })
                    ->whereDoesntHave('application', fn ($q) => $q
                        ->where('status', ApplicationStatus::Rejected)
                    )
                    ->when($campus !== '', function ($q) use ($campus): void {
                        $q->where(function ($sub) use ($campus): void {
                            $sub->where('fixed_list_items.campus', $campus)
                                ->orWhereHas('application.studentProfile', fn ($sq) => $sq->where('campus', $campus))
                                ->orWhereHas('studentProfile', fn ($sq) => $sq->where('campus', $campus));
                        });
                    })
                    ->get(['id', 'application_id']);

                $linkedAppIds = $flItems->pluck('application_id')->filter()->all();

                $standaloneApprovedApps = $program->applications()
                    ->previouslyApprovedBeneficiaries()
                    ->when($term !== null, $termScope)
                    ->when($campus !== '', $campusScope)
                    ->when(! empty($linkedAppIds), fn ($q) => $q->whereNotIn('id', $linkedAppIds))
                    ->count();

                $filledSlots = $flItems->count() + $standaloneApprovedApps;

                $program->setAttribute('approved_count', $filledSlots);
                $program->setAttribute('available_slots', max(0, (int) $program->total_slots - $filledSlots));

                return $program;
            });

        $programSlots = (int) SponsorshipProgram::query()
            ->when($sponsorshipProgramId > 0, fn ($q) => $q->where('id', $sponsorshipProgramId))
            ->sum('total_slots');
        $filledSlots = (int) $slotUtilization->sum(fn (SponsorshipProgram $program): int => (int) $program->approved_count);
        $applicantCategoryTotals = $this->categoryTotals($applicantsByCategory);
        $categoryBreakdown = collect($this->categoryTotals($categoryBreakdown))
            ->map(fn (int $programs, string $category): array => [
                'category' => $category,
                'programs' => $programs,
                'applicants' => $applicantCategoryTotals[$category] ?? 0,
            ])
            ->values()
            ->all();

        $trendMonths = array_keys(array_replace($applicantTrends, $approvalTrends));
        sort($trendMonths);

        $chartTrends = [
            'labels' => array_map(
                fn (string $month): string => Carbon::parse($month.'-01')->format('M Y'),
                $trendMonths
            ),
            'applications' => array_map(fn (string $month): int => (int) ($applicantTrends[$month] ?? 0), $trendMonths),
            'approvals' => array_map(fn (string $month): int => (int) ($approvalTrends[$month] ?? 0), $trendMonths),
        ];

        $chartRuralUrban = [
            'labels' => ['Rural', 'Urban'],
            'data' => [$demographics['rural'], $demographics['urban']],
        ];

        $chartGender = [
            'labels' => array_keys($genderDistribution),
            'data' => array_values($genderDistribution),
        ];

        $chartCampus = [
            'labels' => array_keys($demographics['by_campus']),
            'data' => array_values($demographics['by_campus']),
        ];

        $chartCourse = [
            'labels' => array_keys($demographics['by_course']),
            'data' => array_values($demographics['by_course']),
        ];

        return [
            'user' => $this->actor($request),
            'applicantTrends' => $applicantTrends,
            'applicantCounts' => $this->statusTotals($applicantCounts),
            'approvedBeneficiaries' => $approvedBeneficiaries,
            'rejectedApplicationsCount' => $rejectedApplicationsCount,
            'confirmedLists' => $confirmedLists,
            'confirmedListNames' => $confirmedListNames,
            'applicantsByCategory' => $this->categoryTotals($applicantsByCategory),
            'approvedByCategory' => $this->categoryTotals($approvedByCategory),
            'demographics' => $demographics,
            'report' => [
                'slot_utilization_pct' => $programSlots > 0 ? round(($filledSlots / $programSlots) * 100, 1) : 0.0,
                'utilization_rate' => $programSlots > 0 ? round(($filledSlots / $programSlots) * 100, 1) : 0.0,
                'slots_filled' => $filledSlots,
                'slots_total' => $programSlots,
                'total_applicants' => array_sum($this->statusTotals($applicantCounts)),
                'confirmed_beneficiaries' => $confirmedListNames,
                'rural_pct' => $demographics['rural'] + $demographics['urban'] > 0
                    ? round(($demographics['rural'] / ($demographics['rural'] + $demographics['urban'])) * 100, 1)
                    : 0.0,
                'rural_rate' => $demographics['rural'] + $demographics['urban'] > 0
                    ? round(($demographics['rural'] / ($demographics['rural'] + $demographics['urban'])) * 100, 1)
                    : 0.0,
            ],
            'categoryBreakdown' => $categoryBreakdown,
            'genderDistribution' => $genderDistribution,
            'campusDistribution' => $demographics['by_campus'],
            'slotUtilization' => $slotUtilization,
            'ruralityDistribution' => [
                'Rural' => $demographics['rural'],
                'Urban' => $demographics['urban'],
            ],
            'municipalityDistribution' => $byMunicipality,
            'chartTrends' => $chartTrends,
            'chartRuralUrban' => $chartRuralUrban,
            'chartGender' => $chartGender,
            'chartCampus' => $chartCampus,
            'chartCourse' => $chartCourse,
            'academicYears' => $academicYears,
            'filters' => [
                'academic_year' => $academicYear,
                'semester' => $semester,
                'campus' => $campus,
                'sponsorship_program_id' => $sponsorshipProgramId,
            ],
            'programs' => SponsorshipProgram::query()->orderBy('program_name')->get(['id', 'program_name']),
        ];
    }

    private function resolveTerm(string $academicYear, string $semester): ?array
    {
        if (! preg_match('/^(\d{4})-(\d{4})$/', $academicYear, $matches)) {
            return null;
        }

        $startYear = (int) $matches[1];
        $endYear = (int) $matches[2];

        return match ($semester) {
            'First', 'First Semester' => [sprintf('%04d-08-01 00:00:00', $startYear), sprintf('%04d-01-31 23:59:59', $endYear)],
            'Second', 'Second Semester' => [sprintf('%04d-02-01 00:00:00', $endYear), sprintf('%04d-06-30 23:59:59', $endYear)],
            default => [sprintf('%04d-08-01 00:00:00', $startYear), sprintf('%04d-06-30 23:59:59', $endYear)],
        };
    }

    private function statusTotals(array $counts): array
    {
        $counts = $this->stringifyKeys($counts);
        $totals = [];

        foreach (ApplicationStatus::cases() as $status) {
            $totals[$status->value] = (int) ($counts[$status->value] ?? 0);
        }

        return $totals;
    }

    private function categoryTotals(array $counts): array
    {
        $counts = $this->stringifyKeys($counts);
        $totals = [];

        foreach (ProgramCategory::cases() as $category) {
            $totals[$category->value] = (int) ($counts[$category->value] ?? 0);
        }

        return $totals;
    }

    private function stringifyKeys(array $counts): array
    {
        $normalized = [];

        foreach ($counts as $key => $total) {
            $normalized[$key instanceof \BackedEnum ? $key->value : (string) $key] = (int) $total;
        }

        return $normalized;
    }
}
