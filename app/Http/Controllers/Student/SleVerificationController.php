<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Concerns\ResolvesModuleContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\SleFheRequestSubmissionRequest;
use App\Http\Requests\Student\UpdateVerificationRequest;
use App\Models\AcademicProgram;
use App\Models\SleFheRequest;
use App\Models\StudentProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SleVerificationController extends Controller
{
    use ResolvesModuleContext;

    public function show(Request $request): View
    {
        $user = $this->actor($request);
        $profile = $this->studentProfile($request, required: false);

        $programs = AcademicProgram::query()
            ->where('is_active', true)
            ->where('is_undergraduate', true)
            ->orderBy('name')
            ->get();

        $provinces = DB::table('localaddress')
            ->whereNotNull('province')
            ->distinct()
            ->orderBy('province')
            ->pluck('province');

        $requestedAddress = $profile?->sleFheRequest;
        $verification = $profile?->sleFheVerification;

        $verifiedComponents = $profile !== null
            && $profile->isSleFheVerified()
            && $requestedAddress === null
            && filled($verification?->verified_address)
            ? $this->splitVerifiedAddress((string) $verification->verified_address)
            : [];

        $effectiveProvince = $requestedAddress?->province ?: ($verifiedComponents['province'] ?? '');
        $selectedProvince = old('province', $effectiveProvince);
        $municipalities = $selectedProvince !== ''
            ? DB::table('localaddress')
                ->where('province', $selectedProvince)
                ->distinct()
                ->orderBy('city')
                ->pluck('city')
                ->filter()
                ->values()
            : collect();

        return view('student.sle-verification', [
            'user' => $user,
            'profile' => $profile,
            'programs' => $programs,
            'provinces' => $provinces,
            'municipalities' => $municipalities,
            'verifiedComponents' => $verifiedComponents,
        ]);
    }

    private function splitVerifiedAddress(string $address): array
    {
        $parts = array_values(array_filter(
            array_map('trim', explode(',', $address)),
            static fn (string $part): bool => $part !== '',
        ));

        return [
            'street_purok' => $parts[0] ?? '',
            'barangay' => $parts[1] ?? '',
            'municipality_city' => $parts[2] ?? '',
            'province' => $parts[3] ?? '',
        ];
    }

    public function municipalities(string $province): JsonResponse
    {
        $cities = DB::table('localaddress')
            ->where('province', $province)
            ->whereNotNull('city')
            ->distinct()
            ->orderBy('city')
            ->pluck('city');

        return response()->json($cities);
    }

    public function update(UpdateVerificationRequest $request): RedirectResponse
    {
        $user = $this->actor($request);

        $data = $request->validated();

        $program = null;
        if (filled($data['academic_program_id'] ?? null)) {
            $program = AcademicProgram::query()->find($data['academic_program_id']);
            $data['course'] = $program?->name;
        }

        $profile = StudentProfile::query()->updateOrCreate(
            ['user_id' => $user->id],
            $data,
        );

        $sleFheVerified = $profile->syncSleFheFromFixedLists();

        $this->audit($request, 'student.verification.updated', 'student_profiles');

        $message = $sleFheVerified
            ? 'Student ID saved. SLE-FHE status is verified against a sponsor fixed list.'
            : 'Student ID saved. SLE-FHE is not yet verified. FASSG must match your ID on a fixed list.';

        return redirect()
            ->route('student.sle-fhe')
            ->with('status', $message);
    }

    public function submit(SleFheRequestSubmissionRequest $request): RedirectResponse
    {
        $profile = $this->studentProfile($request);

        if ($profile->sleFheVerification()->exists()) {
            return redirect()
                ->route('student.sle-fhe')
                ->with('error', 'Your SLE-FHE status is already verified and your residential address is locked.');
        }

        SleFheRequest::query()->updateOrCreate(
            ['student_profile_id' => $profile->id],
            array_merge($request->validated(), [
                'status' => 'pending',
                'submitted_at' => now(),
            ]),
        );

        $this->audit($request, 'student.sle-fhe.request.submitted', 'sle_fhe_requests');

        return redirect()
            ->route('student.sle-fhe')
            ->with('status', 'Your verification request has been submitted. FASSG will review your residential address.');
    }
}
