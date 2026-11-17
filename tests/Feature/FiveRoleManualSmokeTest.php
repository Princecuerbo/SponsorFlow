<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\DocumentType;
use App\Enums\FixedListItemStatus;
use App\Enums\FixedListStatus;
use App\Enums\GeneratedBatchStatus;
use App\Enums\ProgramStatus;
use App\Enums\UserRole;
use App\Models\AcademicProgram;
use App\Models\Application;
use App\Models\FixedList;
use App\Models\GeneratedBatch;
use App\Models\Sponsor;
use App\Models\SponsorshipProgram;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FiveRoleManualSmokeTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;
    private User $fassg;
    private User $sponsorUser;
    private Sponsor $sponsorOrg;
    private User $accounting;
    private User $studentUser;
    private StudentProfile $studentProfile;
    private AcademicProgram $programBsam;
    private SponsorshipProgram $sponsorship;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'mysql']);
        config(['database.connections.mysql.database' => 'sponsorflow_isolated_test']);
        DB::purge('mysql');
        DB::reconnect('mysql');

        $currentDb = DB::connection()->getDatabaseName();
        $mysqlHost = (string) config('database.connections.mysql.host', '127.0.0.1');
        $pgsqlHost = (string) config('database.connections.pgsql.host', '');

        if (strtolower($currentDb) === 'sponsorflow') {
            $this->fail('CRITICAL SAFETY BLOCK: Test attempted to execute against local production sponsorflow.');
        }
        if (str_contains(strtolower($mysqlHost), 'render.com') || str_contains(strtolower($pgsqlHost), 'render.com')) {
            $this->fail('CRITICAL SAFETY BLOCK: Test attempted to execute against remote Render PostgreSQL.');
        }

        $this->assertSame('sponsorflow_isolated_test', $currentDb);
        Storage::fake('local');

        $this->programBsam = AcademicProgram::firstOrCreate(
            ['code' => 'BSAM'],
            [
                'name' => 'Bachelor of Science in Agribusiness Management',
                'short_name' => 'BSAM',
                'is_undergraduate' => true,
                'is_active' => true,
            ]
        );

        $suffix = uniqid();

        // 1. Admin
        $this->admin = User::factory()->create([
            'role' => UserRole::Admin,
            'email' => "admin.{$suffix}@dorsu.edu.ph",
        ]);

        // 2. FASSG
        $this->fassg = User::factory()->create([
            'role' => UserRole::Fassg,
            'email' => "fassg.{$suffix}@dorsu.edu.ph",
        ]);

        // 3. Sponsor
        $this->sponsorUser = User::factory()->create([
            'role' => UserRole::Sponsor,
            'email' => "sponsor.{$suffix}@dorsu.edu.ph",
        ]);
        $this->sponsorOrg = Sponsor::factory()->create([
            'user_id' => $this->sponsorUser->id,
            'company_organization_name' => "DOrSU Alumni Foundation {$suffix}",
        ]);

        // 4. Accounting
        $this->accounting = User::factory()->create([
            'role' => UserRole::Accounting,
            'email' => "accounting.{$suffix}@dorsu.edu.ph",
        ]);

        // 5. Student
        $this->studentUser = User::factory()->create([
            'role' => UserRole::Student,
            'email' => "student.{$suffix}@dorsu.edu.ph",
        ]);
        $this->studentProfile = StudentProfile::factory()->create([
            'user_id' => $this->studentUser->id,
            'student_id_number' => '2024-' . mt_rand(100000, 999999),
            'first_name' => 'Synthetic',
            'last_name' => 'Applicant',
            'academic_program_id' => $this->programBsam->program_id,
            'course' => $this->programBsam->name,
            'year_level' => 2,
            'campus' => 'Main Campus (City of Mati)',
        ]);

        // Sponsorship Program
        $this->sponsorship = SponsorshipProgram::factory()->create([
            'sponsor_id' => $this->sponsorOrg->id,
            'program_name' => "Agribusiness Leadership Grant {$suffix}",
            'total_slots' => 10,
            'available_slots' => 10,
            'min_gpa' => 2.25,
            'target_province' => 'Davao Oriental',
            'required_documents' => ['Certificate of Registration (COR)'],
            'status' => ProgramStatus::Open,
        ]);
        $this->sponsorship->academicPrograms()->attach($this->programBsam->program_id);
    }

    /**
     * Complete smoke test verifying all 5 roles across the full sponsorship lifecycle.
     */
    public function test_five_roles_smoke_test_lifecycle(): void
    {
        // -------------------------------------------------------------
        // ROLE 1: Student Step A — View Dashboard & Submit SLE-FHE Request
        // -------------------------------------------------------------
        $this->actingAs($this->studentUser)
            ->get(route('student.dashboard'))
            ->assertOk();

        $this->actingAs($this->studentUser)
            ->get(route('student.sle-fhe'))
            ->assertOk();

        $this->actingAs($this->studentUser)
            ->post(route('student.sle-fhe.request'), [
                'province' => 'Davao Oriental',
                'municipality_city' => 'City of Mati',
                'barangay' => 'Central',
                'street_purok' => 'Purok Mangga',
            ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('sle_fhe_requests', [
            'student_profile_id' => $this->studentProfile->id,
            'status' => 'pending',
        ]);

        // -------------------------------------------------------------
        // ROLE 2: FASSG Step A — Review SLE-FHE Queue & Verify Student
        // -------------------------------------------------------------
        $this->actingAs($this->fassg)
            ->get(route('fassg.dashboard'))
            ->assertOk();

        $this->actingAs($this->fassg)
            ->get(route('fassg.sle-fhe.index'))
            ->assertOk()
            ->assertSee($this->studentProfile->student_id_number);

        $requestRecord = $this->studentProfile->sleFheRequests()->where('status', 'pending')->firstOrFail();
        $this->actingAs($this->fassg)
            ->post(route('fassg.sle-fhe.verify', $this->studentProfile), [
                'verification_request_id' => $requestRecord->id,
                'confirm_identity' => 1,
                'override_registry_mismatch' => 1,
                'override_reason' => 'Official Dean certification and verified enrollment form presented in person.',
                'evidence_reference' => 'FASSG-VERIF-2024-0089',
            ])->assertSessionHasNoErrors();

        $this->assertTrue($this->studentProfile->fresh()->hasDocumentedSleFheVerification());

        $this->actingAs($this->fassg)
            ->get(route('fassg.sle-fhe.verified'))
            ->assertOk()
            ->assertSee($this->studentProfile->student_id_number);

        // -------------------------------------------------------------
        // ROLE 1: Student Step B — Browse Programs & Apply
        // -------------------------------------------------------------
        $corFile = UploadedFile::fake()->create('cor.pdf', 60, 'application/pdf');
        $this->actingAs($this->studentUser->fresh())
            ->get(route('student.programs.index'))
            ->assertOk()
            ->assertSee($this->sponsorship->program_name);

        $this->actingAs($this->studentUser->fresh())
            ->post(route('student.applications.store'), [
                'sponsorship_program_id' => $this->sponsorship->id,
                'gpa_submitted' => 1.75,
                'address_submitted' => 'Purok Mangga, Central, City of Mati, Davao Oriental',
                'is_rural_submitted' => 1,
                'cor_doc' => $corFile,
            ])->assertSessionHasNoErrors();

        $application = Application::where('student_profile_id', $this->studentProfile->id)
            ->where('sponsorship_program_id', $this->sponsorship->id)
            ->firstOrFail();

        $this->actingAs($this->studentUser)
            ->get(route('student.applications.index'))
            ->assertOk()
            ->assertSee($this->sponsorship->program_name);

        // -------------------------------------------------------------
        // ROLE 2: FASSG Step B — Fixed List Encoding & Batch Management
        // -------------------------------------------------------------
        $this->actingAs($this->fassg)
            ->get(route('fassg.fixed-lists.index'))
            ->assertOk();

        $fixedList = FixedList::create([
            'sponsorship_program_id' => $this->sponsorship->id,
            'batch_name' => 'Agribusiness Priority Roster',
            'status' => FixedListStatus::Draft,
            'uploaded_by_fassg_id' => $this->fassg->id,
            'total_names' => 0,
        ]);

        // Manually encode student with custom year level (SF-001 / SF-002)
        $item = $fixedList->items()->create([
            'student_id_number' => $this->studentProfile->student_id_number,
            'student_name' => 'Synthetic Applicant',
            'course' => $this->programBsam->name,
            'year_level' => 3, // Allowed to differ from profile per SF-001
            'campus' => 'Main Campus (City of Mati)',
            'status' => FixedListItemStatus::Verified,
            'is_sle_fhe_verified' => true,
            'is_fixed_list' => true,
        ]);

        // Endorse fixed list item
        $this->actingAs($this->fassg)
            ->patch(route('fassg.fixed-lists.items.endorse', [$fixedList, $item]))
            ->assertSessionHasNoErrors();

        // Finalize Fixed List
        $this->actingAs($this->fassg)
            ->patch(route('fassg.fixed-lists.finalize', $fixedList))
            ->assertSessionHasNoErrors();

        $this->assertSame(FixedListStatus::Finalized, $fixedList->fresh()->status);

        // Ordinary Applicant Review & Qualification Verification (SF-007)
        $batch = GeneratedBatch::create([
            'sponsorship_program_id' => $this->sponsorship->id,
            'batch_name' => 'Agribusiness Final Candidate Batch',
            'created_by_fassg_id' => $this->fassg->id,
            'total_slots' => 1,
            'status' => GeneratedBatchStatus::Saved,
            'submission_version' => 0,
        ]);
        $batch->items()->create(['application_id' => $application->id, 'origin_type' => 'ranked_queue']);
        $application->update(['is_batched' => true, 'batch_id' => $batch->id]);

        $this->actingAs($this->fassg)
            ->patch(route('fassg.generated-batches.applications.verify', [$batch, $application]), [
                'confirm_eligibility' => 1,
                'confirm_gwa' => 1,
                'confirm_address' => 1,
                'confirm_cor' => 1,
            ])->assertSessionHasNoErrors();

        // Submit Batch to Sponsor
        $this->actingAs($this->fassg)
            ->patch(route('fassg.generated-batches.submit', $batch))
            ->assertSessionHasNoErrors();

        $batch = $batch->fresh();
        $this->assertSame(GeneratedBatchStatus::Submitted, $batch->status);

        // -------------------------------------------------------------
        // ROLE 3: Sponsor — Review Roster, Upload Decision & Confirm
        // -------------------------------------------------------------
        $this->actingAs($this->sponsorUser)
            ->get(route('sponsor.dashboard'))
            ->assertOk();

        $this->actingAs($this->sponsorUser)
            ->get(route('sponsor.lists.show', $batch))
            ->assertOk()
            ->assertSee($this->studentProfile->student_id_number);

        $resolution = UploadedFile::fake()->create('board_resolution.pdf', 80, 'application/pdf');
        $this->actingAs($this->sponsorUser)
            ->post(route('sponsor.approvals.store', $batch), [
                'approval_document' => $resolution,
                'submission_version' => $batch->submission_version,
            ])->assertSessionHasNoErrors();

        $approval = $batch->currentApproval();
        $this->assertNotNull($approval);
        $this->actingAs($this->sponsorUser)
            ->patch(route('sponsor.approvals.confirm', $batch), [
                'approval_id' => $approval->id,
                'submission_version' => $batch->submission_version,
            ])->assertSessionHasNoErrors();

        // -------------------------------------------------------------
        // FASSG Receipt & Forwarding to Accounting
        // -------------------------------------------------------------
        $this->actingAs($this->fassg)
            ->patch(route('fassg.generated-batches.record-decision', $batch), [
                'approval_id' => $approval->id,
                'submission_version' => 1,
            ])->assertSessionHasNoErrors();

        $this->actingAs($this->fassg)
            ->patch(route('fassg.generated-batches.forward-accounting', $batch), [
                'approval_id' => $approval->id,
                'submission_version' => 1,
            ])->assertSessionHasNoErrors();

        // -------------------------------------------------------------
        // ROLE 4: Accounting — Access Beneficiaries & Export
        // -------------------------------------------------------------
        $this->actingAs($this->accounting)
            ->get(route('accounting.dashboard'))
            ->assertOk();

        $this->actingAs($this->accounting)
            ->get(route('accounting.beneficiaries.index'))
            ->assertOk()
            ->assertSee($this->studentProfile->student_id_number);

        $this->actingAs($this->accounting)
            ->get(route('accounting.beneficiaries.export'))
            ->assertOk();

        // -------------------------------------------------------------
        // ROLE 5: Admin — Dashboards, User Management, Settings, Audit
        // -------------------------------------------------------------
        $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertOk();

        $this->actingAs($this->admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee($this->studentUser->email);

        $this->actingAs($this->admin)
            ->get(route('admin.settings.index'))
            ->assertOk();

        $this->actingAs($this->admin)
            ->get(route('admin.audit-logs.index'))
            ->assertOk();
    }
}
