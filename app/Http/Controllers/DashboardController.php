<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Enums\ConfirmationStatus;
use App\Enums\FixedListStatus;
use App\Enums\ProgramStatus;
use App\Models\Application;
use App\Models\FixedList;
use App\Models\SponsorshipProgram;
use App\Models\StudentProfile;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        return $this->__invoke($request);
    }

    public function __invoke(Request $request): View
    {
        $user = $request->user();

        if ($user->isStudent()) {
            $studentProfile = $user->studentProfile;
            $applications = $studentProfile
                ? Application::query()
                ->with('sponsorshipProgram')
                ->where('student_profile_id', $studentProfile->id)
                ->latest()
                ->get()
                : collect();
            $latestApplication = $studentProfile
                ? Application::query()
                ->with(['sponsorshipProgram.sponsor', 'documents'])
                ->where('student_profile_id', $studentProfile->id)
                ->latest()
                ->first()
                : null;

            $pendingApplicationsCount = $studentProfile
                ? Application::query()
                ->where('student_profile_id', $studentProfile->id)
                ->whereIn('status', [
                    ApplicationStatus::Pending,
                    ApplicationStatus::Verified,
                    'FASSG Verified',
                    'Sponsor Reviewed',
                ])
                ->count()
                : 0;

            $activeGrant = $studentProfile
                ? Application::query()
                ->with('sponsorshipProgram.sponsor')
                ->where('student_profile_id', $studentProfile->id)
                ->where('status', ApplicationStatus::Approved)
                ->whereHas('sponsorshipProgram', function ($query): void {
                    $query->whereIn('status', [ProgramStatus::Open, ProgramStatus::Closed]);
                })
                ->latest('approved_at')
                ->first()
                : null;

            $activeSponsorships = $studentProfile
                ? Application::query()
                ->where('student_profile_id', $studentProfile->id)
                ->whereIn('status', [
                    ApplicationStatus::Approved,
                    ApplicationStatus::Ongoing,
                ])
                ->count()
                : 0;

            return view('dashboard', [
                'user' => $user,
                'studentProfile' => $studentProfile,
                'totalApplications' => $studentProfile
                    ? Application::query()->where('student_profile_id', $studentProfile->id)->count()
                    : 0,
                'activeSponsorships' => $activeSponsorships,
                'applications' => $applications,
                'latestApplication' => $latestApplication,
                'currentStatus' => $latestApplication?->status instanceof \BackedEnum
                    ? $latestApplication->status->value
                    : ($latestApplication?->status ? (string) $latestApplication->status : null),
                'pendingApplicationsCount' => $pendingApplicationsCount,
                'activeGrant' => $activeGrant,
            ]);
        }

        if ($user->isFassg()) {
            SponsorshipProgram::query()
                ->where(function ($query): void {
                    $query->where('status', ProgramStatus::Expired)
                        ->orWhere(function ($query): void {
                            $query->where('status', '!=', ProgramStatus::Expired)
                                ->whereNotNull('end_date')
                                ->where('end_date', '<', now()->startOfDay());
                        });
                })
                ->get()
                ->each(function (SponsorshipProgram $program): void {
                    if ($program->status !== ProgramStatus::Expired) {
                        $program->update(['status' => ProgramStatus::Expired]);
                    }

                    $program->cascadeExpiredApplications();
                });

            $statusCounts = Application::query()
                ->select('status')
                ->selectRaw('count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status')
                ->all();

            $pendingVerificationCount = StudentProfile::query()
                ->where('is_sle_fhe_verified', false)
                ->count()
                + Application::query()
                ->where('status', ApplicationStatus::Pending)
                ->count();

            $recentPrograms = SponsorshipProgram::query()
                ->with('sponsor')
                ->latest('updated_at')
                ->limit(5)
                ->get();

            $activeProgramsCount = SponsorshipProgram::query()
                ->whereIn('status', [ProgramStatus::Open->value, ProgramStatus::Closed->value])
                ->count();

            $approvedApplicationsCount = Application::query()->approvedBeneficiaries()->count();

            $confirmedFixedListNamesCount = FixedList::query()
                ->where('status', FixedListStatus::Approved)
                ->whereHas('latestApproval', fn($q) => $q->where('confirmation_status', ConfirmationStatus::Confirmed))
                ->withCount('items')
                ->get()
                ->sum('items_count');

            $totalConfirmedBeneficiaries = $approvedApplicationsCount + $confirmedFixedListNamesCount;

            return view('fassg.dashboard', [
                'user' => $user,
                'stats' => [
                    'total_applicants' => Application::query()->count(),
                    'verified_sle_fhe' => StudentProfile::query()
                        ->where(function ($query): void {
                            $query->where('is_sle_fhe_verified', true)
                                ->orWhereHas('applications', fn($applicationQuery) => $applicationQuery->whereIn('status', [
                                    ApplicationStatus::Verified,
                                    ApplicationStatus::Approved,
                                    ApplicationStatus::Ongoing,
                                ]));
                        })
                        ->count(),
                    'active_programs' => $activeProgramsCount,
                    'confirmed_beneficiaries' => $totalConfirmedBeneficiaries,
                ],
                'applicationStatusBreakdown' => collect(ApplicationStatus::cases())
                    ->mapWithKeys(fn(ApplicationStatus $status): array => [$status->value => (int) ($statusCounts[$status->value] ?? 0)])
                    ->all(),
                'pendingVerificationCount' => $pendingVerificationCount,
                'recentPrograms' => $recentPrograms,
            ]);
        }

        return view('dashboard', ['user' => $user]);
    }
}
