<?php

namespace App\Http\Controllers\Sponsor;

use App\Enums\ApplicationStatus;
use App\Enums\FixedListStatus;
use App\Enums\ProgramStatus;
use App\Http\Controllers\Concerns\ResolvesModuleContext;
use App\Http\Controllers\Controller;
use App\Models\AcademicProgram;
use App\Models\Application;
use App\Models\FixedList;
use App\Models\Sponsor;
use App\Models\StudentProfile;
use App\Models\SponsorshipProgram;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
                ->where('status', ApplicationStatus::Approved)
                ->whereNotNull('sponsor_approval_path')
                ->count(),
        ]);
    }

    public function index(Request $request): View
    {
        $sponsor = $this->sponsorOrganization($request);

        $lists = FixedList::query()
            ->whereHas('sponsorshipProgram', fn($query) => $query->where('sponsor_id', $sponsor->id))
            ->whereIn('status', [FixedListStatus::Submitted, FixedListStatus::Approved, FixedListStatus::Rejected])
            ->with(['sponsorshipProgram', 'latestApproval', 'items'])
            ->withCount('items')
            ->latest()
            ->get();

        return view('sponsor.upload', [
            'user' => $this->actor($request),
            'sponsor' => $sponsor,
            'lists' => $lists,
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
        $sponsor = $this->sponsorOrganization($request);

        $applicants = Application::query()
            ->whereHas('sponsorshipProgram', fn($query) => $query
                ->where('sponsor_id', $sponsor->id)
                ->where('status', '!=', ProgramStatus::Expired))
            ->where('status', ApplicationStatus::Verified)
            ->when($request->filled('course'), fn($query) => $query->whereHas('studentProfile', fn($profileQuery) => $profileQuery->where('course', $request->string('course'))))
            ->when($request->filled('academic_program_id'), fn($query) => $query->whereHas('studentProfile', fn($profileQuery) => $profileQuery->where('academic_program_id', $request->integer('academic_program_id'))))
            ->with(['studentProfile.user', 'sponsorshipProgram'])
            ->latest('submitted_at')
            ->get();

        $fixedLists = FixedList::query()
            ->whereHas('sponsorshipProgram', fn($query) => $query->where('sponsor_id', $sponsor->id))
            ->with('sponsorshipProgram')
            ->latest()
            ->get();

        $courses = $applicants->pluck('studentProfile.course')->filter()->unique()->values();
        $academicPrograms = \App\Models\AcademicProgram::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('sponsor.applicants.index', [
            'user' => $this->actor($request),
            'sponsor' => $sponsor,
            'applicants' => $applicants,
            'fixedLists' => $fixedLists,
            'courses' => $courses,
            'academicPrograms' => $academicPrograms,
        ]);
    }

    public function confirmApplication(Request $request, Application $application): RedirectResponse
    {
        $sponsor = $this->sponsorOrganization($request);
        $application->load(['studentProfile', 'sponsorshipProgram']);

        abort_unless($sponsor->ownsProgram($application->sponsorshipProgram), 403);

        $validated = $request->validate([
            'approval_document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        if ($application->status !== ApplicationStatus::Verified) {
            return back()->withErrors(['application' => 'Only FASSG-verified applications can be confirmed.']);
        }

        $path = $validated['approval_document']->store('sponsor-approvals/applications', 'local');

        $approved = DB::transaction(function () use ($application, $path): bool {
            $program = SponsorshipProgram::query()
                ->lockForUpdate()
                ->findOrFail($application->sponsorship_program_id);
            $profile = StudentProfile::query()
                ->lockForUpdate()
                ->findOrFail($application->student_profile_id);

            if ($program->available_slots < 1 || $profile->hasActiveSponsorship()) {
                return false;
            }

            $application->update([
                'status' => ApplicationStatus::Approved,
                'approved_at' => now(),
                'sponsor_approval_path' => $path,
            ]);

            $profile->update(['active_sponsorship_id' => $application->id]);
            $program->decrementAvailableSlot();

            if ($program->status === ProgramStatus::Closed) {

                $pendingApplicationIds = $program->applications()
                    ->whereIn('status', [ApplicationStatus::Pending, ApplicationStatus::Verified])
                    ->pluck('id');

                if ($pendingApplicationIds->isNotEmpty()) {
                    $program->applications()
                        ->whereKey($pendingApplicationIds)
                        ->update([
                            'status' => ApplicationStatus::Rejected,
                            'rejection_reason' => 'Program capacity reached (0 slots remaining).',
                        ]);

                    StudentProfile::query()
                        ->whereIn('active_sponsorship_id', $pendingApplicationIds)
                        ->update(['active_sponsorship_id' => null]);
                }
            }

            return true;
        });

        if (! $approved) {
            Storage::disk('local')->delete($path);

            return back()->withErrors(['application' => 'This student already has an active sponsorship or the program has no remaining slots.']);
        }

        $this->audit($request, 'sponsor.application.confirmed', 'applications');

        return redirect()->route('sponsor.applicants.index')->with('status', 'Application confirmed and forwarded to Accounting.');
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
        $sponsor = $this->sponsorOrganization($request);

        $applications = Application::query()
            ->whereIn('status', [ApplicationStatus::Approved, ApplicationStatus::Ongoing])
            ->whereHas('sponsorshipProgram', fn($query) => $query->where('sponsor_id', $sponsor->id))
            ->with(['studentProfile.user', 'sponsorshipProgram'])
            ->latest('approved_at')
            ->get();

        $approvals = \App\Models\SponsorApproval::query()
            ->whereHas('sponsorshipProgram', fn($query) => $query->where('sponsor_id', $sponsor->id))
            ->with(['sponsorshipProgram', 'fixedList'])
            ->latest()
            ->get();

        return view('sponsor.approvals.index', [
            'user' => $this->actor($request),
            'applications' => $applications,
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
