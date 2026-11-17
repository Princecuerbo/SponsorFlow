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
use App\Models\AuditLog;
use App\Models\FixedList;
use App\Models\FixedListItem;
use App\Models\GeneratedBatch;
use App\Models\SleFheRegistry;
use App\Models\SleFheRejection;
use App\Models\SleFheRequest;
use App\Models\SleFheVerification;
use App\Models\SponsorshipProgram;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class TenStudentWorkflowRegressionTest extends TestCase
{
    use RefreshDatabase;

    private User $fassg;
    private AcademicProgram $bsam;
    private AcademicProgram $bsit;
    private AcademicProgram $bitm;
    private AcademicProgram $bsitech;
    private SponsorshipProgram $program;
    private FixedList $fixedList;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fassg = User::factory()->create([
            'role' => UserRole::Fassg,
            'email' => 'fassg.officer@dorsu.edu.ph',
            'name' => 'FASSG Verification Officer',
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
            'program_name' => 'Provincial Scholarship Program 2026',
            'available_slots' => 10,
            'total_slots' => 10,
        ]);

        $this->fixedList = FixedList::factory()->create([
            'sponsorship_program_id' => $this->program->id,
            'uploaded_by_fassg_id' => $this->fassg->id,
            'status' => FixedListStatus::Draft,
            'batch_name' => 'Sponsor Nominated Batch A',
        ]);
    }

    /** 1. Encode a student with no existing student profile. */
    public function test_01_encode_student_with_no_existing_student_profile(): void
    {
        $response = $this->actingAs($this->fassg)
            ->post(route('fassg.fixed-lists.items.store', $this->fixedList), [
                'student_name' => 'Maria Santos',
                'student_id_number' => '2024-00101',
                'course' => $this->bsit->name,
                'year_level' => 2,
                'campus' => 'Main Campus (City of Mati)',
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('fixed_list_items', [
            'fixed_list_id' => $this->fixedList->id,
            'student_id_number' => '2024-00101',
            'student_name' => 'Maria Santos',
            'year_level' => 2,
            'status' => FixedListItemStatus::Pending->value,
        ]);
    }

    /** 2. Encode a registered student whose profile year level matches the Fixed List year level. */
    public function test_02_encode_registered_student_whose_profile_year_level_matches(): void
    {
        $studentUser = User::factory()->create(['role' => UserRole::Student]);
        $profile = StudentProfile::factory()->create([
            'user_id' => $studentUser->id,
            'student_id_number' => '2024-00102',
            'academic_program_id' => $this->bsit->program_id,
            'course' => $this->bsit->name,
            'year_level' => 3,
        ]);

        $response = $this->actingAs($this->fassg)
            ->post(route('fassg.fixed-lists.items.store', $this->fixedList), [
                'student_name' => $studentUser->name,
                'student_id_number' => '2024-00102',
                'course' => $this->bsit->name,
                'year_level' => 3,
                'campus' => 'Main Campus (City of Mati)',
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('fixed_list_items', [
            'fixed_list_id' => $this->fixedList->id,
            'student_id_number' => '2024-00102',
            'year_level' => 3,
        ]);
    }

    /** 3. Encode a registered student whose profile year level differs from the Fixed List year level. */
    public function test_03_encode_registered_student_whose_profile_year_level_differs(): void
    {
        $studentUser = User::factory()->create(['role' => UserRole::Student]);
        $profile = StudentProfile::factory()->create([
            'user_id' => $studentUser->id,
            'student_id_number' => '2024-00103',
            'academic_program_id' => $this->bsam->program_id,
            'course' => $this->bsam->name,
            'year_level' => 2, // Profile says Year 2
        ]);

        // Sponsor nominated student as Year 4
        $response = $this->actingAs($this->fassg)
            ->post(route('fassg.fixed-lists.items.store', $this->fixedList), [
                'student_name' => $studentUser->name,
                'student_id_number' => '2024-00103',
                'course' => $this->bsam->name,
                'year_level' => 4,
                'campus' => 'Main Campus (City of Mati)',
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('fixed_list_items', [
            'fixed_list_id' => $this->fixedList->id,
            'student_id_number' => '2024-00103',
            'year_level' => 4,
        ]);

        // Profile must remain Year 2
        $this->assertSame(2, $profile->fresh()->year_level);
    }

    /** 4. Encode a student whose institutional registry year level differs from the Fixed List year level. */
    public function test_04_encode_student_whose_registry_year_level_differs(): void
    {
        SleFheRegistry::create([
            'student_id' => '2024-00104',
            'fname' => 'Elena',
            'lname' => 'Reyes',
            'course' => $this->bsit->name,
            'year_level' => 1, // Registry says Year 1
            'campus' => 'Main Campus',
            'is_verified' => true,
        ]);

        // Sponsor nominated student as Year 3
        $response = $this->actingAs($this->fassg)
            ->post(route('fassg.fixed-lists.items.store', $this->fixedList), [
                'student_name' => 'Elena Reyes',
                'student_id_number' => '2024-00104',
                'course' => $this->bsit->name,
                'year_level' => 3,
                'campus' => 'Main Campus (City of Mati)',
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('fixed_list_items', [
            'fixed_list_id' => $this->fixedList->id,
            'student_id_number' => '2024-00104',
            'year_level' => 3,
        ]);

        // Registry record must remain Year 1
        $registry = SleFheRegistry::where('student_id', '2024-00104')->firstOrFail();
        $this->assertEquals(1, (int) $registry->year_level);
    }

    /** 5. Confirm the manually supplied year level is saved correctly. */
    public function test_05_confirm_manually_supplied_year_level_is_saved_correctly(): void
    {
        $this->actingAs($this->fassg)
            ->post(route('fassg.fixed-lists.items.store', $this->fixedList), [
                'student_name' => 'Grace Hopper',
                'student_id_number' => '2024-00105',
                'course' => $this->bsit->name,
                'year_level' => 5,
                'campus' => 'Main Campus (City of Mati)',
            ])
            ->assertSessionHasNoErrors();

        $item = FixedListItem::where('fixed_list_id', $this->fixedList->id)
            ->where('student_id_number', '2024-00105')
            ->firstOrFail();

        $this->assertSame(5, $item->year_level);
    }

    /** 6. Confirm the student's existing profile and institutional registry remain unchanged. */
    public function test_06_confirm_student_existing_profile_and_institutional_registry_remain_unchanged(): void
    {
        $studentUser = User::factory()->create(['role' => UserRole::Student, 'name' => 'Original Profile Name']);
        $profile = StudentProfile::factory()->create([
            'user_id' => $studentUser->id,
            'student_id_number' => '2024-00106',
            'first_name' => 'Original',
            'last_name' => 'Name',
            'academic_program_id' => $this->bsit->program_id,
            'course' => $this->bsit->name,
            'year_level' => 2,
            'campus' => 'Baganga Campus',
        ]);
        $profileBefore = $profile->fresh()->getAttributes();

        $registry = SleFheRegistry::create([
            'student_id' => '2024-00106',
            'fname' => 'RegistryFirst',
            'lname' => 'RegistryLast',
            'course' => $this->bsit->name,
            'year_level' => 1,
            'campus' => 'Baganga Campus',
            'is_verified' => true,
        ]);
        $registryBefore = $registry->fresh()->getAttributes();

        $this->actingAs($this->fassg)
            ->post(route('fassg.fixed-lists.items.store', $this->fixedList), [
                'student_name' => 'Sponsor Provided Name',
                'student_id_number' => '2024-00106',
                'course' => $this->bsit->name,
                'year_level' => 4,
                'campus' => 'Main Campus (City of Mati)',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame($profileBefore, $profile->fresh()->getAttributes(), 'StudentProfile attributes must be untouched.');
        $this->assertSame($registryBefore, $registry->fresh()->getAttributes(), 'SleFheRegistry attributes must be untouched.');
    }

    /** 7. Import a CSV containing multiple students with different year levels. */
    public function test_07_import_csv_containing_multiple_students_with_different_year_levels(): void
    {
        $csvContent = implode("\n", [
            'student_name,student_id_number,course,year_level,campus',
            'Alice Cruz,2024-00107,Bachelor of Science in Information Technology,1,Main Campus (City of Mati)',
            'Bob Diaz,2024-00108,Bachelor of Science in Agribusiness Management,3,Main Campus (City of Mati)',
            'Charlie Flores,2024-00109,Bachelor in Industrial Technology Management,5,Main Campus (City of Mati)',
        ]);

        $file = UploadedFile::fake()->createWithContent('fixed_list.csv', $csvContent);

        $response = $this->actingAs($this->fassg)
            ->post(route('fassg.fixed-lists.import', $this->fixedList), [
                'file' => $file,
            ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('fixed_list_items', [
            'student_id_number' => '2024-00107',
            'year_level' => 1,
        ]);
        $this->assertDatabaseHas('fixed_list_items', [
            'student_id_number' => '2024-00108',
            'year_level' => 3,
        ]);
        $this->assertDatabaseHas('fixed_list_items', [
            'student_id_number' => '2024-00109',
            'year_level' => 5,
        ]);
    }

    /** 8. Confirm that invalid year-level values are still rejected. */
    public function test_08_invalid_year_levels_are_still_rejected(): void
    {
        foreach ([0, 6, -1, 99] as $invalidYear) {
            $response = $this->actingAs($this->fassg)
                ->post(route('fassg.fixed-lists.items.store', $this->fixedList), [
                    'student_name' => 'Invalid Year Student',
                    'student_id_number' => '2024-00110',
                    'course' => $this->bsit->name,
                    'year_level' => $invalidYear,
                    'campus' => 'Main Campus (City of Mati)',
                ]);

            $response->assertSessionHasErrors('year_level');
        }
    }

    /** 9. Confirm duplicate identity and conflicting source checks remain enforced. */
    public function test_09_duplicate_identity_and_conflicting_source_checks_remain_enforced(): void
    {
        $studentUser = User::factory()->create(['role' => UserRole::Student]);
        StudentProfile::factory()->create([
            'user_id' => $studentUser->id,
            'student_id_number' => '2024-00111',
            'academic_program_id' => $this->bsit->program_id,
            'course' => $this->bsit->name,
            'year_level' => 2,
        ]);

        // Attempting to encode student with a different unreconciled course (BSAM vs BSIT profile)
        $response = $this->actingAs($this->fassg)
            ->post(route('fassg.fixed-lists.items.store', $this->fixedList), [
                'student_name' => $studentUser->name,
                'student_id_number' => '2024-00111',
                'course' => $this->bsam->name, // Mismatch with registered student profile
                'year_level' => 2,
                'campus' => 'Main Campus (City of Mati)',
            ]);

        $response->assertSessionHasErrors('course');
        $this->assertStringContainsString('Academic Program cannot be reconciled', session('errors')->first('course'));
    }

    /** 10. Confirm Fixed List finalization does not approve candidates or consume sponsorship slots. */
    public function test_10_fixed_list_finalization_does_not_approve_candidates_or_consume_slots(): void
    {
        $initialSlots = $this->program->fresh()->available_slots;

        // Add verified & endorsed candidate to list
        $studentUser = User::factory()->create(['role' => UserRole::Student]);
        $profile = StudentProfile::factory()->create([
            'user_id' => $studentUser->id,
            'student_id_number' => '2024-00112',
            'academic_program_id' => $this->bsit->program_id,
            'course' => $this->bsit->name,
            'year_level' => 3,
        ]);
        SleFheVerification::create([
            'student_profile_id' => $profile->id,
            'verified_address' => 'Mati City',
            'verified_at' => now(),
            'verified_by' => $this->fassg->id,
        ]);

        $item = FixedListItem::create([
            'fixed_list_id' => $this->fixedList->id,
            'student_name' => $studentUser->name,
            'student_id_number' => '2024-00112',
            'course' => $this->bsit->name,
            'year_level' => 3,
            'campus' => 'Main Campus',
            'is_sle_fhe_verified' => true,
            'is_manually_endorsed' => true,
            'endorsed_by_id' => $this->fassg->id,
            'endorsed_at' => now(),
            'status' => FixedListItemStatus::Endorsed,
        ]);

        $response = $this->actingAs($this->fassg)
            ->patch(route('fassg.fixed-lists.finalize', $this->fixedList));

        $response->assertSessionHasNoErrors();
        $this->assertSame(FixedListStatus::Finalized, $this->fixedList->fresh()->status);

        // Crucial business rules: Finalization does not approve sponsorship or decrement slots
        $this->assertSame($initialSlots, $this->program->fresh()->available_slots, 'Available slots must remain unchanged.');
        $this->assertDatabaseMissing('applications', [
            'student_profile_id' => $profile->id,
            'status' => 'Approved',
        ]);
    }

    /** 11. Confirm Generated Batch member verification still enforces actual program qualifications. */
    public function test_11_generated_batch_member_verification_still_enforces_qualifications(): void
    {
        // Program requires Year 3 or 4
        $this->program->update(['eligible_year_levels' => [3, 4]]);

        // Student profile is Year 1
        $studentUser = User::factory()->create(['role' => UserRole::Student]);
        $profile = StudentProfile::factory()->create([
            'user_id' => $studentUser->id,
            'student_id_number' => '2024-00113',
            'academic_program_id' => $this->bsit->program_id,
            'course' => $this->bsit->name,
            'year_level' => 1,
        ]);

        // List item encoded as Year 3
        $item = FixedListItem::create([
            'fixed_list_id' => $this->fixedList->id,
            'student_name' => $studentUser->name,
            'student_id_number' => '2024-00113',
            'course' => $this->bsit->name,
            'year_level' => 3,
            'campus' => 'Main Campus',
            'is_sle_fhe_verified' => false,
            'status' => FixedListItemStatus::Pending,
        ]);

        $item->setRelation('studentProfile', $profile);
        $errors = $item->endorsementErrors($this->program);

        // Verification checks must detect unverified SLE-FHE and year level ineligibility
        $this->assertNotEmpty($errors);
        $this->assertTrue(collect($errors)->contains(fn ($err) => str_contains($err, 'SLE-FHE')));
        $this->assertTrue(collect($errors)->contains(fn ($err) => str_contains($err, 'year level') || str_contains($err, 'Year level')));
    }

    /** 12. Confirm all reported duplicate alerts appear only once. */
    public function test_12_confirm_all_reported_duplicate_alerts_appear_only_once(): void
    {
        // SF-002: Fixed List creation redirect message
        $response = $this->actingAs($this->fassg)
            ->post(route('fassg.fixed-lists.store'), [
                'sponsorship_program_id' => $this->program->id,
                'batch_name' => 'New Test Batch For Flash',
            ]);

        $response->assertRedirect();
        $target = $response->headers->get('Location');
        $viewResponse = $this->actingAs($this->fassg)->get($target);
        $viewResponse->assertOk();

        $content = $viewResponse->getContent();
        $message = 'Fixed list batch created. Encode names or upload a CSV.';
        $this->assertSame(1, substr_count($content, $message), 'SF-002: Fixed list creation message must appear exactly once.');

        // SF-004: SLE-FHE verification request submission
        $studentUser = User::factory()->create(['role' => UserRole::Student]);
        $profile = StudentProfile::factory()->create([
            'user_id' => $studentUser->id,
            'student_id_number' => '2024-00114',
            'academic_program_id' => $this->bsit->program_id,
            'course' => $this->bsit->name,
        ]);

        $sleResponse = $this->actingAs($studentUser)
            ->post(route('student.sle-fhe.request'), [
                'province' => 'Davao Oriental',
                'municipality_city' => 'City of Mati',
                'barangay' => 'Central',
                'street_purok' => 'Purok 1',
            ]);

        $sleResponse->assertRedirect(route('student.sle-fhe'));
        $sleView = $this->actingAs($studentUser)->get(route('student.sle-fhe'));
        $sleContent = $sleView->getContent();
        $sleMessage = 'Your verification request has been submitted for FASSG evidence review.';
        $this->assertSame(1, substr_count($sleContent, $sleMessage), 'SF-004: Verification submit message must appear exactly once.');

        // SF-005: FASSG return for correction flash
        $rejectResponse = $this->actingAs($this->fassg)
            ->post(route('fassg.sle-fhe.reject', $profile), [
                'reason' => 'Address details need documentary proof',
            ]);

        $rejectResponse->assertRedirect();
        $rejectView = $this->actingAs($this->fassg)->get(route('fassg.sle-fhe.index'));
        $rejectContent = $rejectView->getContent();
        $rejectMessage = 'Student verification was returned for correction. Original request evidence was preserved.';
        $this->assertSame(1, substr_count($rejectContent, $rejectMessage), 'SF-005: Return for correction message must appear exactly once.');
    }

    /** 13. Verify student registration redirects correctly to login with single alert inside card. */
    public function test_13_student_registration_redirects_correctly_to_login(): void
    {
        $response = $this->post(route('register'), [
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'email' => 'juan.delacruz@dorsu.edu.ph',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'student_id_number' => '2024-1115',
            'academic_program_id' => $this->bsit->program_id,
            'campus' => 'Main Campus (City of Mati)',
            'contact_number' => '09123456789',
            'year_level' => 2,
            'gender' => 'Male',
            'birthdate' => '2004-05-10',
            'privacy_consent' => '1',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status', 'Registration successful! Please sign in with your credentials.');

        $loginView = $this->get(route('login'));
        $loginContent = $loginView->getContent();
        $regMessage = 'Registration successful! Please sign in with your credentials.';
        $this->assertSame(1, substr_count($loginContent, $regMessage), 'SF-003: Registration message must appear exactly once on login page.');
    }

    /** 14. Verify SLE-FHE verification request and return-for-correction status handling. */
    public function test_14_sle_fhe_verification_request_and_return_for_correction_status_handling(): void
    {
        $studentUser = User::factory()->create(['role' => UserRole::Student]);
        $profile = StudentProfile::factory()->create([
            'user_id' => $studentUser->id,
            'student_id_number' => '2024-00116',
            'academic_program_id' => $this->bsit->program_id,
            'course' => $this->bsit->name,
        ]);

        // Student submits verification
        $this->actingAs($studentUser)->post(route('student.sle-fhe.request'), [
            'province' => 'Davao Oriental',
            'municipality_city' => 'City of Mati',
            'barangay' => 'Central',
            'street_purok' => 'Purok 1',
        ]);

        $this->assertSame('Pending Review', $profile->fresh()->sle_fhe_status);
        $this->assertDatabaseHas('sle_fhe_requests', [
            'student_profile_id' => $profile->id,
            'status' => 'pending',
        ]);

        // FASSG returns for correction
        $this->actingAs($this->fassg)->post(route('fassg.sle-fhe.reject', $profile), [
            'reason' => 'Barangay clearance required for verification',
        ]);

        $this->assertDatabaseHas('sle_fhe_rejections', [
            'student_profile_id' => $profile->id,
            'rejection_reason' => 'Barangay clearance required for verification',
            'rejected_by' => $this->fassg->id,
        ]);

        // Request status transitioned to rejected (preserved evidence)
        $this->assertDatabaseHas('sle_fhe_requests', [
            'student_profile_id' => $profile->id,
            'status' => 'rejected',
        ]);

        // Student profile status evaluates to Rejected
        $this->assertSame('Rejected', $profile->fresh()->sle_fhe_status);

        // Verification queue on /fassg/sle-fhe must not contain this student anymore
        $fassgView = $this->actingAs($this->fassg)->get(route('fassg.sle-fhe.index'));
        $fassgView->assertDontSee('2024-00116');

        // General verification page /fassg/verification must not display student with Pending SLE-FHE
        $generalView = $this->actingAs($this->fassg)->get(route('fassg.verification.index'));
        $generalView->assertDontSee('2024-00116');

        // Student portal /student/sle-fhe unlocks form for resubmission
        $studentView = $this->actingAs($studentUser)->get(route('student.sle-fhe'));
        $studentView->assertOk();
        $studentView->assertSee('Request Verification'); // Submit button is enabled
    }

    /** 15. Verify academic program selection is displayed and submitted correctly. */
    public function test_15_academic_program_selection_is_displayed_and_submitted_correctly(): void
    {
        $view = $this->actingAs($this->fassg)->get(route('fassg.fixed-lists.show', $this->fixedList));
        $content = $view->getContent();

        // 1. Clean placeholder option with disabled attribute
        $this->assertStringContainsString('disabled', $content);
        $this->assertStringContainsString('Select Academic Program', $content);

        // 2. Program options format: "CODE — NAME"
        $this->assertStringContainsString('BSAM — Bachelor of Science in Agribusiness Management', $content);
        $this->assertStringContainsString('BITM — Bachelor in Industrial Technology Management', $content);
        $this->assertStringContainsString('BSITech — Bachelor of Science in Industrial Technology', $content);

        // 3. Submitting program name persists cleanly
        $this->actingAs($this->fassg)
            ->post(route('fassg.fixed-lists.items.store', $this->fixedList), [
                'student_name' => 'Agri Student',
                'student_id_number' => '2024-00117',
                'course' => $this->bsam->name,
                'year_level' => 1,
                'campus' => 'Main Campus (City of Mati)',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('fixed_list_items', [
            'student_id_number' => '2024-00117',
            'course' => $this->bsam->name,
        ]);
    }

    /** 16. Test unauthorized access to Fixed List editing and encoding routes. */
    public function test_16_unauthorized_access_to_fixed_list_routes(): void
    {
        $student = User::factory()->create(['role' => UserRole::Student]);
        $sponsor = User::factory()->create(['role' => UserRole::Sponsor]);

        // Student cannot encode
        $this->actingAs($student)
            ->post(route('fassg.fixed-lists.items.store', $this->fixedList), [
                'student_name' => 'Intruder',
                'student_id_number' => '2024-00118',
                'course' => $this->bsit->name,
                'year_level' => 1,
                'campus' => 'Main Campus',
            ])
            ->assertForbidden();

        // Sponsor cannot encode on FASSG route
        $this->actingAs($sponsor)
            ->post(route('fassg.fixed-lists.items.store', $this->fixedList), [
                'student_name' => 'Intruder',
                'student_id_number' => '2024-00118',
                'course' => $this->bsit->name,
                'year_level' => 1,
                'campus' => 'Main Campus',
            ])
            ->assertForbidden();

        // Guest is blocked with 403 Forbidden
        $this->post(route('fassg.fixed-lists.items.store', $this->fixedList), [
            'student_name' => 'Intruder',
            'student_id_number' => '2024-00118',
            'course' => $this->bsit->name,
            'year_level' => 1,
            'campus' => 'Main Campus',
        ])->assertForbidden();
    }

    /** 17. Test the complete workflow with 10 synthetic student scenarios without changing real records. */
    public function test_17_complete_workflow_with_10_synthetic_student_scenarios(): void
    {
        // Scenario 1: Unregistered candidate on sponsor list -> encoded with sponsor-specified year 2
        $this->actingAs($this->fassg)
            ->post(route('fassg.fixed-lists.items.store', $this->fixedList), [
                'student_name' => 'Synthetic Student 1',
                'student_id_number' => '2024-10001',
                'course' => $this->bsit->name,
                'year_level' => 2,
                'campus' => 'Main Campus (City of Mati)',
            ])->assertSessionHasNoErrors();

        // Scenario 2: Registered student (profile year 1) nominated as year 2 -> encoded; profile unchanged
        $u2 = User::factory()->create(['role' => UserRole::Student]);
        $p2 = StudentProfile::factory()->create([
            'user_id' => $u2->id,
            'student_id_number' => '2024-10002',
            'academic_program_id' => $this->bsam->program_id,
            'course' => $this->bsam->name,
            'year_level' => 1,
        ]);
        $this->actingAs($this->fassg)
            ->post(route('fassg.fixed-lists.items.store', $this->fixedList), [
                'student_name' => $u2->name,
                'student_id_number' => '2024-10002',
                'course' => $this->bsam->name,
                'year_level' => 2,
                'campus' => 'Main Campus (City of Mati)',
            ])->assertSessionHasNoErrors();
        $this->assertSame(1, $p2->fresh()->year_level);

        // Scenario 3: Student in institutional registry as Year 4 nominated as Year 3 -> encoded; registry unchanged
        SleFheRegistry::create([
            'student_id' => '2024-10003',
            'fname' => 'Synthetic',
            'lname' => 'Three',
            'course' => $this->bitm->name,
            'year_level' => 4,
            'campus' => 'Main Campus',
            'is_verified' => true,
        ]);
        $this->actingAs($this->fassg)
            ->post(route('fassg.fixed-lists.items.store', $this->fixedList), [
                'student_name' => 'Synthetic Three',
                'student_id_number' => '2024-10003',
                'course' => $this->bitm->name,
                'year_level' => 3,
                'campus' => 'Main Campus (City of Mati)',
            ])->assertSessionHasNoErrors();
        $this->assertEquals(4, SleFheRegistry::where('student_id', '2024-10003')->value('year_level'));

        // Scenario 4: Registered student with matching year 3 encoded
        $u4 = User::factory()->create(['role' => UserRole::Student]);
        StudentProfile::factory()->create([
            'user_id' => $u4->id,
            'student_id_number' => '2024-10004',
            'academic_program_id' => $this->bsitech->program_id,
            'course' => $this->bsitech->name,
            'year_level' => 3,
        ]);
        $this->actingAs($this->fassg)
            ->post(route('fassg.fixed-lists.items.store', $this->fixedList), [
                'student_name' => $u4->name,
                'student_id_number' => '2024-10004',
                'course' => $this->bsitech->name,
                'year_level' => 3,
                'campus' => 'Main Campus (City of Mati)',
            ])->assertSessionHasNoErrors();

        // Scenario 5: Registered student with SLE-FHE verified profile encoded
        $u5 = User::factory()->create(['role' => UserRole::Student]);
        $p5 = StudentProfile::factory()->create([
            'user_id' => $u5->id,
            'student_id_number' => '2024-10005',
            'academic_program_id' => $this->bsit->program_id,
            'course' => $this->bsit->name,
            'year_level' => 4,
        ]);
        SleFheVerification::create([
            'student_profile_id' => $p5->id,
            'verified_address' => 'City of Mati',
            'verified_at' => now(),
            'verified_by' => $this->fassg->id,
        ]);
        $this->actingAs($this->fassg)
            ->post(route('fassg.fixed-lists.items.store', $this->fixedList), [
                'student_name' => $u5->name,
                'student_id_number' => '2024-10005',
                'course' => $this->bsit->name,
                'year_level' => 4,
                'campus' => 'Main Campus (City of Mati)',
            ])->assertSessionHasNoErrors();

        // Scenario 6: Student submitting verification request -> FASSG reviews and verifies
        $u6 = User::factory()->create(['role' => UserRole::Student]);
        $p6 = StudentProfile::factory()->create([
            'user_id' => $u6->id,
            'student_id_number' => '2024-10006',
            'academic_program_id' => $this->bsit->program_id,
            'course' => $this->bsit->name,
        ]);
        $this->actingAs($u6)->post(route('student.sle-fhe.request'), [
            'province' => 'Davao Oriental',
            'municipality_city' => 'City of Mati',
            'barangay' => 'Sainz',
            'street_purok' => 'Purok 5',
        ]);
        $req6 = SleFheRequest::where('student_profile_id', $p6->id)->firstOrFail();
        $this->actingAs($this->fassg)->post(route('fassg.sle-fhe.verify', $p6), [
            'verification_request_id' => $req6->id,
            'confirm_identity' => '1',
            'override_registry_mismatch' => '1',
            'override_reason' => 'Official certified certificate of residency presented in office',
            'evidence_reference' => 'DORSU-FASSG-VER-2026-006',
        ])->assertSessionHasNoErrors();
        $this->assertTrue($p6->fresh()->isSleFheVerified());

        // Scenario 7: Student submitting verification -> Returned for correction -> Unlocked -> Resubmitted -> Verified
        $u7 = User::factory()->create(['role' => UserRole::Student]);
        $p7 = StudentProfile::factory()->create([
            'user_id' => $u7->id,
            'student_id_number' => '2024-10007',
            'academic_program_id' => $this->bsam->program_id,
            'course' => $this->bsam->name,
        ]);
        $this->actingAs($u7)->post(route('student.sle-fhe.request'), [
            'province' => 'Davao Oriental',
            'municipality_city' => 'City of Mati',
            'barangay' => 'Central',
            'street_purok' => 'Incomplete Address',
        ]);
        // FASSG returns for correction
        $this->actingAs($this->fassg)->post(route('fassg.sle-fhe.reject', $p7), [
            'reason' => 'Specify exact Purok and street number',
        ]);
        $this->assertSame('Rejected', $p7->fresh()->sle_fhe_status);
        // Student resubmits corrected address
        $this->actingAs($u7)->post(route('student.sle-fhe.request'), [
            'province' => 'Davao Oriental',
            'municipality_city' => 'City of Mati',
            'barangay' => 'Central',
            'street_purok' => 'Purok Mangga, St. Mary',
        ]);
        $this->assertSame('Pending Review', $p7->fresh()->sle_fhe_status);
        $req7 = SleFheRequest::where('student_profile_id', $p7->id)->where('status', 'pending')->firstOrFail();
        $this->actingAs($this->fassg)->post(route('fassg.sle-fhe.verify', $p7), [
            'verification_request_id' => $req7->id,
            'confirm_identity' => '1',
            'override_registry_mismatch' => '1',
            'override_reason' => 'Corrected address matched to official barangay roster',
            'evidence_reference' => 'DORSU-FASSG-VER-2026-007',
        ])->assertSessionHasNoErrors();
        $this->assertTrue($p7->fresh()->isSleFheVerified());

        // Scenarios 8 & 9: Import 2 candidates via CSV
        $csvContent = implode("\n", [
            'student_name,student_id_number,course,year_level,campus',
            'Synthetic Eight,2024-10008,Bachelor in Industrial Technology Management,4,Main Campus (City of Mati)',
            'Synthetic Nine,2024-10009,Bachelor of Science in Industrial Technology,1,Main Campus (City of Mati)',
        ]);
        $file = UploadedFile::fake()->createWithContent('candidates.csv', $csvContent);
        $this->actingAs($this->fassg)->post(route('fassg.fixed-lists.import', $this->fixedList), [
            'file' => $file,
        ])->assertSessionHasNoErrors();

        // Scenario 10: Ineligible candidate check in Fixed List preparation
        $u10 = User::factory()->create(['role' => UserRole::Student]);
        $p10 = StudentProfile::factory()->create([
            'user_id' => $u10->id,
            'student_id_number' => '2024-10010',
            'academic_program_id' => $this->bsit->program_id,
            'course' => $this->bsit->name,
            'year_level' => 2,
        ]);
        $item10 = FixedListItem::create([
            'fixed_list_id' => $this->fixedList->id,
            'student_name' => $u10->name,
            'student_id_number' => '2024-10010',
            'course' => $this->bsit->name,
            'year_level' => 2,
            'campus' => 'Main Campus',
            'is_sle_fhe_verified' => false,
            'status' => FixedListItemStatus::Ineligible,
        ]);
        $item10->setRelation('studentProfile', $p10);
        $errors10 = $item10->endorsementErrors($this->program);
        $this->assertContains('An ineligible candidate cannot be endorsed through ordinary endorsement.', $errors10);

        // Verification that 8 items exist in the fixed list
        $this->assertEquals(8, $this->fixedList->items()->count());
    }

    /** 18. SF-007: Confirm mandatory qualification checkboxes are strictly enforced while optional criteria pass. */
    public function test_18_sf007_preserves_mandatory_confirmations_while_permitting_optional_criteria(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');

        // Part A: Program with mandatory GWA, Address, and COR requirements
        $mandatoryProgram = SponsorshipProgram::factory()->create([
            'min_gpa' => 2.00,
            'target_province' => 'Davao Oriental',
            'required_documents' => ['Certificate of Registration (COR)'],
            'status' => ProgramStatus::Open,
        ]);

        $uA = User::factory()->create(['role' => UserRole::Student]);
        $pA = StudentProfile::factory()->create([
            'user_id' => $uA->id,
            'student_id_number' => '2024-9901',
            'academic_program_id' => $this->bsit->program_id,
            'course' => $this->bsit->name,
            'year_level' => 3,
        ]);
        $pA->sleFheVerification()->create([
            'verified_address' => 'City of Mati, Davao Oriental',
            'verified_by' => $this->fassg->id,
            'verified_at' => now(),
        ]);
        $appA = Application::create([
            'student_profile_id' => $pA->id,
            'sponsorship_program_id' => $mandatoryProgram->id,
            'status' => ApplicationStatus::Pending,
            'gpa_submitted' => 1.75,
            'address_submitted' => 'City of Mati, Davao Oriental',
            'is_rural_submitted' => true,
        ]);
        $corFile = UploadedFile::fake()->create('cor.pdf', 50, 'application/pdf');
        $path = $corFile->store('applications/documents', 'local');
        $appA->documents()->create([
            'document_type' => DocumentType::CertificateOfRegistration->value,
            'file_path' => $path,
            'file_name' => 'cor.pdf',
        ]);

        $batchA = GeneratedBatch::create([
            'sponsorship_program_id' => $mandatoryProgram->id,
            'batch_name' => 'Mandatory Verification Test Batch',
            'created_by_fassg_id' => $this->fassg->id,
            'total_slots' => 1,
            'status' => GeneratedBatchStatus::Saved,
            'submission_version' => 0,
        ]);
        $batchA->items()->create(['application_id' => $appA->id, 'origin_type' => 'ranked_queue']);
        $appA->update(['is_batched' => true, 'batch_id' => $batchA->id]);

        // Attempt verify with ONLY confirm_eligibility: MUST FAIL on confirm_gwa, confirm_address, confirm_cor
        $responseA = $this->actingAs($this->fassg)->patch(
            route('fassg.generated-batches.applications.verify', [$batchA, $appA]),
            ['confirm_eligibility' => 1]
        );
        $responseA->assertSessionHasErrors(['confirm_gwa', 'confirm_address', 'confirm_cor']);

        // Now provide all required confirmations: MUST PASS
        $this->actingAs($this->fassg)->patch(
            route('fassg.generated-batches.applications.verify', [$batchA, $appA]),
            [
                'confirm_eligibility' => 1,
                'confirm_gwa' => 1,
                'confirm_address' => 1,
                'confirm_cor' => 1,
            ]
        )->assertSessionHasNoErrors();
        $this->assertSame(ApplicationStatus::Verified, $appA->fresh()->status);

        // Part B: Program with NO mandatory qualifications (optional criteria)
        $optionalProgram = SponsorshipProgram::factory()->create([
            'min_gpa' => null,
            'target_province' => null,
            'target_municipality' => null,
            'address_requirement' => null,
            'required_documents' => [],
            'status' => ProgramStatus::Open,
        ]);

        $uB = User::factory()->create(['role' => UserRole::Student]);
        $pB = StudentProfile::factory()->create([
            'user_id' => $uB->id,
            'student_id_number' => '2024-9902',
            'academic_program_id' => $this->bsit->program_id,
            'course' => $this->bsit->name,
            'year_level' => 2,
        ]);
        $pB->sleFheVerification()->create([
            'verified_address' => 'City of Mati',
            'verified_by' => $this->fassg->id,
            'verified_at' => now(),
        ]);
        $appB = Application::create([
            'student_profile_id' => $pB->id,
            'sponsorship_program_id' => $optionalProgram->id,
            'status' => ApplicationStatus::Pending,
        ]);

        $batchB = GeneratedBatch::create([
            'sponsorship_program_id' => $optionalProgram->id,
            'batch_name' => 'Optional Criteria Test Batch',
            'created_by_fassg_id' => $this->fassg->id,
            'total_slots' => 1,
            'status' => GeneratedBatchStatus::Saved,
            'submission_version' => 0,
        ]);
        $batchB->items()->create(['application_id' => $appB->id, 'origin_type' => 'ranked_queue']);
        $appB->update(['is_batched' => true, 'batch_id' => $batchB->id]);

        // Attempt verify with ONLY confirm_eligibility: MUST PASS without error
        $responseB = $this->actingAs($this->fassg)->patch(
            route('fassg.generated-batches.applications.verify', [$batchB, $appB]),
            ['confirm_eligibility' => 1]
        );
        $responseB->assertSessionHasNoErrors();
        $this->assertSame(ApplicationStatus::Verified, $appB->fresh()->status);
    }
}
