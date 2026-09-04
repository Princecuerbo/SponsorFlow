@extends('layouts.app')

@section('title', 'Student verification')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div>
            <p class="text-uppercase small fw-semibold text-success mb-2">Student profile</p>
            <h1 class="display-6 fw-bold mb-1">Verify your identity</h1>
            <p class="text-secondary mb-0">Keep your student details current before submitting an application.</p>
        </div>
        <span class="badge rounded-pill {{ $profile?->is_sle_fhe_verified ? 'text-bg-success' : 'text-bg-warning text-dark' }} px-3 py-2">
            <i class="bi {{ $profile?->is_sle_fhe_verified ? 'bi-patch-check' : 'bi-hourglass-split' }} me-1"></i>
            SLE-FHE {{ $profile?->is_sle_fhe_verified ? 'verified' : 'pending' }}
        </span>
    </div>

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4 p-lg-5">
                    <form method="POST" action="{{ route('student.verification.update') }}" class="row g-3">
        @csrf
        @method('PUT')
                        <div class="col-md-6"><label class="form-label" for="student_id_number">Student ID number</label><input class="form-control" id="student_id_number" name="student_id_number" value="{{ old('student_id_number', $profile?->student_id_number) }}" required></div>
                        <div class="col-md-6"><label class="form-label" for="course">Course</label><input class="form-control" id="course" name="course" value="{{ old('course', $profile?->course) }}" required></div>
                        <div class="col-md-6"><label class="form-label" for="year_level">Year level</label><input class="form-control" id="year_level" name="year_level" type="number" min="1" max="5" value="{{ old('year_level', $profile?->year_level) }}" required></div>
                        <div class="col-md-6"><label class="form-label" for="birthdate">Birthdate</label><input class="form-control" id="birthdate" name="birthdate" type="date" value="{{ old('birthdate', optional($profile?->birthdate)->format('Y-m-d')) }}"></div>
                        <div class="col-12"><label class="form-label" for="address">Complete address</label><input class="form-control" id="address" name="address" value="{{ old('address', $profile?->address) }}" required></div>
                        <div class="col-md-7"><label class="form-label" for="barangay">Barangay</label><input class="form-control" id="barangay" name="barangay" value="{{ old('barangay', $profile?->barangay) }}" required></div>
                        <div class="col-md-5 d-flex align-items-end"><div class="form-check mb-2"><input class="form-check-input" id="is_rural" type="checkbox" name="is_rural" value="1" @checked(old('is_rural', $profile?->is_rural))><label class="form-check-label" for="is_rural">I live in a rural area</label></div></div>
                        <div class="col-12 pt-2"><button class="btn btn-success px-4" type="submit"><i class="bi bi-shield-check me-2"></i>Save and check status</button></div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-xl-4"><div class="bg-white border rounded-4 p-4 h-100"><i class="bi bi-info-circle text-success fs-3"></i><h2 class="h5 mt-3">Why we verify</h2><p class="text-secondary mb-0">Your student ID and SLE-FHE status help FASSG match your application against approved beneficiary records.</p></div></div>
    </div>
@endsection
