<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\AcademicProgram;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        $programs = AcademicProgram::query()
            ->where('is_active', true)
            ->where('is_undergraduate', true)
            ->orderBy('name')
            ->get();

        return view('auth.register', compact('programs'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'gender' => ['required', 'string', 'in:Male,Female'],
            'email' => ['required', 'email', 'max:191', 'regex:/@dorsu\.edu\.ph$/i', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
            'student_id_number' => ['required', 'string', 'max:50', 'regex:/^\d{4}-\d{4,6}$/', 'unique:student_profiles,student_id_number'],
            'academic_program_id' => ['required', 'exists:academic_programs,program_id'],
            'course' => ['nullable', 'string', 'max:150'],
            'year_level' => ['required', 'integer', 'min:1', 'max:5'],
            'birthdate' => ['required', 'date', 'before:today'],
            'municipality' => ['required', 'string', 'in:Mati City,Baganga,Banaybanay,Boston,Caraga,Cateel,Governor Generoso,Lupon,Manay,San Isidro,Tarragona'],
            'address' => ['required', 'string', 'max:500'],
            'is_rural' => ['nullable', 'boolean'],
        ], [
            'email.regex' => 'Registration requires an official DORSU institutional email (@dorsu.edu.ph).',
        ]);

        $isRural = $request->boolean('is_rural');

        $program = AcademicProgram::query()->findOrFail($validated['academic_program_id']);
        $course = $program->name;

        $user = DB::transaction(function () use ($validated, $isRural, $course): User {
            $fullName = trim("{$validated['first_name']} " . ($validated['middle_name'] ?? '') . " {$validated['last_name']}");

            $user = User::query()->create([
                'name' => $fullName,
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => UserRole::Student,
                'status' => UserStatus::Active,
            ]);

            StudentProfile::query()->create([
                'user_id' => $user->id,
                'student_id_number' => $validated['student_id_number'],
                'first_name' => $validated['first_name'],
                'middle_name' => $validated['middle_name'] ?? null,
                'last_name' => $validated['last_name'],
                'academic_program_id' => $validated['academic_program_id'],
                'course' => $course,
                'year_level' => $validated['year_level'],
                'gender' => $validated['gender'],
                'birthdate' => $validated['birthdate'],
                'municipality' => $validated['municipality'],
                'barangay' => null, // Left null since street/barangay details are stored in 'address'
                'address' => $validated['address'],
                'is_rural' => $isRural,
                'is_sle_fhe_verified' => false,
            ]);

            return $user;
        });

        return redirect()->route('login')->with('status', 'Registration successful! Please sign in with your credentials.');
    }
}