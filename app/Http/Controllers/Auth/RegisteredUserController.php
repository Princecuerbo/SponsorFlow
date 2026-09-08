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
            'province' => ['required', 'string', 'max:255'],
            'municipality' => ['required', 'string', 'max:255'],
            'barangay' => ['required', 'string', 'max:255'],
            'home_address' => ['required', 'string', 'max:255'],
            'privacy_consent' => ['accepted'],
        ], [
            'email.regex' => 'Registration requires an official DORSU institutional email (@dorsu.edu.ph).',
            'student_id_number.regex' => 'Student ID must be in the format 2024-0001 (4 digits, a hyphen, and 4 digits).',
            'contact_number.regex' => 'Contact number must be a valid Philippine mobile number in the format 09123456789.',
            'privacy_consent.accepted' => 'You must certify the accuracy of your information and consent to FASSG verifying your SLE-FHE records under the Data Privacy Act.',
        ]);

        $isRural = $this->determineRurality(
            $validated['province'],
            $validated['municipality'],
            $validated['barangay'],
        );

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
                'privacy_consent_at' => now(),
            ]);

            StudentProfile::query()->create([
                'user_id' => $user->id,
                'student_id_number' => $validated['student_id_number'],
                'first_name' => $validated['first_name'],
                'middle_name' => $validated['middle_name'] ?? null,
                'last_name' => $validated['last_name'],
                'academic_program_id' => $validated['academic_program_id'],
                'course' => $course,
                'campus' => $validated['campus'],
                'contact_number' => $validated['contact_number'],
                'year_level' => $validated['year_level'],
                'gender' => $validated['gender'],
                'birthdate' => $validated['birthdate'],
                'province' => $validated['province'],
                'municipality' => $validated['municipality'],
                'barangay' => $validated['barangay'],
                'home_address' => $validated['home_address'],
                'is_rural' => $isRural,
                'is_sle_fhe_verified' => false,
            ]);

            return $user;
        });

        return redirect()->route('login')->with('status', 'Registration successful! Please sign in with your credentials.');
    }

    /**
     * Auto-classify a residence as rural (true) or urban (false).
     *
     * - Major Highly Urbanized Cities (HUCs) and Metro Manila classify as urban.
     * - "City of Mati" classifies per barangay (urban for the listed urban barangays).
     * - All other provincial municipalities default to rural.
     */
    private function determineRurality(string $province, string $municipality, string $barangay): bool
    {
        $urbanCities = [
            'manila', 'quezon city', 'caloocan', 'las piñas', 'las pinas', 'makati',
            'malabon', 'mandaluyong', 'marikina', 'muntinlupa', 'navotas',
            'parañaque', 'paranaque', 'pasay', 'pasig', 'san juan', 'taguig', 'valenzuela',
            'baguio', 'angeles city', 'olongapo city',
            'cebu city', 'mandaue city', 'lapu-lapu city',
            'iloilo city', 'bacolod', 'tacloban city',
            'davao city', 'zamboanga city', 'cagayan de oro', 'iligan',
            'butuan', 'general santos', 'cotabato city', 'puerto princesa',
        ];

        $prov = strtolower(trim($province));
        $city = str_replace(' ', '', strtolower(trim($municipality)));
        $key = strtolower(trim($barangay));

        if (str_contains($prov, 'metro manila') || str_contains($prov, 'national capital')) {
            return false;
        }

        $compactCities = array_map(fn (string $c) => str_replace(' ', '', $c), $urbanCities);

        if (in_array($city, $compactCities, true)) {
            return false;
        }

        if (str_contains($city, 'mati')) {
            $urbanMatiBarangays = ['central', 'dahican', 'sainz', 'matiao'];

            return ! in_array($key, $urbanMatiBarangays, true);
        }

        return true;
    }
}