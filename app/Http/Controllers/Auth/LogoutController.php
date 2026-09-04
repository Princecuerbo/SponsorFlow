<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogoutController extends Controller
{
    public function logout(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $redirectUrl = route('login');

        if ($user instanceof User) {
            if ($user->hasAnyRole(UserRole::Admin)) {
                $redirectUrl = route('admin.login');
            } elseif ($user->hasAnyRole(UserRole::Fassg, UserRole::Sponsor, UserRole::Accounting)) {
                $redirectUrl = route('staff.login');
            }
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect($redirectUrl);
    }
}
