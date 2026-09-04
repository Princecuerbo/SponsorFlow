<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\SponsorshipProgram;
use App\Models\User;
use Database\Seeders\RoleAndUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_role_routes(): void
    {
        $this->get('/student/dashboard')->assertRedirect(route('login'));
    }

    public function test_each_role_can_open_its_dashboard(): void
    {
        foreach (UserRole::cases() as $role) {
            $user = $this->makeUser($role);

            $session = $role === UserRole::Student
                ? ['data_privacy_consented' => true, 'privacy_consented_session' => true]
                : [];

            $this->actingAs($user)
                ->withSession($session)
                ->get(route($user->homeRoute()))
                ->assertOk();
        }
    }

    public function test_a_student_cannot_access_fassg_routes(): void
    {
        $student = $this->makeUser(UserRole::Student);

        $this->actingAs($student)
            ->get(route('fassg.dashboard'))
            ->assertForbidden();
    }

    public function test_accounting_can_read_dashboards_and_cannot_mutate(): void
    {
        $accounting = $this->makeUser(UserRole::Accounting);

        $this->actingAs($accounting)
            ->get(route('accounting.reports.index'))
            ->assertOk();

        $this->actingAs($accounting)
            ->post('/accounting/reports')
            ->assertStatus(405);
    }

    public function test_inactive_users_are_blocked(): void
    {
        $admin = $this->makeUser(UserRole::Admin, UserStatus::Inactive);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_role_seeder_creates_accounts_and_sample_programs(): void
    {
        $this->seed(RoleAndUserSeeder::class);

        $emails = [
            'student@sponsorflow.test',
            'fassg@sponsorflow.test',
            'sponsor@sponsorflow.test',
            'accounting@sponsorflow.test',
            'admin@sponsorflow.test',
        ];

        foreach ($emails as $email) {
            $this->assertTrue(
                Auth::attempt(['email' => $email, 'password' => 'password123']),
                "Failed to authenticate {$email} with password123.",
            );
            Auth::logout();
        }

        $this->assertSame(5, User::query()->count());
        $this->assertGreaterThanOrEqual(3, SponsorshipProgram::query()->count());
    }

    private function makeUser(UserRole $role, UserStatus $status = UserStatus::Active): User
    {
        return User::factory()->create([
            'role' => $role,
            'status' => $status,
            'privacy_consent_at' => now(),
        ]);
    }
}
