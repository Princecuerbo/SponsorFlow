<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\ConfirmationStatus;
use App\Enums\GeneratedBatchStatus;
use App\Enums\ProgramCategory;
use App\Enums\ProgramStatus;
use App\Enums\UserRole;
use App\Models\AcademicProgram;
use App\Models\Application;
use App\Models\BatchCandidate;
use App\Models\GeneratedBatch;
use App\Models\Sponsor;
use App\Models\SponsorshipProgram;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SponsorAccountingReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_sponsor_without_profile_can_open_lists_page(): void
    {
        $sponsorUser = User::factory()->create(['role' => UserRole::Sponsor]);

        $this->actingAs($sponsorUser)
            ->get(route('sponsor.lists.index'))
            ->assertOk();

        $this->assertDatabaseHas('sponsors', [
            'user_id' => $sponsorUser->id,
            'contact_email' => $sponsorUser->email,
        ]);
    }

    public function test_sponsor_can_review_forwarded_list_upload_and_confirm(): void
    {
        Storage::fake('local');

        $sponsor = Sponsor::factory()->create();
        $program = SponsorshipProgram::factory()->create(['sponsor_id' => $sponsor->id]);
        $profile = StudentProfile::factory()->create();
        $application = Application::factory()->create([
            'student_profile_id' => $profile->id,
            'sponsorship_program_id' => $program->id,
            'status' => ApplicationStatus::Pending,
        ]);
        $list = GeneratedBatch::factory()->create([
            'sponsorship_program_id' => $program->id,
            'status' => GeneratedBatchStatus::Submitted,
        ]);
        BatchCandidate::factory()->create([
            'generated_batch_id' => $list->id,
            'application_id' => $application->id,
        ]);

        $this->actingAs($sponsor->user)
            ->get(route('sponsor.lists.show', $list))
            ->assertOk()
            ->assertSee($profile->student_id_number);

        $this->actingAs($sponsor->user)
            ->post(route('sponsor.approvals.store', $list), [
                'approval_document' => UploadedFile::fake()->create('signed-approval.pdf', 120, 'application/pdf'),
            ])
            ->assertRedirect(route('sponsor.lists.show', $list));

        $this->actingAs($sponsor->user)
            ->patch(route('sponsor.approvals.confirm', $list))
            ->assertRedirect();

        $this->assertSame(GeneratedBatchStatus::Approved, $list->fresh()->status);
        $this->assertTrue($list->fresh()->latestApproval->isConfirmed());
        $this->assertSame(ApplicationStatus::Approved, $application->fresh()->status);
        $this->assertSame($application->id, $profile->fresh()->active_sponsorship_id);
    }

    public function test_sponsor_cannot_review_another_sponsors_list(): void
    {
        $sponsor = Sponsor::factory()->create();
        $otherList = GeneratedBatch::factory()->create(['status' => GeneratedBatchStatus::Submitted]);

        $this->actingAs($sponsor->user)
            ->get(route('sponsor.lists.show', $otherList))
            ->assertForbidden();
    }

    public function test_sponsor_can_filter_forwarded_applicants_by_academic_program(): void
    {
        $sponsor = Sponsor::factory()->create();
        $program = SponsorshipProgram::factory()->create(['sponsor_id' => $sponsor->id]);
        $it = AcademicProgram::factory()->create(['name' => 'BS Information Technology']);
        $cs = AcademicProgram::factory()->create(['name' => 'BS Computer Science']);

        $itProfile = StudentProfile::factory()->create([
            'academic_program_id' => $it->program_id,
        ]);
        $csProfile = StudentProfile::factory()->create([
            'academic_program_id' => $cs->program_id,
        ]);

        Application::factory()->create([
            'student_profile_id' => $itProfile->id,
            'sponsorship_program_id' => $program->id,
            'status' => ApplicationStatus::Verified,
        ]);
        Application::factory()->create([
            'student_profile_id' => $csProfile->id,
            'sponsorship_program_id' => $program->id,
            'status' => ApplicationStatus::Verified,
        ]);

        $this->actingAs($sponsor->user)
            ->get(route('sponsor.applicants.index', ['academic_program_id' => $it->program_id]))
            ->assertOk()
            ->assertSee($itProfile->user->name)
            ->assertDontSee($csProfile->user->name);
    }

    public function test_accounting_can_filter_beneficiaries_by_academic_program(): void
    {
        $accounting = User::factory()->create(['role' => UserRole::Accounting]);
        $sponsor = Sponsor::factory()->create();
        $program = SponsorshipProgram::factory()->create(['sponsor_id' => $sponsor->id]);
        $it = AcademicProgram::factory()->create(['name' => 'BS Information Technology']);
        $cs = AcademicProgram::factory()->create(['name' => 'BS Computer Science']);

        $itProfile = StudentProfile::factory()->create([
            'academic_program_id' => $it->program_id,
        ]);
        $csProfile = StudentProfile::factory()->create([
            'academic_program_id' => $cs->program_id,
        ]);

        Application::factory()->create([
            'student_profile_id' => $itProfile->id,
            'sponsorship_program_id' => $program->id,
            'status' => ApplicationStatus::Approved,
            'approved_at' => now(),
        ]);
        Application::factory()->create([
            'student_profile_id' => $csProfile->id,
            'sponsorship_program_id' => $program->id,
            'status' => ApplicationStatus::Approved,
            'approved_at' => now(),
        ]);

        $this->actingAs($accounting)
            ->get(route('accounting.beneficiaries.index', ['academic_program_id' => $it->program_id]))
            ->assertOk()
            ->assertSee($itProfile->user->name)
            ->assertDontSee($csProfile->user->name);
    }

    public function test_sponsor_can_review_and_confirm_an_individual_verified_application(): void
    {
        Storage::fake('local');

        $sponsor = Sponsor::factory()->create();
        $program = SponsorshipProgram::factory()->create(['sponsor_id' => $sponsor->id]);
        $profile = StudentProfile::factory()->create();
        $application = Application::factory()->create([
            'student_profile_id' => $profile->id,
            'sponsorship_program_id' => $program->id,
            'status' => ApplicationStatus::Verified,
        ]);

        $this->actingAs($sponsor->user)
            ->get(route('sponsor.applicants.index'))
            ->assertOk()
            ->assertSee($profile->user->name)
            ->assertSee($profile->student_id_number);

        $this->actingAs($sponsor->user)
            ->post(route('sponsor.applicants.confirm', $application), [
                'approval_document' => UploadedFile::fake()->create('endorsement.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect(route('sponsor.applicants.index'))
            ->assertSessionHas('status', 'Application confirmed and forwarded to Accounting.');

        $this->assertSame(ApplicationStatus::Approved, $application->fresh()->status);
        $this->assertSame($application->id, $profile->fresh()->active_sponsorship_id);
        $this->assertNotNull($application->fresh()->sponsor_approval_path);
    }

    public function test_sponsor_cannot_confirm_without_signed_document(): void
    {
        $sponsor = Sponsor::factory()->create();
        $list = GeneratedBatch::factory()->create([
            'sponsorship_program_id' => SponsorshipProgram::factory()->create(['sponsor_id' => $sponsor->id])->id,
            'status' => GeneratedBatchStatus::Submitted,
        ]);

        $this->actingAs($sponsor->user)
            ->from(route('sponsor.lists.show', $list))
            ->patch(route('sponsor.approvals.confirm', $list))
            ->assertRedirect(route('sponsor.lists.show', $list))
            ->assertSessionHasErrors('approval');
    }

    public function test_approving_last_slot_rejects_remaining_applications(): void
    {
        Storage::fake('local');

        $sponsor = Sponsor::factory()->create();
        $program = SponsorshipProgram::factory()->create([
            'sponsor_id' => $sponsor->id,
            'available_slots' => 1,
        ]);
        $approvedProfile = StudentProfile::factory()->create();
        $pendingProfile = StudentProfile::factory()->create();
        $application = Application::factory()->create([
            'student_profile_id' => $approvedProfile->id,
            'sponsorship_program_id' => $program->id,
            'status' => ApplicationStatus::Verified,
        ]);
        $pendingApplication = Application::factory()->create([
            'student_profile_id' => $pendingProfile->id,
            'sponsorship_program_id' => $program->id,
            'status' => ApplicationStatus::Pending,
        ]);

        $this->actingAs($sponsor->user)
            ->post(route('sponsor.applicants.confirm', $application), [
                'approval_document' => UploadedFile::fake()->create('endorsement.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect(route('sponsor.applicants.index'));

        $this->assertSame(ApplicationStatus::Rejected, $pendingApplication->fresh()->status);
        $this->assertSame('Program capacity reached (0 slots remaining).', $pendingApplication->fresh()->rejection_reason);
        $this->assertSame(ProgramStatus::Closed, $program->fresh()->status);
    }

    public function test_accounting_can_export_confirmed_beneficiaries_and_cannot_post(): void
    {
        $accounting = User::factory()->create(['role' => UserRole::Accounting]);
        $sponsor = Sponsor::factory()->create();
        $program = SponsorshipProgram::factory()->create([
            'sponsor_id' => $sponsor->id,
            'category' => ProgramCategory::Group,
        ]);
        $profile = StudentProfile::factory()->create();
        $profile->user->update(['name' => 'Billing Scholar']);
        $application = Application::factory()->create([
            'student_profile_id' => $profile->id,
            'sponsorship_program_id' => $program->id,
            'status' => ApplicationStatus::Pending,
        ]);
        $list = GeneratedBatch::factory()->create([
            'sponsorship_program_id' => $program->id,
            'status' => GeneratedBatchStatus::Approved,
            'fassg_assigned_at' => now(),
        ]);
        BatchCandidate::factory()->create([
            'generated_batch_id' => $list->id,
            'application_id' => $application->id,
        ]);
        $list->sponsorApprovals()->create([
            'sponsorship_program_id' => $program->id,
            'generated_batch_id' => $list->id,
            'approval_document_path' => 'sponsor-approvals/1/signed.pdf',
            'confirmation_status' => ConfirmationStatus::Confirmed,
            'uploaded_by_sponsor_id' => $sponsor->user_id,
        ]);

        $this->actingAs($accounting)
            ->get(route('accounting.beneficiaries.index'))
            ->assertOk()
            ->assertSee('Billing Scholar');

        $this->actingAs($accounting)
            ->get(route('accounting.beneficiaries.export'))
            ->assertOk()
            ->assertHeader('content-disposition');

        $this->actingAs($accounting)
            ->post(route('accounting.beneficiaries.index'))
            ->assertStatus(405);
    }

    public function test_accounting_lists_only_items_from_confirmed_batches(): void
    {
        $accounting = User::factory()->create(['role' => UserRole::Accounting]);
        $sponsor = Sponsor::factory()->create();
        $program = SponsorshipProgram::factory()->create(['sponsor_id' => $sponsor->id]);

        $verifiedProfile = StudentProfile::factory()->create();
        $verifiedProfile->user->update(['name' => 'Verified Beneficiary']);
        $verifiedApp = Application::factory()->create([
            'student_profile_id' => $verifiedProfile->id,
            'sponsorship_program_id' => $program->id,
            'status' => ApplicationStatus::Verified,
        ]);
        $list = GeneratedBatch::factory()->create([
            'sponsorship_program_id' => $program->id,
            'status' => GeneratedBatchStatus::Approved,
            'fassg_assigned_at' => now(),
        ]);
        BatchCandidate::factory()->create([
            'generated_batch_id' => $list->id,
            'application_id' => $verifiedApp->id,
        ]);
        $list->sponsorApprovals()->create([
            'sponsorship_program_id' => $program->id,
            'generated_batch_id' => $list->id,
            'approval_document_path' => 'sponsor-approvals/verified.pdf',
            'confirmation_status' => ConfirmationStatus::Confirmed,
            'uploaded_by_sponsor_id' => $sponsor->user_id,
        ]);

        $unapprovedProfile = StudentProfile::factory()->create();
        $unapprovedProfile->user->update(['name' => 'Unconfirmed Beneficiary']);
        $pendingApp = Application::factory()->create([
            'student_profile_id' => $unapprovedProfile->id,
            'sponsorship_program_id' => $program->id,
            'status' => ApplicationStatus::Pending,
        ]);
        $otherList = GeneratedBatch::factory()->create([
            'sponsorship_program_id' => $program->id,
            'status' => GeneratedBatchStatus::Submitted,
        ]);
        BatchCandidate::factory()->create([
            'generated_batch_id' => $otherList->id,
            'application_id' => $pendingApp->id,
        ]);

        $this->actingAs($accounting)
            ->get(route('accounting.beneficiaries.index'))
            ->assertOk()
            ->assertSee('Verified Beneficiary')
            ->assertDontSee('Unconfirmed Beneficiary');
    }

    public function test_accounting_can_view_confirmed_fixed_list_document(): void
    {
        Storage::fake('public');

        $accounting = User::factory()->create(['role' => UserRole::Accounting]);
        $sponsor = Sponsor::factory()->create();
        $program = SponsorshipProgram::factory()->create(['sponsor_id' => $sponsor->id]);
        $list = GeneratedBatch::factory()->create([
            'sponsorship_program_id' => $program->id,
            'status' => GeneratedBatchStatus::Approved,
            'fassg_assigned_at' => now(),
        ]);
        $list->sponsorApprovals()->create([
            'sponsorship_program_id' => $program->id,
            'generated_batch_id' => $list->id,
            'approval_document_path' => 'sponsor-approvals/final.pdf',
            'confirmation_status' => ConfirmationStatus::Confirmed,
            'uploaded_by_sponsor_id' => $sponsor->user_id,
        ]);
        Storage::disk('local')->put('sponsor-approvals/final.pdf', '%PDF-1.4 test');

        $this->actingAs($accounting)
            ->get(route('accounting.fixed-lists.document', $list))
            ->assertOk()
            ->assertHeader('content-disposition', 'inline; filename="final.pdf"');
    }

    public function test_accounting_dashboard_combines_applications_and_fixed_list_beneficiaries(): void
    {
        $accounting = User::factory()->create(['role' => UserRole::Accounting]);
        $sponsor = Sponsor::factory()->create();
        $program = SponsorshipProgram::factory()->create(['sponsor_id' => $sponsor->id]);
        $profile = StudentProfile::factory()->create();
        Application::factory()->create([
            'student_profile_id' => $profile->id,
            'sponsorship_program_id' => $program->id,
            'status' => ApplicationStatus::Approved,
            'approved_at' => now(),
        ]);

        $fixedListScholar = StudentProfile::factory()->create();
        $fixedListScholar->user->update(['name' => 'Fixed List Scholar']);
        $fixedListApp = Application::factory()->create([
            'student_profile_id' => $fixedListScholar->id,
            'sponsorship_program_id' => $program->id,
            'status' => ApplicationStatus::Verified,
        ]);
        $list = GeneratedBatch::factory()->create([
            'sponsorship_program_id' => $program->id,
            'status' => GeneratedBatchStatus::Approved,
            'fassg_assigned_at' => now(),
        ]);
        BatchCandidate::factory()->create([
            'generated_batch_id' => $list->id,
            'application_id' => $fixedListApp->id,
        ]);
        $list->sponsorApprovals()->create([
            'sponsorship_program_id' => $program->id,
            'generated_batch_id' => $list->id,
            'approval_document_path' => 'sponsor-approvals/dashboard.pdf',
            'confirmation_status' => ConfirmationStatus::Confirmed,
            'uploaded_by_sponsor_id' => $sponsor->user_id,
        ]);

        $this->actingAs($accounting)
            ->get(route('accounting.dashboard'))
            ->assertOk()
            ->assertSee('Approved beneficiaries')
            ->assertSee('Sponsor Allocation Summary')
            ->assertSee('2')
            ->assertDontSee('Fixed List Scholar');
    }

    public function test_accounting_dashboard_is_separate_and_can_view_confirmation_file_inline(): void
    {
        Storage::fake('public');

        $accounting = User::factory()->create(['role' => UserRole::Accounting]);
        $sponsor = Sponsor::factory()->create();
        $program = SponsorshipProgram::factory()->create(['sponsor_id' => $sponsor->id]);
        $profile = StudentProfile::factory()->create();
        $application = Application::factory()->create([
            'student_profile_id' => $profile->id,
            'sponsorship_program_id' => $program->id,
            'status' => ApplicationStatus::Approved,
            'approved_at' => now(),
            'sponsor_approval_path' => 'sponsor-approvals/applications/confirmation.pdf',
        ]);
        Storage::disk('local')->put($application->sponsor_approval_path, '%PDF-1.4 test');

        $this->actingAs($accounting)
            ->get(route('accounting.dashboard'))
            ->assertOk()
            ->assertSee('Accounting Dashboard')
            ->assertDontSee('Approved Beneficiaries</h1>');

        $this->actingAs($accounting)
            ->get(route('accounting.documents.view', $application))
            ->assertOk()
            ->assertHeader('content-disposition', 'inline; filename="confirmation.pdf"');
    }

    public function test_fassg_reports_include_category_and_applicant_totals(): void
    {
        $fassg = User::factory()->create(['role' => UserRole::Fassg]);
        $program = SponsorshipProgram::factory()->create(['category' => ProgramCategory::Individual]);
        Application::factory()->create([
            'sponsorship_program_id' => $program->id,
            'status' => ApplicationStatus::Approved,
            'approved_at' => now(),
        ]);

        $this->actingAs($fassg)
            ->get(route('fassg.reports.index'))
            ->assertOk()
            ->assertSee('Approved/Ongoing applications: 1')
            ->assertSee('Individual');
    }
}
