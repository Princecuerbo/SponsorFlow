<?php

namespace App\Http\Controllers\Fassg;

use App\Enums\ApplicationStatus;
use App\Enums\DocumentType;
use App\Enums\FixedListItemStatus;
use App\Enums\FixedListStatus;
use App\Http\Controllers\Concerns\ResolvesModuleContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Fassg\RejectApplicationRequest;
use App\Http\Requests\Fassg\VerifyApplicationRequest;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\FixedList;
use App\Models\SponsorshipProgram;
use App\Models\StudentProfile;
use App\Notifications\ApplicationStatusUpdated;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ApplicantVerificationController extends Controller
{
    use ResolvesModuleContext;

    public function index(Request $request): View
    {
        $search    = $request->string('q')->trim()->toString();
        $programId = $request->integer('program_id', 0);
        $campus    = $request->string('campus')->trim()->toString();
        $status    = $request->string('status')->trim()->toString();

        $actionableStatuses = [
            ApplicationStatus::Pending,
            ApplicationStatus::Verified,
            ApplicationStatus::ResubmissionRequested,
        ];

        $filterableStatuses = [
            ApplicationStatus::Pending,
            ApplicationStatus::Verified,
            ApplicationStatus::Approved,
            ApplicationStatus::ResubmissionRequested,
            ApplicationStatus::Rejected,
        ];

        $applications = Application::query()
            ->with(['studentProfile.user', 'sponsorshipProgram.sponsor', 'documents'])
            ->whereHas('studentProfile', function ($q) use ($campus): void {
                $q->where('is_sle_fhe_verified', true);
                if ($campus !== '') {
                    $q->where('campus', $campus);
                }
            })
            ->when($search !== '', function ($q) use ($search): void {
                $q->whereHas('studentProfile', function ($pq) use ($search): void {
                    $pq->where('student_id_number', 'like', "%{$search}%")
                        ->orWhere('course', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($programId > 0, fn ($q) => $q->where('sponsorship_program_id', $programId))
            ->when(
                $status !== '' && in_array(ApplicationStatus::tryFrom($status), $filterableStatuses, true),
                fn ($q) => $q->where('status', $status)
            )
            ->when(
                $status === '',
                fn ($q) => $q->whereIn('status', $actionableStatuses)
            )
            ->when(
                // When a specific program is selected, rank the queue by best GWA first
                // (ASC = best to lowest) so officers can shortlist the top candidates
                // against the program's available slots.
                $programId > 0,
                fn ($q) => $q
                    ->orderBy('applications.gpa_submitted', 'asc')
                    ->orderBy('applications.submitted_at', 'asc'),
                fn ($q) => $q->latest()
            )
            ->paginate(15)
            ->withQueryString();

        $programs = SponsorshipProgram::query()
            ->with('sponsor')
            ->orderBy('program_name')
            ->get();

        $selectedProgram = $programId > 0 ? $programs->firstWhere('id', $programId) : null;

        $scoped = Application::query()
            ->whereHas('studentProfile', fn ($q) => $q->where('is_sle_fhe_verified', true));

        $pendingCount      = (clone $scoped)->where('status', ApplicationStatus::Pending)->count();
        $verifiedCount     = (clone $scoped)->where('status', ApplicationStatus::Verified)->count();
        $approvedCount     = (clone $scoped)->where('status', ApplicationStatus::Approved)->count();
        $resubmissionCount = (clone $scoped)->where('status', ApplicationStatus::ResubmissionRequested)->count();
        $rejectedCount     = (clone $scoped)->where('status', ApplicationStatus::Rejected)->count();

        return view('fassg.applications.index', [
            'user'                 => $this->actor($request),
            'pendingApplications'  => $applications,
            'pendingCount'         => $pendingCount,
            'verifiedCount'        => $verifiedCount,
            'approvedCount'        => $approvedCount,
            'resubmissionCount'    => $resubmissionCount,
            'rejectedCount'        => $rejectedCount,
            'programs'             => $programs,
            'selectedProgram'      => $selectedProgram,
            'availableSlots'       => $selectedProgram?->available_slots ?? null,
            'selectedProgramId'    => $programId,
        ]);
    }

    public function createBatch(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'batch_name' => ['required', 'string', 'max:150'],
            'sponsorship_program_id' => ['required', 'integer', 'exists:sponsorship_programs,id'],
            'selected_applications' => ['required', 'array', 'min:1'],
            'selected_applications.*' => ['integer'],
        ]);

        $programId = (int) $validated['sponsorship_program_id'];
        $applicationIds = array_values(array_unique(array_map('intval', $validated['selected_applications'])));

        $applications = Application::query()
            ->where('sponsorship_program_id', $programId)
            ->whereIn('id', $applicationIds)
            ->with('studentProfile.user')
            ->get();

        if ($applications->isEmpty()) {
            return back()->withErrors([
                'selected_applications' => 'Select at least one application belonging to the target program.',
            ]);
        }

        $list = DB::transaction(function () use ($applications, $programId, $request, $validated): FixedList {
            $list = FixedList::query()->create([
                'sponsorship_program_id' => $programId,
                'batch_name' => $validated['batch_name'],
                'uploaded_by_fassg_id' => $this->actor($request)->id,
                'total_names' => 0,
                'status' => FixedListStatus::Saved,
            ]);

            foreach ($applications as $application) {
                $profile = $application->studentProfile;

                if ($profile === null) {
                    continue;
                }

                $name = $profile->user?->name ?? trim(
                    ($profile->first_name ?? '') . ' ' . ($profile->middle_name ?? '') . ' ' . ($profile->last_name ?? '')
                );

                $list->items()->updateOrCreate(
                    ['application_id' => $application->id],
                    [
                        'student_name' => $name !== '' ? $name : 'Unknown',
                        'student_id_number' => $profile->student_id_number ?: null,
                        'course' => $profile->course ?: 'Unspecified',
                        'year_level' => $profile->year_level ?? 1,
                        'campus' => $profile->campus ?: null,
                        'is_sle_fhe_verified' => (bool) $profile->is_sle_fhe_verified,
                        'status' => FixedListItemStatus::Pending,
                    ],
                );
            }

            $list->update(['total_names' => $list->items()->count()]);

            return $list;
        });

        $this->audit($request, 'fassg.fixed_list.generated_from_applications', 'fixed_lists');

        return redirect()
            ->route('fassg.fixed-lists.show', $list)
            ->with('status', "Batch list '{$list->batch_name}' created from {$list->total_names} applicant(s). Review and submit when ready.");
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

        $requiredDocumentTypes = $application->sponsorshipProgram?->requiredDocumentCanonicalValues() ?? [];

        $submittedDocumentTypes = $application->documents
            ->pluck('document_type')
            ->map(static fn ($type): string => DocumentType::canonicalValue($type))
            ->unique()
            ->values()
            ->all();

        if (array_diff($requiredDocumentTypes, $submittedDocumentTypes) !== []) {
            return back()->withErrors([
                'application' => 'The Certificate of Grades and Proof of Residence / Barangay Certificate are required.',
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

    public function requestResubmission(Request $request, Application $application): RedirectResponse
    {
        $validDocumentTypes = collect(DocumentType::cases())
            ->map(static fn (DocumentType $type): string => DocumentType::canonicalValue($type))
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

        if ($application->studentProfile->user !== null) {
            $application->studentProfile->user->notify(
                new ApplicationStatusUpdated($application, ApplicationStatus::ResubmissionRequested),
            );
        }

        $this->audit($request, 'fassg.application.resubmission_requested', 'applications');

        return back()->with('status', 'Resubmission requested. The student has been notified to upload the corrected documents.');
    }

    private function verifyStatus(Request $request, Application $application): RedirectResponse
    {
        if ($application->status !== ApplicationStatus::Pending) {
            return back()->withErrors(['application' => 'Only pending applications can be verified.']);
        }

        $application->loadMissing(['studentProfile', 'sponsorshipProgram', 'documents']);

        $required = $application->sponsorshipProgram?->requiredDocumentCanonicalValues() ?? [];

        $submitted = $application->documents
            ->pluck('document_type')
            ->map(static fn ($type): string => DocumentType::canonicalValue($type))
            ->unique()
            ->values()
            ->all();

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
