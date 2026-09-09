<?php

namespace App\Http\Controllers\Fassg;

use App\Enums\ApplicationStatus;
use App\Enums\DocumentType;
use App\Enums\ProgramCategory;
use App\Http\Controllers\Concerns\ResolvesModuleContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Fassg\ApproveApplicationRequest;
use App\Http\Requests\Fassg\RejectApplicationRequest;
use App\Models\AcademicProgram;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\SponsorshipProgram;
use App\Models\StudentProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class VerificationController extends Controller
{
    use ResolvesModuleContext;

    public function index(Request $request): View
    {
        $search           = $request->string('q')->trim()->toString();
        $academicProgramId = $request->integer('academic_program_id', 0);
        $programId        = $request->integer('program_id', 0);
        $category         = $request->string('category')->trim()->toString();
        $statusFilter     = $request->string('status')->trim()->toString();

        $pendingSleFheCount = StudentProfile::where('is_sle_fhe_verified', false)->count();

        $pendingAppCount = Application::where('status', 'Pending')
            ->whereHas('studentProfile', fn ($q) => $q->where('is_sle_fhe_verified', true))
            ->count();

        $includeProfiles = in_array($statusFilter, ['', 'Pending', 'pending_sle_fhe'], true);

        // Student profiles (unverified SLE-FHE) — included when All, Pending, or Pending SLE-FHE is selected
        $profiles = $includeProfiles
            ? StudentProfile::query()
                ->with(['user', 'applications.documents'])
                ->where('is_sle_fhe_verified', false)
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($query) use ($search): void {
                        $query->where('student_id_number', 'like', "%{$search}%")
                            ->orWhere('course', 'like', "%{$search}%")
                            ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%"));
                    });
                })
                ->when($academicProgramId > 0, fn ($q) => $q->where('academic_program_id', $academicProgramId))
                ->latest()
                ->get()
            : collect();

        $baseQuery = Application::query()
            ->with(['studentProfile.user', 'sponsorshipProgram.sponsor', 'documents'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->whereHas('studentProfile', function ($pq) use ($search): void {
                    $pq->where('student_id_number', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($academicProgramId > 0, fn ($q) => $q->whereHas(
                'studentProfile',
                fn ($pq) => $pq->where('academic_program_id', $academicProgramId)
            ))
            ->when($programId > 0, fn ($q) => $q->where('sponsorship_program_id', $programId))
            ->when($category !== '', fn ($q) => $q->whereHas(
                'sponsorshipProgram',
                fn ($pq) => $pq->where('category', $category)
            ))
            ->whereHas('studentProfile', fn ($pq) => $pq->where('is_sle_fhe_verified', true));

        $applications = $statusFilter === 'pending_sle_fhe'
            ? collect()
            : (clone $baseQuery)
                ->when(
                    $statusFilter !== '' && ApplicationStatus::tryFrom($statusFilter),
                    fn ($q) => $q->where('status', $statusFilter)
                )
                ->latest('submitted_at')
                ->get();

        $verificationItems = $profiles->map(
            fn (StudentProfile $profile): array => ['type' => 'student', 'profile' => $profile, 'application' => null],
        )->concat($applications->map(
            fn (Application $application): array => ['type' => 'application', 'profile' => $application->studentProfile, 'application' => $application],
        ))->values();

        $academicPrograms = AcademicProgram::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $programs = SponsorshipProgram::query()
            ->with('sponsor')
            ->orderBy('program_name')
            ->get();

        $statusCounts = [
            'pending'      => $pendingAppCount,
            'verified'     => (clone $baseQuery)->where('status', ApplicationStatus::Verified)->count(),
            'approved'     => (clone $baseQuery)->where('status', ApplicationStatus::Approved)->count(),
            'rejected'     => (clone $baseQuery)->where('status', ApplicationStatus::Rejected)->count(),
            'resubmission' => (clone $baseQuery)->where('status', ApplicationStatus::ResubmissionRequested)->count(),
        ];

        return view('fassg.verification.index', [
            'user'                => $this->actor($request),
            'verificationItems'   => $verificationItems,
            'pendingStudents'     => $profiles->count(),
            'pendingApplications' => $applications->count(),
            'pendingSleFheCount'  => $pendingSleFheCount,
            'pendingAppCount'     => $pendingAppCount,
            'academicPrograms'    => $academicPrograms,
            'programs'            => $programs,
            'categories'          => ProgramCategory::cases(),
            'statusCounts'        => $statusCounts,
        ]);
    }

    public function show(Request $request, Application $application): View
    {
        $application->load([
            'studentProfile.user',
            'sponsorshipProgram.sponsor',
            'documents',
        ]);

        $profile = $application->studentProfile;
        $program = $application->sponsorshipProgram;

        $eligibilityErrors = $program->eligibilityErrors(
            $profile,
            (float) $application->gpa_submitted,
            (string) $application->address_submitted,
            (bool) $application->is_rural_submitted,
        );

        return view('fassg.verification.show', [
            'user'             => $this->actor($request),
            'application'      => $application,
            'eligibilityErrors' => $eligibilityErrors,
        ]);
    }

    public function viewDocument(Application $application, ApplicationDocument $document): BinaryFileResponse
    {
        abort_unless($document->application_id === $application->id, 404);

        $path = Storage::disk('local')->path($document->file_path);
        abort_unless(is_file($path), 404, 'Document file not found.');

        $fileName = addcslashes(basename($document->file_name), "\\\"");
        $mimeType = mime_content_type($path) ?: 'application/octet-stream';

        return response()->file($path, [
            'Content-Type'        => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
        ]);
    }

    public function verifyStudent(Request $request, StudentProfile $studentProfile): RedirectResponse
    {
        $studentProfile->update(['is_sle_fhe_verified' => true]);

        $this->audit($request, 'fassg.student.sle_fhe_verified', 'student_profiles');

        return back()->with('success', 'Student SLE-FHE status verified successfully.');
    }

    /**
     * Verify that FASSG approves an application (Pending or Verified) and forward
     * it to the Sponsor Review queue. Final approval and slot reservation are
     * handled by the sponsor.
     */
    public function approve(ApproveApplicationRequest $request, Application $application): RedirectResponse
    {
        if (! in_array($application->status, [ApplicationStatus::Pending, ApplicationStatus::Verified], true)) {
            return back()->withErrors([
                'application' => 'This application cannot be verified in its current status.',
            ]);
        }

        $application->loadMissing(['studentProfile.user', 'sponsorshipProgram.sponsor', 'documents']);
        $program = $application->sponsorshipProgram;

        // Validate required documents and eligibility criteria
        $required  = $program->requiredDocumentCanonicalValues();
        $submitted = $application->documents
            ->pluck('document_type')
            ->map(fn ($type): string => DocumentType::canonicalValue($type))
            ->unique()
            ->values()
            ->all();

        if (array_diff($required, $submitted) !== []) {
            return back()->withErrors(['application' => 'All required program supporting documents must be uploaded before verifying.']);
        }

        $eligibilityErrors = $program->eligibilityErrors(
            $application->studentProfile,
            (float) $application->gpa_submitted,
            (string) $application->address_submitted,
            (bool) $application->is_rural_submitted,
        );

        if ($eligibilityErrors !== []) {
            return back()->withErrors(['application' => $eligibilityErrors]);
        }

        $application->update([
            'status'      => ApplicationStatus::Verified,
            'verified_at' => now(),
        ]);

        $this->audit($request, 'fassg.application.verified', 'applications');

        $studentName = $application->studentProfile->user->name ?? 'Student';

        return redirect()
            ->route('fassg.verification.index')
            ->with('status', "Application for {$studentName} verified and forwarded to {$program->program_name} for sponsor review. Final approval and slot reservation are handled by the sponsor.");
    }

    public function reject(RejectApplicationRequest $request, Application $application): RedirectResponse
    {
        if (! in_array($application->status, [ApplicationStatus::Pending, ApplicationStatus::Verified], true)) {
            return back()->withErrors(['application' => 'This application can no longer be rejected.']);
        }

        $reason = $request->string('reason')->toString();
        $application->update([
            'status'           => ApplicationStatus::Rejected,
            'rejection_reason' => $reason,
        ]);
        $this->audit($request, 'fassg.application.rejected', 'applications');

        return back()->with('status', "Application rejected: {$reason}");
    }

    public function requestResubmission(Request $request, Application $application): RedirectResponse
    {
        $validDocumentTypes = collect(DocumentType::cases())
            ->map(fn (DocumentType $type): string => DocumentType::canonicalValue($type))
            ->unique()
            ->values()
            ->all();

        $validated = $request->validate([
            'resubmission_notes' => ['required', 'string', 'min:5', 'max:1000'],
            'requested_documents' => ['required', 'array', 'min:1'],
            'requested_documents.*' => ['string', Rule::in($validDocumentTypes)],
        ]);

        if (! in_array($application->status, [ApplicationStatus::Pending, ApplicationStatus::Verified], true)) {
            return back()->withErrors(['application' => 'This application cannot be sent for resubmission in its current status.']);
        }

        $application->update([
            'status'              => ApplicationStatus::ResubmissionRequested,
            'resubmission_notes'  => trim($validated['resubmission_notes']),
            'requested_documents' => array_values(array_unique($validated['requested_documents'])),
        ]);

        $this->audit($request, 'fassg.application.resubmission_requested', 'applications');

        return back()->with('status', 'Resubmission requested. The student has been notified to upload corrected documents.');
    }

    public function rejectStudent(Request $request, StudentProfile $studentProfile): RedirectResponse
    {
        $this->audit($request, 'fassg.student.sle_fhe_fix_requested', 'student_profiles');

        return back()->with('status', 'Student verification was returned for correction.');
    }
}
