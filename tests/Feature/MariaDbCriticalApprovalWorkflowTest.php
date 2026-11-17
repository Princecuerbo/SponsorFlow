<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\ConfirmationStatus;
use App\Enums\DocumentType;
use App\Enums\GeneratedBatchStatus;
use App\Enums\ProgramCategory;
use App\Enums\ProgramStatus;
use App\Enums\UserRole;
use App\Models\AcademicProgram;
use App\Models\Application;
use App\Models\BatchCandidate;
use App\Models\GeneratedBatch;
use App\Models\Sponsor;
use App\Models\SponsorApproval;
use App\Models\SponsorshipProgram;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MariaDbCriticalApprovalWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    private User $fassgUser;
    private User $sponsorUser;
    private User $accountingUser;
    private Sponsor $sponsorOrg;
    private AcademicProgram $bsit;

    protected function setUp(): void
    {
        parent::setUp();

        // Strictly point to the isolated MariaDB test database
        config(['database.default' => 'mysql']);
        config(['database.connections.mysql.database' => 'sponsorflow_isolated_test']);
        DB::purge('mysql');
        DB::reconnect('mysql');

        $currentDb = DB::connection()->getDatabaseName();
        $mysqlHost = (string) config('database.connections.mysql.host', '127.0.0.1');
        $pgsqlHost = (string) config('database.connections.pgsql.host', '');

        // Hard fail-closed safeguards preventing any accidental writes to local production or Render
        if (strtolower($currentDb) === 'sponsorflow') {
            $this->fail('CRITICAL SAFETY BLOCK: Test attempted to execute against the local production sponsorflow database.');
        }

        if (str_contains(strtolower($mysqlHost), 'render.com') || str_contains(strtolower($pgsqlHost), 'render.com')) {
            $this->fail('CRITICAL SAFETY BLOCK: Test attempted to execute against remote Render PostgreSQL.');
        }

        $this->assertSame('sponsorflow_isolated_test', $currentDb, 'Safety violation: test must connect exclusively to sponsorflow_isolated_test.');

        Storage::fake('local');

        $this->fassgUser = User::factory()->create([
            'role' => UserRole::Fassg,
            'email' => 'fassg.lead.' . uniqid() . '@dorsu.edu.ph',
        ]);

        $this->sponsorUser = User::factory()->create([
            'role' => UserRole::Sponsor,
            'email' => 'sponsor.' . uniqid() . '@dorsu.edu.ph',
        ]);

        $this->sponsorOrg = Sponsor::factory()->create([
            'user_id' => $this->sponsorUser->id,
            'company_organization_name' => 'Provincial Testing Foundation',
        ]);

        $this->accountingUser = User::factory()->create([
            'role' => UserRole::Accounting,
            'email' => 'accounting.' . uniqid() . '@dorsu.edu.ph',
        ]);

        $this->bsit = AcademicProgram::firstOrCreate(
            ['code' => 'BSIT'],
            [
                'name' => 'Bachelor of Science in Information Technology',
                'short_name' => 'BSIT',
                'is_undergraduate' => true,
                'is_active' => true,
            ]
        );
    }

    private function createStudentWithApplication(SponsorshipProgram $program, float $gpa = 1.75): array
    {
        $uniqueId = uniqid();
        $studentIdNum = '2024-' . mt_rand(100000, 999999);
        $user = User::factory()->create([
            'role' => UserRole::Student,
            'email' => "student.{$uniqueId}@dorsu.edu.ph",
        ]);

        $profile = StudentProfile::factory()->create([
            'user_id' => $user->id,
            'student_id_number' => $studentIdNum,
            'first_name' => 'Test',
            'last_name' => "Student{$uniqueId}",
            'academic_program_id' => $this->bsit->program_id,
            'course' => $this->bsit->name,
            'year_level' => 3,
            'campus' => 'Main Campus (City of Mati)',
        ]);

        $profile->sleFheVerification()->create([
            'verified_address' => 'City of Mati, Davao Oriental',
            'verified_by' => $this->fassgUser->id,
            'verified_at' => now(),
            'notes' => 'Documented verification',
        ]);

        $app = Application::create([
            'student_profile_id' => $profile->id,
            'sponsorship_program_id' => $program->id,
            'status' => ApplicationStatus::Verified,
            'gpa_submitted' => $gpa,
            'address_submitted' => 'City of Mati, Davao Oriental',
            'is_rural_submitted' => true,
            'verified_at' => now(),
        ]);

        $cor = UploadedFile::fake()->create('cor.pdf', 50, 'application/pdf');
        $cog = UploadedFile::fake()->create('grades.pdf', 50, 'application/pdf');
        $path1 = $cor->store('applications/documents', 'local');
        $path2 = $cog->store('applications/documents', 'local');

        $app->documents()->create([
            'document_type' => DocumentType::CertificateOfRegistration->value,
            'file_path' => $path1,
            'file_name' => 'cor.pdf',
        ]);
        $app->documents()->create([
            'document_type' => DocumentType::CertificateOfGrades->value,
            'file_path' => $path2,
            'file_name' => 'grades.pdf',
        ]);

        return [$user, $profile, $app];
    }

    private function createAndSubmitBatch(SponsorshipProgram $program, array $applications, string $batchName = 'Test Batch'): GeneratedBatch
    {
        $batch = GeneratedBatch::create([
            'sponsorship_program_id' => $program->id,
            'batch_name' => $batchName,
            'created_by_fassg_id' => $this->fassgUser->id,
            'total_slots' => count($applications),
            'status' => GeneratedBatchStatus::Saved,
            'submission_version' => 0,
        ]);

        foreach ($applications as $app) {
            $batch->items()->create([
                'application_id' => $app->id,
                'origin_type' => 'ranked_queue',
            ]);
            $app->update(['is_batched' => true, 'batch_id' => $batch->id]);
        }

        $this->actingAs($this->fassgUser)->patch(route('fassg.generated-batches.submit', $batch))->assertSessionHasNoErrors();
        $batch->refresh();

        return $batch;
    }

    /**
     * 1. Whole-batch Sponsor approval is strictly transactional in MariaDB.
     * All matching members are promoted atomically, slots decremented, and active_sponsorship_id set.
     */
    public function test_whole_batch_sponsor_approval_is_transactional(): void
    {
        $program = SponsorshipProgram::factory()->create([
            'sponsor_id' => $this->sponsorOrg->id,
            'total_slots' => 5,
            'available_slots' => 5,
            'min_gpa' => 2.00,
            'status' => ProgramStatus::Open,
            'required_documents' => ['Certificate of Registration (COR)', 'Report Card / Certificate of Grades'],
        ]);

        [$u1, $p1, $a1] = $this->createStudentWithApplication($program, 1.75);
        [$u2, $p2, $a2] = $this->createStudentWithApplication($program, 1.50);

        $batch = $this->createAndSubmitBatch($program, [$a1, $a2], 'Batch Transactional 1');

        $doc = UploadedFile::fake()->create('resolution.pdf', 100, 'application/pdf');
        $this->actingAs($this->sponsorUser)->post(route('sponsor.approvals.store', $batch), [
            'approval_document' => $doc,
            'submission_version' => 1,
        ])->assertSessionHasNoErrors();

        $approval = $batch->currentApproval();

        $this->actingAs($this->sponsorUser)->patch(route('sponsor.approvals.confirm', $batch), [
            'approval_id' => $approval->id,
            'submission_version' => 1,
        ])->assertSessionHasNoErrors();

        $batch->refresh();
        $this->assertSame(GeneratedBatchStatus::Approved, $batch->status);
        $this->assertSame(ApplicationStatus::Approved, $a1->fresh()->status);
        $this->assertSame(ApplicationStatus::Approved, $a2->fresh()->status);
        $this->assertSame($a1->id, $p1->fresh()->active_sponsorship_id);
        $this->assertSame($a2->id, $p2->fresh()->active_sponsorship_id);
        $this->assertSame(3, $program->fresh()->available_slots);
    }

    /**
     * 2. If one member fails current eligibility or document checks, no partial approval occurs.
     */
    public function test_disqualified_member_blocks_entire_approval_transaction_with_zero_partial_updates(): void
    {
        $program = SponsorshipProgram::factory()->create([
            'sponsor_id' => $this->sponsorOrg->id,
            'total_slots' => 5,
            'available_slots' => 5,
            'min_gpa' => 2.00,
            'status' => ProgramStatus::Open,
            'required_documents' => ['Certificate of Registration (COR)', 'Report Card / Certificate of Grades'],
        ]);

        [$u1, $p1, $a1] = $this->createStudentWithApplication($program, 1.75);
        [$u2, $p2, $a2] = $this->createStudentWithApplication($program, 1.50);

        $batch = $this->createAndSubmitBatch($program, [$a1, $a2], 'Batch Failure Check');

        $doc = UploadedFile::fake()->create('resolution.pdf', 100, 'application/pdf');
        $this->actingAs($this->sponsorUser)->post(route('sponsor.approvals.store', $batch), [
            'approval_document' => $doc,
            'submission_version' => 1,
        ]);
        $approval = $batch->currentApproval();

        // Student 2 obtains an active sponsorship in another program after batch was submitted
        $otherProgram = SponsorshipProgram::factory()->create(['status' => ProgramStatus::Open]);
        $otherApp = Application::create([
            'student_profile_id' => $p2->id,
            'sponsorship_program_id' => $otherProgram->id,
            'status' => ApplicationStatus::Approved,
            'approved_at' => now(),
            'gpa_submitted' => 1.50,
        ]);
        $p2->update(['active_sponsorship_id' => $otherApp->id]);

        // Sponsor confirms whole batch: MUST be completely rejected due to student 2 conflict
        $response = $this->actingAs($this->sponsorUser)->patch(route('sponsor.approvals.confirm', $batch), [
            'approval_id' => $approval->id,
            'submission_version' => 1,
        ]);
        $response->assertSessionHasErrors('batch');

        // Verify ZERO partial updates: Student 1 is NOT approved
        $this->assertSame(GeneratedBatchStatus::Submitted, $batch->fresh()->status);
        $this->assertSame(ApplicationStatus::Verified, $a1->fresh()->status, 'Student 1 must NOT be partially approved.');
        $this->assertNull($p1->fresh()->active_sponsorship_id);
        $this->assertSame(5, $program->fresh()->available_slots, 'Slots must NOT be decremented.');
    }

    /**
     * 3. A stale Sponsor decision cannot overwrite a newer decision.
     */
    public function test_stale_sponsor_decision_cannot_overwrite_newer_decision(): void
    {
        $program = SponsorshipProgram::factory()->create([
            'sponsor_id' => $this->sponsorOrg->id,
            'total_slots' => 5,
            'available_slots' => 5,
            'min_gpa' => 2.00,
            'status' => ProgramStatus::Open,
        ]);

        [$u1, $p1, $a1] = $this->createStudentWithApplication($program, 1.75);
        $batch = $this->createAndSubmitBatch($program, [$a1], 'Batch Stale Decision');

        $doc = UploadedFile::fake()->create('resolution.pdf', 100, 'application/pdf');
        $this->actingAs($this->sponsorUser)->post(route('sponsor.approvals.store', $batch), [
            'approval_document' => $doc,
            'submission_version' => 1,
        ]);
        $approval = $batch->currentApproval();

        // Attempt confirm with stale submission_version 99
        $response = $this->actingAs($this->sponsorUser)->patch(route('sponsor.approvals.confirm', $batch), [
            'approval_id' => $approval->id,
            'submission_version' => 99,
        ]);
        $response->assertSessionHasErrors('batch');
        $this->assertSame(GeneratedBatchStatus::Submitted, $batch->fresh()->status);
    }

    /**
     * 4. Simultaneous decisions cannot approve the same batch twice.
     */
    public function test_already_approved_batch_cannot_be_approved_again(): void
    {
        $program = SponsorshipProgram::factory()->create([
            'sponsor_id' => $this->sponsorOrg->id,
            'total_slots' => 5,
            'available_slots' => 5,
            'min_gpa' => 2.00,
            'status' => ProgramStatus::Open,
        ]);

        [$u1, $p1, $a1] = $this->createStudentWithApplication($program, 1.75);
        $batch = $this->createAndSubmitBatch($program, [$a1], 'Batch Double Approval');

        $doc = UploadedFile::fake()->create('resolution.pdf', 100, 'application/pdf');
        $this->actingAs($this->sponsorUser)->post(route('sponsor.approvals.store', $batch), [
            'approval_document' => $doc,
            'submission_version' => 1,
        ]);
        $approval = $batch->currentApproval();

        // First approval succeeds
        $this->actingAs($this->sponsorUser)->patch(route('sponsor.approvals.confirm', $batch), [
            'approval_id' => $approval->id,
            'submission_version' => 1,
        ])->assertSessionHasNoErrors();
        $this->assertSame(GeneratedBatchStatus::Approved, $batch->fresh()->status);

        // Second approval attempt is strictly forbidden (batch is no longer forwarded to Sponsor)
        $second = $this->actingAs($this->sponsorUser)->patch(route('sponsor.approvals.confirm', $batch), [
            'approval_id' => $approval->id,
            'submission_version' => 1,
        ]);
        $second->assertForbidden();
        $this->assertSame(4, $program->fresh()->available_slots);
    }

    /**
     * 5. Available capacity cannot be exceeded by approvals.
     */
    public function test_approval_fails_if_capacity_is_exceeded(): void
    {
        $program = SponsorshipProgram::factory()->create([
            'sponsor_id' => $this->sponsorOrg->id,
            'total_slots' => 1,
            'available_slots' => 1,
            'min_gpa' => 2.00,
            'status' => ProgramStatus::Open,
        ]);

        [$u1, $p1, $a1] = $this->createStudentWithApplication($program, 1.75);
        $batch = $this->createAndSubmitBatch($program, [$a1], 'Batch Capacity Test');

        $doc = UploadedFile::fake()->create('resolution.pdf', 100, 'application/pdf');
        $this->actingAs($this->sponsorUser)->post(route('sponsor.approvals.store', $batch), [
            'approval_document' => $doc,
            'submission_version' => 1,
        ]);
        $approval = $batch->currentApproval();

        // Concurrently consume the remaining slot by another grant
        $program->update(['available_slots' => 0]);

        // Attempting to confirm batch when slots are 0 must fail
        $response = $this->actingAs($this->sponsorUser)->patch(route('sponsor.approvals.confirm', $batch), [
            'approval_id' => $approval->id,
            'submission_version' => 1,
        ]);
        $response->assertSessionHasErrors('approval');
        $this->assertSame(GeneratedBatchStatus::Submitted, $batch->fresh()->status);
        $this->assertSame(ApplicationStatus::Verified, $a1->fresh()->status);
    }

    /**
     * 6. Frozen submitted rosters cannot be modified or deleted.
     */
    public function test_submitted_roster_is_immutable_and_deletion_is_blocked(): void
    {
        $program = SponsorshipProgram::factory()->create([
            'sponsor_id' => $this->sponsorOrg->id,
            'total_slots' => 5,
            'available_slots' => 5,
            'status' => ProgramStatus::Open,
        ]);

        [$u1, $p1, $a1] = $this->createStudentWithApplication($program, 1.75);
        $batch = $this->createAndSubmitBatch($program, [$a1], 'Batch Immutability Test');
        $candidate = $batch->items()->sole();

        // Removal of candidate from submitted batch is blocked (403)
        $this->actingAs($this->fassgUser)->delete(
            route('fassg.generated-batches.items.destroy', [$batch, $candidate])
        )->assertForbidden();

        // Deletion of submitted batch is blocked (403)
        $this->actingAs($this->fassgUser)->delete(
            route('fassg.generated-batches.destroy', $batch)
        )->assertForbidden();

        $this->assertDatabaseHas('generated_batches', ['id' => $batch->id]);
        $this->assertDatabaseHas('generated_batch_items', ['id' => $candidate->id]);
    }

    /**
     * 7. FASSG receipt and Accounting forwarding remain separate explicit actions.
     * 8. Accounting cannot access unforwarded beneficiaries.
     */
    public function test_fassg_receipt_and_accounting_forwarding_are_separate_and_guard_access(): void
    {
        $program = SponsorshipProgram::factory()->create([
            'sponsor_id' => $this->sponsorOrg->id,
            'total_slots' => 5,
            'available_slots' => 5,
            'min_gpa' => 2.00,
            'status' => ProgramStatus::Open,
        ]);

        [$u1, $p1, $a1] = $this->createStudentWithApplication($program, 1.75);
        $batch = $this->createAndSubmitBatch($program, [$a1], 'Batch Forwarding Guard');

        $doc = UploadedFile::fake()->create('resolution.pdf', 100, 'application/pdf');
        $this->actingAs($this->sponsorUser)->post(route('sponsor.approvals.store', $batch), [
            'approval_document' => $doc,
            'submission_version' => 1,
        ]);
        $approval = $batch->currentApproval();

        $this->actingAs($this->sponsorUser)->patch(route('sponsor.approvals.confirm', $batch), [
            'approval_id' => $approval->id,
            'submission_version' => 1,
        ])->assertSessionHasNoErrors();

        // 1. Beneficiary is NOT visible to Accounting immediately after Sponsor confirmation
        $this->actingAs($this->accountingUser)->get(route('accounting.beneficiaries.index'))
            ->assertDontSee($p1->student_id_number);

        // 2. Attempting to forward to Accounting before FASSG records receipt is blocked
        $blockedForward = $this->actingAs($this->fassgUser)->patch(route('fassg.generated-batches.forward-accounting', $batch), [
            'approval_id' => $approval->id,
            'submission_version' => 1,
        ]);
        $blockedForward->assertSessionHasErrors('approval');

        // 3. FASSG records decision receipt
        $this->actingAs($this->fassgUser)->patch(route('fassg.generated-batches.record-decision', $batch), [
            'approval_id' => $approval->id,
            'submission_version' => 1,
        ])->assertSessionHasNoErrors();

        // Still not visible to Accounting after receipt alone
        $this->actingAs($this->accountingUser)->get(route('accounting.beneficiaries.index'))
            ->assertDontSee($p1->student_id_number);

        // 4. FASSG explicitly forwards to Accounting
        $this->actingAs($this->fassgUser)->patch(route('fassg.generated-batches.forward-accounting', $batch), [
            'approval_id' => $approval->id,
            'submission_version' => 1,
        ])->assertSessionHasNoErrors();

        // 5. Accounting now has read-only access to beneficiary
        $this->actingAs($this->accountingUser)->get(route('accounting.beneficiaries.index'))
            ->assertOk()
            ->assertSee($p1->student_id_number);
    }

    /**
     * 9. Genuine Simultaneous-Request Concurrency Test:
     * Distinguishes sequential stale-request tests from overlapping transactions across two distinct PDO connections.
     * Demonstrates that MariaDB InnoDB row-level locking (SELECT ... FOR UPDATE) genuinely blocks concurrent transactions
     * from racing or modifying critical resources simultaneously.
     */
    public function test_genuine_simultaneous_request_concurrency_blocks_conflicting_transactions_via_mariadb_row_locks(): void
    {
        $dbHost = config('database.connections.mysql.host', '127.0.0.1');
        $dbPort = config('database.connections.mysql.port', '3306');
        $dbName = config('database.connections.mysql.database');
        $dbUser = config('database.connections.mysql.username', 'root');
        $dbPass = config('database.connections.mysql.password', '');

        $this->assertSame('sponsorflow_isolated_test', $dbName);

        // Open two distinct physical PDO connections to the isolated test MariaDB database
        $pdo1 = new \PDO("mysql:host={$dbHost};port={$dbPort};dbname={$dbName}", $dbUser, $dbPass, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        ]);
        $pdo2 = new \PDO("mysql:host={$dbHost};port={$dbPort};dbname={$dbName}", $dbUser, $dbPass, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        ]);

        // Configure connection 2 with a 1-second lock wait timeout to detect blocking immediately
        $pdo2->exec('SET SESSION innodb_lock_wait_timeout = 1');

        // Connection 1 begins transaction and acquires exclusive pessimistic row lock (FOR UPDATE)
        $pdo1->beginTransaction();
        $stmt1 = $pdo1->prepare('SELECT program_id, code, name FROM academic_programs WHERE code = :code FOR UPDATE');
        $stmt1->execute(['code' => 'BSIT']);
        $row1 = $stmt1->fetch(\PDO::FETCH_ASSOC);
        $this->assertNotEmpty($row1);

        // Connection 2 begins concurrent transaction and attempts to acquire lock on the same row
        $pdo2->beginTransaction();
        $timedOut = false;
        try {
            $stmt2 = $pdo2->prepare('SELECT program_id, code, name FROM academic_programs WHERE code = :code FOR UPDATE');
            $stmt2->execute(['code' => 'BSIT']);
            $stmt2->fetch(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            // Error code 1205: Lock wait timeout exceeded
            if (str_contains($e->getMessage(), '1205') || str_contains($e->getMessage(), 'Lock wait timeout')) {
                $timedOut = true;
            } else {
                throw $e;
            }
        }

        $this->assertTrue($timedOut, 'Expected Connection 2 to be blocked by MariaDB row lock held by Connection 1 until timeout.');

        // Clean up transactions
        $pdo1->rollBack();
        $pdo2->rollBack();
    }
}
