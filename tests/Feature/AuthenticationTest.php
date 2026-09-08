<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_register_with_a_dorsu_email(): void
    {
        $program = \App\Models\AcademicProgram::factory()->create(['name' => 'BS Information Technology']);

        $response = $this->post(route('register.store'), [
            'first_name' => 'DORSU',
            'last_name' => 'Student',
            'gender' => 'Male',
            'email' => 'student@dorsu.edu.ph',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'student_id_number' => '2026-00001',
            'academic_program_id' => $program->program_id,
            'campus' => 'Main Campus (City of Mati)',
            'contact_number' => '09123456789',
            'year_level' => 2,
            'birthdate' => '2005-01-15',
            'province' => 'Davao Oriental',
            'municipality' => 'Mati City',
            'barangay' => 'Central',
            'home_address' => '123 Rizal St',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertDatabaseHas('users', ['email' => 'student@dorsu.edu.ph', 'role' => UserRole::Student->value]);
        $this->assertDatabaseHas('student_profiles', ['student_id_number' => '2026-00001', 'is_sle_fhe_verified' => false, 'academic_program_id' => $program->program_id]);

        $profile = StudentProfile::query()->where('student_id_number', '2026-00001')->firstOrFail();
        $this->assertSame('Davao Oriental', $profile->province);
        $this->assertSame('Mati City', $profile->municipality);
        $this->assertSame('Central', $profile->barangay);
        $this->assertSame('123 Rizal St', $profile->home_address);
        $this->assertFalse($profile->is_rural, 'Mati City + Central barangay should be classified as urban');
        $this->assertSame('123 Rizal St, Brgy. Central, Mati City, Davao Oriental', $profile->full_address);
    }

    public function test_registration_rejects_non_dorsu_email(): void
    {
        $this->post(route('register.store'), [
            'name' => 'External User',
            'email' => 'student@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'student_id_number' => '2026-00002',
            'course' => 'BS Information Technology',
            'year_level' => 2,
            'birthdate' => '2005-01-15',
            'barangay' => 'Central',
            'address' => 'Davao Oriental',
            'is_rural' => 0,
        ])->assertSessionHasErrors('email');
    }

    public function test_active_users_are_redirected_to_their_role_home(): void
    {
        $user = User::factory()->create(['role' => UserRole::Student, 'status' => UserStatus::Active]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('student.verification.show'));
    }

    public function test_inactive_users_cannot_log_in(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Inactive]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_student_dashboard_renders_without_privacy_consent_session(): void
    {
        $student = User::factory()->create(['role' => UserRole::Student]);

        $this->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertOk();
    }

    public function test_student_can_accept_privacy_consent_to_finish_login(): void
    {
        $student = User::factory()->create(['role' => UserRole::Student]);

        $response = $this->withSession(['pending_user_id' => $student->id])
            ->postJson(route('login.complete'), ['privacy_consent' => true])
            ->assertOk()
            ->assertJsonPath('redirect', route('student.dashboard'));

        $this->assertAuthenticatedAs($student);
        $this->assertNotNull($student->fresh()->privacy_consent_at);
    }

    public function test_student_can_logout_without_privacy_consent(): void
    {
        $student = User::factory()->create(['role' => UserRole::Student]);

        $this->actingAs($student)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
