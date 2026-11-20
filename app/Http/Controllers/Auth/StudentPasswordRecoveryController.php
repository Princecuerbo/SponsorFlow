<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class StudentPasswordRecoveryController extends Controller
{
    private const SESSION_KEY = 'student_password_recovery';

    private const RECOVERY_ERROR_BAG = 'studentRecovery';

    private const RECOVERY_MESSAGE_KEY = 'recovery';

    private const GENERIC_ERROR = "We couldn't verify the information provided. Please check your details or contact the authorized university office.";

    private const SESSION_ERROR = 'Your password reset session is invalid or has expired. Please verify your details again.';

    private const PASSWORD_REUSE_ERROR = 'Your new password must be different from your current password.';

    private const DECAY_SECONDS = 300;

    private const MAX_ATTEMPTS = 5;

    public function showRequestForm(): Response
    {
        return $this->renderPage($this->isEnabled()
            ? 'auth.student-forgot-password'
            : 'auth.student-recovery-assistance');
    }

    public function verify(Request $request): RedirectResponse
    {
        if (! $this->isEnabled()) {
            return redirect()->route('student.password.request');
        }

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:191'],
            'birthdate' => ['required', 'date', 'before:today'],
        ]);

        $email = mb_strtolower((string) $validated['email']);
        $ipKey = 'student-recovery:ip:'.$request->ip();
        $accountKey = 'student-recovery:account:'.hash('sha256', $email).':'.$request->ip();
        $maxAttempts = $this->maxAttempts();

        if (RateLimiter::tooManyAttempts($ipKey, $maxAttempts) || RateLimiter::tooManyAttempts($accountKey, $maxAttempts)) {
            $seconds = max(RateLimiter::availableIn($ipKey), RateLimiter::availableIn($accountKey));

            throw ValidationException::withMessages([
                self::RECOVERY_MESSAGE_KEY => 'Too many verification attempts. Please try again in '.ceil($seconds / 60).' minutes.',
            ])->errorBag(self::RECOVERY_ERROR_BAG);
        }

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->with('studentProfile')
            ->first();

        $profile = $user?->studentProfile;
        $verified = $user
            && $user->isStudent()
            && $user->isActive()
            && $profile !== null
            && $profile->birthdate !== null
            && hash_equals(
                $profile->birthdate->toDateString(),
                Carbon::parse($validated['birthdate'])->toDateString(),
            );

        if (! $verified) {
            RateLimiter::hit($ipKey, self::DECAY_SECONDS);
            RateLimiter::hit($accountKey, self::DECAY_SECONDS);

            throw ValidationException::withMessages([
                self::RECOVERY_MESSAGE_KEY => self::GENERIC_ERROR,
            ])->errorBag(self::RECOVERY_ERROR_BAG);
        }

        RateLimiter::clear($ipKey);
        RateLimiter::clear($accountKey);

        $request->session()->regenerate();
        $request->session()->put(self::SESSION_KEY, [
            'user_id' => $user->id,
            'token' => hash('sha256', Str::random(64)),
            'expires_at' => now()->addMinutes($this->ttl())->getTimestamp(),
        ]);

        return redirect()->route('student.password.reset');
    }

    public function showResetForm(Request $request): Response|RedirectResponse
    {
        if (! $this->isEnabled()) {
            return $this->renderPage('auth.student-recovery-assistance');
        }

        if ($this->authorization($request) === null) {
            return redirect()->route('student.password.request')
                ->withErrors([self::RECOVERY_MESSAGE_KEY => self::SESSION_ERROR], self::RECOVERY_ERROR_BAG);
        }

        return $this->renderPage('auth.student-reset-password');
    }

    public function reset(Request $request): RedirectResponse
    {
        if (! $this->isEnabled()) {
            return redirect()->route('student.password.request');
        }

        $resetKey = 'student-recovery:reset:'.$request->ip();
        $maxAttempts = $this->maxAttempts();

        if (RateLimiter::tooManyAttempts($resetKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($resetKey);

            return redirect()->route('student.password.request')->withErrors([
                self::RECOVERY_MESSAGE_KEY => 'Too many password reset attempts. Please try again in '.ceil($seconds / 60).' minutes.',
            ], self::RECOVERY_ERROR_BAG);
        }

        RateLimiter::hit($resetKey, self::DECAY_SECONDS);

        $authorization = $this->authorization($request);
        if ($authorization === null) {
            return redirect()->route('student.password.request')
                ->withErrors([self::RECOVERY_MESSAGE_KEY => self::SESSION_ERROR], self::RECOVERY_ERROR_BAG);
        }

        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        /** @var User $user */
        $user = $authorization['user'];

        // Reject reuse before consuming the authorization so the student can retry with a valid password.
        if (Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => self::PASSWORD_REUSE_ERROR,
            ]);
        }

        // Consume the authorization before writing so duplicate submissions cannot reuse it.
        $request->session()->forget(self::SESSION_KEY);
        $request->session()->regenerate();

        $user->forceFill(['password' => Hash::make($validated['password'])])->save();

        RateLimiter::clear($resetKey);

        return redirect()->route('login')->with('status', 'Your password has been updated successfully. You can now sign in using your new password.');
    }

    private function authorization(Request $request): ?array
    {
        $data = $request->session()->get(self::SESSION_KEY);

        if (
            ! is_array($data)
            || ! isset($data['user_id'], $data['token'], $data['expires_at'])
            || ! is_string($data['token'])
            || preg_match('/\A[a-f0-9]{64}\z/', $data['token']) !== 1
            || ! is_int($data['expires_at'])
            || $data['expires_at'] <= now()->getTimestamp()
        ) {
            $request->session()->forget(self::SESSION_KEY);

            return null;
        }

        $user = $this->eligibleStudent((int) $data['user_id']);
        if ($user === null) {
            $request->session()->forget(self::SESSION_KEY);

            return null;
        }

        return [
            'user' => $user,
            'token' => $data['token'],
            'expires_at' => $data['expires_at'],
        ];
    }

    private function eligibleStudent(int $userId): ?User
    {
        return User::query()
            ->whereKey($userId)
            ->where('role', UserRole::Student->value)
            ->where('status', UserStatus::Active->value)
            ->whereHas('studentProfile', fn ($query) => $query->whereNotNull('birthdate'))
            ->first();
    }

    private function isEnabled(): bool
    {
        return (bool) config('app.student_password_recovery_enabled', false);
    }

    private function ttl(): int
    {
        return max(1, (int) config('app.student_password_recovery_ttl', 10));
    }

    private function maxAttempts(): int
    {
        try {
            return max(1, (int) SystemSetting::get('max_login_attempts', self::MAX_ATTEMPTS));
        } catch (\Throwable) {
            return self::MAX_ATTEMPTS;
        }
    }

    private function renderPage(string $view): Response
    {
        return response()
            ->view($view)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
    }
}
