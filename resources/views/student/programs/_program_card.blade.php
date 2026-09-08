@php
    // Determine the eligible courses list from $program->courses, $program->eligible_courses, pivot relation, or target_course
    $coursesCollection = collect();

    if (isset($program->eligible_courses) && filled($program->eligible_courses)) {
        $coursesCollection = is_iterable($program->eligible_courses)
            ? collect($program->eligible_courses)
            : collect([$program->eligible_courses]);
    } elseif (isset($program->courses) && filled($program->courses)) {
        $coursesCollection = is_iterable($program->courses)
            ? collect($program->courses)
            : collect([$program->courses]);
    } elseif ($program->relationLoaded('academicPrograms') ? $program->academicPrograms->isNotEmpty() : $program->academicPrograms()->exists()) {
        $coursesCollection = $program->academicPrograms;
    } elseif (filled($program->target_course)) {
        $coursesCollection = collect(explode(',', (string) $program->target_course));
    }

    $courseDisplayNames = $coursesCollection->map(function ($c) {
        if (is_object($c)) {
            return $c->name ?: ($c->code ?: (string) $c);
        }
        return trim((string) $c);
    })->filter()->values();
@endphp

<div class="col-12 col-md-6 col-lg-4">
    <div class="card sf-card h-100">
        <div class="card-body p-4 d-flex flex-column min-w-0">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="badge rounded-2 px-2.5 py-1.5 fw-medium"
                    style="background-color: rgba(15, 41, 66, 0.08) !important; color: #0F2942 !important;">
                    {{ $program->category->value }}
                </span>
                <x-status-badge :status="$program->status" />
            </div>

            <h3 class="h6 sf-heading mb-1 text-break">{{ $program->program_name }}</h3>
            <div class="small text-secondary mb-3 text-break">
                <i class="bi bi-building me-1"></i>{{ $program->sponsor->company_organization_name }}
            </div>

            <ul class="list-unstyled small mb-3 flex-grow-1">
                <li class="d-flex justify-content-between gap-3 py-1 border-bottom">
                    <span class="text-secondary">Available slots</span>
                    <span class="fw-semibold">{{ $program->available_slots }}</span>
                </li>
                @if ($program->min_gpa)
                    <li class="d-flex justify-content-between gap-3 py-1 border-bottom">
                        <span class="text-secondary">Minimum GPA</span>
                        <span class="fw-semibold">{{ number_format($program->min_gpa, 2) }}</span>
                    </li>
                @endif
                <li class="d-flex justify-content-between gap-3 py-1 border-bottom">
                    <span class="text-secondary">Target course</span>
                    <span class="fw-semibold text-end text-break">
                        @if ($courseDisplayNames->isNotEmpty())
                            {{ $courseDisplayNames->implode(', ') }}
                        @else
                            <span class="text-success fw-semibold">All Courses Allowed</span>
                        @endif
                    </span>
                </li>
                @if ($program->address_requirement)
                    <li class="d-flex justify-content-between gap-3 py-1">
                        <span class="text-secondary">Address requirement</span>
                        <span
                            class="fw-semibold text-end text-break">{{ $program->address_requirement }}</span>
                    </li>
                @endif
            </ul>

            <div class="mt-auto pt-3">
                @if ($profile && $program->hasActiveApplicationForStudent($profile->id))
                    <button type="button" class="btn btn-secondary btn-sm w-100" disabled>
                        <i class="bi bi-check2-circle me-1"></i>Already Applied
                    </button>
                @elseif ($program->available_slots <= 0)
                    <button type="button" class="btn btn-outline-secondary btn-sm w-100" disabled>
                        <i class="bi bi-lock me-1"></i>No Slots Available
                    </button>
                @elseif ($hasActiveSponsorship)
                    <button type="button" class="btn btn-outline-secondary btn-sm w-100" disabled
                        title="You already have an active approved sponsorship.">
                        <i class="bi bi-lock me-1"></i>Active Sponsorship Lock
                    </button>
                @elseif (!$profile?->is_sle_fhe_verified)
                    <button type="button" class="btn btn-outline-secondary btn-sm w-100" disabled
                        title="Applications are unavailable for this program.">
                        <i class="bi bi-lock me-1"></i>Applications Unavailable
                    </button>
                @else
                    <a href="{{ route('student.applications.create', ['sponsorshipProgram' => $program->id]) }}"
                        class="btn btn-navy-primary btn-sm w-100 fw-semibold">
                        <i class="bi bi-pencil-square me-1"></i>Apply Now
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>
