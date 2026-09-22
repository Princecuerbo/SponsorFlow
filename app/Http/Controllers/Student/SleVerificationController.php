<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Concerns\ResolvesModuleContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\SleFheRequestSubmissionRequest;
use App\Http\Requests\Student\UpdateVerificationRequest;
use App\Models\AcademicProgram;
use App\Models\SleFheRequest;
use App\Models\StudentProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        return view('student.sle-verification', [
            'user' => $user,
            'profile' => $profile,
            'programs' => $programs,
        ]);
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

        if ($profile->sleFheVerification()->exists() || $profile->is_sle_fhe_verified) {
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

    public function upload(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'document_type' => ['required', 'string', 'in:certificate_of_grades,proof_of_residence,barangay_cert'],
            'document_file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $user = $this->actor($request);
        $profile = $this->studentProfile($request, required: false);

        if (! $profile) {
            return redirect()
                ->route('student.sle-fhe')
                ->with('error', 'Please complete your student profile first.');
        }

        $path = $request->file('document_file')->store('sle-fhe-documents/' . $user->id, 'public');

        $columnMap = [
            'certificate_of_grades' => 'sle_fhe_cg_path',
            'proof_of_residence' => 'sle_fhe_residence_path',
            'barangay_cert' => 'sle_fhe_barangay_path',
        ];

        $profile->update([
            $columnMap[$validated['document_type']] => $path,
        ]);

        $this->audit($request, 'student.sle-fhe.document.uploaded', 'student_profiles');

        return redirect()
            ->route('student.sle-fhe')
            ->with('status', ucfirst(str_replace('_', ' ', $validated['document_type'])) . ' uploaded successfully.');
    }
}
