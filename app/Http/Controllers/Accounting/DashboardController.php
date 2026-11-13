<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Concerns\ResolvesModuleContext;
use App\Http\Controllers\Controller;
use App\Models\BatchCandidate;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    use ResolvesModuleContext;

    public function index(Request $request): View
    {
        $allBeneficiaries = BatchCandidate::accountingBeneficiaries()->map(function (BatchCandidate $item): array {
            $application = $item->application;
            $program = $item->generatedBatch->sponsorshipProgram;

            return [
                'type' => $item->is_fixed_list ? 'fixed_list' : 'ordinary_application',
                'student_name' => $application->studentProfile->full_name,
                'student_id' => $application->studentProfile->student_id_number,
                'program_name' => $program->program_name,
                'program_category' => $program->category?->value ?? 'General',
                'sponsor_name' => $program->sponsor?->company_organization_name,
                'sponsor_id' => $program->sponsor_id,
                'date_approved' => $application->approved_at,
            ];
        })->sortByDesc('date_approved')->values();
        $sponsorAllocation = $allBeneficiaries
            ->groupBy('sponsor_name')
            ->map(fn ($beneficiaries, string $sponsor): array => [
                'sponsor' => $sponsor,
                'beneficiaries' => $beneficiaries->count(),
                'programs' => $beneficiaries->pluck('program_name')->unique()->count(),
            ])
            ->values();

        $activeSponsors = $allBeneficiaries
            ->pluck('sponsor_id')
            ->unique()
            ->count();

        $programBreakdown = $allBeneficiaries
            ->groupBy('program_category')
            ->map(fn ($beneficiaries, string $category): array => [
                'category' => $category,
                'beneficiaries' => $beneficiaries->count(),
            ])
            ->values();

        $recentApprovalCount = $allBeneficiaries
            ->filter(fn ($beneficiary): bool => ($beneficiary['date_approved'] ?? null) >= now()->subDays(30))
            ->count();

        return view('accounting.dashboard', [
            'user' => $this->actor($request),
            'totalApproved' => $allBeneficiaries->count(),
            'activeSponsors' => $activeSponsors,
            'sponsorAllocation' => $sponsorAllocation,
            'programBreakdown' => $programBreakdown,
            'recentApprovalCount' => $recentApprovalCount,
        ]);
    }
}
