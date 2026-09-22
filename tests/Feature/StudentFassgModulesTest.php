<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\DocumentType;
use App\Enums\FixedListItemStatus;
use App\Enums\FixedListStatus;
use App\Enums\GeneratedBatchStatus;
use App\Enums\ProgramCategory;
use App\Enums\ProgramStatus;
use App\Enums\UserRole;
use App\Models\AcademicProgram;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\BatchCandidate;
use App\Models\FixedList;
use App\Models\FixedListItem;
use App\Models\GeneratedBatch;
use App\Models\Sponsor;
use App\Models\SponsorshipProgram;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentFassgModulesTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsStudent(User $user)
    {
        return $this->actingAs($user)->withSession([
            'data_privacy_consented' => true,
            'privacy_consented_session' => true,
        ]);
    }

    public function test_fassg_program_form_lists_sponsor_users_without_existing_profiles(): void
    {
        $fassg = User::factory()->create(['role' => UserRole::Fassg]);
        $sponsorUser = User::factory()->create([
            'name' => 'Legacy Sponsor',
            'role' => UserRole::Sponsor,
        ]);

        $this->actingAs($fassg)
            ->get(route('fassg.programs.create'))
            ->assertOk()
            ->assertSee('Legacy Sponsor');

        $this->assertDatabaseHas('sponsors', ['user_id' => $sponsorUser->id]);
    }

    public function test_student_can_save_id_and_sync_sle_fhe_from_fixed_list(): void
    {
        $student = User::factory()->create(['role' => UserRole::Student]);
        $program = AcademicProgram::factory()->create();

        $this->actingAsStudent($student)->put(route('student.verification.update'), [
            'student_id_number' => '2024-00099',
            'academic_program_id' => $program->program_id,
            'course' => 'Bachelor of Science in Information Technology',
            'year_level' => 3,
            'birthdate' => '2004-06-15',
            'address' => 'Purok 2, Barangay San Isidro, Mati City, Davao Oriental',
            'barangay' => 'San Isidro',
            'is_rural' => '1',
        ])->assertRedirect(route('student.sle-fhe'));

        $this->assertDatabaseHas('student_profiles', [
            'user_id' => $student->id,
            'student_id_number' => '2024-00099',
        ]);
    }

    public function test_unverified_student_cannot_view_or_submit_sponsorship_applications(): void
    {
        $profile = StudentProfile::factory()->create();
        $program = SponsorshipProgram::factory()->create();

        $this->actingAsStudent($profile->user)
            ->get(route('student.programs.index'))
            ->assertOk()
            ->assertDontSee($program->program_name);

        $this->actingAsStudent($profile->user)
            ->get(route('student.applications.create', $program))
            ->assertForbidden();

        $this->actingAsStudent($profile->user)
            ->post(route('student.applications.store'), $this->applicationPayload($program->id))
            ->assertRedirect(route('student.verification.show'))
            ->assertSessionHasErrors('application');
    }

    public function test_student_course_matches_one_of_multiple_target_courses(): void
    {
        Storage::fake('public');

        $academicProgram = AcademicProgram::factory()->create(['name' => 'BSHM']);

        $profile = StudentProfile::factory()->verified()->create([
            'academic_program_id' => $academicProgram->program_id,
            'course' => 'BSHM',
        ]);
        $program = SponsorshipProgram::factory()->create([
            'target_course' => 'BSIT, BSHM',
        ]);

        $this->actingAsStudent($profile->user)
            ->get(route('student.applications.create', $program))
            ->assertOk();

        $this->actingAsStudent($profile->user)
            ->post(route('student.applications.store'), $this->applicationPayload($program->id))
            ->assertRedirect(route('student.applications.index'));

        $this->assertDatabaseHas('applications', [
            'student_profile_id' => $profile->id,
            'sponsorship_program_id' => $program->id,
        ]);
    }

    public function test_application_detail_syncs_approval_from_approved_fixed_list(): void
    {
        $profile = StudentProfile::factory()->verified()->create([
            'active_sponsorship_id' => null,
        ]);
        $program = SponsorshipProgram::factory()->create();
        $application = Application::factory()->create([
            'student_profile_id' => $profile->id,
            'sponsorship_program_id' => $program->id,
            'status' => ApplicationStatus::Pending,
        ]);
        $list = GeneratedBatch::factory()->create([
            'sponsorship_program_id' => $program->id,
            'status' => GeneratedBatchStatus::Approved,
        ]);
        BatchCandidate::factory()->create([
            'generated_batch_id' => $list->id,
            'application_id' => $application->id,
        ]);

        $this->actingAsStudent($profile->user)
            ->get(route('student.applications.show', $application))
            ->assertOk()
            ->assertSee('Approved');

        $this->assertSame(ApplicationStatus::Approved, $application->fresh()->status);
        $this->assertSame($application->id, $profile->fresh()->active_sponsorship_id);
        $this->assertNotNull($application->fresh()->approved_at);
    }

    public function test_pending_application_does_not_block_a_different_program(): void
    {
        Storage::fake('public');

        $profile = StudentProfile::factory()->verified()->create();
        $existingProgram = SponsorshipProgram::factory()->create();
        $newProgram = SponsorshipProgram::factory()->create([
            'target_course' => $profile->course,
            'address_requirement' => 'Rural barangay in Davao Oriental',
        ]);
        $existingApplication = Application::factory()->create([
            'student_profile_id' => $profile->id,
            'sponsorship_program_id' => $existingProgram->id,
            'status' => ApplicationStatus::Pending,
        ]);

        $this->actingAsStudent($profile->user)
            ->post(route('student.applications.store'), $this->applicationPayload($newProgram->id))
            ->assertRedirect(route('student.applications.index'));

        $this->assertDatabaseHas('applications', [
            'student_profile_id' => $profile->id,
            'sponsorship_program_id' => $newProgram->id,
        ]);

        $existingApplication->update(['status' => ApplicationStatus::Expired]);
    }

    public function test_student_cannot_submit_a_second_application_for_the_same_program(): void
    {
        Storage::fake('public');

        $profile = StudentProfile::factory()->verified()->create();
        $program = SponsorshipProgram::factory()->create();
        Application::factory()->create([
            'student_profile_id' => $profile->id,
            'sponsorship_program_id' => $program->id,
            'status' => ApplicationStatus::Pending,
        ]);

        $this->actingAsStudent($profile->user)
            ->post(route('student.applications.store'), $this->applicationPayload($program->id))
            ->assertSessionHasErrors('application');

        $this->assertSame(1, Application::query()->where('student_profile_id', $profile->id)->count());
    }

    public function test_expired_application_does_not_block_reapplication_to_same_program(): void
    {
        Storage::fake('local');

        $profile = StudentProfile::factory()->verified()->create();
        $program = SponsorshipProgram::factory()->create([
            'target_course' => $profile->course,
            'address_requirement' => 'Rural barangay in Davao Oriental',
        ]);
        Application::factory()->create([
            'student_profile_id' => $profile->id,
            'sponsorship_program_id' => $program->id,
            'status' => ApplicationStatus::Expired,
        ]);

        $this->actingAsStudent($profile->user)
            ->post(route('student.applications.store'), $this->applicationPayload($program->id))
            ->assertRedirect(route('student.applications.index'));

        $this->assertSame(2, $profile->applications()->count());
        $this->assertTrue($profile->applications()->where('status', ApplicationStatus::Pending)->exists());
        $this->assertTrue($profile->applications()->where('status', ApplicationStatus::Expired)->exists());
    }

    public function test_student_cannot_apply_with_an_active_sponsorship(): void
    {
        Storage::fake('local');

        $profile = StudentProfile::factory()->verified()->create();
        $program = SponsorshipProgram::factory()->create(['min_gpa' => 2.50]);

        $active = Application::factory()->create([
            'student_profile_id' => $profile->id,
            'status' => ApplicationStatus::Ongoing,
        ]);
        $profile->update(['active_sponsorship_id' => $active->id]);

        $this->actingAsStudent($profile->user)
            ->post(route('student.applications.store'), $this->applicationPayload($program->id))
            ->assertSessionHasErrors('application');

        $this->assertSame(1, $profile->applications()->count());
    }

    public function test_student_can_submit_application_with_required_documents(): void
    {
        Storage::fake('local');

        $profile = StudentProfile::factory()->verified()->create();
        $program = SponsorshipProgram::factory()->create([
            'min_gpa' => 2.50,
            'target_course' => $profile->course,
            'address_requirement' => 'Rural barangay in Davao Oriental',
        ]);

        $this->actingAsStudent($profile->user)
            ->post(route('student.applications.store'), $this->applicationPayload($program->id))
            ->assertRedirect();

        $application = Application::query()->first();

        $this->assertNotNull($application);
        $this->assertSame(ApplicationStatus::Pending, $application->status);
        $this->assertSame(3, $application->documents()->count());
        $this->assertTrue(
            $application->documents->pluck('document_type')->contains(DocumentType::CertificateOfGrades),
        );
    }

    public function test_student_can_submit_application_with_new_document_field_names(): void
    {
        Storage::fake('public');

        $profile = StudentProfile::factory()->verified()->create();
        $program = SponsorshipProgram::factory()->create([
            'min_gpa' => 2.50,
            'target_course' => $profile->course,
            'address_requirement' => 'Rural barangay in Davao Oriental',
        ]);

        $this->actingAsStudent($profile->user)
            ->post(route('student.applications.store'), [
                'sponsorship_program_id' => $program->id,
                'current_gpa' => '1.75',
                'current_address' => 'Purok 2, Barangay San Isidro, Mati City, Davao Oriental',
                'is_rural_submitted' => '1',
                'grade_slip' => UploadedFile::fake()->create('grade-slip.pdf', 100, 'application/pdf'),
                'proof_of_residence' => UploadedFile::fake()->create('residence.pdf', 100, 'application/pdf'),
                'barangay_certification' => UploadedFile::fake()->create('barangay.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect(route('student.applications.index'))
            ->assertSessionHas('success', 'Application submitted successfully!');

        $this->assertDatabaseHas('applications', [
            'sponsorship_program_id' => $program->id,
            'status' => ApplicationStatus::Pending->value,
        ]);
        $this->assertSame(3, Application::query()->first()->documents()->count());
    }

    public function test_fassg_can_create_open_and_close_programs(): void
    {
        $fassg = User::factory()->create(['role' => UserRole::Fassg]);
        $sponsor = Sponsor::factory()->create();

        $this->actingAs($fassg)->post(route('fassg.programs.store'), [
            'sponsor_id' => $sponsor->id,
            'program_name' => 'New Rural Grant',
            'category' => ProgramCategory::Group->value,
            'available_slots' => 12,
            'end_date' => now()->addMonth()->toDateString(),
            'min_gpa' => 2.50,
            'target_course' => null,
            'address_requirement' => 'Rural barangay',
        ])->assertRedirect(route('fassg.programs.index'));

        $program = SponsorshipProgram::query()->where('program_name', 'New Rural Grant')->first();
        $this->assertSame(ProgramStatus::Open, $program->status);
        $this->assertStringStartsWith(
            now()->addMonth()->toDateString(),
            (string) SponsorshipProgram::query()->whereKey($program->id)->value('end_date'),
        );

        $this->actingAs($fassg)
            ->patch(route('fassg.programs.close', $program))
            ->assertRedirect();

        $this->assertSame(ProgramStatus::Closed, $program->fresh()->status);
    }

    public function test_fassg_programs_page_filters_by_status(): void
    {
        $fassg = User::factory()->create(['role' => UserRole::Fassg]);
        SponsorshipProgram::factory()->create(['program_name' => 'Open Grant', 'status' => ProgramStatus::Open]);
        SponsorshipProgram::factory()->create(['program_name' => 'Closed Grant', 'status' => ProgramStatus::Closed]);
        SponsorshipProgram::factory()->create(['program_name' => 'Expired Grant', 'status' => ProgramStatus::Expired]);

        $this->actingAs($fassg)
            ->get(route('fassg.programs.index', ['status' => ProgramStatus::Open->value]))
            ->assertOk()
            ->assertSee('Open Grant')
            ->assertDontSee('Closed Grant')
            ->assertDontSee('Expired Grant');

        $this->actingAs($fassg)
            ->get(route('fassg.programs.index', ['status' => ProgramStatus::Expired->value]))
            ->assertOk()
            ->assertSee('Expired Grant')
            ->assertDontSee('Open Grant')
            ->assertDontSee('Closed Grant');

        $this->actingAs($fassg)
            ->get(route('fassg.programs.index', ['status' => ProgramStatus::Closed->value]))
            ->assertOk()
            ->assertSee('Closed Grant')
            ->assertSee('Expire Program &amp; Release Beneficiaries', false);
    }

    public function test_zero_slot_program_is_displayed_as_closed(): void
    {
        $fassg = User::factory()->create(['role' => UserRole::Fassg]);
        $program = SponsorshipProgram::factory()->create([
            'program_name' => 'Limbert Grant',
            'status' => ProgramStatus::Open,
            'available_slots' => 0,
            'end_date' => now()->addMonth(),
        ]);

        $this->actingAs($fassg)
            ->get(route('fassg.programs.index'))
            ->assertOk()
            ->assertSee('Limbert Grant')
            ->assertSee('Closed');

        $this->assertSame(ProgramStatus::Open, $program->fresh()->status);
    }

    public function test_manual_status_is_preserved_before_future_end_date(): void
    {
        $closedProgram = SponsorshipProgram::factory()->create([
            'status' => ProgramStatus::Closed,
            'available_slots' => 5,
            'end_date' => now()->addMonth(),
        ]);
        $openProgram = SponsorshipProgram::factory()->create([
            'status' => ProgramStatus::Open,
            'available_slots' => 5,
            'end_date' => now()->addMonth(),
        ]);

        $this->assertSame(ProgramStatus::Closed, $closedProgram->effective_status);
        $this->assertSame(ProgramStatus::Open, $openProgram->effective_status);
    }

    public function test_fassg_programs_page_expires_past_end_date_programs(): void
    {
        $fassg = User::factory()->create(['role' => UserRole::Fassg]);
        $profile = StudentProfile::factory()->create();
        $program = SponsorshipProgram::factory()->create([
            'program_name' => 'Past End Date Grant',
            'status' => ProgramStatus::Open,
            'end_date' => now()->subDay(),
        ]);
        $application = Application::factory()->create([
            'student_profile_id' => $profile->id,
            'sponsorship_program_id' => $program->id,
            'status' => ApplicationStatus::Approved,
        ]);
        $profile->update(['active_sponsorship_id' => $application->id]);

        $this->actingAs($fassg)
            ->get(route('fassg.programs.index'))
            ->assertOk()
            ->assertSee('Past End Date Grant');

        $this->assertSame(ProgramStatus::Expired, $program->fresh()->status);
        $this->assertSame(ApplicationStatus::Expired, $application->fresh()->status);
        $this->assertNull($profile->fresh()->active_sponsorship_id);

        $this->actingAs($fassg)
            ->get(route('fassg.programs.index', ['status' => ProgramStatus::Expired->value]))
            ->assertOk()
            ->assertSee('Past End Date Grant');
    }

    public function test_fassg_can_update_program_status_and_end_date(): void
    {
        $fassg = User::factory()->create(['role' => UserRole::Fassg]);
        $program = SponsorshipProgram::factory()->create();

        $this->actingAs($fassg)
            ->put(route('fassg.programs.update', $program), [
                'sponsor_id' => $program->sponsor_id,
                'program_name' => $program->program_name,
                'category' => $program->category->value,
                'available_slots' => $program->available_slots,
                'min_gpa' => $program->min_gpa,
                'target_course' => $program->target_course,
                'address_requirement' => $program->address_requirement,
                'status' => ProgramStatus::Closed->value,
                'end_date' => now()->addWeek()->toDateString(),
            ])
            ->assertRedirect(route('fassg.programs.index'));

        $updated = $program->fresh();
        $this->assertSame(ProgramStatus::Closed, $updated->status);
        $this->assertSame(now()->addWeek()->toDateString(), $updated->end_date?->toDateString());
    }

    public function test_fassg_can_manually_expire_a_program(): void
    {
        $fassg = User::factory()->create(['role' => UserRole::Fassg]);
        $profile = StudentProfile::factory()->create();
        $program = SponsorshipProgram::factory()->create(['status' => ProgramStatus::Open]);
        $application = Application::factory()->create([
            'student_profile_id' => $profile->id,
            'sponsorship_program_id' => $program->id,
            'status' => ApplicationStatus::Ongoing,
        ]);
        $profile->update(['active_sponsorship_id' => $application->id]);

        $this->actingAs($fassg)
            ->patch(route('fassg.programs.expire', $program))
            ->assertRedirect();

        $this->assertSame(ProgramStatus::Expired, $program->fresh()->status);
        $this->assertSame(ApplicationStatus::Expired, $application->fresh()->status);
        $this->assertNull($profile->fresh()->active_sponsorship_id);
    }

    public function test_updating_program_to_expired_cascades_to_approved_applications(): void
    {
        $fassg = User::factory()->create(['role' => UserRole::Fassg]);
        $profile = StudentProfile::factory()->create();
        $program = SponsorshipProgram::factory()->create(['status' => ProgramStatus::Closed]);
        $application = Application::factory()->create([
            'student_profile_id' => $profile->id,
            'sponsorship_program_id' => $program->id,
            'status' => ApplicationStatus::Approved,
        ]);
        $profile->update(['active_sponsorship_id' => $application->id]);

        $this->actingAs($fassg)
            ->put(route('fassg.programs.update', $program), [
                'sponsor_id' => $program->sponsor_id,
                'program_name' => $program->program_name,
                'category' => $program->category->value,
                'available_slots' => $program->available_slots,
                'status' => ProgramStatus::Expired->value,
                'end_date' => null,
                'min_gpa' => $program->min_gpa,
                'target_course' => $program->target_course,
                'address_requirement' => $program->address_requirement,
            ])
            ->assertRedirect(route('fassg.programs.index'));

        $this->assertSame(ProgramStatus::Expired, $program->fresh()->status);
        $this->assertSame(ApplicationStatus::Expired, $application->fresh()->status);
        $this->assertNull($profile->fresh()->active_sponsorship_id);
    }

    public function test_fassg_can_verify_and_reject_applications(): void
    {
        Storage::fake('local');

        $fassg = User::factory()->create(['role' => UserRole::Fassg]);
        $profile = StudentProfile::factory()->create();
        $program = SponsorshipProgram::factory()->create(['min_gpa' => 2.50]);
        $application = Application::factory()->create([
            'student_profile_id' => $profile->id,
            'sponsorship_program_id' => $program->id,
            'gpa_submitted' => 1.75,
            'status' => ApplicationStatus::Pending,
        ]);

        foreach (DocumentType::requiredForApplication() as $type) {
            ApplicationDocument::query()->create([
                'application_id' => $application->id,
                'document_type' => $type,
                'file_path' => "application-documents/{$application->id}/{$type->value}.pdf",
                'file_name' => $type->value.'.pdf',
            ]);
        }

        $this->actingAs($fassg)->patch(route('fassg.applications.verify', $application), [
            'grades_verified' => '1',
            'address_verified' => '1',
        ])->assertRedirect();

        $this->assertSame(ApplicationStatus::Verified, $application->fresh()->status);
        $this->assertNotNull($application->fresh()->verified_at);

        $this->actingAs($fassg)
            ->patch(route('fassg.applications.reject', $application), ['reason' => 'Incomplete barangay cert'])
            ->assertRedirect();

        $this->assertSame(ApplicationStatus::Rejected, $application->fresh()->status);
    }

    public function test_fassg_can_encode_fixed_list_and_verify_sle_fhe(): void
    {
        $fassg = User::factory()->create(['role' => UserRole::Fassg]);
        $profile = StudentProfile::factory()->verified()->create([
            'student_id_number' => '2024-00111',
        ]);
        $program = SponsorshipProgram::factory()->create();

        $this->actingAs($fassg)->post(route('fassg.fixed-lists.store'), [
            'sponsorship_program_id' => $program->id,
            'batch_name' => 'AY 2026 Batch A',
        ]);

        $list = FixedList::query()->first();

        $this->actingAs($fassg)->post(route('fassg.fixed-lists.items.store', $list), [
            'student_name' => $profile->user->name,
            'student_id_number' => '2024-00111',
            'course' => $profile->course,
            'year_level' => 3,
            'campus' => 'Main Campus (City of Mati)',
        ]);

        $item = FixedListItem::query()->first();

        $this->actingAs($fassg)
            ->patch(route('fassg.fixed-lists.items.verify', [$list, $item]))
            ->assertRedirect();

        $this->assertTrue($item->fresh()->is_sle_fhe_verified);
        $this->assertSame(FixedListItemStatus::Verified, $item->fresh()->status);
        $this->assertTrue($profile->fresh()->sleFheVerification()->exists());
    }

    public function test_fassg_cannot_verify_fixed_list_item_for_unverified_student(): void
    {
        $fassg = User::factory()->create(['role' => UserRole::Fassg]);
        $profile = StudentProfile::factory()->create([
            'student_id_number' => '2024-00222',
        ]);
        $list = FixedList::factory()->create();
        $item = FixedListItem::factory()->create([
            'fixed_list_id' => $list->id,
            'student_id_number' => $profile->student_id_number,
            'is_sle_fhe_verified' => false,
            'status' => FixedListItemStatus::Pending,
        ]);

        $this->actingAs($fassg)
            ->patch(route('fassg.fixed-lists.items.verify', [$list, $item]))
            ->assertSessionHasErrors('verify');

        $this->assertFalse($item->fresh()->is_sle_fhe_verified);
    }

    public function test_fassg_can_create_fixed_list_with_csv_rows(): void
    {
        $fassg = User::factory()->create(['role' => UserRole::Fassg]);
        $program = SponsorshipProgram::factory()->create();
        $csv = UploadedFile::fake()->createWithContent(
            'students.csv',
            "student_name,student_id_number,course,year_level\nJane Doe,2024-00999,BSIT,2\nJohn Smith,2024-01000,BSHM,3\n",
        );

        $this->actingAs($fassg)
            ->post(route('fassg.fixed-lists.store'), [
                'sponsorship_program_id' => $program->id,
                'batch_name' => 'CSV Batch',
                'file' => $csv,
            ])
            ->assertRedirect();

        $list = FixedList::query()->where('batch_name', 'CSV Batch')->firstOrFail();

        $this->assertSame(2, $list->items()->count());
        $this->assertSame(2, $list->fresh()->total_names);
        $this->assertDatabaseHas('fixed_list_items', [
            'fixed_list_id' => $list->id,
            'student_id_number' => '2024-00999',
            'student_name' => 'Jane Doe',
        ]);
    }

    public function test_fassg_can_rename_and_delete_a_draft_fixed_list(): void
    {
        $fassg = User::factory()->create(['role' => UserRole::Fassg]);
        $program = SponsorshipProgram::factory()->create();
        $list = FixedList::factory()->create([
            'sponsorship_program_id' => $program->id,
            'uploaded_by_fassg_id' => $fassg->id,
            'status' => FixedListStatus::Draft,
        ]);

        $this->actingAs($fassg)
            ->get(route('fassg.fixed-lists.edit', $list))
            ->assertOk()
            ->assertSee('Rename Fixed List');

        $this->actingAs($fassg)
            ->put(route('fassg.fixed-lists.update', $list), ['batch_name' => 'Renamed Batch'])
            ->assertRedirect(route('fassg.fixed-lists.show', $list));

        $this->assertSame('Renamed Batch', $list->fresh()->batch_name);

        $this->actingAs($fassg)
            ->delete(route('fassg.fixed-lists.destroy', $list))
            ->assertRedirect(route('fassg.fixed-lists.index'));

        $this->assertSoftDeleted('fixed_lists', ['id' => $list->id]);
    }

    public function test_submitted_fixed_list_cannot_be_renamed_or_deleted(): void
    {
        $fassg = User::factory()->create(['role' => UserRole::Fassg]);
        $list = FixedList::factory()->create([
            'uploaded_by_fassg_id' => $fassg->id,
            'status' => FixedListStatus::Submitted,
        ]);

        $this->actingAs($fassg)
            ->put(route('fassg.fixed-lists.update', $list), ['batch_name' => 'Should Not Change'])
            ->assertForbidden();

        $this->actingAs($fassg)
            ->delete(route('fassg.fixed-lists.destroy', $list))
            ->assertForbidden();

        $this->assertSame($list->batch_name, $list->fresh()->batch_name);
    }

    public function test_fassg_can_set_eligible_academic_programs_on_create_and_update(): void
    {
        $fassg = User::factory()->create(['role' => UserRole::Fassg]);
        $sponsor = Sponsor::factory()->create();

        $it = AcademicProgram::factory()->create(['code' => 'BSIT', 'name' => 'BS Information Technology']);
        $cs = AcademicProgram::factory()->create(['code' => 'BSCS', 'name' => 'BS Computer Science']);

        $this->actingAs($fassg)->post(route('fassg.programs.store'), [
            'sponsor_id' => $sponsor->id,
            'program_name' => 'Tech Scholars Grant',
            'category' => ProgramCategory::Group->value,
            'available_slots' => 10,
            'target_course' => null,
            'academic_program_ids' => [$it->program_id, $cs->program_id],
        ])->assertRedirect(route('fassg.programs.index'));

        $program = SponsorshipProgram::query()->where('program_name', 'Tech Scholars Grant')->firstOrFail();

        $this->assertSame(
            [$it->program_id, $cs->program_id],
            $program->academicPrograms()->orderBy('program_id')->pluck('academic_programs.program_id')->all(),
        );
        $this->assertDatabaseHas('program_academic_program', [
            'sponsorship_program_id' => $program->id,
            'academic_program_id' => $it->program_id,
        ]);

        $this->actingAs($fassg)->put(route('fassg.programs.update', $program), [
            'sponsor_id' => $sponsor->id,
            'program_name' => 'Tech Scholars Grant',
            'category' => ProgramCategory::Group->value,
            'available_slots' => 10,
            'status' => ProgramStatus::Open->value,
            'academic_program_ids' => [$cs->program_id],
        ])->assertRedirect(route('fassg.programs.index'));

        $program->refresh();
        $this->assertSame(
            [$cs->program_id],
            $program->academicPrograms()->pluck('academic_programs.program_id')->all(),
        );
    }

    public function test_application_queue_ranks_by_gwa_primary_then_submission_date(): void
    {
        $fassg = User::factory()->create(['role' => UserRole::Fassg]);
        $program = SponsorshipProgram::factory()->create(['available_slots' => 5]);

        $studentA = StudentProfile::factory()->verified()->create();
        $studentB = StudentProfile::factory()->verified()->create();
        $studentC = StudentProfile::factory()->verified()->create();

        // Student A: Earlier submission, higher GWA (2.25)
        $appA = Application::factory()->create([
            'student_profile_id' => $studentA->id,
            'sponsorship_program_id' => $program->id,
            'gpa_submitted' => 2.25,
            'status' => ApplicationStatus::Pending,
            'created_at' => now()->subHours(5),
        ]);

        // Student B: Later submission, lower GWA (1.25) - Should be #1
        $appB = Application::factory()->create([
            'student_profile_id' => $studentB->id,
            'sponsorship_program_id' => $program->id,
            'gpa_submitted' => 1.25,
            'status' => ApplicationStatus::Pending,
            'created_at' => now()->subHours(2),
        ]);

        // Student C: Same GWA as Student B (1.25), but even later submission - Should be #2
        $appC = Application::factory()->create([
            'student_profile_id' => $studentC->id,
            'sponsorship_program_id' => $program->id,
            'gpa_submitted' => 1.25,
            'status' => ApplicationStatus::Pending,
            'created_at' => now()->subHours(1),
        ]);

        $response = $this->actingAs($fassg)
            ->get(route('fassg.applications.index', ['program_id' => $program->id]))
            ->assertOk()
            ->assertSee('Ranked by GWA, then Submission Date');

        $apps = $response->viewData('pendingApplications');
        $this->assertSame($appB->id, $apps[0]->id);
        $this->assertSame($appC->id, $apps[1]->id);
        $this->assertSame($appA->id, $apps[2]->id);
    }

    public function test_program_can_save_year_5_and_unchecking_checkboxes_persists_empty_arrays(): void
    {
        $fassg = User::factory()->create(['role' => UserRole::Fassg]);
        $sponsor = Sponsor::factory()->create();

        $this->actingAs($fassg)->post(route('fassg.programs.store'), [
            'sponsor_id' => $sponsor->id,
            'program_name' => 'Engineering 5-Year Grant',
            'category' => ProgramCategory::Individual->value,
            'available_slots' => 10,
            'eligible_year_levels' => ['4', '5'],
            'eligible_campuses' => ['Main Campus (City of Mati)'],
            'required_documents' => ['Report Card / Certificate of Grades'],
        ])->assertRedirect(route('fassg.programs.index'));

        $program = SponsorshipProgram::query()->where('program_name', 'Engineering 5-Year Grant')->firstOrFail();
        $this->assertSame(['4', '5'], $program->eligible_year_levels);
        $this->assertSame(['Main Campus (City of Mati)'], $program->eligible_campuses);

        // Update program without passing checkboxes (all unchecked)
        $this->actingAs($fassg)->put(route('fassg.programs.update', $program), [
            'sponsor_id' => $sponsor->id,
            'program_name' => 'Engineering 5-Year Grant',
            'category' => ProgramCategory::Individual->value,
            'available_slots' => 10,
            'status' => ProgramStatus::Open->value,
            // Omit eligible_year_levels, eligible_campuses, required_documents
        ])->assertRedirect(route('fassg.programs.index'));

        $program->refresh();
        $this->assertSame([], $program->eligible_year_levels);
        $this->assertSame([], $program->eligible_campuses);
        $this->assertSame([], $program->required_documents);
    }

    /**
     * @return array<string, mixed>
     */
    private function applicationPayload(int $programId): array
    {
        return [
            'sponsorship_program_id' => $programId,
            'gpa_submitted' => '1.75',
            'address_submitted' => 'Purok 2, Barangay San Isidro, Mati City, Davao Oriental',
            'is_rural_submitted' => '1',
            'certificate_of_grades' => UploadedFile::fake()->create('cog.pdf', 100, 'application/pdf'),
            'proof_of_residence' => UploadedFile::fake()->create('residence.pdf', 100, 'application/pdf'),
            'barangay_cert' => UploadedFile::fake()->create('barangay.pdf', 100, 'application/pdf'),
        ];
    }
}
