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
        $response = $this->withSession([
            'data_privacy_consented' => true,
            'privacy_consented_session' => true,
        ])->post(route('register.store'), [
            'name' => 'DORSU Student',
            'email' => 'student@dorsu.edu.ph',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'student_id_number' => '2026-00001',
            'course' => 'BS Information Technology',
            'year_level' => 2,
            'birthdate' => '2005-01-15',
            'barangay' => 'Central',
            'address' => 'Davao Oriental',
            'is_rural' => 1,
        ]);

        $response->assertRedirect(route('student.verification.show'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'student@dorsu.edu.ph', 'role' => UserRole::Student->value]);
        $this->assertDatabaseHas('student_profiles', ['student_id_number' => '2026-00001', 'is_sle_fhe_verified' => false]);
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

    public function test_student_without_privacy_consent_session_is_redirected_to_dashboard(): void
    {
        $student = User::factory()->create(['role' => UserRole::Student]);

        $this->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertRedirect(route('student.dashboard'));
    }

    public function test_student_with_session_consent_is_sent_to_dashboard_when_visiting_consent_page(): void
    {
        $student = User::factory()->create(['role' => UserRole::Student]);

        $this->actingAs($student)
            ->withSession(['privacy_consented_session' => true])
            ->get(route('student.dashboard'))
            ->assertRedirect(route('student.dashboard'));
    }

    public function test_student_can_accept_privacy_consent_for_current_session(): void
    {
        $student = User::factory()->create(['role' => UserRole::Student]);

        $this->actingAs($student)
            ->post(route('privacy.accept'), ['agree' => '1'])
            ->assertRedirect(route('student.dashboard'))
            ->assertSessionHas('success', 'Data privacy terms accepted for this session.');

        $this->assertTrue(session()->get('privacy_consented_session'));
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
