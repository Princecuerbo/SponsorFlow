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
        $campusOptions = [
            'Main Campus (City of Mati)',
            'Baganga Campus',
            'Banaybanay Campus',
            'Cateel Campus',
            'San Isidro Campus',
            'Tarragona Campus',
        ];

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'extension_name' => ['nullable', 'string', 'max:10'],
            'gender' => ['required', 'string', 'in:Male,Female'],
            'contact_number' => ['required', 'string', 'regex:/^09\d{9}$/'],
            'email' => ['required', 'email', 'max:191', 'regex:/^[a-zA-Z0-9._%+-]+@dorsu\.edu\.ph$/i', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
            'student_id_number' => ['required', 'string', 'regex:/^\d{4}-\d{4}$/', 'unique:student_profiles,student_id_number'],
            'campus' => ['required', 'string', Rule::in($campusOptions)],
            'academic_program_id' => ['required', 'exists:academic_programs,program_id'],
            'course' => ['nullable', 'string', 'max:150'],
            'year_level' => ['required', 'integer', 'min:1', 'max:5'],
            'birthdate' => ['required', 'date', 'before:today'],
            'privacy_consent' => ['accepted'],
        ], [
            'email.regex' => 'Registration requires an official DORSU institutional email (@dorsu.edu.ph).',
            'student_id_number.regex' => 'Student ID must be in the format 2024-0001 (4 digits, a hyphen, and 4 digits).',
            'contact_number.regex' => 'Contact number must be a valid Philippine mobile number in the format 09123456789.',
            'privacy_consent.accepted' => 'You must certify the accuracy of your information and consent to FASSG verifying your SLE-FHE records under the Data Privacy Act.',
        ]);

        $program = AcademicProgram::query()->findOrFail($validated['academic_program_id']);
        $course = $program->name;

        $user = DB::transaction(function () use ($validated, $course): User {
            $nameParts = array_filter([
                $validated['first_name'],
                $validated['middle_name'] ?? null,
                $validated['last_name'],
                $validated['extension_name'] ?? null,
            ]);
            $fullName = trim(implode(' ', $nameParts));

            $user = User::query()->create([
                'name' => $fullName,
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => UserRole::Student,
                'status' => UserStatus::Active,
                'privacy_consent_at' => now(),
            ]);

            StudentProfile::query()->create([
                'user_id' => $user->id,
                'student_id_number' => $validated['student_id_number'],
                'first_name' => $validated['first_name'],
                'middle_name' => $validated['middle_name'] ?? null,
                'last_name' => $validated['last_name'],
                'extension_name' => $validated['extension_name'] ?? null,
                'academic_program_id' => $validated['academic_program_id'],
                'course' => $course,
                'campus' => $validated['campus'],
                'contact_number' => $validated['contact_number'],
                'year_level' => $validated['year_level'],
                'gender' => $validated['gender'],
                'birthdate' => $validated['birthdate'],
            ]);

            return $user;
        });

        return redirect()->route('login')->with('status', 'Registration successful! Please sign in with your credentials.');
    }
}
