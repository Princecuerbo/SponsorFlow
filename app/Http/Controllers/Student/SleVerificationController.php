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

        // Never write client-supplied course text or clear a historical course
        // during an unrelated profile update.
        unset($data['course']);

        $profile = DB::transaction(function () use ($user, $data): StudentProfile {
            $existing = StudentProfile::query()->where('user_id', $user->id)->lockForUpdate()->first();
            if ($existing !== null && $existing->student_id_number !== $data['student_id_number']
                && ($existing->sleFheVerification()->exists()
                    || $existing->applications()->whereHas('batchCandidates.generatedBatch', fn ($batch) => $batch->where('status', '!=', 'Saved'))->exists())) {
                throw \Illuminate\Validation\ValidationException::withMessages(['student_id_number' => 'FASSG must review identity corrections for an existing verified or submitted record.']);
            }

            if (array_key_exists('academic_program_id', $data)) {
                if ((string) $existing?->academic_program_id === (string) $data['academic_program_id']) {
                    // Retaining the same ID must not rewrite its historical text.
                    unset($data['academic_program_id']);
                } else {
                    $program = AcademicProgram::query()->where('is_active', true)
                        ->where('is_undergraduate', true)->find($data['academic_program_id']);
                    if ($program === null) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'academic_program_id' => 'Select an active undergraduate Academic Program from the official list.',
                        ]);
                    }
                    $data['course'] = $program->name;
                }
            }

            return StudentProfile::query()->updateOrCreate(['user_id' => $user->id], $data);
        });

        $this->audit($request, 'student.verification.updated', 'student_profiles');

        $message = 'Student profile saved. Saving a profile does not verify SLE-FHE eligibility; verification is recorded separately by FASSG.';

        return redirect()
            ->route('student.sle-fhe')
            ->with('status', $message);
    }

    public function submit(SleFheRequestSubmissionRequest $request): RedirectResponse
    {
        $profile = $this->studentProfile($request);
        DB::transaction(function () use ($profile, $request): void {
            $profile = StudentProfile::query()->lockForUpdate()->findOrFail($profile->id);
            if ($profile->sleFheVerification()->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages(['verification' => 'Existing verification evidence is preserved. Ask FASSG to review any correction.']);
            }
            // Preserve prior request facts; only the queue disposition changes.
            $profile->sleFheRequests()->where('status', 'pending')->update(['status' => 'superseded']);
            $profile->sleFheRequests()->create([
                ...$request->validated(), 'status' => 'pending', 'submitted_at' => now(),
            ]);
        });
        $this->audit($request, 'student.sle-fhe.request.submitted', 'sle_fhe_requests');

        return redirect()->route('student.sle-fhe')->with('status', 'Your verification request has been submitted for FASSG evidence review.');
    }
}
