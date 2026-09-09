<?php

namespace App\Http\Controllers\Student;

use App\Enums\ApplicationStatus;
use App\Enums\DocumentType;
use App\Enums\FixedListStatus;
use App\Http\Controllers\Concerns\ResolvesModuleContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StoreApplicationRequest;
use App\Models\Application;
use App\Models\FixedListItem;
use App\Models\SponsorshipProgram;
use App\Models\StudentProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ApplicationController extends Controller
{
    use ResolvesModuleContext;

    public function programs(Request $request): View
    {
        $profile = $this->studentProfile($request, required: false);

        if ($profile?->is_sle_fhe_verified) {
            $query = SponsorshipProgram::query()->open()->with(['sponsor', 'academicPrograms']);

            if ($request->filled('q')) {
                $search = $request->input('q');
                $query->where(function ($query) use ($search): void {
                    $query->where('program_name', 'like', "%{$search}%")
                        ->orWhereHas('sponsor', function ($sponsorQuery) use ($search): void {
                            $sponsorQuery->where('company_organization_name', 'like', "%{$search}%");
                        });
                });
            }

            if ($request->filled('category')) {
                $query->where('category', $request->input('category'));
            }

            $programs = $query->latest()->get();
        } else {
            $programs = collect();
        }

        $hasActiveGrant = $profile ? Application::where('student_profile_id', $profile->id)
            ->whereIn('status', [ApplicationStatus::Approved, ApplicationStatus::Ongoing])
            ->exists() : false;

        return view('student.programs.index', [
            'user' => $this->actor($request),
            'profile' => $profile,
            'programs' => $programs,
            'hasActiveSponsorship' => $hasActiveGrant,
        ]);
    }

    public function checkEligibility(Request $request, SponsorshipProgram $sponsorshipProgram): JsonResponse
    {
        $profile = $this->studentProfile($request, required: false);

        if ($profile === null || ! $profile->is_sle_fhe_verified) {
            return response()->json([
                'is_eligible' => false,
                'reasons' => ['Complete SLE-FHE verification before applying.'],
            ]);
        }

        $eligibility = $sponsorshipProgram->checkEligibility($profile);

        return response()->json([
            'is_eligible' => $eligibility['is_eligible'],
            'reasons' => $eligibility['reasons'],
        ]);
    }

    public function create(Request $request, string $id): View|RedirectResponse
    {
        $program = SponsorshipProgram::with('academicPrograms')->findOrFail($id);
        $profile = $this->studentProfile($request);
        $student = $profile;

        if (! $profile->is_sle_fhe_verified) {
            return redirect()
                ->route('student.verification.show')
                ->withErrors(['application' => 'Complete SLE-FHE verification before applying.']);
        }

        if ($program->isApplicationClosed()) {
            return redirect()
                ->route('student.programs.index')
                ->with('error', 'The application deadline for this program has passed.');
        }

        // Check for strictly active grants
        $hasActiveGrant = Application::where('student_profile_id', $student->id)
            ->whereIn('status', [ApplicationStatus::Approved, ApplicationStatus::Ongoing])
            ->exists();

        if ($hasActiveGrant) {
            return back()->with('error', 'You already have an active sponsorship. New applications are disabled until your current grant expires.');
        }

        $alreadyAppliedToProgram = Application::where('student_profile_id', $student->id)
            ->where('sponsorship_program_id', $program->id)
            ->whereIn('status', [
                ApplicationStatus::Pending,
                ApplicationStatus::Verified,
                ApplicationStatus::Approved,
                ApplicationStatus::Ongoing,
            ])->exists();

        if ($alreadyAppliedToProgram) {
            return back()->with('error', 'You have an active or pending application for this program.');
        }

        if ($this->hasBlockingApplication($profile)) {
            return redirect()
                ->route('student.programs.index')
                ->with('error', 'You already have an active or pending sponsorship application.');
        }

        $eligibility = $program->checkEligibility($profile);

        if (! $eligibility['is_eligible']) {
            return redirect()
                ->route('student.programs.index')
                ->with('error', $eligibility['reasons'][0]);
        }

        $program->load('sponsor');

        $urbanMunicipalities = ['Mati City', 'Mati', 'Matiao'];
        $profileMunicipality = trim((string) ($profile->municipality ?? ''));
        $profileIsUrban = ! $profile->is_rural
            || in_array($profileMunicipality, $urbanMunicipalities, true);

        $programRequiresRural = filled($program->address_requirement)
            && str_contains(strtolower($program->address_requirement), 'rural');

        return view('student.applications.create', [
            'user' => $this->actor($request),
            'profile' => $profile,
            'program' => $program,
            'profileIsUrban' => $profileIsUrban,
            'programRequiresRural' => $programRequiresRural,
        ]);
    }

    public function index(Request $request): View
    {
        $profile = $this->studentProfile($request);

        $applications = $profile->applications()
            ->with(['sponsorshipProgram.sponsor', 'documents'])
            ->latest()
            ->get();

        return view('student.applications.index', [
            'user' => $this->actor($request),
            'profile' => $profile,
            'applications' => $applications,
            'hasActiveSponsorship' => $profile->hasActiveSponsorship(),
        ]);
    }

    public function show(Request $request, Application $application): View
    {
        $profile = $this->studentProfile($request);
        $this->assertOwnsApplication($profile->id, $application);

        $isApprovedOnFixedList = FixedListItem::query()
            ->where('student_id_number', $profile->student_id_number)
            ->where('is_sle_fhe_verified', true)
            ->whereHas('fixedList', function ($query) use ($application): void {
                $query->where('sponsorship_program_id', $application->sponsorship_program_id)
                    ->where('status', FixedListStatus::Approved);
            })
            ->exists();

        if ($isApprovedOnFixedList && $application->status !== ApplicationStatus::Approved) {
            DB::transaction(function () use ($application, $profile): void {
                SponsorshipProgram::query()
                    ->lockForUpdate()
                    ->findOrFail($application->sponsorship_program_id);

                $application->update([
                    'status' => ApplicationStatus::Approved,
                    'approved_at' => now(),
                ]);
                $profile->update(['active_sponsorship_id' => $application->id]);
            });
        }

        $application->load(['sponsorshipProgram.sponsor', 'documents']);

        return view('student.applications.show', [
            'user' => $this->actor($request),
            'application' => $application,
        ]);
    }

    public function store(StoreApplicationRequest $request): RedirectResponse
    {
        $profile = $this->studentProfile($request);
        $student = $profile;

        if (! $profile->is_sle_fhe_verified) {
            return redirect()
                ->route('student.verification.show')
                ->withErrors(['application' => 'Complete SLE-FHE verification before applying.']);
        }

        // Check for strictly active grants
        $hasActiveGrant = Application::where('student_profile_id', $student->id)
            ->whereIn('status', [ApplicationStatus::Approved, ApplicationStatus::Ongoing])
            ->exists();

        if ($hasActiveGrant) {
            return back()->with('error', 'You already have an active sponsorship. New applications are disabled until your current grant expires.');
        }

        if ($this->hasBlockingApplication($profile)) {
            return back()
                ->withErrors([
                    'application' => 'You already have an active sponsorship and cannot submit another application.',
                ])
                ->withInput();
        }

        if (! $profile->hasCompleteIdentity()) {
            return redirect()
                ->route('student.verification.show')
                ->withErrors([
                    'student_id_number' => 'Verify your student ID before applying.',
                ]);
        }

        $program = SponsorshipProgram::query()->findOrFail($request->integer('sponsorship_program_id'));

        if ($program->isApplicationClosed()) {
            return back()
                ->withErrors(['application' => 'The application deadline for this program has passed.'])
                ->withInput();
        }

        $alreadyAppliedToProgram = Application::where('student_profile_id', $student->id)
            ->where('sponsorship_program_id', $program->id)
            ->whereIn('status', [
                ApplicationStatus::Pending,
                ApplicationStatus::Verified,
                ApplicationStatus::Approved,
                ApplicationStatus::Ongoing,
            ])->exists();

        if ($alreadyAppliedToProgram) {
            return back()->with('error', 'You have an active or pending application for this program.');
        }

        $eligibility = $program->checkEligibility($profile);

        if (! $eligibility['is_eligible']) {
            return redirect()
                ->route('student.programs.index')
                ->with('error', $eligibility['reasons'][0]);
        }

        $eligibilityErrors = $program->eligibilityErrors(
            $profile,
            (float) $request->input('current_gpa', $request->input('gpa_submitted')),
            (string) $request->input('current_address', $request->input('address_submitted')),
            $request->boolean('is_rural_submitted'),
        );

        if ($eligibilityErrors !== []) {
            return back()->withErrors(['application' => $eligibilityErrors])->withInput();
        }

        try {
            $application = DB::transaction(function () use ($request, $profile, $program) {
                $application = $profile->applications()->create([
                    'sponsorship_program_id' => $program->id,
                    'gpa_submitted' => $request->input('current_gpa', $request->input('gpa_submitted')),
                    'address_submitted' => $request->input('current_address', $request->input('address_submitted')),
                    'is_rural_submitted' => $request->boolean('is_rural_submitted'),
                    'employee_name' => $request->input('employee_name'),
                    'employee_id_number' => $request->input('employee_id_number'),
                    'employee_relationship' => $request->input('employee_relationship'),
                    'status' => ApplicationStatus::Pending,
                    'submitted_at' => now(),
                ]);

                $requiredDocuments = (array) ($program->required_documents ?? []);

                $uploadFields = [
                    'Report Card / Certificate of Grades' => [
                        'grade_slip' => DocumentType::CertificateOfGrades,
                        'certificate_of_grades' => DocumentType::CertificateOfGrades,
                    ],
                    'Certificate of Indigency' => [
                        'indigency_doc' => DocumentType::CertificateOfIndigency,
                    ],
                    'Certificate of Registration (COR)' => [
                        'cor_doc' => DocumentType::CertificateOfRegistration,
                    ],
                    'Proof of Residence / Barangay Cert' => [
                        'proof_of_residence' => DocumentType::ProofOfResidence,
                        'barangay_certification' => DocumentType::BarangayCertificate,
                        'barangay_cert' => DocumentType::BarangayCertificate,
                    ],
                    'Employee ID / Proof of Kinship' => [
                        'employee_id_doc' => DocumentType::EmployeeProofOfKinship,
                    ],
                ];

                $uploads = [];
                foreach ($uploadFields as $label => $fields) {
                    if (! in_array($label, $requiredDocuments, true)) {
                        continue;
                    }

                    foreach ($fields as $field => $type) {
                        if ($request->hasFile($field)) {
                            $uploads[$type->value] = $request->file($field);
                            break;
                        }
                    }
                }

                foreach ($uploads as $type => $file) {
                    $path = $file->store('applications/documents', 'local');

                    $application->documents()->create([
                        'document_type' => $type,
                        'file_path' => $path,
                        'file_name' => $file->getClientOriginalName(),
                    ]);
                }

                return $application;
            });
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withErrors(['application' => 'Unable to submit the application. Please try again.'])
                ->withInput();
        }

        $this->audit($request, 'student.application.submitted', 'applications');

        return redirect()
            ->route('student.applications.index')
            ->with('success', 'Application submitted successfully!');
    }

    public function downloadDocument(Request $request, Application $application, string $documentType): StreamedResponse
    {
        $profile = $this->studentProfile($request);
        $this->assertOwnsApplication($profile->id, $application);

        $type = DocumentType::tryFrom($documentType);
        abort_unless($type !== null, 404, 'Document type not found.');
        $document = $application->documents()->where('document_type', $type)->firstOrFail();

        abort_unless(Storage::disk('local')->exists($document->file_path), 404, 'Document file not found.');
        $fileContents = Storage::disk('local')->get($document->file_path);

        return response()->streamDownload(
            static function () use ($fileContents): void {
                echo $fileContents;
            },
            $document->file_name,
        );
    }

    public function resubmit(Request $request, Application $application): RedirectResponse
    {
        $profile = $this->studentProfile($request);
        $this->assertOwnsApplication($profile->id, $application);

        if ($application->status !== ApplicationStatus::ResubmissionRequested) {
            return back()->withErrors(['application' => 'This application is not awaiting document resubmission.']);
        }

        $validated = $request->validate([
            'documents' => ['sometimes', 'array'],
            'documents.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $validKeys = collect(DocumentType::cases())
            ->map(fn (DocumentType $type): string => DocumentType::canonicalValue($type))
            ->unique()
            ->values()
            ->all();

        $files = array_intersect_key((array) ($validated['documents'] ?? []), array_flip($validKeys));

        if ($files === []) {
            return back()
                ->withErrors(['documents' => 'Upload at least one corrected document to resubmit.'])
                ->withInput();
        }

        try {
            DB::transaction(function () use ($application, $files): void {
                foreach ($files as $documentType => $file) {
                    $canon = DocumentType::canonicalValue((string) $documentType);

                    $existing = $application->documents->first(
                        fn ($doc): bool => DocumentType::canonicalValue($doc->document_type) === $canon,
                    );

                    $newPath = $file->store('applications/documents', 'local');
                    $payload = [
                        'document_type' => $canon,
                        'file_path' => $newPath,
                        'file_name' => $file->getClientOriginalName(),
                    ];

                    if ($existing) {
                        $oldPath = $existing->file_path;
                        $existing->update($payload);

                        if ($oldPath && $oldPath !== $newPath) {
                            Storage::disk('local')->delete($oldPath);
                        }
                    } else {
                        $application->documents()->create($payload);
                    }
                }

                $application->update([
                    'status'             => ApplicationStatus::Pending,
                    'resubmission_notes' => null,
                    'requested_documents' => null,
                    'verified_at'        => null,
                ]);
            });
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withErrors(['application' => 'Unable to resubmit the documents. Please try again.'])
                ->withInput();
        }

        $this->audit($request, 'student.application.resubmitted', 'applications');

        return back()->with('success', 'Documents resubmitted successfully. Your application is now back under review.');
    }

    private function assertOwnsApplication(int $studentProfileId, Application $application): void
    {
        abort_unless((int) $application->student_profile_id === $studentProfileId, 403, 'You are not authorized to access this application.');
    }

    private function hasBlockingApplication($profile): bool
    {
        if ($profile === null) {
            return false;
        }

        return $profile->applications()
            ->whereIn('status', [
                ApplicationStatus::Verified,
                ApplicationStatus::Approved,
                ApplicationStatus::Ongoing,
            ])
            ->exists();
    }
}
