<?php

namespace App\Http\Controllers\Fassg;

use App\Enums\ApplicationStatus;
use App\Enums\ConfirmationStatus;
use App\Enums\FixedListStatus;
use App\Enums\ProgramCategory;
use App\Http\Controllers\Concerns\ResolvesModuleContext;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\FixedList;
use App\Models\FixedListItem;
use App\Models\SponsorshipProgram;
use App\Models\StudentProfile;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
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

        return $pdf->download('sponsorship-report-' . now()->format('Y-m-d') . '.pdf');
    }

    private function getReportData(Request $request): array
    {
        $driver = DB::getDriverName();

        $dateFormat = match ($driver) {
            'sqlite' => "strftime('%Y-%m', submitted_at)",
            'pgsql'  => "TO_CHAR(submitted_at, 'YYYY-MM')",
            default  => "DATE_FORMAT(submitted_at, '%Y-%m')",
        };

        $applicantTrends = Application::query()
            ->whereNotNull('submitted_at')
            ->selectRaw("{$dateFormat} as month, COUNT(*) as total")
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month')
            ->all();

        $applicantCounts = Application::query()
            ->select('applications.status', DB::raw('count(*) as total'))
            ->groupBy('applications.status')
            ->pluck('total', 'status')
            ->all();

        $approvedBeneficiaries = Application::query()->previouslyApprovedBeneficiaries()->count();

        $confirmedLists = FixedList::query()
            ->where('status', FixedListStatus::Approved)
            ->whereHas('latestApproval', fn($query) => $query->where('confirmation_status', ConfirmationStatus::Confirmed))
            ->count();

        $confirmedListNames = FixedList::query()
            ->where('status', FixedListStatus::Approved)
            ->whereHas('latestApproval', fn($query) => $query->where('confirmation_status', ConfirmationStatus::Confirmed))
            ->withCount('items')
            ->get()
            ->sum('items_count');

        $totalBeneficiaries = $approvedBeneficiaries + $confirmedListNames;

        $categoryBreakdown = SponsorshipProgram::query()
            ->select('category', DB::raw('count(*) as programs'))
            ->groupBy('category')
            ->pluck('programs', 'category')
            ->all();

        $applicantsByCategory = Application::query()
            ->join('sponsorship_programs', 'applications.sponsorship_program_id', '=', 'sponsorship_programs.id')
            ->select('sponsorship_programs.category', DB::raw('count(*) as total'))
            ->groupBy('sponsorship_programs.category')
            ->pluck('total', 'category')
            ->all();

        $approvedByCategory = Application::query()
            ->previouslyApprovedBeneficiaries()
            ->join('sponsorship_programs', 'applications.sponsorship_program_id', '=', 'sponsorship_programs.id')
            ->select('sponsorship_programs.category', DB::raw('count(*) as total'))
            ->groupBy('sponsorship_programs.category')
            ->pluck('total', 'category')
            ->all();

        // Gather student profiles from both individual applications and confirmed fixed lists
        $applicantProfileIds = Application::query()->pluck('student_profile_id');

        $confirmedFixedListStudentIds = FixedListItem::query()
            ->whereHas('fixedList', fn($q) => $q->where('status', FixedListStatus::Approved)
                ->whereHas('latestApproval', fn($sub) => $sub->where('confirmation_status', ConfirmationStatus::Confirmed)))
            ->pluck('student_id_number');

        $fixedListProfileIds = StudentProfile::query()
            ->whereIn('student_id_number', $confirmedFixedListStudentIds)
            ->pluck('id');

        $allProfileIds = $applicantProfileIds->merge($fixedListProfileIds)->unique();

        $genderDistribution = StudentProfile::query()
            ->whereIn('id', $allProfileIds)
            ->whereNotNull('gender')
            ->select('gender', DB::raw('count(*) as total'))
            ->groupBy('gender')
            ->pluck('total', 'gender')
            ->all();

        $byMunicipality = StudentProfile::query()
            ->whereIn('id', $allProfileIds)
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

        $baseProfileQuery = fn (): \Illuminate\Database\Eloquent\Builder => StudentProfile::query()->whereIn('id', $allProfileIds);

        $demographics = [
            'rural' => $baseProfileQuery()->where('is_rural', true)->count(),
            'urban' => $baseProfileQuery()->where('is_rural', false)->count(),
            'sle_fhe_verified' => $baseProfileQuery()->where('is_sle_fhe_verified', true)->count(),
            'by_gender' => $baseProfileQuery()
                ->whereNotNull('gender')
                ->select('gender', DB::raw('count(*) as total'))
                ->groupBy('gender')
                ->pluck('total', 'gender')
                ->all(),
            'by_campus' => $baseProfileQuery()
                ->whereNotNull('campus')
                ->select('campus', DB::raw('count(*) as total'))
                ->groupBy('campus')
                ->orderByDesc('total')
                ->pluck('total', 'campus')
                ->all(),
            'by_year_level' => $baseProfileQuery()
                ->select('year_level', DB::raw('count(*) as total'))
                ->groupBy('year_level')
                ->orderBy('year_level')
                ->pluck('total', 'year_level')
                ->all(),
            'by_course' => $baseProfileQuery()
                ->select('course', DB::raw('count(*) as total'))
                ->groupBy('course')
                ->orderByDesc('total')
                ->limit(10)
                ->pluck('total', 'course')
                ->all(),
            'by_barangay' => $byMunicipality,
            'by_municipality' => $byMunicipality,
        ];

        $slotUtilization = SponsorshipProgram::query()
            ->select('id', 'program_name', 'total_slots', 'available_slots')
            ->withCount(['applications as approved_count' => fn ($q) => $q->previouslyApprovedBeneficiaries()])
            ->orderBy('program_name')
            ->get()
            ->map(function (SponsorshipProgram $program): SponsorshipProgram {
                $filled = (int) $program->approved_count;

                $program->setAttribute('available_slots', max(0, (int) $program->total_slots - $filled));

                return $program;
            });

        $programSlots = (int) SponsorshipProgram::sum('total_slots');
        $filledSlots = (int) Application::query()
            ->previouslyApprovedBeneficiaries()
            ->count();
        $applicantCategoryTotals = $this->categoryTotals($applicantsByCategory);
        $categoryBreakdown = collect($this->categoryTotals($categoryBreakdown))
            ->map(fn(int $programs, string $category): array => [
                'category' => $category,
                'programs' => $programs,
                'applicants' => $applicantCategoryTotals[$category] ?? 0,
            ])
            ->values()
            ->all();

        return [
            'user' => $this->actor($request),
            'applicantTrends' => $applicantTrends,
            'applicantCounts' => $this->statusTotals($applicantCounts),
            'approvedBeneficiaries' => $totalBeneficiaries,
            'confirmedLists' => $confirmedLists,
            'confirmedListNames' => $confirmedListNames,
            'applicantsByCategory' => $this->categoryTotals($applicantsByCategory),
            'approvedByCategory' => $this->categoryTotals($approvedByCategory),
            'demographics' => $demographics,
            'report' => [
                'slot_utilization_pct' => $programSlots > 0 ? round(($filledSlots / $programSlots) * 100, 1) : 0,
                'slots_filled' => $filledSlots,
                'slots_total' => $programSlots,
                'total_applicants' => array_sum($this->statusTotals($applicantCounts)),
                'confirmed_beneficiaries' => $totalBeneficiaries,
                'rural_pct' => $demographics['rural'] + $demographics['urban'] > 0
                    ? round(($demographics['rural'] / ($demographics['rural'] + $demographics['urban'])) * 100, 1)
                    : 0,
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
        ];
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
