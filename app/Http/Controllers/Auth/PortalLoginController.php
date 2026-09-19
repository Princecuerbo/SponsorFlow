<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

abstract class PortalLoginController extends Controller
{
    /** @var list<string> */
    protected array $allowedRoles = [];

    protected string $loginView;

    protected string $redirectRoute;

    protected string $accessDeniedMessage;

    public function showLoginForm(): Response
    {
        return response()
            ->view($this->loginView)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        try {
            $maxAttempts = (int) SystemSetting::get('max_login_attempts', 5);
        } catch (\Throwable) {
            $maxAttempts = 5;
        }
        $key = strtolower($request->input('email')) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);

            return back()->withErrors([
                'email' => 'Too many login attempts. Please try again in ' . ceil($seconds / 60) . ' minutes.',
            ])->onlyInput('email');
        }

        try {
            $authenticated = Auth::attempt($credentials, $request->boolean('remember'));
        } catch (\Throwable) {
            return back()
                ->withErrors(['email' => 'Unable to verify your credentials at this time. Please try again shortly.'])
                ->onlyInput('email');
        }

        if (! $authenticated) {
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

        try {
            $user->update(['last_login_at' => now()]);
        } catch (\Throwable) {
            // A timestamp write failure must never crash the login response.
        }

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
        try {
            $maxAttempts = (int) SystemSetting::get('max_login_attempts', 5);
        } catch (\Throwable) {
            $maxAttempts = 5;
        }

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'email' => 'Too many login attempts. Please try again in ' . ceil($seconds / 60) . ' minutes.',
            ]);
        }

        try {
            $user = User::query()->where('email', $credentials['email'])->first();
            $credentialsValid = $user ? Auth::validate($credentials) : false;
        } catch (\Throwable) {
            return response()->json([
                'message' => 'Unable to verify your credentials at this time. Please try again shortly.',
            ], 503);
        }

        if (! $user || ! $credentialsValid) {
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

        try {
            $user = User::query()->find($userId);
        } catch (\Throwable) {
            return response()->json([
                'message' => 'Your account is temporarily unavailable. Please try again shortly.',
            ], 503);
        }

        if (! $user || ! $user->isActive() || ! $user->hasAnyRole(...$this->allowedRoles)) {
            return response()->json([
                'message' => 'Your account is no longer available.',
            ], 422);
        }

        $request->session()->regenerate();

        try {
            Auth::loginUsingId($userId);
        } catch (\Throwable) {
            return response()->json([
                'message' => 'Your session could not be established. Please sign in again.',
            ], 422);
        }

        try {
            $user->forceFill(['last_login_at' => now(), 'privacy_consent_at' => now()])->save();
        } catch (\Throwable) {
            // Timestamp write failures must never crash the consent response.
        }

        $request->session()->forget('pending_user_id');

        return response()->json([
            'redirect' => route('student.dashboard'),
        ]);
    }
}
