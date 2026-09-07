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
            'gender' => 'Male',
            'email' => 'juan.delacruz@dorsu.edu.ph',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'student_id_number' => '2024-0006',
            'academic_program_id' => $program->program_id,
            'year_level' => 2,
            'birthdate' => '2000-01-15',
            'municipality' => 'Baganga',
            'address' => '123 Main Street, City',
            // Intentionally omit 'is_rural' to test checkbox default
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status', 'Registration successful! Please sign in with your credentials.');

        $user = User::query()->where('email', 'juan.delacruz@dorsu.edu.ph')->firstOrFail();
        $profile = StudentProfile::query()->where('user_id', $user->id)->firstOrFail();

        $this->assertFalse($profile->is_rural, 'is_rural should default to false when checkbox is not checked');
        $this->assertSame('Juan', $profile->first_name);
        $this->assertSame('Dela Cruz', $profile->last_name);
        $this->assertSame('Male', $profile->gender);
        $this->assertSame('Baganga', $profile->municipality);
        $this->assertSame('Baganga', $profile->barangay);
        $this->assertTrue($user->isStudent());
    }

    public function test_student_can_register_with_is_rural_checkbox_checked(): void
    {
        $program = \App\Models\AcademicProgram::factory()->create(['name' => 'Business Administration']);

        $response = $this->post(route('register.store'), [
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'gender' => 'Female',
            'email' => 'maria.santos@dorsu.edu.ph',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'student_id_number' => '2024-0002',
            'academic_program_id' => $program->program_id,
            'year_level' => 3,
            'birthdate' => '1999-06-20',
            'municipality' => 'Caraga',
            'address' => '456 Provincial Road, Remote Area',
            'is_rural' => '1',
        ]);

        $response->assertRedirect(route('login'));

        $user = User::query()->where('email', 'maria.santos@dorsu.edu.ph')->firstOrFail();
        $profile = StudentProfile::query()->where('user_id', $user->id)->firstOrFail();

        $this->assertTrue($profile->is_rural, 'is_rural should be true when checkbox is checked');
        $this->assertSame('Female', $profile->gender);
        $this->assertSame('Caraga', $profile->barangay);
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
            'municipality' => 'Mati City',
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
            'municipality' => 'Mati City',
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
            'gender' => 'Male',
            'email' => 'CaseUser@DORSU.EDU.PH',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'student_id_number' => '2024-0005',
            'academic_program_id' => $program->program_id,
            'year_level' => 4,
            'birthdate' => '1998-08-12',
            'municipality' => 'Mati City',
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
            'municipality' => 'Mati City',
            'address' => '999 Hospital Street',
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
            'municipality' => 'Mati City',
            'address' => '123 Test Street',
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
            'municipality' => 'Mati City',
            'address' => '123 Test Street',
        ]);

        $response->assertSessionHasErrors('gender');
    }

    public function test_student_registration_requires_municipality(): void
    {
        $response = $this->post(route('register.store'), [
            'first_name' => 'Test',
            'last_name' => 'User',
            'gender' => 'Male',
            'email' => 'muni.test@dorsu.edu.ph',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'student_id_number' => '2024-0012',
            'year_level' => 1,
            'birthdate' => '2001-12-01',
            'address' => '123 Test Street',
        ]);

        $response->assertSessionHasErrors('municipality');
    }

    public function test_student_registration_rejects_invalid_municipality(): void
    {
        $response = $this->post(route('register.store'), [
            'first_name' => 'Test',
            'last_name' => 'User',
            'gender' => 'Male',
            'email' => 'muni.test2@dorsu.edu.ph',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'student_id_number' => '2024-0013',
            'year_level' => 1,
            'birthdate' => '2001-12-01',
            'municipality' => 'Davao City',
            'address' => '123 Test Street',
        ]);

        $response->assertSessionHasErrors('municipality');
    }
}

