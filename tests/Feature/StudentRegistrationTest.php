<?php

namespace Tests\Feature;

use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_register_without_checking_is_rural_checkbox(): void
    {
        $program = \App\Models\AcademicProgram::factory()->create(['name' => 'Computer Science']);

        $response = $this->post(route('register.store'), [
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'email' => 'juan.delacruz@dorsu.edu.ph',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'student_id_number' => '2024-0006',
            'academic_program_id' => $program->program_id,
            'year_level' => 2,
            'birthdate' => '2000-01-15',
            'barangay' => 'Saganganan',
            'address' => '123 Main Street, City',
            // Intentionally omit 'is_rural' to test checkbox default
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status', 'Registration successful! Please sign in with your credentials.');

        $user = User::query()->where('email', 'juan.delacruz@dorsu.edu.ph')->firstOrFail();
        $profile = StudentProfile::query()->where('user_id', $user->id)->firstOrFail();

        $this->assertFalse($profile->is_rural, 'is_rural should default to false when checkbox is not checked');
        $this->assertTrue($user->isStudent());
    }

    public function test_student_can_register_with_is_rural_checkbox_checked(): void
    {
        $program = \App\Models\AcademicProgram::factory()->create(['name' => 'Business Administration']);

        $response = $this->post(route('register.store'), [
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'email' => 'maria.santos@dorsu.edu.ph',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'student_id_number' => '2024-0002',
            'academic_program_id' => $program->program_id,
            'year_level' => 3,
            'birthdate' => '1999-06-20',
            'barangay' => 'Rural Barangay',
            'address' => '456 Provincial Road, Remote Area',
            'is_rural' => '1',
        ]);

        $response->assertRedirect(route('login'));

        $user = User::query()->where('email', 'maria.santos@dorsu.edu.ph')->firstOrFail();
        $profile = StudentProfile::query()->where('user_id', $user->id)->firstOrFail();

        $this->assertTrue($profile->is_rural, 'is_rural should be true when checkbox is checked');
    }

    public function test_student_registration_fails_with_non_dorsu_email(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Invalid User',
            'email' => 'invalid@gmail.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'student_id_number' => '2024-0003',
            'course' => 'Information Technology',
            'year_level' => 1,
            'birthdate' => '2001-03-10',
            'barangay' => 'Test Barangay',
            'address' => '789 Test Street',
            'is_rural' => '0',
        ]);

        $response->assertSessionHasErrors('email');
        $response->assertSessionHasErrorsIn('default', 'email');

        $this->assertNull(User::query()->where('email', 'invalid@gmail.com')->first(), 'User should not be created');
    }

    public function test_student_registration_fails_with_yahoo_email(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Yahoo User',
            'email' => 'user@yahoo.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'student_id_number' => '2024-0004',
            'course' => 'Engineering',
            'year_level' => 2,
            'birthdate' => '2000-11-25',
            'barangay' => 'Test Barangay 2',
            'address' => '321 Another Street',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_student_registration_succeeds_with_mixed_case_dorsu_email(): void
    {
        $program = \App\Models\AcademicProgram::factory()->create(['name' => 'Bachelor of Arts']);

        $response = $this->post(route('register.store'), [
            'first_name' => 'Case',
            'last_name' => 'Test User',
            'email' => 'CaseUser@DORSU.EDU.PH',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'student_id_number' => '2024-0005',
            'academic_program_id' => $program->program_id,
            'year_level' => 4,
            'birthdate' => '1998-08-12',
            'barangay' => 'Central',
            'address' => '654 Central Avenue',
        ]);

        $response->assertRedirect(route('login'));

        $user = User::query()->where('email', 'CaseUser@DORSU.EDU.PH')->firstOrFail();
        $this->assertNotNull($user, 'User with mixed-case DORSU email should be created');
    }

    public function test_student_registration_requires_valid_student_id_format(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Invalid ID User',
            'email' => 'invalid.id@dorsu.edu.ph',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'student_id_number' => 'INVALID-ID',
            'course' => 'Medicine',
            'year_level' => 1,
            'birthdate' => '2001-12-01',
            'barangay' => 'Medical District',
            'address' => '999 Hospital Street',
        ]);

        $response->assertSessionHasErrors('student_id_number');
    }
}
