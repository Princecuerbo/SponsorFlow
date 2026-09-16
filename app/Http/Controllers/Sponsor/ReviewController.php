<?php

namespace App\Http\Controllers\Sponsor;

use App\Enums\ApplicationStatus;
use App\Enums\ConfirmationStatus;
use App\Enums\FixedListStatus;
use App\Enums\ProgramStatus;
use App\Http\Controllers\Concerns\ResolvesModuleContext;
use App\Http\Controllers\Controller;
use App\Models\AcademicProgram;
use App\Models\Application;
use App\Models\FixedList;
use App\Models\Sponsor;
use App\Models\SponsorApproval;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReviewController extends Controller
{
    use ResolvesModuleContext;

    public function dashboard(Request $request): View
    {
        $user = $this->actor($request);
        $sponsor = $user->sponsor;
        $pendingReviewCount = ($sponsor?->forwardedFixedLists()->count() ?? 0)
            + Application::query()
            ->whereHas('sponsorshipProgram', fn($query) => $query->where('sponsor_id', $sponsor?->id))
            ->where('status', ApplicationStatus::Verified)
            ->count();

        return view('sponsor.dashboard', [
            'user' => $user,
            'sponsor' => $sponsor,
            'connectedPrograms' => $sponsor?->sponsorshipPrograms()->count() ?? 0,
            'listsPendingReview' => $pendingReviewCount,
            'uploadedApprovals' => Application::query()
                ->whereHas('sponsorshipProgram', fn($query) => $query->where('sponsor_id', $sponsor?->id))
                ->whereIn('status', [ApplicationStatus::Approved, ApplicationStatus::Ongoing])
                ->whereNotNull('sponsor_approval_path')
                ->count(),
        ]);
    }

    public function index(Request $request): View
    {
        $sponsor = $this->sponsorOrganization($request);

        $baseApplicants = Application::query()
            ->whereHas('sponsorshipProgram', fn($query) => $query
                ->where('sponsor_id', $sponsor->id)
                ->where('status', '!=', ProgramStatus::Expired))
            ->where('status', ApplicationStatus::Verified);

        // Dropdown options always come from master reference data so they are never
        // emptied out by an active filter (an empty result set still shows all options).
        $courses = AcademicProgram::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get()
            ->mapWithKeys(fn ($ap) => [$ap->name => "{$ap->code} — {$ap->name}"])
            ->all();

        $campuses = [
            'Main Campus (City of Mati)',
            'Baganga Campus',
            'Banaybanay Campus',
            'Cateel Campus',
            'San Isidro Campus',
            'Tarragona Campus',
        ];

        $programs = $sponsor->sponsorshipPrograms()
            ->orderBy('program_name')
            ->get(['id', 'program_name']);

        $applicants = (clone $baseApplicants)
            ->when($request->filled('sponsorship_program_id'), fn($query) => $query->where('sponsorship_program_id', $request->integer('sponsorship_program_id')))
            ->when($request->filled('course'), fn($query) => $query->whereHas('studentProfile', fn($profileQuery) => $profileQuery->where('course', $request->string('course'))))
            ->when($request->filled('campus'), fn($query) => $query->whereHas('studentProfile', fn($profileQuery) => $profileQuery->where('campus', $request->string('campus'))))
            ->with(['studentProfile.user', 'sponsorshipProgram'])
            ->latest('submitted_at')
            ->get();

        $fixedLists = FixedList::query()
            ->whereHas('sponsorshipProgram', fn($query) => $query->where('sponsor_id', $sponsor->id))
            ->where('status', FixedListStatus::Submitted)
            ->with(['sponsorshipProgram', 'latestApproval'])
            ->latest()
            ->get();

        return view('sponsor.approvals.index', [
            'user' => $this->actor($request),
            'sponsor' => $sponsor,
            'applicants' => $applicants,
            'fixedLists' => $fixedLists,
            'courses' => $courses,
            'campuses' => $campuses,
            'programs' => $programs,
        ]);
    }

    public function programs(Request $request): View
    {
        $sponsor = $this->sponsorOrganization($request);

        return view('sponsor.programs.index', [
            'user' => $this->actor($request),
            'sponsor' => $sponsor,
            'programs' => $sponsor->sponsorshipPrograms()
                ->withCount('applications')
                ->latest()
                ->get(),
        ]);
    }

    public function show(Request $request, FixedList $fixedList): View
    {
        $sponsor = $this->sponsorOrganization($request);
        $this->assertOwnsList($sponsor, $fixedList);

        $fixedList->load(['sponsorshipProgram', 'items', 'latestApproval', 'uploadedByFassg']);

        return view('sponsor.lists.show', [
            'user' => $this->actor($request),
            'sponsor' => $sponsor,
            'list' => $fixedList,
        ]);
    }

    public function applicants(Request $request): View
    {
        return $this->index($request);
    }

    public function confirmApplication(Request $request, Application $application): RedirectResponse
    {
        abort(403, 'Individual application confirmation is disabled. Please use the Batch List workflow instead.');
    }

    public function reject(Request $request, Application $application): RedirectResponse
    {
        $sponsor = $this->sponsorOrganization($request);
        $application->load('sponsorshipProgram');

        abort_unless($sponsor->ownsProgram($application->sponsorshipProgram), 403);

        $validated = $request->validate([
            'rejection_reason' => ['nullable', 'string', 'max:500'],
        ]);

        if ($application->status !== ApplicationStatus::Verified) {
            return back()->withErrors(['application' => 'Only FASSG-verified applications can be declined.']);
        }

        $application->update([
            'status' => ApplicationStatus::Rejected,
            'rejection_reason' => $validated['rejection_reason'] ?? 'Not selected by sponsor.',
        ]);

        return redirect()
            ->route('sponsor.applicants.index')
            ->with('success', 'Applicant has been declined and removed from your queue.');
    }

    public function downloadApprovalDocument(Request $request, Application $application): BinaryFileResponse
    {
        $sponsor = $this->sponsorOrganization($request);
        $application->load('sponsorshipProgram');

        abort_unless($sponsor->ownsProgram($application->sponsorshipProgram), 403);
        abort_if(blank($application->sponsor_approval_path), 404, 'Approval document not found.');

        $path = Storage::disk('local')->path($application->sponsor_approval_path);
        abort_unless(is_file($path), 404, 'Approval document not found.');

        return response()->file($path, [
            'Content-Disposition' => 'inline; filename="' . basename($application->sponsor_approval_path) . '"',
        ]);
    }

    public function approvals(Request $request): View
    {
        return $this->index($request);
    }

    public function history(Request $request): View
    {
        $sponsor = $this->sponsorOrganization($request);
        $sponsorId = $sponsor->id;

        // Approved applications remain in history even if the parent program
        // expired (cascadeExpiredApplications() reflags them to Expired, but
        // approved_at still identifies them as previously finalized approvals).
        $approvedApplications = Application::where(function ($query): void {
            $query->whereIn('status', ['Approved', 'Ongoing'])
                ->orWhere(function ($q): void {
                    $q->where('status', 'Expired')
                        ->whereNotNull('approved_at');
                });
        })
            ->whereHas('sponsorshipProgram', function ($query) use ($sponsorId): void {
                $query->where('sponsor_id', $sponsorId);
            })
            ->with(['studentProfile.user', 'sponsorshipProgram'])
            ->latest('approved_at')
            ->get();

        $approvals = SponsorApproval::query()
            ->whereHas('sponsorshipProgram', fn($query) => $query->where('sponsor_id', $sponsorId))
            ->where('confirmation_status', ConfirmationStatus::Confirmed)
            ->with(['fixedList', 'sponsorshipProgram'])
            ->latest()
            ->get();

        return view('sponsor.approvals.history', [
            'user' => $this->actor($request),
            'sponsor' => $sponsor,
            'applications' => $approvedApplications,
            'approvedApplications' => $approvedApplications,
            'approvals' => $approvals,
        ]);
    }

    public function showApplicant(Request $request, Application $application): View
    {
        $sponsor = $this->sponsorOrganization($request);
        $application->load(['studentProfile.user', 'sponsorshipProgram', 'documents']);

        abort_unless($sponsor->ownsProgram($application->sponsorshipProgram), 403);

        return view('sponsor.applicants.show', [
            'user' => $this->actor($request),
            'application' => $application,
        ]);
    }

    private function assertOwnsList(Sponsor $sponsor, FixedList $fixedList): void
    {
        $fixedList->loadMissing('sponsorshipProgram');

        abort_unless($sponsor->ownsProgram($fixedList->sponsorshipProgram), 403);
    }
}
