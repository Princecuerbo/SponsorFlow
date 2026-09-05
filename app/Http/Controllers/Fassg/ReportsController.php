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
use App\Models\SponsorshipProgram;
use App\Models\StudentProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportsController extends Controller
{
    use ResolvesModuleContext;

    public function index(Request $request): View
    {
        $driver = DB::getDriverName();

        $dateFormat = match ($driver) {
            'sqlite' => "strftime('%Y-%m', submitted_at)",
            'pgsql'  => "TO_CHAR(submitted_at, 'YYYY-MM')",
            default  => "DATE_FORMAT(submitted_at, '%Y-%m')", // MySQL / MariaDB
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

        $approvedBeneficiaries = Application::query()->approvedBeneficiaries()->count();
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
            ->approvedBeneficiaries()
            ->join('sponsorship_programs', 'applications.sponsorship_program_id', '=', 'sponsorship_programs.id')
            ->select('sponsorship_programs.category', DB::raw('count(*) as total'))
            ->groupBy('sponsorship_programs.category')
            ->pluck('total', 'category')
            ->all();

        $applicantProfileIds = Application::query()->pluck('student_profile_id');

        $genderDistribution = StudentProfile::query()
            ->whereIn('id', $applicantProfileIds)
            ->whereNotNull('gender')
            ->select('gender', DB::raw('count(*) as total'))
            ->groupBy('gender')
            ->pluck('total', 'gender')
            ->all();

        $demographics = [
            'rural' => StudentProfile::query()->whereIn('id', $applicantProfileIds)->where('is_rural', true)->count(),
            'urban' => StudentProfile::query()->whereIn('id', $applicantProfileIds)->where('is_rural', false)->count(),
            'sle_fhe_verified' => StudentProfile::query()->whereIn('id', $applicantProfileIds)->where('is_sle_fhe_verified', true)->count(),
            'by_year_level' => StudentProfile::query()
                ->whereIn('id', $applicantProfileIds)
                ->select('year_level', DB::raw('count(*) as total'))
                ->groupBy('year_level')
                ->orderBy('year_level')
                ->pluck('total', 'year_level')
                ->all(),
            'by_course' => StudentProfile::query()
                ->whereIn('id', $applicantProfileIds)
                ->select('course', DB::raw('count(*) as total'))
                ->groupBy('course')
                ->orderByDesc('total')
                ->limit(10)
                ->pluck('total', 'course')
                ->all(),
            'by_barangay' => StudentProfile::query()
                ->whereIn('id', $applicantProfileIds)
                ->whereNotNull('barangay')
                ->select('barangay', DB::raw('count(*) as total'))
                ->groupBy('barangay')
                ->orderByDesc('total')
                ->limit(10)
                ->pluck('total', 'barangay')
                ->all(),
        ];

        $slotUtilization = SponsorshipProgram::query()
            ->select('program_name', 'total_slots', 'available_slots')
            ->withCount([
                'applications as approved_count' => fn($query) => $query->whereIn('status', [
                    ApplicationStatus::Approved,
                    ApplicationStatus::Ongoing,
                ]),
            ])
            ->orderBy('program_name')
            ->get()
            ->map(fn($program): array => [
                'program_name' => $program->program_name,
                'total_slots' => (int) $program->total_slots,
                'filled_slots' => (int) $program->approved_count,
                'available_slots' => (int) $program->available_slots,
                'utilization_pct' => (int) $program->total_slots > 0
                    ? round(((int) $program->approved_count / (int) $program->total_slots) * 100, 1)
                    : 0,
            ])
            ->all();

        $programSlots = collect($slotUtilization)->sum('total_slots');
        $filledSlots = collect($slotUtilization)->sum('filled_slots');
        $applicantCategoryTotals = $this->categoryTotals($applicantsByCategory);
        $categoryBreakdown = collect($this->categoryTotals($categoryBreakdown))
            ->map(fn(int $programs, string $category): array => [
                'category' => $category,
                'programs' => $programs,
                'applicants' => $applicantCategoryTotals[$category] ?? 0,
            ])
            ->values()
            ->all();

        return view('fassg.reports.index', [
            'user' => $this->actor($request),
            'applicantTrends' => $applicantTrends,
            'applicantCounts' => $this->statusTotals($applicantCounts),
            'approvedBeneficiaries' => $approvedBeneficiaries,
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
                'confirmed_beneficiaries' => $approvedBeneficiaries,
                'rural_pct' => $demographics['rural'] + $demographics['urban'] > 0
                    ? round(($demographics['rural'] / ($demographics['rural'] + $demographics['urban'])) * 100, 1)
                    : 0,
            ],
            'categoryBreakdown' => $categoryBreakdown,
            'genderDistribution' => $genderDistribution,
            'slotUtilization' => $slotUtilization,
            'ruralityDistribution' => [
                'Rural' => $demographics['rural'],
                'Urban' => $demographics['urban'],
            ],
        ]);
    }

    /**
     * @param  array<array-key, int|string>  $counts
     * @return array<string, int>
     */
    private function statusTotals(array $counts): array
    {
        $counts = $this->stringifyKeys($counts);
        $totals = [];

        foreach (ApplicationStatus::cases() as $status) {
            $totals[$status->value] = (int) ($counts[$status->value] ?? 0);
        }

        return $totals;
    }

    /**
     * @param  array<array-key, int|string>  $counts
     * @return array<string, int>
     */
    private function categoryTotals(array $counts): array
    {
        $counts = $this->stringifyKeys($counts);
        $totals = [];

        foreach (ProgramCategory::cases() as $category) {
            $totals[$category->value] = (int) ($counts[$category->value] ?? 0);
        }

        return $totals;
    }

    /**
     * @param  array<array-key, int|string>  $counts
     * @return array<string, int>
     */
    private function stringifyKeys(array $counts): array
    {
        $normalized = [];

        foreach ($counts as $key => $total) {
            $normalized[$key instanceof \BackedEnum ? $key->value : (string) $key] = (int) $total;
        }

        return $normalized;
    }
}
