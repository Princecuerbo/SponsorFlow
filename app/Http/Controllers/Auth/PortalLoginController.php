<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

abstract class PortalLoginController extends Controller
{
    /** @var list<string> */
    protected array $allowedRoles = [];

    protected string $loginView;

    protected string $redirectRoute;

    protected string $accessDeniedMessage;

    public function showLoginForm(): View
    {
        return view($this->loginView);
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $maxAttempts = (int) SystemSetting::get('max_login_attempts', 5);
        $key = strtolower($request->input('email')) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);

            return back()->withErrors([
                'email' => 'Too many login attempts. Please try again in ' . ceil($seconds / 60) . ' minutes.',
            ])->onlyInput('email');
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($key, 60 * 5);

            return back()
                ->withErrors(['email' => 'These credentials do not match our records.'])
                ->onlyInput('email');
        }

        RateLimiter::clear($key);

        /** @var User $user */
        $user = $request->user();

        if (! $user->hasAnyRole(...$this->allowedRoles)) {
            Auth::logout();

            return back()
                ->withErrors(['email' => $this->accessDeniedMessage])
                ->onlyInput('email');
        }

        if (! $user->isActive()) {
            Auth::logout();

            return back()
                ->withErrors(['email' => 'Your account is not active.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();
        $user->update(['last_login_at' => now()]);

        $destination = $this->destinationRoute($user);
        $request->session()->forget('url.intended');

        return redirect()->route($destination);
    }

    protected function destinationRoute(User $user): string
    {
        return $user->isStudent() ? 'student.verification.show' : $this->redirectRoute;
    }

    public function verify(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $key = strtolower($request->input('email')) . '|' . $request->ip();
        $maxAttempts = (int) SystemSetting::get('max_login_attempts', 5);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'email' => 'Too many login attempts. Please try again in ' . ceil($seconds / 60) . ' minutes.',
            ]);
        }

        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user || ! Auth::validate($credentials)) {
            RateLimiter::hit($key, 60 * 5);

            return response()->json([
                'message' => 'These credentials do not match our records.',
            ], 422);
        }

        if (! $user->hasAnyRole(...$this->allowedRoles)) {
            return response()->json([
                'message' => $this->accessDeniedMessage,
            ], 422);
        }

        if (! $user->isActive()) {
            return response()->json([
                'message' => 'Your account is not active.',
            ], 422);
        }

        RateLimiter::clear($key);

        session(['pending_user_id' => $user->id]);

        return response()->json([
            'success' => true,
            'pending_token' => session()->getId(),
        ]);
    }

    public function complete(Request $request): JsonResponse
    {
        if (! $request->boolean('privacy_consent')) {
            return response()->json([
                'message' => 'Consent is required to continue.',
            ], 422);
        }

        $userId = session('pending_user_id');

        if (! $userId) {
            return response()->json([
                'message' => 'Your session has expired. Please sign in again.',
            ], 422);
        }

        /** @var User|null $user */
        $user = User::query()->find($userId);

        if (! $user || ! $user->isActive() || ! $user->hasAnyRole(...$this->allowedRoles)) {
            return response()->json([
                'message' => 'Your account is no longer available.',
            ], 422);
        }

        $request->session()->regenerate();

        Auth::loginUsingId($userId);
        $user->forceFill(['last_login_at' => now(), 'privacy_consent_at' => now()])->save();

        $request->session()->forget('pending_user_id');

        return response()->json([
            'redirect' => route('student.dashboard'),
        ]);
    }
}
