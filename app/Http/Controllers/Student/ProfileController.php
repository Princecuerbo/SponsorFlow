<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        return view('student.profile', [
            'user' => $request->user(),
            'profile' => $request->user()->studentProfile,
        ]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        if ($request->has('new_password') && !$request->has('password')) {
            $request->merge(['password' => $request->input('new_password')]);
        }

        if ($request->has('new_password_confirmation') && !$request->has('password_confirmation')) {
            $request->merge(['password_confirmation' => $request->input('new_password_confirmation')]);
        }

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        $user = $request->user();
        $user->forceFill([
            'password' => Hash::make($validated['password']),
        ])->save();

        return back()->with('status', 'Your password has been updated successfully.');
    }
}
