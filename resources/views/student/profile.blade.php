@extends('layouts.app')

@section('title', 'My Profile')
@section('eyebrow', 'Student Portal')
@section('page-title', 'My Profile')

@php
    $nameParts = preg_split('/\s+/', trim($user->name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $firstName = $nameParts[0] ?? 'N/A';
    $lastName = count($nameParts) > 1 ? end($nameParts) : 'N/A';
    $middleName = count($nameParts) > 2 ? implode(' ', array_slice($nameParts, 1, -1)) : 'N/A';
    $academicYear = now()->year . '-' . (now()->year + 1);
@endphp

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
        <div>
            <h2 class="h4 sf-heading mb-1">Account profile</h2>
            <p class="text-secondary small mb-0">Review your student information and manage account security.</p>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-lg-4 align-self-start">
            <div class="card border-0 shadow-sm overflow-hidden">
                <div class="bg-primary text-white text-center px-4 pt-4 pb-5"
                    style="background: linear-gradient(135deg, #0f294a, #1e4b7a) !important;">
                    <span class="small text-uppercase fw-semibold opacity-75">Student Portal</span>
                    <h3 class="h6 mb-0 mt-1">My Profile</h3>
                </div>
                <div class="card-body text-center position-relative pt-0 px-4 pb-4">
                    <div class="position-relative d-inline-block" style="margin-top: -58px;">
                        <img class="rounded-circle border border-4 border-white shadow-sm"
                            style="width:116px;height:116px;object-fit:cover;"
                            src="{{ $user->profile_photo_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&background=FFC72C&color=002B66&size=232' }}"
                            alt="{{ $user->name }} profile photo">
                        <button type="button"
                            class="btn btn-sm btn-sf-navy rounded-circle position-absolute bottom-0 end-0 shadow"
                            aria-label="Profile photo options" title="Profile photo options">
                            <i class="bi bi-camera-fill"></i>
                        </button>
                    </div>
                    <h3 class="h5 fw-bold mt-3 mb-1">{{ $user->name }}</h3>
                    <p class="text-muted small mb-3">{{ $profile?->course ?? 'Student' }}</p>
                    <div class="d-flex flex-wrap justify-content-center gap-2">
                        <span class="badge rounded-pill text-bg-primary">Batch
                            {{ $profile?->created_at?->format('Y') ?? '2024' }}</span>
                        <span
                            class="badge rounded-pill bg-success">{{ $profile?->is_sle_fhe_verified ? 'SLE-FHE Verified' : 'Main Campus' }}</span>
                    </div>
                    <hr class="my-4">
                    <div class="d-flex justify-content-between small text-muted">
                        <span>Student ID</span>
                        <span class="fw-semibold text-dark">{{ $profile?->student_id_number ?? 'N/A' }}</span>
                    </div>
                    <div class="d-flex justify-content-between small text-muted mt-2">
                        <span>Account Status</span>
                        <span class="fw-semibold text-dark">{{ $user->status?->value ?? ($user->status ?? 'Active') }}</span>
                    </div>
                    <div class="d-flex justify-content-between small text-muted mt-2">
                        <span>Campus</span>
                        <span class="fw-semibold text-dark">Main Campus</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 p-4 pb-0">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <span
                                class="rounded-3 bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center"
                                style="width:42px;height:42px;"><i class="bi bi-person-fill fs-5"></i></span>
                            <div>
                                <h3 class="h5 fw-bold mb-1">Personal Information</h3>
                                <p class="text-muted small mb-0">Your institutional student record</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3">Student Information</h6>
                    <div class="row g-3">
                        @foreach ([['First Name', $firstName], ['Middle Name', $middleName], ['Last Name', $lastName], ['Suffix', 'N/A']] as [$label, $value])
                            <div class="col-md-6">
                                <label class="form-label text-muted small fw-bold"
                                    for="{{ str($label)->slug() }}">{{ $label }}</label>
                                <input id="{{ str($label)->slug() }}" class="form-control bg-light rounded-3"
                                    value="{{ $value }}" readonly>
                            </div>
                        @endforeach
                    </div>

                    <hr class="my-4">
                    <h6 class="fw-bold mb-3">Recent School Credentials</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-bold" for="student-id">ID Number</label>
                            <input id="student-id" class="form-control bg-light rounded-3"
                                value="{{ $profile?->student_id_number ?? 'N/A' }}" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-bold" for="course">COURSE</label>
                            <input id="course" class="form-control bg-light rounded-3"
                                value="{{ $profile?->course ?? 'N/A' }}" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-bold" for="school-year">SCHOOL YEAR</label>
                            <input id="school-year" class="form-control bg-light rounded-3" value="{{ $academicYear }}"
                                readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-bold" for="semester">SEMESTER</label>
                            <input id="semester" class="form-control bg-light rounded-3" value="First Semester" readonly>
                        </div>
                    </div>

                    <hr class="my-4">
                    <h6 class="fw-bold mb-3">Current Status</h6>
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="small text-muted">Current Academic Year and Semester:</div>
                            <div class="fw-semibold text-dark">{{ $academicYear }}, First Semester</div>
                        </div>
                        <div class="col-md-6">
                            <div class="small text-muted mb-1">STATUS</div>
                            <span class="badge bg-success">ENROLLED</span>
                        </div>
                        <div class="col-md-6">
                            <div class="small text-muted mb-1">DATE REGISTERED</div>
                            <div class="fw-semibold text-dark">{{ $user->created_at?->format('M d, Y') ?? 'N/A' }}</div>
                        </div>
                    </div>

                    <hr class="my-4">
                    <h6 class="fw-bold mb-3"><i class="bi bi-shield-lock me-2"></i>Account Security</h6>

                    @if ($errors->any())
                        <div class="alert alert-danger small">{{ $errors->first() }}</div>
                    @endif

                    <form method="POST" action="{{ route('student.profile.password.update') }}" class="row g-3">
                        @csrf
                        @method('PUT')
                        <div class="col-12">
                            <label class="form-label text-muted small fw-bold" for="current-password">CURRENT
                                PASSWORD</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-key-fill text-muted"></i></span>
                                <input id="current-password" class="form-control rounded-end-3" type="password"
                                    name="current_password" autocomplete="current-password" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-bold" for="new-password">NEW PASSWORD</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-lock text-muted"></i></span>
                                <input id="new-password" class="form-control rounded-end-3" type="password"
                                    name="password" autocomplete="new-password" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-bold" for="confirm-password">CONFIRM
                                PASSWORD</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-lock-fill text-muted"></i></span>
                                <input id="confirm-password" class="form-control rounded-end-3" type="password"
                                    name="password_confirmation" autocomplete="new-password" required>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="rounded-3 p-3 small"
                                style="background-color: rgba(15, 41, 66, 0.08); border: 1px solid rgba(15, 41, 66, 0.2); color: #0F2942;">
                                <strong>Password guidelines</strong>
                                <ul class="mb-0 mt-2 ps-3">
                                    <li>Must be at least 8 characters</li>
                                    <li>At least one uppercase letter (A-Z)</li>
                                    <li>At least one lowercase letter (a-z)</li>
                                    <li>At least one number (0-9)</li>
                                    <li>At least one symbol (e.g., !@#$%^&amp;*)</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-12 d-flex justify-content-end pt-2">
                            <button type="submit" class="btn btn-primary px-4"
                                style="background-color: #0F2942; border-color: #0F2942;"><i
                                    class="bi bi-check2 me-1"></i>
                                Update Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
