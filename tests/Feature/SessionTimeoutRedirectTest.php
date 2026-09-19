<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SessionTimeoutRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Register test routes that throw TokenMismatchException to test exception handler
        Route::post('/test-csrf-trigger', function () {
            throw new TokenMismatchException('CSRF token mismatch.');
        });

        Route::post('/admin/test-csrf-trigger', function () {
            throw new TokenMismatchException('CSRF token mismatch.');
        });

        Route::post('/fassg/test-csrf-trigger', function () {
            throw new TokenMismatchException('CSRF token mismatch.');
        });

        Route::post('/student/test-csrf-trigger', function () {
            throw new TokenMismatchException('CSRF token mismatch.');
        });
    }

    public function test_token_mismatch_from_admin_referer_redirects_to_admin_login_gate(): void
    {
        $response = $this->withHeader('Referer', url('/admin/dashboard'))
            ->post('/test-csrf-trigger');

        $response->assertRedirect(route('admin.login', ['session_expired' => 1]));
        $response->assertSessionHas('warning', 'Your session has expired due to inactivity. Please sign in again.');
    }

    public function test_token_mismatch_from_admin_path_redirects_to_admin_login_gate(): void
    {
        $response = $this->post('/admin/test-csrf-trigger');

        $response->assertRedirect(route('admin.login', ['session_expired' => 1]));
        $response->assertSessionHas('warning', 'Your session has expired due to inactivity. Please sign in again.');
    }

    public function test_token_mismatch_from_staff_referer_redirects_to_staff_login_gate(): void
    {
        foreach (['/fassg/dashboard', '/sponsor/dashboard', '/accounting/dashboard'] as $path) {
            $response = $this->withHeader('Referer', url($path))
                ->post('/test-csrf-trigger');

            $response->assertRedirect(route('staff.login', ['session_expired' => 1]));
            $response->assertSessionHas('warning', 'Your session has expired due to inactivity. Please sign in again.');
        }
    }

    public function test_token_mismatch_from_staff_path_redirects_to_staff_login_gate(): void
    {
        $response = $this->post('/fassg/test-csrf-trigger');

        $response->assertRedirect(route('staff.login', ['session_expired' => 1]));
        $response->assertSessionHas('warning', 'Your session has expired due to inactivity. Please sign in again.');
    }

    public function test_token_mismatch_from_student_referer_redirects_to_student_login(): void
    {
        $response = $this->withHeader('Referer', url('/student/dashboard'))
            ->post('/test-csrf-trigger');

        $response->assertRedirect(route('login', ['session_expired' => 1]));
        $response->assertSessionHas('warning', 'Your session has expired due to inactivity. Please sign in again.');
    }

    public function test_token_mismatch_from_student_path_redirects_to_student_login(): void
    {
        $response = $this->post('/student/test-csrf-trigger');

        $response->assertRedirect(route('login', ['session_expired' => 1]));
        $response->assertSessionHas('warning', 'Your session has expired due to inactivity. Please sign in again.');
    }

    public function test_token_mismatch_for_json_request_returns_419_with_message(): void
    {
        $response = $this->withHeader('Referer', url('/admin/users'))
            ->postJson('/test-csrf-trigger');

        $response->assertStatus(419);
        $response->assertJson([
            'message' => 'Your session has expired due to inactivity. Please sign in again.',
            'redirect' => route('admin.login', ['session_expired' => 1]),
        ]);
    }

    public function test_authenticated_user_layout_renders_correct_login_gate_data_attributes(): void
    {
        // Admin
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $response = $this->actingAs($admin)->get(route('admin.dashboard'));
        $response->assertOk();
        $response->assertSee('data-login-gate="' . route('admin.login') . '"', false);
        $response->assertSee('id="idle-logout-btn"', false);

        // Staff (FASSG)
        $fassg = User::factory()->create(['role' => UserRole::Fassg]);
        $response = $this->actingAs($fassg)->get(route('fassg.dashboard'));
        $response->assertOk();
        $response->assertSee('data-login-gate="' . route('staff.login') . '"', false);
        $response->assertSee('id="idle-logout-btn"', false);

        // Student
        $student = User::factory()->create(['role' => UserRole::Student]);
        $response = $this->actingAs($student)->get(route('student.dashboard'));
        $response->assertOk();
        $response->assertSee('data-login-gate="' . route('login') . '"', false);
        $response->assertSee('id="idle-logout-btn"', false);
    }

    public function test_login_pages_display_expiration_warning(): void
    {
        // Student login
        $this->get(route('login', ['session_expired' => 1]))
            ->assertOk()
            ->assertSee('Your session has expired due to inactivity. Please sign in again.');

        // Staff login
        $this->get(route('staff.login', ['session_expired' => 1]))
            ->assertOk()
            ->assertSee('Your session has expired due to inactivity. Please sign in again.');

        // Admin login
        $this->get(route('admin.login', ['session_expired' => 1]))
            ->assertOk()
            ->assertSee('Your session has expired due to inactivity. Please sign in again.');
    }
}
