<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentPasswordRecoveryTest extends TestCase
{
    use RefreshDatabase;

    private const BIRTHDATE = '2004-06-15';

    private const OLD_PASSWORD = 'OldPassword123!';

    private const NEW_PASSWORD = 'NewPassword456!';

    private const GENERIC_ERROR = "We couldn't verify the information provided. Please check your details or contact the authorized university office.";

    private const SESSION_ERROR = 'Your password reset session is invalid or has expired. Please verify your details again.';

    private const RECOVERY_BAG = 'studentRecovery';

    private const RECOVERY_MESSAGE_KEY = 'recovery';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.student_password_recovery_enabled', true);
    }

    public function test_forgot_password_link_is_present_on_student_sign_in(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee(route('student.password.request'), false);
    }

    public function test_student_email_and_birthdate_match_creates_single_use_authorization(): void
    {
        [$student] = $this->student();

        $this->from(route('student.password.request'))->post(route('student.password.verify'), [
            'email' => $student->email,
            'birthdate' => self::BIRTHDATE,
        ])->assertRedirect(route('student.password.reset'));

        $authorization = session('student_password_recovery');
        $this->assertIsArray($authorization);
        $this->assertSame(['user_id', 'token', 'expires_at'], array_keys($authorization));
        $this->assertSame($student->id, $authorization['user_id']);
        $this->assertGreaterThan(now()->getTimestamp(), $authorization['expires_at']);
        $this->assertStringNotContainsString(self::BIRTHDATE, json_encode($authorization));
        $this->assertStringNotContainsString(self::OLD_PASSWORD, json_encode($authorization));

        $this->get(route('student.password.reset'))->assertOk()->assertSee('Create New Password');
    }

    public function test_incorrect_email_is_rejected_with_a_generic_error(): void
    {
        [, $profile] = $this->student();

        $this->from(route('student.password.request'))->post(route('student.password.verify'), [
            'email' => 'missing@dorsu.edu.ph',
            'birthdate' => $profile->birthdate->toDateString(),
        ])->assertRedirect(route('student.password.request'));

        $this->assertRecoveryMessage(self::GENERIC_ERROR);
        $this->assertNull(session('student_password_recovery'));
    }

    public function test_incorrect_birthdate_is_rejected_with_a_generic_error(): void
    {
        [$student] = $this->student();

        $this->from(route('student.password.request'))->post(route('student.password.verify'), [
            'email' => $student->email,
            'birthdate' => '2004-06-16',
        ])->assertRedirect(route('student.password.request'));

        $this->assertRecoveryMessage(self::GENERIC_ERROR);
        $this->assertNull(session('student_password_recovery'));
    }

    public function test_account_from_a_different_role_is_never_eligible(): void
    {
        foreach ([UserRole::Admin, UserRole::Fassg, UserRole::Sponsor, UserRole::Accounting] as $role) {
            $admin = User::factory()->create([
                'role' => $role,
                'status' => UserStatus::Active,
                'password' => Hash::make(self::OLD_PASSWORD),
            ]);
            StudentProfile::factory()->create([
                'user_id' => $admin->id,
                'birthdate' => self::BIRTHDATE,
            ]);

            $this->from(route('student.password.request'))->post(route('student.password.verify'), [
                'email' => $admin->email,
                'birthdate' => self::BIRTHDATE,
            ])->assertRedirect(route('student.password.request'));

            $this->assertRecoveryMessage(self::GENERIC_ERROR);
            $this->assertNull(session('student_password_recovery'));
        }
    }

    public function test_inactive_student_account_is_not_eligible(): void
    {
        [$student] = $this->student(['status' => UserStatus::Inactive]);

        $this->from(route('student.password.request'))->post(route('student.password.verify'), [
            'email' => $student->email,
            'birthdate' => self::BIRTHDATE,
        ])->assertRedirect(route('student.password.request'));

        $this->assertRecoveryMessage(self::GENERIC_ERROR);
        $this->assertNull(session('student_password_recovery'));
    }

    public function test_student_without_a_profile_is_not_eligible(): void
    {
        $student = User::factory()->create([
            'role' => UserRole::Student,
            'status' => UserStatus::Active,
        ]);

        $this->from(route('student.password.request'))->post(route('student.password.verify'), [
            'email' => $student->email,
            'birthdate' => self::BIRTHDATE,
        ])->assertRedirect(route('student.password.request'));

        $this->assertRecoveryMessage(self::GENERIC_ERROR);
        $this->assertNull(session('student_password_recovery'));
    }

    public function test_student_without_a_birthdate_is_not_eligible(): void
    {
        $student = User::factory()->create([
            'role' => UserRole::Student,
            'status' => UserStatus::Active,
        ]);
        StudentProfile::factory()->create([
            'user_id' => $student->id,
            'birthdate' => null,
        ]);

        $this->from(route('student.password.request'))->post(route('student.password.verify'), [
            'email' => $student->email,
            'birthdate' => self::BIRTHDATE,
        ])->assertRedirect(route('student.password.request'));

        $this->assertRecoveryMessage(self::GENERIC_ERROR);
        $this->assertNull(session('student_password_recovery'));
    }

    public function test_suspended_student_account_is_not_eligible(): void
    {
        [$student] = $this->student(['status' => UserStatus::Suspended]);

        $this->from(route('student.password.request'))->post(route('student.password.verify'), [
            'email' => $student->email,
            'birthdate' => self::BIRTHDATE,
        ])->assertRedirect(route('student.password.request'));

        $this->assertRecoveryMessage(self::GENERIC_ERROR);
        $this->assertNull(session('student_password_recovery'));
    }

    public function test_email_and_birthdate_from_different_accounts_are_not_matched(): void
    {
        [$student] = $this->student();

        $otherStudent = User::factory()->create([
            'role' => UserRole::Student,
            'status' => UserStatus::Active,
        ]);
        StudentProfile::factory()->create([
            'user_id' => $otherStudent->id,
            'birthdate' => '1999-12-31',
        ]);

        $this->from(route('student.password.request'))->post(route('student.password.verify'), [
            'email' => $student->email,
            'birthdate' => '1999-12-31',
        ])->assertRedirect(route('student.password.request'));

        $this->assertRecoveryMessage(self::GENERIC_ERROR);
        $this->assertNull(session('student_password_recovery'));
    }

    public function test_browser_style_birthdate_format_is_normalized_before_comparison(): void
    {
        [$student] = $this->student();

        $this->from(route('student.password.request'))->post(route('student.password.verify'), [
            'email' => $student->email,
            'birthdate' => '06/15/2004',
        ])->assertRedirect(route('student.password.reset'));

        $this->assertNotNull(session('student_password_recovery'));
    }

    public function test_identity_failure_is_rendered_once_inside_the_form_column(): void
    {
        $this->from(route('student.password.request'))->post(route('student.password.verify'), [
            'email' => 'unknown@example.test',
            'birthdate' => self::BIRTHDATE,
        ])->assertRedirect(route('student.password.request'));

        $escapedError = htmlspecialchars(self::GENERIC_ERROR, ENT_QUOTES, 'UTF-8');

        $html = $this->get(route('student.password.request'))
            ->assertOk()
            ->assertSee($escapedError, false)
            ->getContent();

        $this->assertSame(1, substr_count($html, $escapedError));
        $this->assertLessThan(strpos($html, 'id="student-verify-form"'), strpos($html, 'sl-auth-alert-danger'));
        $this->assertLessThan(strpos($html, 'for="email"'), strpos($html, 'sl-auth-alert-danger'));
        $this->assertStringNotContainsString('id="email-error"', $html);
        $this->assertStringNotContainsString('alert alert-danger w-100', $html);
        $this->assertStringNotContainsString('Please fix the following:', $html);
    }

    public function test_field_level_email_validation_is_still_rendered_below_the_input(): void
    {
        $this->from(route('student.password.request'))->post(route('student.password.verify'), [
            'email' => 'not-an-email',
            'birthdate' => self::BIRTHDATE,
        ])->assertSessionHasErrors('email');

        $html = $this->get(route('student.password.request'))
            ->assertOk()
            ->assertSee('id="email-error"', false)
            ->assertDontSee(self::GENERIC_ERROR, false)
            ->getContent();

        $this->assertSame(1, substr_count($html, 'id="email-error"'));
        $this->assertStringNotContainsString('Please fix the following:', $html);
    }

    public function test_password_confirmation_mismatch_does_not_change_the_password(): void
    {
        [$student] = $this->student();
        $this->authorizeFor($student);

        $this->post(route('student.password.reset.store'), [
            'password' => self::NEW_PASSWORD,
            'password_confirmation' => 'DifferentPassword456!',
        ])->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check(self::OLD_PASSWORD, $student->fresh()->password));
    }

    public function test_password_must_meet_the_existing_registration_policy(): void
    {
        [$student] = $this->student();
        $this->authorizeFor($student);

        $this->post(route('student.password.reset.store'), [
            'password' => 'weakpassword',
            'password_confirmation' => 'weakpassword',
        ])->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check(self::OLD_PASSWORD, $student->fresh()->password));
    }

    public function test_current_password_reuse_is_rejected_without_consuming_authorization(): void
    {
        [$student] = $this->student();
        $this->authorizeFor($student);

        $this->from(route('student.password.reset'))->post(route('student.password.reset.store'), [
            'password' => self::OLD_PASSWORD,
            'password_confirmation' => self::OLD_PASSWORD,
        ])->assertRedirect(route('student.password.reset'));

        $this->assertPasswordReuseError();
        $this->assertTrue(Hash::check(self::OLD_PASSWORD, $student->fresh()->password));
        $this->assertNotNull(session('student_password_recovery'));

        $this->post(route('student.password.reset.store'), [
            'password' => self::NEW_PASSWORD,
            'password_confirmation' => self::NEW_PASSWORD,
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check(self::NEW_PASSWORD, $student->fresh()->password));
    }

    public function test_current_password_reuse_error_renders_only_beneath_new_password(): void
    {
        [$student] = $this->student();
        $this->authorizeFor($student);

        $message = 'Your new password must be different from your current password.';

        $this->from(route('student.password.reset'))->post(route('student.password.reset.store'), [
            'password' => self::OLD_PASSWORD,
            'password_confirmation' => self::OLD_PASSWORD,
        ])->assertRedirect(route('student.password.reset'));

        $html = $this->get(route('student.password.reset'))
            ->assertOk()
            ->assertSee($message, false)
            ->getContent();

        $this->assertSame(1, substr_count($html, $message));
        $this->assertSame(1, substr_count($html, 'id="password-error"'));
        $this->assertStringNotContainsString('Please fix the following:', $html);

        preg_match('/<input[^>]*id="password"[^>]*>/', $html, $passwordInput);
        $this->assertNotEmpty($passwordInput);
        $this->assertStringContainsString('aria-invalid="true"', $passwordInput[0]);
        $this->assertMatchesRegularExpression('/aria-describedby="password-help\s+password-error\s*"/', $passwordInput[0]);

        preg_match('/<input[^>]*id="password_confirmation"[^>]*>/', $html, $confirmationInput);
        $this->assertNotEmpty($confirmationInput);
        $this->assertStringNotContainsString('is-invalid', $confirmationInput[0]);
        $this->assertStringNotContainsString('aria-invalid', $confirmationInput[0]);
    }

    public function test_each_password_policy_rule_is_enforced_during_recovery(): void
    {
        $rejectedPasswords = [
            'Ab1!',          // too short
            'abcdefgh1!',    // no uppercase
            'ABCDEFGH1!',    // no lowercase
            'Abcdefgh!',     // no number
            'Abcdefgh1',     // no symbol
        ];

        foreach ($rejectedPasswords as $password) {
            [$student] = $this->student();
            $this->authorizeFor($student);

            $this->post(route('student.password.reset.store'), [
                'password' => $password,
                'password_confirmation' => $password,
            ])->assertSessionHasErrors('password');

            $this->assertTrue(Hash::check(self::OLD_PASSWORD, $student->fresh()->password));
        }
    }

    public function test_valid_local_demonstration_reset_updates_the_hashed_password(): void
    {
        [$student] = $this->student();
        $this->authorizeFor($student);

        $this->post(route('student.password.reset.store'), [
            'password' => self::NEW_PASSWORD,
            'password_confirmation' => self::NEW_PASSWORD,
        ])->assertRedirect(route('login'))
            ->assertSessionHas('status', 'Your password has been updated successfully. You can now sign in using your new password.');

        $this->assertFalse(Hash::check(self::OLD_PASSWORD, $student->fresh()->password));
        $this->assertTrue(Hash::check(self::NEW_PASSWORD, $student->fresh()->password));
        $this->assertGuest();
        $this->assertNull(session('student_password_recovery'));
    }

    public function test_success_message_is_rendered_once_inside_the_login_form(): void
    {
        [$student] = $this->student();
        $this->authorizeFor($student);

        $successMessage = 'Your password has been updated successfully. You can now sign in using your new password.';

        $this->post(route('student.password.reset.store'), [
            'password' => self::NEW_PASSWORD,
            'password_confirmation' => self::NEW_PASSWORD,
        ])->assertRedirect(route('login'));

        $html = $this->get(route('login'))
            ->assertOk()
            ->assertSee($successMessage, false)
            ->getContent();

        $this->assertSame(1, substr_count($html, $successMessage));
        $this->assertLessThan(strpos($html, $successMessage), strpos($html, 'Student Sign In'));
        $this->assertGreaterThan(strpos($html, $successMessage), strpos($html, 'id="student-login-form"'));
    }

    public function test_success_message_is_consumed_after_the_landing_request(): void
    {
        [$student] = $this->student();
        $this->authorizeFor($student);

        $successMessage = 'Your password has been updated successfully. You can now sign in using your new password.';

        $this->post(route('student.password.reset.store'), [
            'password' => self::NEW_PASSWORD,
            'password_confirmation' => self::NEW_PASSWORD,
        ])->assertRedirect(route('login'));

        $this->get(route('login'))
            ->assertOk()
            ->assertSee($successMessage, false);

        $this->assertNull(session('status'));

        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee($successMessage, false);

        $this->get(route('student.password.request'))
            ->assertOk()
            ->assertDontSee($successMessage, false);
    }

    public function test_failed_login_does_not_retain_the_reset_success_alert(): void
    {
        [$student] = $this->student();
        $this->authorizeFor($student);

        $successMessage = 'Your password has been updated successfully. You can now sign in using your new password.';

        $this->post(route('student.password.reset.store'), [
            'password' => self::NEW_PASSWORD,
            'password_confirmation' => self::NEW_PASSWORD,
        ])->assertRedirect(route('login'));

        $this->get(route('login'))->assertOk()->assertSee($successMessage, false);

        $this->from(route('login'))->post(route('login.store'), [
            'email' => 'unknown@example.test',
            'password' => 'invalid-credentials',
        ])->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertNull(session('status'));

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('These credentials do not match our records.', false)
            ->assertDontSee($successMessage, false);
    }

    public function test_success_alert_exposes_dismiss_and_auto_dismiss_hooks(): void
    {
        [$student] = $this->student();
        $this->authorizeFor($student);

        $this->post(route('student.password.reset.store'), [
            'password' => self::NEW_PASSWORD,
            'password_confirmation' => self::NEW_PASSWORD,
        ])->assertRedirect(route('login'));

        $html = $this->get(route('login'))->assertOk()->getContent();

        $this->assertStringContainsString('id="student-login-status"', $html);
        $this->assertStringContainsString('data-student-login-status', $html);
        $this->assertStringContainsString('data-auto-dismiss="6000"', $html);
        $this->assertStringContainsString('data-dismiss-student-login-status', $html);
        $this->assertStringContainsString('bi bi-x-lg', $html);
        $this->assertStringContainsString("document.addEventListener('student-login-attempt', dismiss)", $html);
        $this->assertStringContainsString("dispatchEvent(new CustomEvent('student-login-attempt'))", $html);
    }

    public function test_sensitive_recovery_pages_send_no_store_headers(): void
    {
        [$student] = $this->student();

        $forgotResponse = $this->get(route('student.password.request'));
        $forgotResponse->assertHeader('Pragma', 'no-cache');
        $this->assertStringContainsString('no-store', (string) $forgotResponse->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-cache', (string) $forgotResponse->headers->get('Cache-Control'));

        $this->authorizeFor($student);

        $resetResponse = $this->get(route('student.password.reset'));
        $resetResponse->assertHeader('Pragma', 'no-cache');
        $this->assertStringContainsString('no-store', (string) $resetResponse->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-cache', (string) $resetResponse->headers->get('Cache-Control'));
    }

    public function test_recovery_responses_do_not_expose_passwords_birthdates_hashes_or_tokens(): void
    {
        [$student] = $this->student();
        $this->authorizeFor($student);

        $html = $this->get(route('student.password.reset'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString(self::OLD_PASSWORD, $html);
        $this->assertStringNotContainsString(self::NEW_PASSWORD, $html);
        $this->assertStringNotContainsString(self::BIRTHDATE, $html);
        $this->assertStringNotContainsString(str_repeat('c', 64), $html);
        $this->assertStringNotContainsString($student->password, $html);
    }

    public function test_direct_navigation_to_the_reset_page_is_blocked_without_authorization(): void
    {
        $this->get(route('student.password.reset'))
            ->assertRedirect(route('student.password.request'));

        $this->assertRecoveryMessage(self::SESSION_ERROR);
    }

    public function test_expired_authorization_is_rejected(): void
    {
        [$student] = $this->student();

        $this->withSession(['student_password_recovery' => [
            'user_id' => $student->id,
            'token' => str_repeat('a', 64),
            'expires_at' => now()->subMinute()->getTimestamp(),
        ]])->post(route('student.password.reset.store'), [
            'password' => self::NEW_PASSWORD,
            'password_confirmation' => self::NEW_PASSWORD,
        ])->assertRedirect(route('student.password.request'));

        $this->assertRecoveryMessage(self::SESSION_ERROR);
        $this->assertTrue(Hash::check(self::OLD_PASSWORD, $student->fresh()->password));
    }

    public function test_authorization_cannot_be_reused_for_a_second_reset(): void
    {
        [$student] = $this->student();
        $this->authorizeFor($student);

        $this->post(route('student.password.reset.store'), [
            'password' => self::NEW_PASSWORD,
            'password_confirmation' => self::NEW_PASSWORD,
        ])->assertRedirect(route('login'));

        $this->post(route('student.password.reset.store'), [
            'password' => 'AnotherPassword789!',
            'password_confirmation' => 'AnotherPassword789!',
        ])->assertRedirect(route('student.password.request'));

        $this->assertRecoveryMessage(self::SESSION_ERROR);
        $this->assertTrue(Hash::check(self::NEW_PASSWORD, $student->fresh()->password));
        $this->assertFalse(Hash::check('AnotherPassword789!', $student->fresh()->password));
    }

    public function test_verification_attempts_are_rate_limited(): void
    {
        [$student] = $this->student();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->from(route('student.password.request'))->post(route('student.password.verify'), [
                'email' => $student->email,
                'birthdate' => '1999-01-01',
            ])->assertRedirect(route('student.password.request'));

            $this->assertRecoveryMessage(self::GENERIC_ERROR);
        }

        $this->from(route('student.password.request'))->post(route('student.password.verify'), [
            'email' => $student->email,
            'birthdate' => self::BIRTHDATE,
        ])->assertRedirect(route('student.password.request'));

        $this->assertRecoveryMessage('Too many verification attempts. Please try again in 5 minutes.');
        $this->assertNull(session('student_password_recovery'));
    }

    public function test_password_reset_attempts_are_rate_limited(): void
    {
        [$student] = $this->student();
        $this->authorizeFor($student);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->from(route('student.password.reset'))->post(route('student.password.reset.store'), [
                'password' => 'weakpassword',
                'password_confirmation' => 'weakpassword',
            ])->assertSessionHasErrors('password');
        }

        $this->post(route('student.password.reset.store'), [
            'password' => self::NEW_PASSWORD,
            'password_confirmation' => self::NEW_PASSWORD,
        ])->assertRedirect(route('student.password.request'));

        $this->assertRecoveryMessage('Too many password reset attempts. Please try again in 5 minutes.');
        $this->assertTrue(Hash::check(self::OLD_PASSWORD, $student->fresh()->password));
    }

    public function test_production_configuration_blocks_the_demonstration_reset(): void
    {
        config()->set('app.student_password_recovery_enabled', false);
        [$student] = $this->student();

        $this->get(route('student.password.request'))
            ->assertOk()
            ->assertSee('Account Recovery Assistance')
            ->assertSee('To reset your student account password, please contact the authorized DOrSU system administrator for identity verification.')
            ->assertSee('Back to Student Sign In');

        $this->from(route('student.password.request'))->post(route('student.password.verify'), [
            'email' => $student->email,
            'birthdate' => self::BIRTHDATE,
        ])->assertRedirect(route('student.password.request'));

        $this->assertNull(session('student_password_recovery'));

        $this->withSession(['student_password_recovery' => [
            'user_id' => $student->id,
            'token' => str_repeat('b', 64),
            'expires_at' => now()->addMinutes(10)->getTimestamp(),
        ]])->post(route('student.password.reset.store'), [
            'password' => self::NEW_PASSWORD,
            'password_confirmation' => self::NEW_PASSWORD,
        ])->assertRedirect(route('student.password.request'));

        $this->assertTrue(Hash::check(self::OLD_PASSWORD, $student->fresh()->password));
        $this->get(route('student.password.reset'))->assertOk()->assertSee('Account Recovery Assistance');
    }

    public function test_student_can_sign_in_normally_after_an_authorized_reset(): void
    {
        [$student] = $this->student();
        $this->authorizeFor($student);

        $this->post(route('student.password.reset.store'), [
            'password' => self::NEW_PASSWORD,
            'password_confirmation' => self::NEW_PASSWORD,
        ])->assertRedirect(route('login'));

        $this->post(route('login.store'), [
            'email' => $student->email,
            'password' => self::OLD_PASSWORD,
        ])->assertSessionHasErrors('email');

        $this->assertGuest();

        $this->post(route('login.store'), [
            'email' => $student->email,
            'password' => self::NEW_PASSWORD,
        ])->assertRedirect(route('student.verification.show'));

        $this->assertAuthenticatedAs($student);
    }

    private function assertRecoveryMessage(string $message): void
    {
        $errors = session('errors');

        $this->assertInstanceOf(\Illuminate\Support\ViewErrorBag::class, $errors);

        $bag = $errors->getBag(self::RECOVERY_BAG);

        $this->assertTrue($bag->has(self::RECOVERY_MESSAGE_KEY));
        $this->assertSame($message, $bag->first(self::RECOVERY_MESSAGE_KEY));
    }

    private function assertPasswordReuseError(): void
    {
        $errors = session('errors');

        $this->assertInstanceOf(\Illuminate\Support\ViewErrorBag::class, $errors);

        $bag = $errors->getBag('default');

        $this->assertTrue($bag->has('password'));
        $this->assertSame(
            'Your new password must be different from your current password.',
            $bag->first('password'),
        );
    }

    /**
     * @param  array<string, mixed>  $userAttributes
     * @return array{User, StudentProfile}
     */
    private function student(array $userAttributes = []): array
    {
        $user = User::factory()->create(array_merge([
            'role' => UserRole::Student,
            'status' => UserStatus::Active,
            'password' => Hash::make(self::OLD_PASSWORD),
        ], $userAttributes));

        $profile = StudentProfile::factory()->create([
            'user_id' => $user->id,
            'birthdate' => self::BIRTHDATE,
        ]);

        return [$user, $profile];
    }

    private function authorizeFor(User $student): void
    {
        $this->withSession(['student_password_recovery' => [
            'user_id' => $student->id,
            'token' => str_repeat('c', 64),
            'expires_at' => now()->addMinutes(10)->getTimestamp(),
        ]]);
    }
}
