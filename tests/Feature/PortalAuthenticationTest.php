<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_login_portal_is_available_to_guests(): void
    {
        $this->get(route('login'))->assertOk();
        $this->get(route('staff.login'))->assertOk();
        $this->get(route('admin.login'))->assertOk();
    }

    public function test_student_portal_rejects_staff_credentials(): void
    {
        $staff = User::factory()->create(['role' => UserRole::Fassg]);

        $this->post(route('login.store'), [
            'email' => $staff->email,
            'password' => 'password',
        ])->assertSessionHasErrors([
            'email' => 'Access denied. Staff and Admin users must use their designated portal logins.',
        ]);

        $this->assertGuest();
    }

    public function test_staff_portal_redirects_each_institutional_role(): void
    {
        foreach (
            [
                [UserRole::Fassg, 'fassg.dashboard'],
                [UserRole::Sponsor, 'sponsor.dashboard'],
                [UserRole::Accounting, 'accounting.dashboard'],
            ] as [$role, $destination]
        ) {
            $user = User::factory()->create(['role' => $role]);

            $this->post(route('staff.login.store'), [
                'email' => $user->email,
                'password' => 'password',
            ])->assertRedirect(route($destination));

            $this->post(route('logout'));
        }
    }

    public function test_successful_login_is_recorded_for_each_role(): void
    {
        foreach (
            [
                [UserRole::Student, 'login.store'],
                [UserRole::Fassg, 'staff.login.store'],
                [UserRole::Sponsor, 'staff.login.store'],
                [UserRole::Accounting, 'staff.login.store'],
                [UserRole::Admin, 'admin.login.store'],
            ] as [$role, $route]
        ) {
            $user = User::factory()->create(['role' => $role]);

            $this->withHeader('User-Agent', 'SponsorFlow-Audit-Test')
                ->post(route($route), [
                    'email' => $user->email,
                    'password' => 'password',
                ]);

            $this->assertDatabaseHas('audit_logs', [
                'user_id' => $user->id,
                'role' => $role->value,
                'action' => $role->value . '.user.login',
                'target_module' => 'authentication',
                'user_agent' => 'SponsorFlow-Audit-Test',
                'details' => 'User logged into system: ' . $user->email,
            ]);

            $this->post(route('logout'));
        }
    }

    public function test_admin_portal_rejects_non_admin_credentials(): void
    {
        $staff = User::factory()->create(['role' => UserRole::Sponsor]);

        $this->post(route('admin.login.store'), [
            'email' => $staff->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
