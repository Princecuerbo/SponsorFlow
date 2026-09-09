<?php

namespace App\Http\Controllers\Fassg;

use App\Enums\ApplicationStatus;
use App\Enums\ProgramStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Concerns\ResolvesModuleContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Fassg\StoreSponsorshipProgramRequest;
use App\Http\Requests\Fassg\UpdateSponsorshipProgramRequest;
use App\Models\AcademicProgram;
use App\Models\Sponsor;
use App\Models\SponsorshipProgram;
use App\Models\StudentProfile;
use App\Models\User;
use App\Notifications\NewSponsorshipProgramOpened;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ProgramManagementController extends Controller
{
    use ResolvesModuleContext;

    public function index(Request $request): View
    {
        if (Schema::hasColumn('sponsorship_programs', 'end_date')) {
            SponsorshipProgram::query()
                ->where(function ($query): void {
                    $query->where('status', ProgramStatus::Expired)
                        ->orWhere(function ($query): void {
                            $query->where('status', '!=', ProgramStatus::Expired)
                                ->whereNotNull('end_date')
                                ->where('end_date', '<', now()->startOfDay());
                        });
                })
                ->get()
                ->each(function (SponsorshipProgram $program): void {
                    if ($program->status !== ProgramStatus::Expired) {
                        $program->update(['status' => ProgramStatus::Expired]);
                    }

                    $program->cascadeExpiredApplications();
                });
        }

        $programs = SponsorshipProgram::query()
            ->with('sponsor')
            ->withCount('applications')
            ->when(
                $request->filled('status'),
                fn($query) => $query->where(
                    'status',
                    ProgramStatus::tryFrom(ucfirst(strtolower($request->string('status')->toString()))),
                ),
            )
            ->latest()
            ->get();

        return view('fassg.programs.index', [
            'user' => $this->actor($request),
            'programs' => $programs,
        ]);
    }

    public function create(Request $request): View
    {
        return view('fassg.programs.create', [
            'user' => $this->actor($request),
            'program' => new SponsorshipProgram,
            'sponsors' => $this->availableSponsors(),
            'academicPrograms' => $this->availableAcademicPrograms(),
        ]);
    }

    public function store(StoreSponsorshipProgramRequest $request): RedirectResponse
    {
        $slots = (int) $request->validated('total_slots');
        $academicProgramIds = array_values(array_map('intval', $request->input('academic_program_ids', []) ?: []));

        $program = DB::transaction(function () use ($request, $slots, $academicProgramIds): SponsorshipProgram {
            $program = SponsorshipProgram::query()->create([
                ...$request->validated(),
                'total_slots' => $slots,
                'available_slots' => $slots,
                'target_course' => $this->resolveTargetCourse($academicProgramIds),
                'requires_relative_verification' => $request->boolean('requires_relative_verification'),
                'eligible_campuses' => $request->validated('eligible_campuses') ?? [],
                'required_documents' => $request->validated('required_documents') ?? [],
                'status' => ProgramStatus::Open,
            ]);

            $program->academicPrograms()->sync($academicProgramIds);

            return $program;
        });

        $this->audit($request, 'fassg.program.created', 'sponsorship_programs');

        $this->notifyEligibleStudents($program->fresh());

        return redirect()
            ->route('fassg.programs.index')
            ->with('status', "Program {$program->program_name} was created and opened.");
    }

    public function edit(Request $request, SponsorshipProgram $sponsorshipProgram): View
    {
        return view('fassg.programs.edit', [
            'user' => $this->actor($request),
            'program' => $sponsorshipProgram,
            'approvedCount' => $sponsorshipProgram->applications()
                ->where('status', ApplicationStatus::Approved)
                ->count(),
            'sponsors' => $this->availableSponsors(),
            'academicPrograms' => $this->availableAcademicPrograms(),
        ]);
    }

    public function update(UpdateSponsorshipProgramRequest $request, SponsorshipProgram $sponsorshipProgram): RedirectResponse
    {
        $requestedStatus = $request->enum('status', ProgramStatus::class);
        $wasOpen = $sponsorshipProgram->isOpen();
        $shouldOpen = $requestedStatus === ProgramStatus::Open
            && (int) $request->input('available_slots') > 0;
        $shouldExpire = ! $shouldOpen && ($requestedStatus === ProgramStatus::Expired
            || ($request->filled('end_date') && Carbon::parse($request->input('end_date'))->isPast()));

        DB::transaction(function () use ($request, $sponsorshipProgram, $shouldExpire, $shouldOpen): void {
            $attributes = $request->validated();

            if ($shouldOpen) {
                $attributes['status'] = ProgramStatus::Open;
            } elseif ($shouldExpire) {
                $attributes['status'] = ProgramStatus::Expired;
            }

            $attributes['requires_relative_verification'] = $request->boolean('requires_relative_verification');
            $attributes['eligible_campuses'] = $request->validated('eligible_campuses') ?? [];
            $attributes['required_documents'] = $request->validated('required_documents') ?? [];

            $approvedCount = $sponsorshipProgram->applications()
                ->previouslyApprovedBeneficiaries()
                ->distinct('student_profile_id')
                ->count('student_profile_id');
            $attributes['available_slots'] = max(0, (int) $attributes['total_slots'] - $approvedCount);

            $academicProgramIds = array_values(array_map('intval', $request->input('academic_program_ids', []) ?: []));
            unset($attributes['academic_program_ids']);
            $attributes['target_course'] = $this->resolveTargetCourse($academicProgramIds);

            $sponsorshipProgram->update($attributes);

            $sponsorshipProgram->academicPrograms()->sync($academicProgramIds);

            if ($shouldExpire) {
                $applicationIds = $sponsorshipProgram->applications()
                    ->whereIn('status', [
                        ApplicationStatus::Approved,
                        ApplicationStatus::Ongoing,
                    ])
                    ->pluck('id');

                if ($applicationIds->isNotEmpty()) {
                    $sponsorshipProgram->applications()
                        ->whereKey($applicationIds)
                        ->update(['status' => ApplicationStatus::Expired]);

                    StudentProfile::query()
                        ->whereIn('active_sponsorship_id', $applicationIds)
                        ->update(['active_sponsorship_id' => null]);
                }
            }
        });

        $this->audit($request, 'fassg.program.updated', 'sponsorship_programs');

        if ($shouldOpen && ! $wasOpen) {
            $this->notifyEligibleStudents($sponsorshipProgram->fresh());
        }

        return redirect()
            ->route('fassg.programs.index')
            ->with('status', "Program {$sponsorshipProgram->program_name} was updated.");
    }

    /**
     * Derive the legacy free-text course target from the selected academic programs.
     *
     * Exactly one selected course -> its full program name stored in target_course;
     * none or multiple selections -> null, so eligibility resolves through the
     * program_academic_program pivot instead of the free-text fallback.
     *
     * @param list<int> $academicProgramIds
     */
    private function resolveTargetCourse(array $academicProgramIds): ?string
    {
        if (count($academicProgramIds) !== 1) {
            return null;
        }

        return AcademicProgram::query()
            ->where('program_id', $academicProgramIds[0])
            ->value('name');
    }

    private function availableSponsors()
    {
        User::query()
            ->where('role', UserRole::Sponsor)
            ->each(function (User $user): void {
                Sponsor::query()->updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'company_organization_name' => $user->name,
                        'contact_person' => $user->name,
                        'contact_email' => $user->email,
                    ],
                );
            });

        return Sponsor::query()
            ->whereNotIn('company_organization_name', ['A', 'B'])
            ->whereNotNull('company_organization_name')
            ->whereHas('user', fn($query) => $query
                ->where('role', UserRole::Sponsor)
                ->where('status', UserStatus::Active))
            ->orderBy('company_organization_name')
            ->get();
    }

    private function availableAcademicPrograms()
    {
        return AcademicProgram::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get();
    }

    public function open(Request $request, SponsorshipProgram $sponsorshipProgram): RedirectResponse
    {
        $newAvailableSlots = max(0, (int) $sponsorshipProgram->total_slots - $sponsorshipProgram->filled_slots);

        $sponsorshipProgram->update([
            'status' => ProgramStatus::Open,
            'available_slots' => $newAvailableSlots,
        ]);

        $this->audit($request, 'fassg.program.opened', 'sponsorship_programs');

        $this->notifyEligibleStudents($sponsorshipProgram->fresh());

        return back()->with('status', "Program {$sponsorshipProgram->program_name} is now open.");
    }

    public function reopen(Request $request, SponsorshipProgram $sponsorshipProgram): RedirectResponse
    {
        $newAvailableSlots = max(0, (int) $sponsorshipProgram->total_slots - $sponsorshipProgram->filled_slots);

        if ($newAvailableSlots <= 0) {
            return back()->with('error', 'Cannot reopen a program with 0 available slots. Please edit available slots first.');
        }

        $sponsorshipProgram->update([
            'status' => ProgramStatus::Open,
            'available_slots' => $newAvailableSlots,
        ]);

        $this->audit($request, 'fassg.program.reopened', 'sponsorship_programs');

        $this->notifyEligibleStudents($sponsorshipProgram->fresh());

        return back()->with('success', 'Program successfully reopened for student applications.');
    }

    public function close(Request $request, SponsorshipProgram $sponsorshipProgram): RedirectResponse
    {
        $sponsorshipProgram->update(['status' => ProgramStatus::Closed]);

        $this->audit($request, 'fassg.program.closed', 'sponsorship_programs');

        return back()->with('status', "Program {$sponsorshipProgram->program_name} is now closed.");
    }

    public function expire(Request $request, SponsorshipProgram $sponsorshipProgram): RedirectResponse
    {
        DB::transaction(function () use ($sponsorshipProgram): void {
            $sponsorshipProgram->update(['status' => ProgramStatus::Expired]);
        });

        $this->audit($request, 'fassg.program.expired', 'sponsorship_programs');

        return back()->with('status', "Program {$sponsorshipProgram->program_name} has expired.");
    }

    public function toggleStatus(Request $request, SponsorshipProgram $sponsorshipProgram): RedirectResponse
    {
        return $sponsorshipProgram->isOpen()
            ? $this->close($request, $sponsorshipProgram)
            : $this->open($request, $sponsorshipProgram);
    }

    public function destroy(Request $request, SponsorshipProgram $sponsorshipProgram): RedirectResponse
    {
        if ($sponsorshipProgram->applications()->exists()) {
            return back()->withErrors([
                'program' => 'Cannot delete a program that already has applications.',
            ]);
        }

        $name = $sponsorshipProgram->program_name;
        $sponsorshipProgram->delete();

        $this->audit($request, 'fassg.program.deleted', 'sponsorship_programs');

        return redirect()
            ->route('fassg.programs.index')
            ->with('status', "Program {$name} was deleted.");
    }

    /**
     * Notify eligible active students that a program has just been opened.
     */
    private function notifyEligibleStudents(SponsorshipProgram $program): void
    {
        $eligibleProfiles = StudentProfile::query()
            ->with('user')
            ->whereHas('user', fn ($query) => $query
                ->where('role', UserRole::Student)
                ->where('status', UserStatus::Active))
            ->get()
            ->reject(fn (StudentProfile $profile) => $program->hasActiveApplicationForStudent($profile->id));

        foreach ($eligibleProfiles as $profile) {
            $result = $program->checkEligibility($profile);

            if ($result['is_eligible'] && $profile->user !== null) {
                $profile->user->notify(new NewSponsorshipProgramOpened($program));
            }
        }
    }
}
