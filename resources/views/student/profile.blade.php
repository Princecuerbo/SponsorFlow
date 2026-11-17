@extends('layouts.app')

@section('title', 'My Profile')
@section('eyebrow', 'Student Portal · Profile')
@section('page-title', 'My Profile')
@section('subtitle', 'Review your student information and manage account security.')

@php
    $firstName = $studentProfile?->first_name;
    $middleName = $studentProfile?->middle_name;
    $lastName = $studentProfile?->last_name;
    $extensionName = $studentProfile?->extension_name;
    $course = $studentProfile?->display_course;
    $initials = mb_strtoupper(mb_substr($firstName ?? ($user->name[0] ?? ''), 0, 1))
        . mb_strtoupper(mb_substr($lastName ?? (substr($user->name, -1) ?? ''), 0, 1));
    $initials = $initials ?: 'S';
    $academicYear = now()->year . '-' . (now()->year + 1);
    $rawStatus = $user->status?->value ?? ($user->status ?? 'Active');
    $isActiveStatus = strtolower($rawStatus) === 'active';
    $sleStatus = $studentProfile?->sle_fhe_status ?? 'Unverified';
@endphp

@section('content')
    {{-- Full-width Navy Profile Banner --}}
    <div class="rounded-4 mb-4" style="border: 1px solid var(--sf-border-color, #E1E8EC);">
        <div class="position-relative text-white px-4 py-4 rounded-4"
            style="background: linear-gradient(135deg, #0F2942 0%, #1A3E5E 100%);">
            <div class="d-flex flex-wrap align-items-center gap-3">
                {{-- Avatar --}}
                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                    style="width: 68px; height: 68px; font-size: 1.5rem; font-weight: 700; background: rgba(255,255,255,0.15); color: #FFFFFF; border: 2.5px solid rgba(255,255,255,0.4);">
                    {{ $initials }}
                </div>
                {{-- Name & Role --}}
                <div class="min-w-0 flex-fill">
                    <h2 class="h5 fw-bold text-white mb-1">{{ $user->name }}</h2>
                    <p class="mb-0.5 small text-white-50">Student Applicant</p>
                    <p class="mb-0 small text-white-50">{{ $course ?? 'Academic Program Not Set' }}</p>
                </div>
                {{-- Status Badges --}}
                <div class="d-flex flex-wrap align-items-center gap-2 mt-2 mt-md-0">
                    @php
                        $sleBadgeBg = match($sleStatus) {
                            'Verified' => 'rgba(16, 185, 129, 0.25)',
                            'Pending Review' => 'rgba(245, 158, 11, 0.25)',
                            'Rejected' => 'rgba(239, 68, 68, 0.25)',
                            default => 'rgba(148, 163, 184, 0.25)',
                        };
                    @endphp
                    <span class="badge rounded-pill border" style="background: {{ $sleBadgeBg }}; border-color: rgba(255,255,255,0.2) !important; color: #FFFFFF; font-size: 0.75rem; padding: 0.35rem 0.75rem;">
                        SLE-FHE: {{ $sleStatus }}
                    </span>
                    <span class="badge rounded-pill border" style="background: rgba(255,255,255,0.15); border-color: rgba(255,255,255,0.2) !important; color: #FFFFFF; font-size: 0.75rem; padding: 0.35rem 0.75rem;">
                        Batch {{ $studentProfile?->created_at?->format('Y') ?? '2024' }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Student Information Card --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4" style="border: 1px solid var(--sf-border-color, #E1E8EC) !important;">
        <div class="card-header bg-white border-bottom px-4 py-3">
            <div class="d-flex align-items-center gap-3">
                <span class="rounded-3 d-inline-flex align-items-center justify-content-center flex-shrink-0"
                    style="width: 40px; height: 40px; background-color: #E8F0F6; color: #0F2942;">
                    <i class="bi bi-person-circle fs-5"></i>
                </span>
                <div>
                    <h3 class="h6 sf-heading mb-0 fw-bold" style="color: #0F2942;">Student Information</h3>
                    <p class="text-secondary small mb-0 mt-0.5" style="font-size: 0.775rem;">Your institutional student record</p>
                </div>
            </div>
        </div>

        <div class="card-body p-4">
            {{-- Personal Information --}}
            <h4 class="h6 sf-heading mb-3 fw-semibold text-dark">Personal Information</h4>
            <div class="row g-3">
                @foreach ([['First Name', $firstName], ['Middle Name', $middleName ?? 'N/A'], ['Last Name', $lastName], ['Suffix / Ext. Name', $extensionName ?? 'N/A']] as [$label, $value])
                    <div class="col-md-6">
                        <label class="form-label text-secondary small fw-semibold mb-1.5"
                            for="{{ str($label)->slug() }}">{{ $label }}</label>
                        <input id="{{ str($label)->slug() }}" class="form-control rounded-3"
                            style="background-color: #F8FAFC; border-color: #E1E8EC; height: 42px; font-size: 0.875rem;"
                            value="{{ $value }}" readonly>
                    </div>
                @endforeach
                <div class="col-md-6">
                    <label class="form-label text-secondary small fw-semibold mb-1.5" for="sex-gender">Sex / Gender</label>
                    <input id="sex-gender" class="form-control rounded-3"
                        style="background-color: #F8FAFC; border-color: #E1E8EC; height: 42px; font-size: 0.875rem;"
                        value="{{ $studentProfile?->gender ?? 'N/A' }}" readonly>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-secondary small fw-semibold mb-1.5" for="municipality">Residential Address</label>
                    <input id="municipality" class="form-control rounded-3"
                        style="background-color: #F8FAFC; border-color: #E1E8EC; height: 42px; font-size: 0.875rem;"
                        value="{{ $studentProfile?->full_address ?: 'N/A' }}" readonly>
                </div>
            </div>

            <hr class="my-4" style="border-color: #E1E8EC;">

            {{-- Academic Information --}}
            <h4 class="h6 sf-heading mb-3 fw-semibold text-dark">Academic Information</h4>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label text-secondary small fw-semibold mb-1.5" for="student-id">Student ID</label>
                    <input id="student-id" class="form-control rounded-3 font-monospace"
                        style="background-color: #F8FAFC; border-color: #E1E8EC; height: 42px; font-size: 0.875rem;"
                        value="{{ $studentProfile?->student_id_number ?? 'N/A' }}" readonly>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-secondary small fw-semibold mb-1.5" for="course">Academic Program</label>
                    <input id="course" class="form-control rounded-3"
                        style="background-color: #F8FAFC; border-color: #E1E8EC; height: 42px; font-size: 0.875rem;"
                        value="{{ $course ?? 'N/A' }}" readonly>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-secondary small fw-semibold mb-1.5" for="year-level">Year Level</label>
                    <input id="year-level" class="form-control rounded-3"
                        style="background-color: #F8FAFC; border-color: #E1E8EC; height: 42px; font-size: 0.875rem;"
                        value="{{ $studentProfile?->year_level ? 'Year ' . $studentProfile->year_level : 'N/A' }}" readonly>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-secondary small fw-semibold mb-1.5" for="campus">Campus</label>
                    <input id="campus" class="form-control rounded-3"
                        style="background-color: #F8FAFC; border-color: #E1E8EC; height: 42px; font-size: 0.875rem;"
                        value="{{ $studentProfile?->campus ?? 'Main Campus' }}" readonly>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-secondary small fw-semibold mb-1.5" for="school-year">Academic Year</label>
                    <input id="school-year" class="form-control rounded-3"
                        style="background-color: #F8FAFC; border-color: #E1E8EC; height: 42px; font-size: 0.875rem;"
                        value="{{ $academicYear }}" readonly>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-secondary small fw-semibold mb-1.5" for="semester">Semester</label>
                    <input id="semester" class="form-control rounded-3"
                        style="background-color: #F8FAFC; border-color: #E1E8EC; height: 42px; font-size: 0.875rem;"
                        value="First Semester" readonly>
                </div>
            </div>

            <hr class="my-4" style="border-color: #E1E8EC;">

            {{-- Account Information --}}
            <h4 class="h6 sf-heading mb-3 fw-semibold text-dark">Account Information</h4>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label text-secondary small fw-semibold mb-1.5 d-block">Account Status</label>
                    <div class="d-flex align-items-center" style="height: 42px;">
                        <span class="badge {{ $isActiveStatus ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary' }} rounded-pill px-2.5 py-1.5 fw-semibold" style="font-size: 0.75rem;">
                            {{ strtoupper($rawStatus) }}
                        </span>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-secondary small fw-semibold mb-1.5 d-block">Date Registered</label>
                    <div class="d-flex align-items-center" style="height: 42px;">
                        <span class="fw-semibold text-dark" style="font-size: 0.875rem;">{{ $studentProfile?->created_at?->format('M d, Y, h:i A') ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Account Security Card --}}
    <div class="card border-0 shadow-sm rounded-4" style="border: 1px solid var(--sf-border-color, #E1E8EC) !important;">
        <div class="card-header bg-white border-bottom px-4 py-3">
            <div class="d-flex align-items-center gap-3">
                <span class="rounded-3 d-inline-flex align-items-center justify-content-center flex-shrink-0"
                    style="width: 40px; height: 40px; background-color: #E8F0F6; color: #0F2942;">
                    <i class="bi bi-shield-lock fs-5"></i>
                </span>
                <div>
                    <h3 class="h6 sf-heading mb-0 fw-bold" style="color: #0F2942;">Account Security</h3>
                    <p class="text-secondary small mb-0 mt-0.5" style="font-size: 0.775rem;">Update your login password</p>
                </div>
            </div>
        </div>

        <div class="card-body p-4">
            @if ($errors->any())
                <div class="alert alert-danger small rounded-3 mb-3">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('student.profile.password.update') }}" class="row g-3">
                @csrf
                @method('PUT')

                {{-- Current Password --}}
                <div class="col-12">
                    <label class="form-label text-secondary small fw-semibold mb-1.5" for="current_password">CURRENT PASSWORD</label>
                    <div class="position-relative">
                        <div class="position-absolute top-50 translate-middle-y ps-3 pointer-events-none text-muted" style="left: 0;">
                            <svg style="width:1.1rem;height:1.1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                            </svg>
                        </div>
                        <input
                            type="password"
                            id="current_password"
                            name="current_password"
                            autocomplete="current-password"
                            required
                            class="form-control rounded-3"
                            style="padding-left: 2.5rem; padding-right: 2.75rem; background-color: #F8FAFC; border-color: #E1E8EC; height: 42px; font-size: 0.875rem; transition: all .15s ease;"
                        >
                        <button
                            type="button"
                            onclick="togglePasswordVisibility('current_password', this)"
                            class="position-absolute top-50 translate-middle-y end-0 pe-3 border-0 bg-transparent text-muted d-flex align-items-center"
                            style="cursor:pointer;"
                            aria-label="Toggle password visibility"
                        >
                            <svg class="eye-icon" style="width:1.1rem;height:1.1rem;pointer-events:none;" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- New Password --}}
                <div class="col-md-6">
                    <label class="form-label text-secondary small fw-semibold mb-1.5" for="new_password">NEW PASSWORD</label>
                    <div class="position-relative">
                        <div class="position-absolute top-50 translate-middle-y ps-3 pointer-events-none text-muted" style="left: 0;">
                            <svg style="width:1.1rem;height:1.1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <input
                            type="password"
                            id="new_password"
                            name="password"
                            autocomplete="new-password"
                            required
                            class="form-control rounded-3"
                            style="padding-left: 2.5rem; padding-right: 2.75rem; background-color: #F8FAFC; border-color: #E1E8EC; height: 42px; font-size: 0.875rem; transition: all .15s ease;"
                        >
                        <button
                            type="button"
                            onclick="togglePasswordVisibility('new_password', this)"
                            class="position-absolute top-50 translate-middle-y end-0 pe-3 border-0 bg-transparent text-muted d-flex align-items-center"
                            style="cursor:pointer;"
                            aria-label="Toggle password visibility"
                        >
                            <svg class="eye-icon" style="width:1.1rem;height:1.1rem;pointer-events:none;" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Confirm Password --}}
                <div class="col-md-6">
                    <label class="form-label text-secondary small fw-semibold mb-1.5" for="new_password_confirmation">CONFIRM PASSWORD</label>
                    <div class="position-relative">
                        <div class="position-absolute top-50 translate-middle-y ps-3 pointer-events-none text-muted" style="left: 0;">
                            <svg style="width:1.1rem;height:1.1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <input
                            type="password"
                            id="new_password_confirmation"
                            name="password_confirmation"
                            autocomplete="new-password"
                            required
                            class="form-control rounded-3"
                            style="padding-left: 2.5rem; padding-right: 2.75rem; background-color: #F8FAFC; border-color: #E1E8EC; height: 42px; font-size: 0.875rem; transition: all .15s ease;"
                        >
                        <button
                            type="button"
                            onclick="togglePasswordVisibility('new_password_confirmation', this)"
                            class="position-absolute top-50 translate-middle-y end-0 pe-3 border-0 bg-transparent text-muted d-flex align-items-center"
                            style="cursor:pointer;"
                            aria-label="Toggle password visibility"
                        >
                            <svg class="eye-icon" style="width:1.1rem;height:1.1rem;pointer-events:none;" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Password Guidelines --}}
                <div class="col-12">
                    <div class="rounded-3 p-3.5 small"
                        style="background-color: #E8F0F6; border: 1px solid #D2E1ED; color: #0F2942;">
                        <div class="d-flex align-items-center gap-2 fw-bold mb-2">
                            <i class="bi bi-info-circle-fill" style="color: #173B57;"></i>
                            <span>Password Guidelines</span>
                        </div>
                        <ul class="mb-0 ps-3 text-secondary" style="line-height: 1.6;">
                            <li>Must be at least 8 characters</li>
                            <li>At least one uppercase letter (A-Z)</li>
                            <li>At least one lowercase letter (a-z)</li>
                            <li>At least one number (0-9)</li>
                            <li>At least one symbol (e.g., !@#$%^&amp;*)</li>
                        </ul>
                    </div>
                </div>

                {{-- Submit Button --}}
                <div class="col-12 d-flex justify-content-end pt-3">
                    <button type="submit" class="btn btn-primary px-4 py-2.5 rounded-3 shadow-sm fw-semibold"
                        style="background-color: #0F2942 !important; border-color: #0F2942 !important; min-height: 42px;">
                        <i class="bi bi-check2 me-1"></i>
                        Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
