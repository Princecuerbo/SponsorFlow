<?php

namespace App\Http\Controllers\Accounting;

use App\Enums\ApplicationStatus;
use App\Enums\ConfirmationStatus;
use App\Enums\GeneratedBatchStatus;
use App\Http\Controllers\Concerns\ResolvesModuleContext;
use App\Http\Controllers\Controller;
use App\Models\AcademicProgram;
use App\Models\Application;
use App\Models\BatchCandidate;
use App\Models\GeneratedBatch;
use App\Models\SponsorshipProgram;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReferenceController extends Controller
{
    use ResolvesModuleContext;

    public function dashboard(Request $request): View
    {
        return view('accounting.dashboard', [
            'user' => $this->actor($request),
            'approvedApplications' => Application::query()->approvedBeneficiaries()->count(),
            'confirmedLists' => GeneratedBatch::query()
                ->where('status', GeneratedBatchStatus::Approved)
                ->whereHas('latestApproval', fn ($approval) => $approval->where('confirmation_status', ConfirmationStatus::Confirmed))
                ->count(),
            'latestApprovedAt' => Application::query()->approvedBeneficiaries()->max('approved_at'),
        ]);
    }

    public function index(Request $request): View
    {
        $beneficiaries = $this->beneficiaryRows($request);

        return view('accounting.index', [
            'user' => $this->actor($request),
            'beneficiaries' => $beneficiaries,
            'totalApproved' => count($beneficiaries),
            'programs' => SponsorshipProgram::query()
                ->select(['id', 'program_name as name'])
                ->orderBy('program_name')
                ->get(),
            'academicPrograms' => AcademicProgram::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function show(Request $request, Application $application): View
    {
        abort_unless($application->status?->isActiveSponsorship(), 404);

        $application->load(['studentProfile.user', 'sponsorshipProgram.sponsor', 'documents']);

        return view('accounting.show', [
            'user' => $this->actor($request),
            'application' => $application,
        ]);
    }

    public function showFixedListReference(Request $request, GeneratedBatch $generatedBatch): View
    {
        abort_unless($generatedBatch->status === GeneratedBatchStatus::Approved, 404);

        $approval = $generatedBatch->latestApproval;
        abort_unless($approval?->confirmation_status === ConfirmationStatus::Confirmed, 404);

        $generatedBatch->load([
            'sponsorshipProgram.sponsor',
            'latestApproval',
            'items' => function ($query): void {
                $query->whereDoesntHave('application', fn ($applicationQuery) => $applicationQuery->where('status', ApplicationStatus::Rejected));
            },
            'items.application.studentProfile.user',
        ]);

        return view('accounting.beneficiaries.show', [
            'user' => $this->actor($request),
            'fixedList' => $generatedBatch,
            'generatedBatch' => $generatedBatch,
            'approval' => $approval,
        ]);
    }

    public function viewDocument(Request $request, Application $application): BinaryFileResponse
    {
        return $this->viewApplicationDocument($request, $application);
    }

    public function viewApplicationDocument(Request $request, Application $application): BinaryFileResponse
    {
        abort_unless($application->status?->isActiveSponsorship(), 404);
        abort_if(blank($application->sponsor_approval_path), 404, 'Confirmation document not found.');

        $path = Storage::disk('local')->path($application->sponsor_approval_path);
        abort_unless(is_file($path), 404, 'Confirmation document not found.');

        $fileName = addcslashes(basename($application->sponsor_approval_path), '\\"');
        $mimeType = mime_content_type($path) ?: 'application/octet-stream';

        return response()->file($path, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="'.$fileName.'"',
        ]);
    }

    public function viewFixedListDocument(Request $request, GeneratedBatch $generatedBatch): BinaryFileResponse
    {
        abort_unless($generatedBatch->status === GeneratedBatchStatus::Approved, 404);

        $approval = $generatedBatch->latestApproval;
        abort_unless($approval?->confirmation_status === ConfirmationStatus::Confirmed, 404);
        abort_if(blank($approval->approval_document_path), 404, 'Confirmation document not found.');

        $path = Storage::disk('local')->path($approval->approval_document_path);
        abort_unless(is_file($path), 404, 'Confirmation document not found.');

        $fileName = addcslashes(basename($approval->approval_document_path), '\\"');
        $mimeType = mime_content_type($path) ?: 'application/octet-stream';

        return response()->file($path, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="'.$fileName.'"',
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->audit($request, 'accounting.beneficiaries.exported', 'accounting');
        $rows = $this->beneficiaryRows($request);
        $filename = 'sponsorflow-beneficiaries-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'source',
                'student_id_number',
                'student_name',
                'course',
                'year_level',
                'program',
                'category',
                'sponsor',
                'gwa',
                'campus',
                'address',
                'rurality',
                'billing_contact',
                'confirmation_document',
                'approved_at',
                'status',
            ]);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $this->sanitizeCsvValue($row['source']),
                    $this->sanitizeCsvValue($row['student_id_number']),
                    $this->sanitizeCsvValue($row['student_name']),
                    $this->sanitizeCsvValue($row['course']),
                    $this->sanitizeCsvValue($row['year_level']),
                    $this->sanitizeCsvValue($row['program']),
                    $this->sanitizeCsvValue($row['category']),
                    $this->sanitizeCsvValue($row['sponsor']),
                    $this->sanitizeCsvValue($row['gwa']),
                    $this->sanitizeCsvValue($row['campus']),
                    $this->sanitizeCsvValue($row['address']),
                    $this->sanitizeCsvValue($row['rurality']),
                    $this->sanitizeCsvValue($row['billing_contact']),
                    $this->sanitizeCsvValue($row['confirmation_document']),
                    $row['approved_at']?->format('Y-m-d'),
                    $this->sanitizeCsvValue($row['application_status']),
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function sanitizeCsvValue(mixed $value): mixed
    {
        if (is_string($value) && preg_match('/^[=+\-@]/', $value) === 1) {
            return "'".$value;
        }

        return $value;
    }

    public function store(): never
    {
        abort(405, 'Accounting access is read-only. Only GET requests are allowed.');
    }

    public function update(): never
    {
        abort(405, 'Accounting access is read-only. Only GET requests are allowed.');
    }

    public function destroy(): never
    {
        abort(405, 'Accounting access is read-only. Only GET requests are allowed.');
    }

    /** @return list<array<string, mixed>> */
    private function beneficiaryRows(Request $request): array
    {
        $search = $request->string('q')->trim()->toString();
        $sponsorshipProgramId = $request->integer('sponsorship_program_id', 0);
        $academicProgramId = $request->integer('academic_program_id', 0);

        $approvedApplications = Application::query()
            ->approvedBeneficiaries()
            ->with(['studentProfile.user', 'sponsorshipProgram.sponsor'])
            ->when($sponsorshipProgramId > 0, fn ($query) => $query->where('sponsorship_program_id', $sponsorshipProgramId))
            ->when($academicProgramId > 0, fn ($query) => $query->whereHas('studentProfile', fn ($profileQuery) => $profileQuery->where('academic_program_id', $academicProgramId)))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->whereHas('studentProfile', function ($profileQuery) use ($search): void {
                        $profileQuery->where('student_id_number', 'like', "%{$search}%")
                            ->orWhere('course', 'like', "%{$search}%")
                            ->orWhereHas('academicProgram', fn ($ap) => $ap->where('name', 'like', "%{$search}%"))
                            ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', "%{$search}%"));
                    })->orWhereHas('sponsorshipProgram', function ($programQuery) use ($search): void {
                        $programQuery->where('program_name', 'like', "%{$search}%")
                            ->orWhereHas('sponsor', fn ($sponsorQuery) => $sponsorQuery->where('company_organization_name', 'like', "%{$search}%"));
                    });
                });
            })
            ->latest('approved_at')
            ->get()
            ->map(function (Application $application): array {
                $profile = $application->studentProfile;
                $program = $application->sponsorshipProgram;

                return [
                    'source' => 'approved_application',
                    'application_id' => $application->id,
                    'student_id_number' => $profile->student_id_number,
                    'student_name' => $profile->user->name,
                    'course' => $profile->academicProgram?->name ?? $profile->course ?? 'Unspecified',
                    'year_level' => $profile->year_level,
                    'campus' => $profile->campus,
                    'program' => $program->program_name,
                    'category' => $program->category->value,
                    'sponsor' => $program->sponsor->company_organization_name,
                    'billing_contact' => $program->sponsor->contact_person,
                    'gwa' => $application->gpa_submitted,
                    'address' => $application->address_submitted,
                    'rurality' => $application->is_rural_submitted ? 'Rural' : 'Urban',
                    'confirmation_document' => $application->sponsor_approval_path,
                    'document_url' => $application->sponsor_approval_path
                        ? route('accounting.applications.document', $application)
                        : null,
                    'approved_at' => $application->approved_at,
                    'billing_status' => 'Confirmed for reference',
                    'application_status' => $application->status->value,
                ];
            });

        $confirmedItems = BatchCandidate::query()
            ->whereDoesntHave('application', fn ($q) => $q->where('status', ApplicationStatus::Rejected))
            ->whereHas('generatedBatch', function ($query) use ($academicProgramId, $sponsorshipProgramId): void {
                $query->where('status', GeneratedBatchStatus::Approved)
                    ->whereHas('latestApproval', fn ($approval) => $approval->where('confirmation_status', ConfirmationStatus::Confirmed))
                    ->whereNotNull('fassg_assigned_at');
                if ($sponsorshipProgramId > 0) {
                    $query->where('sponsorship_program_id', $sponsorshipProgramId);
                }
                if ($academicProgramId > 0) {
                    $query->whereHas('sponsorshipProgram', fn ($programQuery) => $programQuery->whereHas('academicPrograms', fn ($programFilter) => $programFilter->where('academic_programs.program_id', $academicProgramId)));
                }
            })
            ->with(['generatedBatch.sponsorshipProgram.sponsor', 'generatedBatch.latestApproval', 'application.studentProfile'])
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->whereHas('application.studentProfile', function ($profileQuery) use ($search): void {
                    $profileQuery->where('student_id_number', 'like', "%{$search}%")
                        ->orWhere('course', 'like', "%{$search}%")
                        ->orWhereHas('academicProgram', fn ($ap) => $ap->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', "%{$search}%"));
                });
            }))
            ->latest()
            ->get()
            ->map(function (BatchCandidate $item): array {
                $batch = $item->generatedBatch;
                $program = $batch->sponsorshipProgram;
                $approval = $batch->latestApproval;

                $application = $item->application;
                $profile = $application?->studentProfile;

                return [
                    'source' => 'confirmed_fixed_list',
                    'fixed_list_id' => $batch->id,
                    'item_id' => $item->id,
                    'application_id' => $application?->id,
                    'student_id_number' => $profile?->student_id_number ?? 'N/A',
                    'student_name' => $profile?->user?->name ?? 'Unknown',
                    'course' => $profile?->academicProgram?->name ?? $profile?->course ?? 'Unspecified',
                    'year_level' => $profile?->year_level,
                    'campus' => $profile?->campus,
                    'program' => $program->program_name,
                    'category' => $program->category->value,
                    'sponsor' => $program->sponsor->company_organization_name,
                    'billing_contact' => $program->sponsor->contact_person,
                    'gwa' => $application?->gpa_submitted,
                    'address' => $application?->address_submitted ?? $profile?->full_address,
                    'rurality' => $application !== null
                        ? ($application->is_rural_submitted ? 'Rural' : 'Urban')
                        : null,
                    'confirmation_document' => $approval?->approval_document_path,
                    'document_url' => $approval?->approval_document_path
                        ? route('accounting.fixed-lists.document', $batch)
                        : null,
                    'approved_at' => $approval?->created_at,
                    'billing_status' => 'Confirmed for reference',
                    'application_status' => 'List beneficiary',
                    'reference_label' => 'Application Batch',
                ];
            });

        return $confirmedItems->concat($approvedApplications)
            ->unique('student_id_number')
            ->values()
            ->all();
    }
}
