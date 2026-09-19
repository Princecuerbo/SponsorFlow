<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        try {
            $authenticated = Auth::attempt($credentials, $request->boolean('remember'));
        } catch (\Throwable) {
            return back()->withErrors(['email' => 'Unable to verify your credentials at this time. Please try again shortly.'])->onlyInput('email');
        }

        if (! $authenticated) {
            return back()->withErrors(['email' => 'These credentials do not match our records.'])->onlyInput('email');
        }

        /** @var User $user */
        $user = $request->user();
        if (! $user->isActive()) {
            Auth::logout();
            return back()->withErrors(['email' => 'Your account is not active.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        $user->timestamps = false;
        try {
            $user->update(['last_login_at' => now()]);
        } catch (\Throwable) {
        }

        $dashboardRoute = match ($user->role) {
            UserRole::Admin => route('admin.dashboard'),
            UserRole::Fassg => route('fassg.dashboard'),
            UserRole::Sponsor => route('sponsor.dashboard'),
            UserRole::Accounting => route('accounting.dashboard'),
            UserRole::Student => route('student.dashboard'),
        };

        $request->session()->forget('url.intended');

        return redirect()->to($dashboardRoute);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
