<?php

namespace App\Http\Controllers\Sponsor;

use App\Enums\ApplicationStatus;
use App\Enums\ConfirmationStatus;
use App\Enums\GeneratedBatchStatus;
use App\Http\Controllers\Concerns\ResolvesModuleContext;
use App\Http\Controllers\Controller;
use App\Models\AcademicProgram;
use App\Models\Application;
use App\Models\GeneratedBatch;
use App\Models\Sponsor;
use App\Models\SponsorApproval;
use App\Models\StudentProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $pendingReviewCount = GeneratedBatch::query()
            ->whereIn('sponsorship_program_id', $sponsorProgramIds)
            ->where('status', GeneratedBatchStatus::Submitted)
            ->count();

        return view('sponsor.dashboard', [
            'user' => $user,
            'sponsor' => $sponsor,
            'connectedPrograms' => $sponsor?->sponsorshipPrograms()->count() ?? 0,
            'listsPendingReview' => $pendingReviewCount,
            'uploadedApprovals' => GeneratedBatch::query()
                ->whereHas('sponsorshipProgram', fn ($query) => $query->where('sponsor_id', $sponsor?->id))
                ->where('status', GeneratedBatchStatus::Approved)
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

        $generatedBatches = GeneratedBatch::query()
            ->whereHas('sponsorshipProgram', fn ($query) => $query->where('sponsor_id', $sponsor->id))
            ->where('status', GeneratedBatchStatus::Submitted)
            ->with(['sponsorshipProgram', 'latestApproval'])
            ->when($request->filled('sponsorship_program_id'), fn ($query) => $query->where('sponsorship_program_id', $request->integer('sponsorship_program_id')))
            ->latest()
            ->get();

        return view('sponsor.approvals.index', [
            'user' => $this->actor($request),
            'sponsor' => $sponsor,
            'generatedBatches' => $generatedBatches,
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

    public function show(Request $request, GeneratedBatch $generatedBatch): View
    {
        $sponsor = $this->sponsorOrganization($request);
        $this->assertOwnsBatch($sponsor, $generatedBatch);
        abort_if($generatedBatch->status === GeneratedBatchStatus::Saved, 403, 'FASSG must submit this batch before Sponsor review.');

        $generatedBatch->load([
            'sponsorshipProgram',
            'items',
            'items.application.studentProfile.user',
            'latestApproval',
            'sponsorApprovals',
            'createdByFassg',
        ]);

        return view('sponsor.lists.show', [
            'user' => $this->actor($request),
            'sponsor' => $sponsor,
            'list' => $generatedBatch,
            'generatedBatch' => $generatedBatch,
            'currentApproval' => $generatedBatch->currentApproval(),
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
            ->whereHas('batchCandidates.generatedBatch', fn ($batch) => $batch->where('status', GeneratedBatchStatus::Submitted))
            ->when($academicProgramId > 0, fn ($query) => $query->whereHas('studentProfile', fn ($pq) => $pq->where('academic_program_id', $academicProgramId)))
            ->when($course !== '', fn ($query) => $query->whereHas('studentProfile', fn ($pq) => $pq
                ->where('course', $course)
                ->orWhereHas('academicProgram', fn ($ap) => $ap->where('name', $course))))
            ->with(['studentProfile.user', 'sponsorshipProgram'])
            ->latest('created_at')
            ->get();

        $generatedBatches = GeneratedBatch::query()
            ->whereHas('sponsorshipProgram', fn ($query) => $query->where('sponsor_id', $sponsor->id))
            ->where('status', GeneratedBatchStatus::Submitted)
            ->with(['sponsorshipProgram', 'latestApproval'])
            ->latest()
            ->get();

        $academicPrograms = AcademicProgram::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $courses = AcademicProgram::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name')
            ->concat(StudentProfile::query()
                ->whereNotNull('course')
                ->distinct()
                ->pluck('course'))
            ->filter()
            ->unique()
            ->values();

        return view('sponsor.applicants.index', [
            'user' => $this->actor($request),
            'sponsor' => $sponsor,
            'applicants' => $applicants,
            'generatedBatches' => $generatedBatches,
            'fixedLists' => $generatedBatches,
            'academicPrograms' => $academicPrograms,
            'courses' => $courses,
        ]);
    }

    public function confirmApplication(Request $request, Application $application): RedirectResponse
    {
        $sponsor = $this->sponsorOrganization($request);
        abort_unless($sponsor->ownsProgram($application->sponsorshipProgram), 403);

        return back()->withErrors(['approval' => 'Sponsor decisions require the complete FASSG-created Generated Batch. Open that batch to approve or reject it.']);
    }

    public function reject(Request $request, Application $application): RedirectResponse
    {
        $sponsor = $this->sponsorOrganization($request);
        abort_unless($sponsor->ownsProgram($application->sponsorshipProgram), 403);

        return back()->withErrors(['approval' => 'Individual Sponsor decisions are disabled. Review the complete FASSG-created Generated Batch.']);
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
            ->whereIn('confirmation_status', [ConfirmationStatus::Confirmed, ConfirmationStatus::Rejected])
            ->with(['generatedBatch.items.application', 'sponsorshipProgram'])
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

    private function assertOwnsBatch(Sponsor $sponsor, GeneratedBatch $generatedBatch): void
    {
        $generatedBatch->loadMissing('sponsorshipProgram');

        abort_unless($sponsor->ownsProgram($generatedBatch->sponsorshipProgram), 403);
    }
}
