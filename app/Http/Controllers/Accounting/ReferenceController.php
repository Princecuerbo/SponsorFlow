<?php

namespace App\Http\Controllers\Accounting;

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
        return app(DashboardController::class)->index($request);
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
        $candidate = $application->accountingCandidate();
        abort_unless($candidate !== null, 404);
        $application->load(['studentProfile.user', 'sponsorshipProgram.sponsor']);

        return view('accounting.show', [
            'user' => $this->actor($request), 'application' => $application,
            'approval' => $candidate->generatedBatch->currentApproval(),
            'generatedBatch' => $candidate->generatedBatch,
        ]);
    }

    public function showFixedListReference(Request $request, GeneratedBatch $generatedBatch): View
    {
        $items = $generatedBatch->items()->accountingVisible()->get()
            ->filter(fn (BatchCandidate $item) => $item->isAccountingEligible())->values();
        abort_if($items->isEmpty(), 404);
        $generatedBatch->setRelation('items', $items);

        return view('accounting.beneficiaries.show', [
            'user' => $this->actor($request), 'fixedList' => $generatedBatch,
            'generatedBatch' => $generatedBatch, 'approval' => $generatedBatch->currentApproval(),
        ]);
    }

    public function viewDocument(Request $request, Application $application): BinaryFileResponse
    {
        return $this->viewApplicationDocument($request, $application);
    }

    public function viewApplicationDocument(Request $request, Application $application): BinaryFileResponse
    {
        $candidate = $application->accountingCandidate();
        abort_unless($candidate !== null, 404);

        return $this->viewFixedListDocument($request, $candidate->generatedBatch);
    }

    public function viewFixedListDocument(Request $request, GeneratedBatch $generatedBatch): BinaryFileResponse
    {
        $items = $generatedBatch->items()->get();
        abort_if($items->isEmpty() || $items->contains(fn (BatchCandidate $item) => ! $item->isAccountingEligible()), 404);
        // A shared document must not reveal any member who fails Accounting authorization.
        $approval = $generatedBatch->currentApproval();
        abort_unless($approval?->isForwardedToAccounting(), 404);
        $path = Storage::disk('local')->path($approval->approval_document_path);
        abort_unless(is_file($path), 404);
        $fileName = addcslashes(basename($approval->approval_document_path), '\\"');

        return response()->file($path, [
            'Content-Type' => mime_content_type($path) ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.$fileName.'"',
            'Cache-Control' => 'private, no-store',
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
        $search = mb_strtolower($request->string('q')->trim()->toString());
        $programId = $request->integer('sponsorship_program_id');
        $academicProgramId = $request->integer('academic_program_id');

        return BatchCandidate::accountingBeneficiaries()->filter(function (BatchCandidate $item) use ($search, $programId, $academicProgramId): bool {
            $application = $item->application;
            $profile = $application->studentProfile;
            $program = $item->generatedBatch->sponsorshipProgram;
            return ($programId === 0 || (int) $program->id === $programId)
                && ($academicProgramId === 0 || (int) $profile->academic_program_id === $academicProgramId)
                && ($search === '' || str_contains(mb_strtolower(implode(' ', [
                    $profile->student_id_number, $profile->full_name, $profile->academicProgram?->name, $profile->course,
                    $program->program_name, $program->sponsor?->company_organization_name,
                ])), $search));
        })->map(function (BatchCandidate $item): array {
            $application = $item->application;
            $profile = $application->studentProfile;
            $batch = $item->generatedBatch;
            $program = $batch->sponsorshipProgram;
            $approval = $batch->currentApproval();

            return [
                'source' => $item->is_fixed_list ? 'fixed_list' : 'ordinary_application',
                'application_id' => $application->id,
                'fixed_list_id' => $batch->id, // Existing reference route accepts a Generated Batch ID.
                'item_id' => $item->id,
                'student_id_number' => $profile->student_id_number,
                'student_name' => $profile->full_name,
                'course' => $profile->academicProgram?->name ?? $profile->course,
                'year_level' => $profile->year_level, 'campus' => $profile->campus,
                'program' => $program->program_name, 'category' => $program->category?->value,
                'sponsor' => $program->sponsor?->company_organization_name,
                'billing_contact' => $program->sponsor?->contact_person,
                'gwa' => $application->gpa_submitted, 'address' => $application->address_submitted,
                'rurality' => $application->is_rural_submitted === null ? null : ($application->is_rural_submitted ? 'Rural' : 'Urban'),
                'confirmation_document' => basename($approval->approval_document_path),
                'document_url' => route('accounting.fixed-lists.document', $batch),
                'approved_at' => $application->approved_at,
                'billing_status' => 'Confirmed for reference',
                'application_status' => $application->status->value,
                'reference_label' => $item->is_fixed_list ? 'Fixed-list beneficiary' : 'Ordinary applicant',
            ];
        })->sortByDesc('approved_at')->values()->all();
    }
}
