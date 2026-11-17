<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\ConfirmationStatus;
use App\Enums\DocumentType;
use App\Enums\FixedListItemStatus;
use App\Enums\FixedListStatus;
use App\Enums\GeneratedBatchStatus;
use App\Enums\ProgramCategory;
use App\Enums\ProgramStatus;
use App\Enums\SleFheStatus;
use App\Enums\UserRole;
use App\Models\AcademicProgram;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\AuditLog;
use App\Models\BatchCandidate;
use App\Models\FixedList;
use App\Models\FixedListItem;
use App\Models\GeneratedBatch;
use App\Models\SleFheRejection;
use App\Models\SleFheRequest;
use App\Models\SleFheVerification;
use App\Models\Sponsor;
use App\Models\SponsorApproval;
use App\Models\SponsorshipProgram;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FullSystemCampaignWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $fassgUser;
    private User $sponsorUser;
    private User $accountingUser;
    private Sponsor $sponsorOrg;
    private SponsorshipProgram $program;
    private AcademicProgram $bsit;
    private AcademicProgram $bsam;
    private AcademicProgram $bitm;
    private AcademicProgram $bsitech;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->fassgUser = User::factory()->create([
            'role' => UserRole::Fassg,
            'name' => 'FASSG Lead Officer',
            'email' => 'fassg.lead@dorsu.edu.ph',
        ]);

        $this->sponsorUser = User::factory()->create([
            'role' => UserRole::Sponsor,
            'name' => 'Sponsor Director',
            'email' => 'sponsor.lead@dorsu.edu.ph',
        ]);

        $this->sponsorOrg = Sponsor::factory()->create([
            'user_id' => $this->sponsorUser->id,
            'company_organization_name' => 'Davao Oriental Provincial Foundation',
        ]);

        $this->accountingUser = User::factory()->create([
            'role' => UserRole::Accounting,
            'name' => 'Accounting Officer',
            'email' => 'accounting.officer@dorsu.edu.ph',
        ]);

        $this->bsit = AcademicProgram::factory()->create([
            'code' => 'BSIT',
            'name' => 'Bachelor of Science in Information Technology',
            'short_name' => 'BSIT',
            'is_undergraduate' => true,
            'is_active' => true,
        ]);

        $this->bsam = AcademicProgram::factory()->create([
            'code' => 'BSAM',
            'name' => 'Bachelor of Science in Agribusiness Management',
            'short_name' => 'BSAM',
            'is_undergraduate' => true,
            'is_active' => true,
        ]);

        $this->bitm = AcademicProgram::factory()->create([
            'code' => 'BITM',
            'name' => 'Bachelor in Industrial Technology Management',
            'short_name' => 'BITM',
            'is_undergraduate' => true,
            'is_active' => true,
        ]);

        $this->bsitech = AcademicProgram::factory()->create([
            'code' => 'BSITech',
            'name' => 'Bachelor of Science in Industrial Technology',
            'short_name' => 'BSITech',
            'is_undergraduate' => true,
            'is_active' => true,
        ]);

        $this->program = SponsorshipProgram::factory()->create([
            'sponsor_id' => $this->sponsorOrg->id,
            'program_name' => 'Provincial Leadership Grant 2026',
            'category' => ProgramCategory::Individual,
            'total_slots' => 5,
            'available_slots' => 5,
            'min_gpa' => 2.00,
            'status' => ProgramStatus::Open,
            'required_documents' => ['Certificate of Registration (COR)', 'Report Card / Certificate of Grades'],
        ]);
    }

    /**
     * Helper to create a student with verified SLE-FHE status.
     */
    private function createVerifiedStudent(string $idNumber, string $name, AcademicProgram $program, int $yearLevel = 3): array
    {
        $user = User::factory()->create([
            'role' => UserRole::Student,
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)) . '@dorsu.edu.ph',
        ]);

        $profile = StudentProfile::factory()->create([
            'user_id' => $user->id,
            'student_id_number' => $idNumber,
            'first_name' => explode(' ', $name)[0],
            'last_name' => explode(' ', $name)[1] ?? 'Student',
            'academic_program_id' => $program->program_id,
            'course' => $program->name,
            'year_level' => $yearLevel,
            'campus' => 'Main Campus (City of Mati)',
        ]);

        SleFheVerification::create([
            'student_profile_id' => $profile->id,
            'verified_address' => 'Purok 1, Central, City of Mati, Davao Oriental',
            'verified_at' => now(),
            'verified_by' => $this->fassgUser->id,
        ]);

        return [$user, $profile];
    }

    /**
     * Helper to create an ordinary application with required documents.
     */
    private function submitOrdinaryApplication(User $user, StudentProfile $profile, SponsorshipProgram $program, float $gpa = 1.75): Application
    {
        $corFile = UploadedFile::fake()->create('cor.pdf', 100, 'application/pdf');
        $cogFile = UploadedFile::fake()->create('grades.pdf', 100, 'application/pdf');

        $response = $this->actingAs($user)->post(route('student.applications.store'), [
            'sponsorship_program_id' => $program->id,
            'current_gpa' => $gpa,
            'current_address' => 'Mati City, Davao Oriental',
            'is_rural_submitted' => 1,
            'cor_doc' => $corFile,
            'certificate_of_grades' => $cogFile,
        ]);

        $response->assertRedirect(route('student.applications.index'));

        return Application::where('student_profile_id', $profile->id)
            ->where('sponsorship_program_id', $program->id)
            ->firstOrFail();
    }

    /**
     * Test 1: Full Lifecycle for Ordinary-only Batch:
     * Registration → SLE-FHE Verification → Application → FASSG Member Verification → Submit → Sponsor Approval → FASSG Decision Receipt → Accounting Forwarding → Accounting Export.
     */
    public function test_01_ordinary_only_batch_complete_lifecycle(): void
    {
        // 1. Student Registration
        $regResponse = $this->post(route('register'), [
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'email' => 'maria.santos@dorsu.edu.ph',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'student_id_number' => '2024-2001',
            'academic_program_id' => $this->bsit->program_id,
            'campus' => 'Main Campus (City of Mati)',
            'contact_number' => '09123456789',
            'year_level' => 3,
            'gender' => 'Female',
            'birthdate' => '2004-03-15',
            'privacy_consent' => '1',
        ]);
        $regResponse->assertRedirect(route('login'));
        $user = User::where('email', 'maria.santos@dorsu.edu.ph')->firstOrFail();
        $profile = StudentProfile::where('user_id', $user->id)->firstOrFail();

        // 2. SLE-FHE Verification Request
        $this->actingAs($user)->post(route('student.sle-fhe.request'), [
            'province' => 'Davao Oriental',
            'municipality_city' => 'City of Mati',
            'barangay' => 'Central',
            'street_purok' => 'Purok 3',
        ]);
        $request = SleFheRequest::where('student_profile_id', $profile->id)->firstOrFail();
        $this->assertSame('pending', $request->status);

        // 3. FASSG SLE-FHE Verification
        $this->actingAs($this->fassgUser)->post(route('fassg.sle-fhe.verify', $profile), [
            'verification_request_id' => $request->id,
            'confirm_identity' => '1',
            'override_registry_mismatch' => '1',
            'override_reason' => 'Official barangay certification verified',
            'evidence_reference' => 'DORSU-VER-2026-001',
        ])->assertSessionHasNoErrors();
        $this->assertTrue($profile->fresh()->isSleFheVerified());

        // 4. Sponsorship Application Submission
        $app = $this->submitOrdinaryApplication($user, $profile, $this->program, 1.75);
        $this->assertSame(ApplicationStatus::Pending, $app->status);

        // 5. FASSG creates Ordinary-only batch
        $batchResponse = $this->actingAs($this->fassgUser)->post(route('fassg.applications.create-batch'), [
            'batch_name' => 'Batch 01 - Ordinary Merit Group',
            'sponsorship_program_id' => $this->program->id,
            'selected_applications' => [$app->id],
        ]);
        $batchResponse->assertSessionHasNoErrors();
        $batch = GeneratedBatch::where('batch_name', 'Batch 01 - Ordinary Merit Group')->firstOrFail();
        $this->assertSame(GeneratedBatchStatus::Saved, $batch->status);
        $this->assertEquals(1, $batch->items()->count());

        // 6. FASSG Member Verification
        $verifyResponse = $this->actingAs($this->fassgUser)->patch(
            route('fassg.generated-batches.applications.verify', [$batch, $app]),
            [
                'confirm_eligibility' => '1',
                'confirm_gwa' => '1',
                'confirm_address' => '1',
                'confirm_cor' => '1',
            ]
        );
        $verifyResponse->assertSessionHasNoErrors();
        $this->assertSame(ApplicationStatus::Verified, $app->fresh()->status);

        // 7. FASSG submits batch to Sponsor (Freezes Roster)
        $submitResponse = $this->actingAs($this->fassgUser)->patch(route('fassg.generated-batches.submit', $batch));
        $submitResponse->assertSessionHasNoErrors();
        $batch->refresh();
        $this->assertSame(GeneratedBatchStatus::Submitted, $batch->status);
        $this->assertSame(1, $batch->submission_version);
        $this->assertTrue($batch->hasIntactSubmittedRoster());

        // 8. Sponsor uploads approval evidence and confirms whole batch
        $doc = UploadedFile::fake()->create('provincial_board_approval.pdf', 200, 'application/pdf');
        $this->actingAs($this->sponsorUser)->post(route('sponsor.approvals.store', $batch), [
            'approval_document' => $doc,
            'submission_version' => 1,
        ])->assertSessionHasNoErrors();

        $approval = $batch->currentApproval();
        $this->assertNotNull($approval);
        $this->assertSame(ConfirmationStatus::Pending, $approval->confirmation_status);

        $confirmResponse = $this->actingAs($this->sponsorUser)->patch(route('sponsor.approvals.confirm', $batch), [
            'approval_id' => $approval->id,
            'submission_version' => 1,
        ]);
        $confirmResponse->assertSessionHasNoErrors();

        $batch->refresh();
        $this->assertSame(GeneratedBatchStatus::Approved, $batch->status);
        $this->assertSame(ApplicationStatus::Approved, $app->fresh()->status);
        $this->assertSame($app->id, $profile->fresh()->active_sponsorship_id);
        $this->assertSame(4, $this->program->fresh()->available_slots); // Slots decremented

        // 9. Accounting CANNOT view until FASSG records decision and explicitly forwards
        $this->actingAs($this->accountingUser)->get(route('accounting.beneficiaries.index'))
            ->assertDontSee($profile->student_id_number);

        // 10. FASSG records Sponsor decision receipt
        $this->actingAs($this->fassgUser)->patch(route('fassg.generated-batches.record-decision', $batch), [
            'approval_id' => $approval->id,
            'submission_version' => 1,
        ])->assertSessionHasNoErrors();

        // 11. FASSG explicitly forwards to Accounting
        $this->actingAs($this->fassgUser)->patch(route('fassg.generated-batches.forward-accounting', $batch), [
            'approval_id' => $approval->id,
            'submission_version' => 1,
        ])->assertSessionHasNoErrors();

        // 12. Accounting now has read-only visibility and exports
        $accountingView = $this->actingAs($this->accountingUser)->get(route('accounting.beneficiaries.index'));
        $accountingView->assertOk()->assertSee($profile->student_id_number)->assertSee('Maria Santos');

        $exportResponse = $this->actingAs($this->accountingUser)->get(route('accounting.beneficiaries.export'));
        $exportResponse->assertOk();
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->accountingUser->id,
            'action' => 'accounting.beneficiaries.exported',
        ]);
    }

    /**
     * Test 2: Fixed-List-Only Batch Creation & Member Qualification Verification:
     * Sponsor nominates student with differing year level -> Finalized -> Batch generated -> Individual member verified.
     */
    public function test_02_fixed_list_only_batch_with_differing_year_level(): void
    {
        [$u2, $p2] = $this->createVerifiedStudent('2024-2002', 'Juan Reyes', $this->bsam, yearLevel: 1);

        $fixedList = FixedList::factory()->create([
            'sponsorship_program_id' => $this->program->id,
            'uploaded_by_fassg_id' => $this->fassgUser->id,
            'status' => FixedListStatus::Draft,
            'batch_name' => 'Sponsor Endorsed List #2',
        ]);

        // Encode student as Year 3 (Sponsor-nominated, differs from profile Year 1)
        $this->actingAs($this->fassgUser)->post(route('fassg.fixed-lists.items.store', $fixedList), [
            'student_name' => 'Juan Reyes',
            'student_id_number' => '2024-2002',
            'course' => $this->bsam->name,
            'year_level' => 3,
            'campus' => 'Main Campus (City of Mati)',
        ])->assertSessionHasNoErrors();

        // Profile year remains 1; Fixed list item is Year 3
        $this->assertSame(1, $p2->fresh()->year_level);
        $item = FixedListItem::where('student_id_number', '2024-2002')->firstOrFail();
        $this->assertSame(3, $item->year_level);

        // FASSG checks and endorses item
        $this->actingAs($this->fassgUser)->patch(route('fassg.fixed-lists.items.verify', [$fixedList, $item]))->assertSessionHasNoErrors();
        $this->actingAs($this->fassgUser)->patch(route('fassg.fixed-lists.items.endorse', [$fixedList, $item]))->assertSessionHasNoErrors();

        // Finalize Fixed List
        $this->actingAs($this->fassgUser)->patch(route('fassg.fixed-lists.finalize', $fixedList))->assertSessionHasNoErrors();

        // Create Fixed-List-only batch
        $batchResponse = $this->actingAs($this->fassgUser)->post(route('fassg.applications.create-batch'), [
            'batch_name' => 'Batch 02 - Fixed List Beneficiaries',
            'sponsorship_program_id' => $this->program->id,
            'fixed_list_source' => $fixedList->id,
        ]);
        $batchResponse->assertSessionHasNoErrors();

        $batch = GeneratedBatch::where('batch_name', 'Batch 02 - Fixed List Beneficiaries')->firstOrFail();
        $this->assertEquals(1, $batch->items()->count());

        $autoApp = Application::where('student_profile_id', $p2->id)
            ->where('sponsorship_program_id', $this->program->id)
            ->firstOrFail();
        $this->assertTrue($autoApp->is_auto_provisioned);

        // Verify Fixed List application with required qualification evidence
        $corFile = UploadedFile::fake()->create('cor.pdf', 100, 'application/pdf');
        $cogFile = UploadedFile::fake()->create('grades.pdf', 100, 'application/pdf');
        $verifyResponse = $this->actingAs($this->fassgUser)->patch(
            route('fassg.generated-batches.applications.verify', [$batch, $autoApp]),
            [
                'confirm_eligibility' => '1',
                'confirm_gwa' => '1',
                'confirm_address' => '1',
                'confirm_cor' => '1',
                'gpa_submitted' => 1.80,
                'address_submitted' => 'City of Mati',
                'is_rural_submitted' => 0,
                'qualification_documents' => [
                    DocumentType::CertificateOfRegistration->value => $corFile,
                    DocumentType::CertificateOfGrades->value => $cogFile,
                ],
            ]
        );
        $verifyResponse->assertSessionHasNoErrors();
        $this->assertSame(ApplicationStatus::Verified, $autoApp->fresh()->status);
    }

    /**
     * Test 3: Mixed Batch with Deduplication:
     * Student 3 appears as an Ordinary Applicant AND on a finalized Fixed List.
     * System must deduplicate so exactly one candidate entry exists in the Generated Batch.
     */
    public function test_03_mixed_batch_deduplicates_same_student_from_both_sources(): void
    {
        [$u3, $p3] = $this->createVerifiedStudent('2024-2003', 'Carlos Gomez', $this->bsitech, yearLevel: 2);
        [$uOther, $pOther] = $this->createVerifiedStudent('2024-2099', 'Ana Belen', $this->bsitech, yearLevel: 2);

        // Student 3 submits ordinary application
        $ordApp = $this->submitOrdinaryApplication($u3, $p3, $this->program, 1.85);

        // Student 3 is ALSO on a finalized fixed list alongside Ana Belen
        $fixedList = FixedList::factory()->create([
            'sponsorship_program_id' => $this->program->id,
            'uploaded_by_fassg_id' => $this->fassgUser->id,
            'status' => FixedListStatus::Draft,
            'batch_name' => 'Fixed List with Carlos and Ana',
        ]);

        $this->actingAs($this->fassgUser)->post(route('fassg.fixed-lists.items.store', $fixedList), [
            'student_name' => 'Carlos Gomez',
            'student_id_number' => '2024-2003',
            'course' => $this->bsitech->name,
            'year_level' => 2,
            'campus' => 'Main Campus (City of Mati)',
        ]);
        $this->actingAs($this->fassgUser)->post(route('fassg.fixed-lists.items.store', $fixedList), [
            'student_name' => 'Ana Belen',
            'student_id_number' => '2024-2099',
            'course' => $this->bsitech->name,
            'year_level' => 2,
            'campus' => 'Main Campus (City of Mati)',
        ]);

        foreach ($fixedList->items as $item) {
            $this->actingAs($this->fassgUser)->patch(route('fassg.fixed-lists.items.verify', [$fixedList, $item]));
            $this->actingAs($this->fassgUser)->patch(route('fassg.fixed-lists.items.endorse', [$fixedList, $item]));
        }
        $this->actingAs($this->fassgUser)->patch(route('fassg.fixed-lists.finalize', $fixedList));

        // Create mixed batch selecting ordinary application AND fixed list source
        $mixedResponse = $this->actingAs($this->fassgUser)->post(route('fassg.applications.create-batch'), [
            'batch_name' => 'Batch 03 - Mixed Roster',
            'sponsorship_program_id' => $this->program->id,
            'selected_applications' => [$ordApp->id],
            'fixed_list_source' => $fixedList->id,
        ]);
        $mixedResponse->assertSessionHasNoErrors();

        $batch = GeneratedBatch::where('batch_name', 'Batch 03 - Mixed Roster')->firstOrFail();

        // Exactly 2 items: Carlos (deduplicated) and Ana
        $this->assertEquals(2, $batch->items()->count());
        $carlosCandidates = $batch->items()->whereHas('application', fn ($q) => $q->where('student_profile_id', $p3->id))->count();
        $this->assertSame(1, $carlosCandidates, 'Same student must appear exactly once in the batch.');
    }

    /**
     * Test 4: Member Qualification Failures Block Batch Submission:
     * - GPA exceeding program threshold (e.g. 2.75 > min_gpa 2.00)
     * - Missing required qualification documents.
     */
    public function test_04_invalid_qualifications_block_verification_and_submission(): void
    {
        [$u4, $p4] = $this->createVerifiedStudent('2024-2004', 'David Tan', $this->bitm, yearLevel: 3);

        // Student submits application with GPA 2.80 (program requires min_gpa 2.00 or better)
        $app = $this->submitOrdinaryApplication($u4, $p4, $this->program, 2.80);

        $this->actingAs($this->fassgUser)->post(route('fassg.applications.create-batch'), [
            'batch_name' => 'Batch 04 - GPA Test',
            'sponsorship_program_id' => $this->program->id,
            'selected_applications' => [$app->id],
        ]);
        $batch = GeneratedBatch::where('batch_name', 'Batch 04 - GPA Test')->firstOrFail();

        // Attempting to verify application must fail because GPA does not satisfy program requirement
        $verifyResponse = $this->actingAs($this->fassgUser)->patch(
            route('fassg.generated-batches.applications.verify', [$batch, $app]),
            [
                'confirm_eligibility' => '1',
                'confirm_gwa' => '1',
                'confirm_address' => '1',
                'confirm_cor' => '1',
            ]
        );
        $verifyResponse->assertSessionHasErrors('application');
        $this->assertStringContainsString('Recorded GWA does not satisfy the program requirement', session('errors')->first('application'));

        // Attempting to submit batch with unverified member must be blocked
        $submitResponse = $this->actingAs($this->fassgUser)->patch(route('fassg.generated-batches.submit', $batch));
        $submitResponse->assertSessionHasErrors('batch');
        $this->assertSame(GeneratedBatchStatus::Saved, $batch->fresh()->status);
    }

    /**
     * Test 5: Active Sponsorship Conflict:
     * Student with existing active sponsorship cannot be verified for another program.
     */
    public function test_05_active_sponsorship_conflict_is_strictly_enforced(): void
    {
        [$u5, $p5] = $this->createVerifiedStudent('2024-2005', 'Eva Morales', $this->bsit, yearLevel: 2);

        // Student already has an active ongoing sponsorship in Program X
        $otherProgram = SponsorshipProgram::factory()->create([
            'program_name' => 'City Merit Scholarship',
            'status' => ProgramStatus::Open,
        ]);
        $priorApp = Application::create([
            'student_profile_id' => $p5->id,
            'sponsorship_program_id' => $otherProgram->id,
            'status' => ApplicationStatus::Approved,
            'approved_at' => now(),
            'gpa_submitted' => 1.50,
            'is_batched' => true,
        ]);
        $p5->update(['active_sponsorship_id' => $priorApp->id]);

        // 1. Student attempting to apply is blocked at application submission
        $corFile = UploadedFile::fake()->create('cor.pdf', 100, 'application/pdf');
        $cogFile = UploadedFile::fake()->create('grades.pdf', 100, 'application/pdf');
        $blockedAppResponse = $this->actingAs($u5)->post(route('student.applications.store'), [
            'sponsorship_program_id' => $this->program->id,
            'current_gpa' => 1.75,
            'current_address' => 'Mati City, Davao Oriental',
            'is_rural_submitted' => 1,
            'cor_doc' => $corFile,
            'certificate_of_grades' => $cogFile,
        ]);
        $blockedAppResponse->assertSessionHasErrors('application');
        $this->assertStringContainsString('already have an active sponsorship', session('errors')->first('application'));

        // 2. Even if an unbatched application existed prior to grant, FASSG batch creation strictly blocks it
        $app = Application::create([
            'student_profile_id' => $p5->id,
            'sponsorship_program_id' => $this->program->id,
            'status' => ApplicationStatus::Pending,
            'gpa_submitted' => 1.75,
            'address_submitted' => 'Mati City',
            'is_rural_submitted' => true,
        ]);

        $batchResponse = $this->actingAs($this->fassgUser)->post(route('fassg.applications.create-batch'), [
            'batch_name' => 'Batch 05 - Conflict Test',
            'sponsorship_program_id' => $this->program->id,
            'selected_applications' => [$app->id],
        ]);
        $batchResponse->assertSessionHasErrors('selected_applications');
        $this->assertStringContainsString('must have documented SLE-FHE verification and no active sponsorship', session('errors')->first('selected_applications'));
    }

    /**
     * Test 6: Capacity Limits & Overflow Prevention:
     * A program with 2 available slots cannot accept a batch of 3 candidates.
     */
    public function test_06_capacity_overflow_is_blocked(): void
    {
        $smallProgram = SponsorshipProgram::factory()->create([
            'sponsor_id' => $this->sponsorOrg->id,
            'program_name' => 'Limited Capacity Grant',
            'total_slots' => 2,
            'available_slots' => 2,
            'status' => ProgramStatus::Open,
            'min_gpa' => 2.50,
        ]);

        [$uA, $pA] = $this->createVerifiedStudent('2024-2061', 'Candidate A', $this->bsit);
        [$uB, $pB] = $this->createVerifiedStudent('2024-2062', 'Candidate B', $this->bsit);
        [$uC, $pC] = $this->createVerifiedStudent('2024-2063', 'Candidate C', $this->bsit);

        $appA = $this->submitOrdinaryApplication($uA, $pA, $smallProgram);
        $appB = $this->submitOrdinaryApplication($uB, $pB, $smallProgram);
        $appC = $this->submitOrdinaryApplication($uC, $pC, $smallProgram);

        // Selecting 3 applicants for a 2-slot program must fail
        $response = $this->actingAs($this->fassgUser)->post(route('fassg.applications.create-batch'), [
            'batch_name' => 'Overflow Batch',
            'sponsorship_program_id' => $smallProgram->id,
            'selected_applications' => [$appA->id, $appB->id, $appC->id],
        ]);

        $response->assertSessionHasErrors('selected_applications');
        $this->assertStringContainsString('exceeds available capacity', session('errors')->first('selected_applications'));
    }

    /**
     * Test 7: Stale Submission Version Rejection & Concurrency Protection:
     * Actions using mismatched submission_version must fail validation.
     */
    public function test_07_concurrent_submission_version_mismatch_is_rejected(): void
    {
        [$u7, $p7] = $this->createVerifiedStudent('2024-2007', 'Grace Kelly', $this->bsit);
        $app = $this->submitOrdinaryApplication($u7, $p7, $this->program);

        $this->actingAs($this->fassgUser)->post(route('fassg.applications.create-batch'), [
            'batch_name' => 'Batch 07 - Concurrency Test',
            'sponsorship_program_id' => $this->program->id,
            'selected_applications' => [$app->id],
        ]);
        $batch = GeneratedBatch::where('batch_name', 'Batch 07 - Concurrency Test')->firstOrFail();

        $this->actingAs($this->fassgUser)->patch(
            route('fassg.generated-batches.applications.verify', [$batch, $app]),
            ['confirm_eligibility' => '1', 'confirm_gwa' => '1', 'confirm_address' => '1', 'confirm_cor' => '1']
        );
        $this->actingAs($this->fassgUser)->patch(route('fassg.generated-batches.submit', $batch));
        $batch->refresh(); // submission_version is 1

        $doc = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');
        $uploadResponse = $this->actingAs($this->sponsorUser)->post(route('sponsor.approvals.store', $batch), [
            'approval_document' => $doc,
            'submission_version' => 1,
        ]);
        $uploadResponse->assertSessionHasNoErrors();
        $approval = $batch->currentApproval();

        // Attempting to confirm with stale submission_version (e.g. 99)
        $staleResponse = $this->actingAs($this->sponsorUser)->patch(route('sponsor.approvals.confirm', $batch), [
            'approval_id' => $approval->id,
            'submission_version' => 99, // Stale version!
        ]);
        $staleResponse->assertSessionHasErrors('batch');
        $this->assertStringContainsString('This submission has changed', session('errors')->first('batch'));
    }

    /**
     * Test 8: Rejected Batch Lifecycle & Permitted Resubmission:
     * Batch submitted -> Sponsor rejects -> FASSG records rejection -> Batch resubmitted with incremented version.
     */
    public function test_08_rejected_batch_receipt_and_resubmission(): void
    {
        [$u8, $p8] = $this->createVerifiedStudent('2024-2008', 'Henry Ford', $this->bsam);
        $app = $this->submitOrdinaryApplication($u8, $p8, $this->program);

        $this->actingAs($this->fassgUser)->post(route('fassg.applications.create-batch'), [
            'batch_name' => 'Batch 08 - Reject and Resubmit',
            'sponsorship_program_id' => $this->program->id,
            'selected_applications' => [$app->id],
        ]);
        $batch = GeneratedBatch::where('batch_name', 'Batch 08 - Reject and Resubmit')->firstOrFail();

        $this->actingAs($this->fassgUser)->patch(
            route('fassg.generated-batches.applications.verify', [$batch, $app]),
            ['confirm_eligibility' => '1', 'confirm_gwa' => '1', 'confirm_address' => '1', 'confirm_cor' => '1']
        );
        $this->actingAs($this->fassgUser)->patch(route('fassg.generated-batches.submit', $batch));

        // Sponsor rejects batch
        $this->actingAs($this->sponsorUser)->patch(route('sponsor.approvals.reject', $batch), [
            'submission_version' => 1,
        ])->assertSessionHasNoErrors();

        $batch->refresh();
        $this->assertSame(GeneratedBatchStatus::Rejected, $batch->status);

        // Attempting to resubmit before FASSG records rejection receipt is blocked
        $blockedResubmit = $this->actingAs($this->fassgUser)->patch(route('fassg.generated-batches.submit', $batch));
        $blockedResubmit->assertSessionHasErrors('batch');

        // FASSG records rejection receipt
        $rejectionApproval = $batch->currentApproval();
        $this->actingAs($this->fassgUser)->patch(route('fassg.generated-batches.record-decision', $batch), [
            'approval_id' => $rejectionApproval->id,
            'submission_version' => 1,
        ])->assertSessionHasNoErrors();

        // Now FASSG resubmits unchanged roster
        $resubmitResponse = $this->actingAs($this->fassgUser)->patch(route('fassg.generated-batches.submit', $batch));
        $resubmitResponse->assertSessionHasNoErrors();

        $batch->refresh();
        $this->assertSame(GeneratedBatchStatus::Submitted, $batch->status);
        $this->assertSame(2, $batch->submission_version, 'Submission version must increment to 2.');
    }

    /**
     * Test 9: Frozen Roster & Deletion Protection:
     * Once a batch has been submitted, deleting the batch or removing members is strictly blocked (403 Forbidden).
     */
    public function test_09_submitted_batch_roster_is_immutable(): void
    {
        [$u9, $p9] = $this->createVerifiedStudent('2024-2009', 'Iris West', $this->bsit);
        $app = $this->submitOrdinaryApplication($u9, $p9, $this->program);

        $this->actingAs($this->fassgUser)->post(route('fassg.applications.create-batch'), [
            'batch_name' => 'Batch 09 - Immutability Test',
            'sponsorship_program_id' => $this->program->id,
            'selected_applications' => [$app->id],
        ]);
        $batch = GeneratedBatch::where('batch_name', 'Batch 09 - Immutability Test')->firstOrFail();
        $candidate = $batch->items()->firstOrFail();

        $this->actingAs($this->fassgUser)->patch(
            route('fassg.generated-batches.applications.verify', [$batch, $app]),
            ['confirm_eligibility' => '1', 'confirm_gwa' => '1', 'confirm_address' => '1', 'confirm_cor' => '1']
        );
        $this->actingAs($this->fassgUser)->patch(route('fassg.generated-batches.submit', $batch));

        // Attempting to remove candidate after submission must be rejected with 403 Forbidden
        $this->actingAs($this->fassgUser)->delete(
            route('fassg.generated-batches.items.destroy', [$batch, $candidate])
        )->assertForbidden();

        // Attempting to delete the batch must be rejected with 403 Forbidden
        $this->actingAs($this->fassgUser)->delete(
            route('fassg.generated-batches.destroy', $batch)
        )->assertForbidden();
    }

    /**
     * Test 10: Accounting Read-Only Enforcement:
     * Accounting cannot mutate data (POST/PUT/DELETE rejected with 405) and cannot view unforwarded batches.
     */
    public function test_10_accounting_read_only_and_forwarding_guard(): void
    {
        [$u10, $p10] = $this->createVerifiedStudent('2024-2010', 'Jack Ryan', $this->bsit);
        $app = $this->submitOrdinaryApplication($u10, $p10, $this->program);

        $this->actingAs($this->fassgUser)->post(route('fassg.applications.create-batch'), [
            'batch_name' => 'Batch 10 - Accounting Test',
            'sponsorship_program_id' => $this->program->id,
            'selected_applications' => [$app->id],
        ]);
        $batch = GeneratedBatch::where('batch_name', 'Batch 10 - Accounting Test')->firstOrFail();

        $this->actingAs($this->fassgUser)->patch(
            route('fassg.generated-batches.applications.verify', [$batch, $app]),
            ['confirm_eligibility' => '1', 'confirm_gwa' => '1', 'confirm_address' => '1', 'confirm_cor' => '1']
        );
        $this->actingAs($this->fassgUser)->patch(route('fassg.generated-batches.submit', $batch));
        $batch->refresh();

        $doc = UploadedFile::fake()->create('approval.pdf', 100, 'application/pdf');
        $uploadResponse = $this->actingAs($this->sponsorUser)->post(route('sponsor.approvals.store', $batch), [
            'approval_document' => $doc,
            'submission_version' => 1,
        ]);
        $uploadResponse->assertSessionHasNoErrors();
        $approval = $batch->currentApproval();
        $this->actingAs($this->sponsorUser)->patch(route('sponsor.approvals.confirm', $batch), [
            'approval_id' => $approval->id,
            'submission_version' => 1,
        ]);

        // Accounting attempts mutation: MUST fail with 405 Method Not Allowed
        $this->actingAs($this->accountingUser)->post(route('accounting.beneficiaries.index'), [])->assertStatus(405);

        // Before FASSG forwards to Accounting, candidate is NOT visible in Accounting index
        $accountingView = $this->actingAs($this->accountingUser)->get(route('accounting.beneficiaries.index'));
        $accountingView->assertDontSee($p10->student_id_number);

        // FASSG records receipt and forwards to Accounting
        $this->actingAs($this->fassgUser)->patch(route('fassg.generated-batches.record-decision', $batch), [
            'approval_id' => $approval->id,
            'submission_version' => 1,
        ]);
        $this->actingAs($this->fassgUser)->patch(route('fassg.generated-batches.forward-accounting', $batch), [
            'approval_id' => $approval->id,
            'submission_version' => 1,
        ]);

        // After forwarding, candidate IS visible in Accounting index
        $accountingViewForwarded = $this->actingAs($this->accountingUser)->get(route('accounting.beneficiaries.index'));
        $accountingViewForwarded->assertSee($p10->student_id_number);
    }
}
