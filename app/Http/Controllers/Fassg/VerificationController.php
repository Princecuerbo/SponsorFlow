<?php

namespace App\Http\Controllers\Fassg;

use App\Enums\ApplicationStatus;
use App\Enums\DocumentType;
use App\Http\Controllers\Concerns\ResolvesModuleContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Fassg\RejectApplicationRequest;
use App\Http\Requests\Fassg\VerifyApplicationRequest;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\StudentProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class VerificationController extends Controller
{
    use ResolvesModuleContext;

    public function index(Request $request): View
    {
        $search = $request->string('q')->trim()->toString();
        $academicProgramId = $request->integer('academic_program_id', 0);

        $profiles = StudentProfile::query()
            ->with(['user', 'applications.documents'])
            ->where('is_sle_fhe_verified', false)
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('student_id_number', 'like', "%{$search}%")
                        ->orWhere('course', 'like', "%{$search}%")
                        ->orWhereHas('user', fn($userQuery) => $userQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($academicProgramId > 0, fn($query) => $query->where('academic_program_id', $academicProgramId))
            ->latest()
            ->get();

        $applications = Application::query()
            ->with(['studentProfile.user', 'documents'])
            ->where('status', ApplicationStatus::Pending)
            ->when($search !== '', function ($query) use ($search): void {
                $query->whereHas('studentProfile', function ($profileQuery) use ($search): void {
                    $profileQuery->where('student_id_number', 'like', "%{$search}%")
                        ->orWhereHas('user', fn($userQuery) => $userQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($academicProgramId > 0, fn($query) => $query->whereHas('studentProfile', fn($profileQuery) => $profileQuery->where('academic_program_id', $academicProgramId)))
            ->latest('submitted_at')
            ->get();

        $verificationItems = $profiles->map(
            fn(StudentProfile $profile): array => ['type' => 'student', 'profile' => $profile, 'application' => null],
        )->concat($applications->map(
            fn(Application $application): array => ['type' => 'application', 'profile' => $application->studentProfile, 'application' => $application],
        ))->values();

        $academicPrograms = \App\Models\AcademicProgram::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('fassg.verification.index', [
            'user' => $this->actor($request),
            'verificationItems' => $verificationItems,
            'pendingStudents' => $profiles->count(),
            'pendingApplications' => $applications->count(),
            'academicPrograms' => $academicPrograms,
        ]);
    }

    public function show(Request $request, Application $application): View
    {
        $application->load([
            'studentProfile.user',
            'sponsorshipProgram.sponsor',
            'documents',
        ]);

        return view('fassg.verification.show', [
            'user' => $this->actor($request),
            'application' => $application,
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
            'Content-Type' => $mimeType,
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
        $required = array_map(static fn(DocumentType $type): string => $type->value, DocumentType::requiredForApplication());
        $submitted = $application->documents
            ->pluck('document_type')
            ->map(static fn($type): string => $type instanceof DocumentType ? $type->value : (string) $type)
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

        return back()->with('status', 'Application marked as Verified.');
    }

    public function reject(RejectApplicationRequest $request, Application $application): RedirectResponse
    {
        if (! in_array($application->status, [ApplicationStatus::Pending, ApplicationStatus::Verified], true)) {
            return back()->withErrors(['application' => 'This application can no longer be rejected.']);
        }

        $reason = $request->string('reason')->toString();
        $application->update([
            'status' => ApplicationStatus::Rejected,
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
