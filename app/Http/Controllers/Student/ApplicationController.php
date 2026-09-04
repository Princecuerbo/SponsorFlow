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
            $query = SponsorshipProgram::query()->open()->with('sponsor');

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

        return view('student.programs.index', [
            'user' => $this->actor($request),
            'profile' => $profile,
            'programs' => $programs,
            'hasActiveSponsorship' => $profile?->hasActiveSponsorship() ?? false,
        ]);
    }

    public function create(Request $request, SponsorshipProgram $sponsorshipProgram): View
    {
        $profile = $this->studentProfile($request);
        abort_unless($profile->is_sle_fhe_verified, 403, 'Complete SLE-FHE verification before applying.');
        abort_unless($sponsorshipProgram->isOpen() && $sponsorshipProgram->available_slots > 0, 403, 'This sponsorship program is not accepting applications.');
        abort_unless(! $this->hasBlockingApplication($profile), 403, 'You already have an active or pending sponsorship application.');
        abort_unless($this->courseIsAllowed($profile->course, $sponsorshipProgram->target_course), 403, 'Your course is not eligible for this program.');

        return view('student.applications.create', [
            'user' => $this->actor($request),
            'profile' => $profile,
            'program' => $sponsorshipProgram,
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

        if (! $profile->is_sle_fhe_verified) {
            return redirect()
                ->route('student.verification.show')
                ->withErrors(['application' => 'Complete SLE-FHE verification before applying.']);
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

        if ($program->available_slots <= 0) {
            return back()
                ->withErrors(['application' => 'This program has reached maximum capacity and is no longer accepting applications.'])
                ->withInput();
        }

        $existingActiveApplication = $profile->applications()
            ->where('sponsorship_program_id', $program->id)
            ->whereIn('status', [
                'submitted',
                ApplicationStatus::Pending,
                ApplicationStatus::Verified,
                ApplicationStatus::Approved,
                ApplicationStatus::Ongoing,
            ])
            ->exists();

        if ($existingActiveApplication) {
            return back()
                ->withErrors(['application' => 'You already have an active application for this sponsorship program.'])
                ->withInput();
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
                    'status' => ApplicationStatus::Pending,
                    'submitted_at' => now(),
                ]);

                $uploads = [
                    DocumentType::CertificateOfGrades->value => $request->file('grade_slip') ?? $request->file('certificate_of_grades'),
                    DocumentType::ProofOfResidence->value => $request->file('proof_of_residence'),
                    DocumentType::BarangayCertificate->value => $request->file('barangay_certification') ?? $request->file('barangay_cert'),
                ];

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

    private function assertOwnsApplication(int $studentProfileId, Application $application): void
    {
        abort_unless((int) $application->student_profile_id === $studentProfileId, 403, 'You are not authorized to access this application.');
    }

    private function courseIsAllowed(?string $studentCourse, ?string $targetCourse): bool
    {
        if (! filled($targetCourse)) {
            return true;
        }

        $allowedCourses = array_map('trim', explode(',', $targetCourse));

        return in_array(trim((string) $studentCourse), $allowedCourses, true);
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
