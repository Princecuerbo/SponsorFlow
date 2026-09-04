<?php

namespace App\Http\Controllers\Accounting;

use App\Enums\ApplicationStatus;
use App\Enums\ConfirmationStatus;
use App\Enums\FixedListStatus;
use App\Http\Controllers\Concerns\ResolvesModuleContext;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\FixedList;
use App\Models\FixedListItem;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    use ResolvesModuleContext;

    public function index(Request $request): View
    {
        $approvedApplications = Application::query()
            ->where('status', ApplicationStatus::Approved)
            ->with(['studentProfile.user', 'sponsorshipProgram.sponsor'])
            ->latest('approved_at')
            ->get();

        $confirmedListItems = FixedListItem::query()
            ->where('is_sle_fhe_verified', true)
            ->whereHas('fixedList', function ($query): void {
                $query->where('status', FixedListStatus::Approved)
                    ->whereHas('latestApproval', fn($approval) => $approval->where('confirmation_status', ConfirmationStatus::Confirmed));
            })
            ->with('fixedList.sponsorshipProgram.sponsor')
            ->get();

        $applicationBeneficiaries = $approvedApplications->map(function (Application $application): array {
            $program = $application->sponsorshipProgram;

            return [
                'type' => 'application',
                'student_name' => $application->studentProfile->user->name,
                'student_id' => $application->studentProfile->student_id_number,
                'program_name' => $program->program_name,
                'program_category' => $program->category?->value ?? 'General',
                'sponsor_name' => $program->sponsor->company_organization_name,
                'sponsor_id' => $program->sponsor_id,
                'date_approved' => $application->approved_at ?? $application->updated_at,
            ];
        });

        $fixedListBeneficiaries = $confirmedListItems->map(function (FixedListItem $item): array {
            $list = $item->fixedList;
            $program = $list->sponsorshipProgram;

            return [
                'type' => 'fixed_list',
                'student_name' => $item->student_name,
                'student_id' => $item->student_id_number ?: 'N/A',
                'program_name' => $program->program_name,
                'program_category' => $program->category?->value ?? 'General',
                'sponsor_name' => $program->sponsor->company_organization_name,
                'sponsor_id' => $program->sponsor_id,
                'date_approved' => $list->updated_at,
            ];
        });

        $allBeneficiaries = $applicationBeneficiaries->concat($fixedListBeneficiaries);
        $sponsorAllocation = $allBeneficiaries
            ->groupBy('sponsor_name')
            ->map(fn($beneficiaries, string $sponsor): array => [
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
            ->map(fn($beneficiaries, string $category): array => [
                'category' => $category,
                'beneficiaries' => $beneficiaries->count(),
            ])
            ->values();

        $recentApprovals = $allBeneficiaries
            ->sortByDesc('date_approved')
            ->take(5)
            ->values();

        return view('accounting.dashboard', [
            'user' => $this->actor($request),
            'totalApproved' => $allBeneficiaries->count(),
            'activeSponsors' => $activeSponsors,
            'sponsorAllocation' => $sponsorAllocation,
            'programBreakdown' => $programBreakdown,
            'recentApprovals' => $recentApprovals,
        ]);
    }
}
