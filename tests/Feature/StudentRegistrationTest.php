<?php

namespace Tests\Feature;

use App\Enums\SleFheStatus;
use App\Models\AcademicProgram;
use App\Models\SleFheRejection;
use App\Models\SleFheRequest;
use App\Models\SleFheVerification;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_register_without_address_and_defaults_to_unverified_sle_fhe_status(): void
    {
        $program = AcademicProgram::factory()->create(['name' => 'Computer Science']);

        $response = $this->post(route('register.store'), [
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'gender' => 'Male',
            'email' => 'juan.delacruz@dorsu.edu.ph',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'student_id_number' => '2024-0006',
            'academic_program_id' => $program->program_id,
            'campus' => 'Baganga Campus',
            'contact_number' => '09123456789',
            'year_level' => 2,
            'birthdate' => '2000-01-15',
            'privacy_consent' => 1,
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status', 'Registration successful! Please sign in with your credentials.');

        $user = User::query()->where('email', 'juan.delacruz@dorsu.edu.ph')->firstOrFail();
        $profile = StudentProfile::query()->where('user_id', $user->id)->firstOrFail();

        $this->assertSame(SleFheStatus::Unverified->value, $profile->sle_fhe_status, 'New registrations must default to Unverified SLE-FHE status');
        $this->assertFalse($profile->is_sle_fhe_verified);
        $this->assertNull($profile->sleFheRequest, 'No SLE-FHE request is created during registration');
        $this->assertNull($profile->sleFheVerification, 'No SLE-FHE verification is created during registration');
        $this->assertCount(0, $profile->sleFheRejections, 'No SLE-FHE rejection is created during registration');
        $this->assertSame('Juan', $profile->first_name);
        $this->assertSame('Dela Cruz', $profile->last_name);
        $this->assertSame('Male', $profile->gender);
        $this->assertNull($profile->province, 'Address should not be captured during registration');
        $this->assertNull($profile->municipality);
        $this->assertNull($profile->barangay);
        $this->assertNull($profile->home_address);
        $this->assertTrue($user->isStudent());
    }

    public function test_student_profile_sle_fhe_status_is_derived_from_dedicated_tables(): void
    {
        $profile = StudentProfile::factory()->create();

        $this->assertSame(SleFheStatus::Unverified->value, $profile->sle_fhe_status, 'No SLE-FHE records means Unverified');

        SleFheRequest::create([
            'student_profile_id' => $profile->id,
            'status' => 'pending',
        ]);
        SleFheRejection::create([
            'student_profile_id' => $profile->id,
        ]);

        $this->assertSame(SleFheStatus::PendingReview->value, $profile->fresh()->sle_fhe_status, 'An open request takes priority over a past rejection');

        SleFheVerification::create([
            'student_profile_id' => $profile->id,
        ]);

        $this->assertSame(SleFheStatus::Verified->value, $profile->fresh()->sle_fhe_status, 'A verification overrides earlier request/rejection records');
    }

    public function test_student_profile_sle_fhe_status_returns_rejected_when_only_rejections_exist(): void
    {
        $profile = StudentProfile::factory()->create();

        SleFheRejection::create([
            'student_profile_id' => $profile->id,
        ]);

        $this->assertSame(SleFheStatus::Rejected->value, $profile->fresh()->sle_fhe_status);
    }

    public function test_student_registration_ignores_address_and_rurality_inputs_on_store(): void
    {
        $program = AcademicProgram::factory()->create(['name' => 'Computer Science']);

        $response = $this->post(route('register.store'), [
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'gender' => 'Female',
            'email' => 'maria.santos@dorsu.edu.ph',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'student_id_number' => '2024-0002',
            'academic_program_id' => $program->program_id,
            'campus' => 'Main Campus (City of Mati)',
            'contact_number' => '09123456789',
            'year_level' => 3,
            'birthdate' => '1999-06-20',
            'province' => 'Davao del Sur',
            'municipality' => 'Davao City',
            'barangay' => 'Buhangin',
            'home_address' => '456 Roxas Ave',
            'privacy_consent' => 1,
        ]);

        $response->assertRedirect(route('login'));

        $user = User::query()->where('email', 'maria.santos@dorsu.edu.ph')->firstOrFail();
        $profile = StudentProfile::query()->where('user_id', $user->id)->firstOrFail();

        $this->assertSame(SleFheStatus::Unverified->value, $profile->sle_fhe_status);
        $this->assertNull($profile->province, 'Address and rurality are no longer captured on registration');
        $this->assertNull($profile->municipality);
        $this->assertNull($profile->barangay);
        $this->assertNull($profile->home_address);
        $this->assertFalse($profile->is_rural);
    }

    public function test_student_registration_fails_with_non_dorsu_email(): void
    {
        $program = AcademicProgram::factory()->create(['name' => 'Information Technology']);

        $response = $this->post(route('register.store'), [
            'name' => 'Invalid User',
            'email' => 'invalid@gmail.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'student_id_number' => '2024-0003',
            'academic_program_id' => $program->program_id,
            'course' => 'Information Technology',
            'year_level' => 1,
            'birthdate' => '2001-03-10',
        ]);

        $response->assertSessionHasErrors('email');
        $response->assertSessionHasErrorsIn('default', 'email');

        $this->assertNull(User::query()->where('email', 'invalid@gmail.com')->first(), 'User should not be created');
    }

    public function test_student_registration_fails_with_yahoo_email(): void
    {
        $program = AcademicProgram::factory()->create(['name' => 'Engineering']);

        $response = $this->post(route('register.store'), [
            'name' => 'Yahoo User',
            'email' => 'user@yahoo.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'student_id_number' => '2024-0004',
            'academic_program_id' => $program->program_id,
            'course' => 'Engineering',
            'year_level' => 2,
            'birthdate' => '2000-11-25',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_student_registration_succeeds_with_mixed_case_dorsu_email(): void
    {
        $program = AcademicProgram::factory()->create(['name' => 'Bachelor of Arts']);

        $response = $this->post(route('register.store'), [
            'first_name' => 'Case',
            'last_name' => 'Test User',
            'gender' => 'Male',
            'email' => 'CaseUser@DORSU.EDU.PH',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'student_id_number' => '2024-0005',
            'academic_program_id' => $program->program_id,
            'campus' => 'Main Campus (City of Mati)',
            'contact_number' => '09123456789',
            'year_level' => 4,
            'birthdate' => '1998-08-12',
            'privacy_consent' => 1,
        ]);

        $response->assertRedirect(route('login'));

        $user = User::query()->where('email', 'CaseUser@DORSU.EDU.PH')->firstOrFail();
        $this->assertNotNull($user, 'User with mixed-case DORSU email should be created');

        $profile = StudentProfile::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame(SleFheStatus::Unverified->value, $profile->sle_fhe_status);
    }

    public function test_student_registration_requires_valid_student_id_format(): void
    {
        $program = AcademicProgram::factory()->create(['name' => 'Medicine']);

        $response = $this->post(route('register.store'), [
            'name' => 'Invalid ID User',
            'email' => 'invalid.id@dorsu.edu.ph',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'student_id_number' => 'INVALID-ID',
            'academic_program_id' => $program->program_id,
            'course' => 'Medicine',
            'year_level' => 1,
            'birthdate' => '2001-12-01',
        ]);

        $response->assertSessionHasErrors('student_id_number');
    }

    public function test_student_registration_requires_gender(): void
    {
        $response = $this->post(route('register.store'), [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'gender.test@dorsu.edu.ph',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'student_id_number' => '2024-0010',
            'year_level' => 1,
            'birthdate' => '2001-12-01',
        ]);

        $response->assertSessionHasErrors('gender');
    }

    public function test_student_registration_rejects_invalid_gender(): void
    {
        $response = $this->post(route('register.store'), [
            'first_name' => 'Test',
            'last_name' => 'User',
            'gender' => 'Other',
            'email' => 'gender.test2@dorsu.edu.ph',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'student_id_number' => '2024-0011',
            'year_level' => 1,
            'birthdate' => '2001-12-01',
        ]);

        $response->assertSessionHasErrors('gender');
    }

    public function test_student_registration_no_longer_requires_address_fields(): void
    {
        $program = AcademicProgram::factory()->create(['name' => 'Computer Science']);

        $response = $this->post(route('register.store'), [
            'first_name' => 'Test',
            'last_name' => 'User',
            'gender' => 'Male',
            'email' => 'no.address@dorsu.edu.ph',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'student_id_number' => '2024-0012',
            'academic_program_id' => $program->program_id,
            'campus' => 'Main Campus (City of Mati)',
            'contact_number' => '09123456789',
            'year_level' => 1,
            'birthdate' => '2001-12-01',
            'privacy_consent' => 1,
        ]);

        $response->assertRedirect(route('login'));

        $profile = StudentProfile::query()->where('student_id_number', '2024-0012')->firstOrFail();
        $this->assertNull($profile->province);
        $this->assertNull($profile->municipality);
        $this->assertNull($profile->barangay);
        $this->assertNull($profile->home_address);
        $this->assertSame(SleFheStatus::Unverified->value, $profile->sle_fhe_status);
    }

    public function test_student_registration_does_not_classify_rurality_at_signup(): void
    {
        $program = AcademicProgram::factory()->create(['name' => 'Computer Science']);

        $response = $this->post(route('register.store'), [
            'first_name' => 'Test',
            'last_name' => 'User',
            'gender' => 'Male',
            'email' => 'no.rurality@dorsu.edu.ph',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'student_id_number' => '2024-0013',
            'academic_program_id' => $program->program_id,
            'campus' => 'Main Campus (City of Mati)',
            'contact_number' => '09123456789',
            'year_level' => 1,
            'birthdate' => '2001-12-01',
            'privacy_consent' => 1,
        ]);

        $response->assertRedirect(route('login'));

        $profile = StudentProfile::query()->where('student_id_number', '2024-0013')->firstOrFail();
        $this->assertFalse($profile->is_rural, 'Rurality is no longer computed at registration');
        $this->assertSame(SleFheStatus::Unverified->value, $profile->sle_fhe_status);
    }
}
