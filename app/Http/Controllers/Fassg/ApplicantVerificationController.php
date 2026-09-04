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

class ApplicantVerificationController extends Controller
{
    use ResolvesModuleContext;

    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();

        $applications = Application::query()
            ->with(['studentProfile.user', 'sponsorshipProgram', 'documents'])
            ->when(
                $status !== '' && ApplicationStatus::tryFrom($status),
                fn ($query) => $query->where('status', $status),
            )
            ->latest('submitted_at')
            ->get();

        return view('fassg.verification.index', [
            'user' => $this->actor($request),
            'applications' => $applications,
            'status' => $status,
        ]);
    }

    public function show(Request $request, Application $application): View
    {
        $application->load(['studentProfile.user', 'sponsorshipProgram.sponsor', 'documents']);

        $eligibilityErrors = $application->sponsorshipProgram->eligibilityErrors(
            $application->studentProfile,
            (float) $application->gpa_submitted,
            $application->address_submitted,
            (bool) $application->is_rural_submitted,
        );

        return view('fassg.applications.show', [
            'user' => $this->actor($request),
            'application' => $application,
            'eligibilityErrors' => $eligibilityErrors,
        ]);
    }

    public function verify(VerifyApplicationRequest $request, Application $application): RedirectResponse
    {
        if ($application->status !== ApplicationStatus::Pending) {
            return back()->withErrors([
                'application' => 'Only pending applications can be verified.',
            ]);
        }

        $application->loadMissing(['studentProfile', 'sponsorshipProgram', 'documents']);

        $requiredDocumentTypes = array_map(
            static fn (DocumentType $type): string => $type->value,
            DocumentType::requiredForApplication(),
        );
        $submittedDocumentTypes = $application->documents
            ->pluck('document_type')
            ->map(static fn ($type): string => $type instanceof DocumentType ? $type->value : (string) $type)
            ->unique()
            ->all();

        if (array_diff($requiredDocumentTypes, $submittedDocumentTypes) !== []) {
            return back()->withErrors([
                'application' => 'The Certificate of Grades, Proof of Residence, and Barangay Certification are required.',
            ]);
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

        $application->update([
            'status' => ApplicationStatus::Verified,
            'verified_at' => now(),
        ]);

        $this->audit($request, 'fassg.application.verified', 'applications');

        return back()->with('status', 'Application marked as Verified.');
    }

    public function reject(RejectApplicationRequest $request, Application $application): RedirectResponse
    {
        if (! in_array($application->status, [ApplicationStatus::Pending, ApplicationStatus::Verified], true)) {
            return back()->withErrors([
                'application' => 'This application can no longer be rejected.',
            ]);
        }

        $application->update([
            'status' => ApplicationStatus::Rejected,
        ]);

        $this->audit($request, 'fassg.application.rejected', 'applications');

        $reason = $request->string('reason')->toString();
        $message = $reason !== ''
            ? "Application rejected: {$reason}"
            : 'Application marked as Rejected.';

        return back()->with('status', $message);
    }

    public function updateStatus(Request $request, Application $application): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:Verified,Rejected'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        if ($validated['status'] === ApplicationStatus::Verified->value) {
            return $this->verifyStatus($request, $application);
        }

        $application->update(['status' => ApplicationStatus::Rejected]);
        $this->audit($request, 'fassg.application.rejected', 'applications');

        return back()->with('status', 'Application marked as Rejected.');
    }

    private function verifyStatus(Request $request, Application $application): RedirectResponse
    {
        if ($application->status !== ApplicationStatus::Pending) {
            return back()->withErrors(['application' => 'Only pending applications can be verified.']);
        }

        $application->loadMissing(['studentProfile', 'sponsorshipProgram', 'documents']);
        $required = array_map(static fn (DocumentType $type): string => $type->value, DocumentType::requiredForApplication());
        $submitted = $application->documents->pluck('document_type')->map(static fn ($type): string => $type instanceof DocumentType ? $type->value : (string) $type)->unique()->all();

        if (array_diff($required, $submitted) !== []) {
            return back()->withErrors(['application' => 'All required application documents must be uploaded.']);
        }

        $application->update(['status' => ApplicationStatus::Verified, 'verified_at' => now()]);
        $this->audit($request, 'fassg.application.verified', 'applications');

        return back()->with('status', 'Application marked as Verified.');
    }

    public function downloadDocument(Application $application, ApplicationDocument $applicationDocument): BinaryFileResponse
    {
        abort_unless($applicationDocument->application_id === $application->id, 404);

        $path = Storage::disk('public')->path($applicationDocument->file_path);
        abort_unless(is_file($path), 404, 'Document file not found.');

        $fileName = addcslashes(basename($applicationDocument->file_name), "\\\"");
        $mimeType = mime_content_type($path) ?: 'application/octet-stream';

        return response()->file($path, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="'.$fileName.'"',
        ]);
    }

    public function verifySleFhe(Request $request, StudentProfile $studentProfile): RedirectResponse
    {
        $studentProfile->update(['is_sle_fhe_verified' => true]);

        $this->audit($request, 'fassg.student.sle_fhe_verified', 'student_profiles');

        return back()->with('status', "SLE-FHE verified for {$studentProfile->student_id_number}.");
    }
}
