<?php

namespace App\Http\Controllers\Fassg;

use App\Enums\ApplicationStatus;
use App\Enums\DocumentType;
use App\Enums\ProgramCategory;
use App\Http\Controllers\Concerns\ResolvesModuleContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Fassg\RejectApplicationRequest;
use App\Http\Requests\Fassg\VerifyApplicationRequest;
use App\Models\AcademicProgram;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\SponsorshipProgram;
use App\Models\StudentProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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

        // Student profiles (unverified SLE-FHE) — not affected by application filters
        $profiles = StudentProfile::query()
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
            ->get();

        $applications = Application::query()
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
            'pending'   => $applications->where('status', ApplicationStatus::Pending)->count(),
            'verified'  => $applications->where('status', ApplicationStatus::Verified)->count(),
            'approved'  => $applications->where('status', ApplicationStatus::Approved)->count(),
            'rejected'  => $applications->where('status', ApplicationStatus::Rejected)->count(),
        ];

        return view('fassg.verification.index', [
            'user'              => $this->actor($request),
            'verificationItems' => $verificationItems,
            'pendingStudents'   => $profiles->count(),
            'pendingApplications' => $applications->count(),
            'academicPrograms'  => $academicPrograms,
            'programs'          => $programs,
            'categories'        => ProgramCategory::cases(),
            'statusCounts'      => $statusCounts,
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

    public function verify(VerifyApplicationRequest $request, Application $application): RedirectResponse
    {
        if ($application->status !== ApplicationStatus::Pending) {
            return back()->withErrors(['application' => 'Only pending applications can be verified.']);
        }

        $application->loadMissing(['studentProfile', 'sponsorshipProgram', 'documents']);
        $required  = array_map(static fn (DocumentType $type): string => $type->value, DocumentType::requiredForApplication());
        $submitted = $application->documents
            ->pluck('document_type')
            ->map(static fn ($type): string => $type instanceof DocumentType ? $type->value : (string) $type)
            ->unique()
            ->all();

        if (array_diff($required, $submitted) !== []) {
            return back()->withErrors(['application' => 'The Certificate of Grades, Proof of Residence, and Barangay Certification are required.']);
        }

        $eligibilityErrors = $application->sponsorshipProgram->eligibilityErrors(
            $application->studentProfile,
            (float) $application->gpa_submitted,
            $application->address_submitted,
            (bool) $application->is_rural_submitted,
        );

        if ($eligibilityErrors !== []) {
            return back()->withErrors(['application' => $eligibilityErrors]);
        }

        $application->update(['status' => ApplicationStatus::Verified, 'verified_at' => now()]);
        $this->audit($request, 'fassg.application.verified', 'applications');

        return back()->with('status', 'Application marked as Verified. You may now approve and reserve a slot.');
    }

    /**
     * Approve an application (Pending or Verified) and decrement the program's available slot.
     */
    public function approve(Request $request, Application $application): RedirectResponse
    {
        if (! in_array($application->status, [ApplicationStatus::Pending, ApplicationStatus::Verified], true)) {
            return back()->withErrors([
                'application' => 'This application cannot be approved in its current status.',
            ]);
        }

        $application->loadMissing(['studentProfile.user', 'sponsorshipProgram.sponsor', 'documents']);
        $program = $application->sponsorshipProgram;

        if ($program->available_slots <= 0) {
            return back()->withErrors([
                'application' => 'This program has no remaining slots. The slot cannot be reserved.',
            ]);
        }

        // Validate required documents and eligibility criteria
        $required  = array_map(static fn (DocumentType $type): string => $type->value, DocumentType::requiredForApplication());
        $submitted = $application->documents
            ->pluck('document_type')
            ->map(static fn ($type): string => $type instanceof DocumentType ? $type->value : (string) $type)
            ->unique()
            ->all();

        if (array_diff($required, $submitted) !== []) {
            return back()->withErrors(['application' => 'All supporting documents (Certificate of Grades, Proof of Residence, Barangay Certificate) must be uploaded before approval.']);
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

        DB::transaction(function () use ($application, $program): void {
            // Lock program row to prevent race conditions on slot count
            SponsorshipProgram::query()->lockForUpdate()->find($program->id);

            $decremented = $program->decrementAvailableSlot();

            if (! $decremented) {
                throw new \RuntimeException('No slots available.');
            }

            $application->update([
                'status'      => ApplicationStatus::Approved,
                'verified_at' => $application->verified_at ?? now(),
                'approved_at' => now(),
            ]);

            $application->studentProfile->update([
                'active_sponsorship_id' => $application->id,
            ]);
        });

        $this->audit($request, 'fassg.application.approved', 'applications');

        $studentName = $application->studentProfile->user->name ?? 'Student';

        return redirect()
            ->route('fassg.verification.index')
            ->with('status', "Application for {$studentName} approved and 1 slot successfully reserved on {$program->program_name}.");
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

    public function rejectStudent(Request $request, StudentProfile $studentProfile): RedirectResponse
    {
        $this->audit($request, 'fassg.student.sle_fhe_fix_requested', 'student_profiles');

        return back()->with('status', 'Student verification was returned for correction.');
    }
}
