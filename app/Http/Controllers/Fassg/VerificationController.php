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
use App\Models\SleFheRegistry;
use App\Models\SleFheRejection;
use App\Models\SleFheRequest;
use App\Models\SleFheVerification;
use App\Models\SponsorshipProgram;
use App\Models\StudentProfile;
use App\Notifications\ApplicationStatusUpdated;
use App\Notifications\SleFheVerificationUpdated;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class VerificationController extends Controller
{
    use ResolvesModuleContext;

    public function index(Request $request): View
    {
        $search = $request->string('q')->trim()->toString();
        $academicProgramId = $request->integer('academic_program_id', 0);
        $programId = $request->integer('program_id', 0);
        $category = $request->string('category')->trim()->toString();
        $statusFilter = $request->string('status')->trim()->toString();

        $pendingSleFheCount = StudentProfile::whereDoesntHave('sleFheVerification')->count();

        $pendingAppCount = Application::where('status', 'Pending')
            ->whereHas('studentProfile', fn($q) => $q->whereHas('sleFheVerification'))
            ->count();

        $isPendingSleFhe = in_array(strtolower($statusFilter), ['pending_sle_fhe', 'pending sle-fhe'], true);
        $isAllStatuses = $statusFilter === '';

        // Student profiles awaiting SLE-FHE verification are included only when 'Pending SLE-FHE' or 'All statuses' is selected.
        // Selecting 'Pending' strictly queries applications without mixing profile records.
        $includeProfiles = $isPendingSleFhe || ($isAllStatuses && $request->integer('page', 1) <= 1);

        $profiles = $includeProfiles
            ? StudentProfile::query()
            ->with(['user', 'applications.documents'])
            ->whereDoesntHave('sleFheVerification')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('student_id_number', 'like', "%{$search}%")
                        ->orWhere('course', 'like', "%{$search}%")
                        ->orWhereHas('academicProgram', fn($ap) => $ap->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($academicProgramId > 0, fn($q) => $q->where('academic_program_id', $academicProgramId))
            ->latest()
            ->get()
            : collect();

        $baseQuery = Application::query()
            ->with(['studentProfile.user', 'sponsorshipProgram.sponsor', 'documents'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->whereHas('studentProfile', function ($pq) use ($search): void {
                    $pq->where('student_id_number', 'like', "%{$search}%")
                        ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($academicProgramId > 0, fn($q) => $q->whereHas(
                'studentProfile',
                fn($pq) => $pq->where('academic_program_id', $academicProgramId)
            ))
            ->when($programId > 0, fn($q) => $q->where('sponsorship_program_id', $programId))
            ->when($category !== '', fn($q) => $q->whereHas(
                'sponsorshipProgram',
                fn($pq) => $pq->where('category', $category)
            ))
            ->whereHas('studentProfile', fn($pq) => $pq->whereHas('sleFheVerification'));

        $actionableStatuses = [
            ApplicationStatus::Pending,
            ApplicationStatus::Verified,
            ApplicationStatus::ResubmissionRequested,
        ];

        // When Status Filter = 'Pending SLE-FHE', only unverified student profiles are returned (no applications).
        $applications = $isPendingSleFhe
            ? collect()
            : (clone $baseQuery)
            ->whereIn('status', $actionableStatuses)
            ->when(
                $statusFilter !== '' && in_array(ApplicationStatus::tryFrom($statusFilter), $actionableStatuses, true),
                fn($q) => $q->where('status', $statusFilter)
            )
            ->latest()
            ->paginate(15);

        $verificationItems = $profiles->map(
            fn(StudentProfile $profile): array => ['type' => 'student', 'profile' => $profile, 'application' => null],
        )->concat($applications->map(
            fn(Application $application): array => ['type' => 'application', 'profile' => $application->studentProfile, 'application' => $application],
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
            'pending' => $pendingAppCount,
            'verified' => (clone $baseQuery)->where('status', ApplicationStatus::Verified)->count(),
            'approved' => (clone $baseQuery)->where('status', ApplicationStatus::Approved)->count(),
            'rejected' => (clone $baseQuery)->where('status', ApplicationStatus::Rejected)->count(),
            'resubmission' => (clone $baseQuery)->where('status', ApplicationStatus::ResubmissionRequested)->count(),
        ];

        return view('fassg.verification.index', [
            'user' => $this->actor($request),
            'verificationItems' => $verificationItems,
            'pendingStudents' => $profiles->count(),
            'pendingApplications' => method_exists($applications, 'total') ? $applications->total() : $applications->count(),
            'pendingSleFheCount' => $pendingSleFheCount,
            'pendingAppCount' => $pendingAppCount,
            'academicPrograms' => $academicPrograms,
            'programs' => $programs,
            'categories' => ProgramCategory::cases(),
            'statusCounts' => $statusCounts,
            'applications' => $applications,
        ]);
    }

    public function sleFheIndex(Request $request): View
    {
        $search = $request->string('q')->trim()->toString();
        $campus = $request->string('campus')->trim()->toString();

        $pendingRequests = $this->pendingSleFheRequestQuery()
            ->with(['studentProfile.user', 'studentProfile.academicProgram'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->whereHas('studentProfile', function (Builder $profile) use ($search): void {
                        $profile->where(function (Builder $profile) use ($search): void {
                            $profile->where('student_id_number', 'like', "%{$search}%")
                                ->orWhere('course', 'like', "%{$search}%")
                                ->orWhereHas('academicProgram', fn($ap) => $ap->where('name', 'like', "%{$search}%"))
                                ->orWhere('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        })
                            ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', "%{$search}%"));
                    });
                });
            })
            ->when($campus !== '', fn(Builder $query) => $query->whereHas('studentProfile', fn(Builder $profile) => $profile->where('campus', $campus)))
            ->latest('submitted_at')
            ->get();

        return view('fassg.sle-fhe.index', [
            'user' => $this->actor($request),
            'pendingRequests' => $pendingRequests,
            'pendingSleFheCount' => $this->pendingSleFheRequestQuery()->count(),
            'verifiedSleFheCount' => SleFheVerification::query()->whereNotNull('verified_by')->whereNotNull('verified_at')->count(),
            'registryMatches' => $this->masterRegistryMatches($pendingRequests),
            'registryTotalCount' => SleFheRegistry::query()->count(),
        ]);
    }

    /**
     * Cross-reference the queued students against the anonymized master
     * SLE-FHE registry. This is advisory only: a match assists the reviewing
     * officer but never auto-approves a request, so the explicit
     * sle_fhe_requests -> sle_fhe_verifications pipeline stays authoritative.
     *
     * @param  Collection<int, SleFheRequest>  $pendingRequests
     * @return Collection<string, SleFheRegistry>
     */
    private function masterRegistryMatches($pendingRequests)
    {
        $studentIds = $pendingRequests
            ->map(fn(SleFheRequest $request): string => trim((string) ($request->studentProfile?->student_id_number ?? '')))
            ->filter()
            ->unique()
            ->values();

        if ($studentIds->isEmpty()) {
            return collect();
        }

        return SleFheRegistry::query()
            ->where('is_verified', true)
            ->whereIn('student_id', $studentIds)
            ->get()
            ->keyBy('student_id');
    }

    public function verifiedIndex(Request $request): View
    {
        $search = $request->string('q')->trim()->toString();
        $campus = $request->string('campus')->trim()->toString();

        $verifiedVerifications = SleFheVerification::query()->whereNotNull('verified_by')->whereNotNull('verified_at')
            ->with(['studentProfile.user', 'studentProfile.academicProgram', 'verifiedBy'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->whereHas('studentProfile', function (Builder $profile) use ($search): void {
                        $profile->where(function (Builder $profile) use ($search): void {
                            $profile->where('student_id_number', 'like', "%{$search}%")
                                ->orWhere('course', 'like', "%{$search}%")
                                ->orWhereHas('academicProgram', fn($ap) => $ap->where('name', 'like', "%{$search}%"))
                                ->orWhere('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        })
                            ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', "%{$search}%"));
                    });
                });
            })
            ->when($campus !== '', fn(Builder $query) => $query->whereHas('studentProfile', fn(Builder $profile) => $profile->where('campus', $campus)))
            ->latest('verified_at')
            ->get();

        return view('fassg.sle-fhe.verified', [
            'user' => $this->actor($request),
            'verifiedVerifications' => $verifiedVerifications,
            'verifiedCount' => SleFheVerification::query()->whereNotNull('verified_by')->whereNotNull('verified_at')->count(),
            'pendingSleFheCount' => $this->pendingSleFheRequestQuery()->count(),
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

        $eligibilityErrors = $program->beneficiaryEligibilityErrors($application);

        return view('fassg.verification.show', [
            'user' => $this->actor($request),
            'application' => $application,
            'eligibilityErrors' => $eligibilityErrors,
        ]);
    }

    public function viewDocument(Application $application, ApplicationDocument $document): BinaryFileResponse
    {
        abort_unless($document->application_id === $application->id, 404);

        $path = Storage::disk('local')->path($document->file_path);
        abort_unless(is_file($path), 404, 'Document file not found.');

        $fileName = addcslashes(basename($document->file_name), '\\"');
        $mimeType = mime_content_type($path) ?: 'application/octet-stream';

        return response()->file($path, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
        ]);
    }

    public function verifyStudent(Request $request, StudentProfile $studentProfile): RedirectResponse
    {
        abort_unless($request->user()?->isFassg(), 403);
        $validated = $request->validate([
            'verification_request_id' => ['required', 'integer'],
            'confirm_identity' => ['required', 'accepted'],
            'override_registry_mismatch' => ['nullable', 'boolean'],
            'override_reason' => ['nullable', 'string', 'min:10', 'max:1000'],
            'evidence_reference' => ['nullable', 'string', 'min:5', 'max:1000'],
        ]);
        DB::transaction(function () use ($request, $studentProfile, $validated): void {
            $profile = StudentProfile::query()->lockForUpdate()->findOrFail($studentProfile->id);
            if ($profile->sleFheVerification()->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages(['verification' => 'Existing verification is preserved. Corrections require an evidence-history review; it cannot be overwritten here.']);
            }
            $pending = $profile->sleFheRequests()->where('status', 'pending')->whereNotNull('submitted_at')
                ->lockForUpdate()->find($validated['verification_request_id']);
            if ($pending === null || ! $profile->hasCompleteIdentity() || blank($pending->province)
                || blank($pending->municipality_city) || blank($pending->barangay) || mb_strlen($pending->full_address) > 255) {
                throw \Illuminate\Validation\ValidationException::withMessages(['verification' => 'A matching pending request, valid student identity and complete address evidence are required.']);
            }
            $registry = $this->registryMatchFor($profile);
            if ($registry === null && (! $request->boolean('override_registry_mismatch')
                || blank($validated['override_reason'] ?? null) || blank($validated['evidence_reference'] ?? null))) {
                throw \Illuminate\Validation\ValidationException::withMessages(['override_registry_mismatch' => 'A registry mismatch requires an explicit FASSG override, a reason and a traceable office evidence reference.']);
            }
            $verification = SleFheVerification::query()->create([
                'student_profile_id' => $profile->id,
                'verified_address' => $pending->full_address,
                'verified_by' => $request->user()->id,
                'verified_at' => now(),
            ]);
            $pending->update(['status' => 'verified']);
            $details = json_encode([
                'verification_id' => $verification->id, 'request_id' => $pending->id,
                'student_profile_id' => $profile->id, 'student_id_number' => $profile->student_id_number,
                'request_address' => $pending->only(['province', 'municipality_city', 'barangay', 'street_purok']),
                'registry' => $registry?->only(['id', 'student_id', 'campus', 'course', 'year_level', 'purok', 'barangay', 'municipality', 'is_verified', 'updated_at']),
                'override' => $registry === null,
                'override_reason' => $registry === null ? $validated['override_reason'] : null,
                'evidence_reference' => $validated['evidence_reference'] ?? null,
            ]);
            \App\Models\AuditLog::record('fassg.student.sle_fhe_verified', 'student_profiles', $request->user(), $request->ip(), $details);
            if ($registry === null) {
                \App\Models\AuditLog::record('fassg.student.sle_fhe_verified_registry_override', 'student_profiles', $request->user(), $request->ip(), $details);
            }
        }, 3);
        $studentProfile->user?->notify(new SleFheVerificationUpdated($studentProfile, 'verified'));

        return back()->with('success', 'Student SLE-FHE status verified successfully.');
    }

    /**
     * The single registry row for a student, keyed by student ID.
     */
    private function registryMatchFor(StudentProfile $studentProfile): ?SleFheRegistry
    {
        $studentId = trim((string) $studentProfile->student_id_number);

        if ($studentId === '') {
            return null;
        }

        return SleFheRegistry::query()->byStudentId($studentId)->where('is_verified', true)->first();
    }

    /** FASSG verification establishes eligibility; only a submitted batch reaches Sponsor review. */
    public function approve(ApproveApplicationRequest $request, Application $application): RedirectResponse
    {
        return app(ApplicantVerificationController::class)->verify($request, $application);
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

        if ($application->studentProfile->user !== null) {
            $application->studentProfile->user->notify(new ApplicationStatusUpdated($application, ApplicationStatus::Rejected));
        }

        $this->audit($request, 'fassg.application.rejected', 'applications');

        return back()->with('status', "Application rejected: {$reason}");
    }

    public function requestResubmission(Request $request, Application $application): RedirectResponse
    {
        $validDocumentTypes = collect(DocumentType::cases())
            ->map(fn(DocumentType $type): string => DocumentType::canonicalValue($type))
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
            'status' => ApplicationStatus::ResubmissionRequested,
            'resubmission_notes' => trim($validated['resubmission_notes']),
            'requested_documents' => array_values(array_unique($validated['requested_documents'])),
        ]);

        if ($application->studentProfile->user !== null) {
            $application->studentProfile->user->notify(new ApplicationStatusUpdated($application, ApplicationStatus::ResubmissionRequested));
        }

        $this->audit($request, 'fassg.application.resubmission_requested', 'applications');

        return back()->with('status', 'Resubmission requested. The student has been notified to upload corrected documents.');
    }

    public function rejectStudent(Request $request, StudentProfile $studentProfile): RedirectResponse
    {
        $validated = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        DB::transaction(function () use ($request, $studentProfile, $validated): void {
            $profile = StudentProfile::query()->lockForUpdate()->findOrFail($studentProfile->id);
            abort_if($profile->sleFheVerification()->exists(), 403, 'Existing verification evidence cannot be overwritten or removed.');
            $pending = $profile->sleFheRequests()->where('status', 'pending')->latest('id')->lockForUpdate()->first();
            if ($pending === null) {
                throw \Illuminate\Validation\ValidationException::withMessages(['verification' => 'There is no pending request to return for correction.']);
            }
            SleFheRejection::query()->create([
                'student_profile_id' => $profile->id, 'rejection_reason' => trim($validated['reason']),
                'rejected_by' => $request->user()->id, 'rejected_at' => now(),
            ]);
            $pending->update(['status' => 'rejected']);
            \App\Models\AuditLog::record('fassg.student.sle_fhe_fix_requested', 'student_profiles', $request->user(), $request->ip(),
                json_encode(['student_profile_id' => $profile->id, 'request_id' => $pending->id, 'reason' => $validated['reason']]));
        });
        $studentProfile->user?->notify(new SleFheVerificationUpdated($studentProfile, 'fix_required'));

        return back()->with('status', 'Student verification was returned for correction. Original request evidence was preserved.');
    }

    private function pendingSleFheRequestQuery(): Builder
    {
        return SleFheRequest::query()
            ->where('status', 'pending')
            ->whereNotExists(function ($query): void {
                $query->select(DB::raw(1))
                    ->from('sle_fhe_verifications')
                    ->whereColumn('sle_fhe_verifications.student_profile_id', 'sle_fhe_requests.student_profile_id');
            });
    }
}
