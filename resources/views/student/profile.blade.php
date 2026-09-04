@extends('layouts.app')

@section('title', 'My Profile')
@section('eyebrow', 'Student Portal')
@section('page-title', 'My Profile')

@php
    $nameParts = preg_split('/\s+/', trim($user->name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $firstName = $nameParts[0] ?? 'N/A';
    $lastName = count($nameParts) > 1 ? end($nameParts) : 'N/A';
    $middleName = count($nameParts) > 2 ? implode(' ', array_slice($nameParts, 1, -1)) : 'N/A';
@endphp

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
        <div>
            <h2 class="h4 sf-heading mb-1">Account profile</h2>
            <p class="text-secondary small mb-0">Review your student information and manage account security.</p>
        </div>
    </div>

    <div class="border-bottom mb-3">
        <div class="d-flex gap-2" role="tablist" aria-label="Profile sections">
            <button id="tab-profile-btn" type="button" class="btn btn-sm btn-sf-navy rounded-bottom-0" role="tab"
                aria-selected="true">Profile</button>
            <button id="tab-password-btn" type="button" class="btn btn-sm btn-light border rounded-bottom-0" role="tab"
                aria-selected="false">Change Password</button>
        </div>
    </div>

    <section id="section-profile" role="tabpanel">
        <div class="sf-card bg-white p-4 mb-3 text-center">
            <img class="rounded-circle border border-2 border-warning" style="width:112px;height:112px;object-fit:cover;"
                src="{{ $user->profile_photo_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&background=FFC72C&color=002B66&size=224' }}"
                alt="{{ $user->name }} profile photo">
            <h3 class="h5 fw-bold mt-3 mb-1">{{ $user->name }}</h3>
            <p class="small text-secondary mb-0">{{ $user->email }}</p>
        </div>

        <div class="row g-3">
            <div class="col-lg-7">
                <div class="sf-card bg-white p-4 h-100">
                    <h3 class="h6 sf-heading mb-3">Student Information</h3>
                    <div class="row g-3">
                        @foreach ([['First Name', $firstName], ['Middle Name', $middleName], ['Last Name', $lastName], ['Suffix', 'N/A']] as [$label, $value])
                            <div class="col-md-6"><label class="form-label small text-secondary"
                                    for="{{ str($label)->slug() }}">{{ $label }}</label><input
                                    id="{{ str($label)->slug() }}" class="form-control bg-light" value="{{ $value }}"
                                    readonly></div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="sf-card bg-white p-4 h-100">
                    <h3 class="h6 sf-heading mb-3">Recent School Credentials</h3>
                    <div class="row g-3">
                        <div class="col-12"><label class="form-label small text-secondary" for="student-id">ID
                                Number</label><input id="student-id" class="form-control bg-light"
                                value="{{ $profile?->student_id_number ?? 'N/A' }}" readonly></div>
                        <div class="col-12"><label class="form-label small text-secondary"
                                for="course">Course</label><input id="course" class="form-control bg-light"
                                value="{{ $profile?->course ?? 'N/A' }}" readonly></div>
                        <div class="col-md-6"><label class="form-label small text-secondary" for="school-year">School
                                Year</label><input id="school-year" class="form-control bg-light" value="N/A" readonly>
                        </div>
                        <div class="col-md-6"><label class="form-label small text-secondary"
                                for="semester">Semester</label><input id="semester" class="form-control bg-light"
                                value="N/A" readonly></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="sf-card bg-white p-4 mt-3">
            <h3 class="h6 sf-heading mb-3">Current Status</h3>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <span class="badge text-bg-primary">Academic Year {{ now()->format('Y') }}</span>
                <span class="badge bg-success text-white">Enrolled</span>
                <span class="small text-secondary">Registered {{ $user->created_at?->format('M d, Y') ?? 'N/A' }}</span>
            </div>
        </div>
    </section>

    <section id="section-password" class="d-none" role="tabpanel" aria-hidden="true">
        <div class="sf-card bg-white p-4">
            <h3 class="h6 sf-heading mb-1">Change Password</h3>
            <p class="small text-secondary mb-4">Use a strong password you do not reuse elsewhere.</p>

            @if ($errors->any())
                <div class="alert alert-danger small">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('student.profile.password.update') }}" class="row g-3">
                @csrf
                @method('PUT')
                <div class="col-12"><label class="form-label" for="profile-email">Email Address</label><input
                        id="profile-email" class="form-control bg-light" value="{{ $user->email }}" disabled></div>
                <div class="col-12"><label class="form-label" for="current-password">Current Password</label><input
                        id="current-password" class="form-control" type="password" name="current_password"
                        autocomplete="current-password" required></div>
                <div class="col-md-6"><label class="form-label" for="new-password">New Password</label><input
                        id="new-password" class="form-control" type="password" name="password"
                        autocomplete="new-password" required></div>
                <div class="col-md-6"><label class="form-label" for="confirm-password">Confirm Password</label><input
                        id="confirm-password" class="form-control" type="password" name="password_confirmation"
                        autocomplete="new-password" required></div>
                <div class="col-12">
                    <div class="rounded-3 border border-warning-subtle bg-warning-subtle p-3 small"><strong>Password
                            guidelines</strong>
                        <ul class="mb-0 mt-2 ps-3">
                            <li>Must be at least 8 characters</li>
                            <li>At least one uppercase letter (A-Z)</li>
                            <li>At least one lowercase letter (a-z)</li>
                            <li>At least one number (0-9)</li>
                            <li>At least one symbol (e.g., !@#$%^&amp;*)</li>
                        </ul>
                    </div>
                </div>
                <div class="col-12 d-flex justify-content-end gap-2"><button id="cancel-password-btn" type="button"
                        class="btn btn-secondary">CANCEL</button><button type="submit"
                        class="btn btn-sf-navy px-4">SAVE</button></div>
            </form>
        </div>
    </section>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const profileTabBtn = document.getElementById('tab-profile-btn');
                const passwordTabBtn = document.getElementById('tab-password-btn');
                const profileSection = document.getElementById('section-profile');
                const passwordSection = document.getElementById('section-password');
                const cancelPasswordBtn = document.getElementById('cancel-password-btn');

                if (!profileTabBtn || !passwordTabBtn || !profileSection || !passwordSection) {
                    return;
                }

                function showSection(section) {
                    const showPassword = section === 'password';
                    profileSection.classList.toggle('d-none', showPassword);
                    passwordSection.classList.toggle('d-none', !showPassword);
                    profileSection.setAttribute('aria-hidden', String(showPassword));
                    passwordSection.setAttribute('aria-hidden', String(!showPassword));
                    profileTabBtn.classList.toggle('btn-sf-navy', !showPassword);
                    profileTabBtn.classList.toggle('btn-light', showPassword);
                    passwordTabBtn.classList.toggle('btn-sf-navy', showPassword);
                    passwordTabBtn.classList.toggle('btn-light', !showPassword);
                    profileTabBtn.setAttribute('aria-selected', String(!showPassword));
                    passwordTabBtn.setAttribute('aria-selected', String(showPassword));
                }

                profileTabBtn.addEventListener('click', function() {
                    showSection('profile');
                });
                passwordTabBtn.addEventListener('click', function() {
                    showSection('password');
                });
                cancelPasswordBtn?.addEventListener('click', function() {
                    showSection('profile');
                });
            });
        </script>
    @endpush
@endsection
