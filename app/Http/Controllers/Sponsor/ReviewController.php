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
use App\Models\SponsorshipProgram;
use App\Models\StudentProfile;
use App\Notifications\ApplicationStatusUpdated;
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
        $sponsorProgramIds = $sponsor?->sponsorshipPrograms()->pluck('id') ?? collect();

        $pendingReviewCount = FixedList::query()
            ->whereIn('sponsorship_program_id', $sponsorProgramIds)
            ->where('status', FixedListStatus::Submitted)
            ->count();

        return view('sponsor.dashboard', [
            'user' => $user,
            'sponsor' => $sponsor,
            'connectedPrograms' => $sponsor?->sponsorshipPrograms()->count() ?? 0,
            'listsPendingReview' => $pendingReviewCount,
            'uploadedApprovals' => FixedList::query()
                ->whereHas('sponsorshipProgram', fn ($query) => $query->where('sponsor_id', $sponsor?->id))
                ->where('status', FixedListStatus::Approved)
                ->whereNotNull('fassg_assigned_at')
                ->whereHas('latestApproval', fn ($approval) => $approval
                    ->whereNotNull('approval_document_path')
                    ->where('confirmation_status', ConfirmationStatus::Confirmed))
                ->count(),
        ]);
    }

    public function index(Request $request): View
    {
        $sponsor = $this->sponsorOrganization($request);

        $programs = $sponsor->sponsorshipPrograms()
            ->orderBy('program_name')
            ->get(['id', 'program_name']);

        // Lists generated from the FASSG Application Queue carry linked
        // applications on their items, whereas manually encoded / CSV-imported
        // lists have items with no application linkage. Keep them isolated so
        // individual applications cannot bypass the batch workflow.
        $generatedBatches = FixedList::query()
            ->whereHas('sponsorshipProgram', fn ($query) => $query->where('sponsor_id', $sponsor->id))
            ->where('status', FixedListStatus::Submitted)
            ->whereHas('items', fn ($query) => $query->whereNotNull('application_id'))
            ->with(['sponsorshipProgram', 'latestApproval'])
            ->when($request->filled('sponsorship_program_id'), fn ($query) => $query->where('sponsorship_program_id', $request->integer('sponsorship_program_id')))
            ->latest()
            ->get();

        $externalLists = FixedList::query()
            ->whereHas('sponsorshipProgram', fn ($query) => $query->where('sponsor_id', $sponsor->id))
            ->where('status', FixedListStatus::Submitted)
            ->whereDoesntHave('items', fn ($query) => $query->whereNotNull('application_id'))
            ->with(['sponsorshipProgram', 'latestApproval'])
            ->when($request->filled('sponsorship_program_id'), fn ($query) => $query->where('sponsorship_program_id', $request->integer('sponsorship_program_id')))
            ->latest()
            ->get();

        return view('sponsor.approvals.index', [
            'user' => $this->actor($request),
            'sponsor' => $sponsor,
            'generatedBatches' => $generatedBatches,
            'externalLists' => $externalLists,
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

        $fixedList->load(['sponsorshipProgram', 'items.application', 'latestApproval', 'uploadedByFassg']);

        return view('sponsor.lists.show', [
            'user' => $this->actor($request),
            'sponsor' => $sponsor,
            'list' => $fixedList,
        ]);
    }

    public function applicants(Request $request): View
    {
        $sponsor = $this->sponsorOrganization($request);

        $academicProgramId = $request->integer('academic_program_id', 0);
        $course = $request->string('course')->trim()->toString();
        $status = $request->string('status')->trim()->toString();

        $applicants = Application::query()
            ->whereHas('sponsorshipProgram', fn ($query) => $query->where('sponsor_id', $sponsor->id))
            ->where('status', ApplicationStatus::Verified)
            ->when($academicProgramId > 0, fn ($query) => $query->whereHas('studentProfile', fn ($pq) => $pq->where('academic_program_id', $academicProgramId)))
            ->when($course !== '', fn ($query) => $query->whereHas('studentProfile', fn ($pq) => $pq->where('course', $course)))
            ->with(['studentProfile.user', 'sponsorshipProgram'])
            ->latest('created_at')
            ->get();

        $fixedLists = FixedList::query()
            ->whereHas('sponsorshipProgram', fn ($query) => $query->where('sponsor_id', $sponsor->id))
            ->where('status', FixedListStatus::Submitted)
            ->with(['sponsorshipProgram', 'latestApproval'])
            ->latest()
            ->get();

        $academicPrograms = AcademicProgram::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $courses = StudentProfile::query()
            ->whereNotNull('course')
            ->distinct()
            ->pluck('course')
            ->filter()
            ->values();

        return view('sponsor.applicants.index', [
            'user' => $this->actor($request),
            'sponsor' => $sponsor,
            'applicants' => $applicants,
            'fixedLists' => $fixedLists,
            'academicPrograms' => $academicPrograms,
            'courses' => $courses,
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
            $program->refresh();

            if ($program->status === ProgramStatus::Closed || (int) $program->getRawOriginal('available_slots') <= 0 || $program->available_slots <= 0) {
                $program->update(['status' => ProgramStatus::Closed]);

                $pendingApplications = $program->applications()
                    ->whereIn('status', [ApplicationStatus::Pending, ApplicationStatus::Verified])
                    ->get();

                foreach ($pendingApplications as $pendingApp) {
                    $pendingApp->update([
                        'status' => ApplicationStatus::Rejected,
                        'rejection_reason' => 'Program capacity reached (0 slots remaining).',
                    ]);
                }
            }

            return true;
        });

        if (! $approved) {
            Storage::disk('local')->delete($path);

            return back()->withErrors(['application' => 'This student already has an active sponsorship or the program has no remaining slots.']);
        }

        $this->audit($request, 'sponsor.application.confirmed', 'applications');

        $studentUser = $application->studentProfile->user ?? null;

        if ($studentUser !== null) {
            $studentUser->notify(new ApplicationStatusUpdated($application, ApplicationStatus::Approved));
        }

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
            'Content-Disposition' => 'inline; filename="'.basename($application->sponsor_approval_path).'"',
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
            ->whereHas('sponsorshipProgram', fn ($query) => $query->where('sponsor_id', $sponsorId))
            ->where('confirmation_status', ConfirmationStatus::Confirmed)
            ->with(['fixedList.items.application', 'sponsorshipProgram'])
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
