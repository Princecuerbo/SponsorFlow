@extends('layouts.app')

@section('title', 'Apply | ' . $program->program_name)

@section('content')
    <div class="mb-4"><a class="text-success text-decoration-none small" href="{{ route('student.programs.index') }}"><i
                class="bi bi-arrow-left me-1"></i>Back to programs</a>
        <p class="text-uppercase small fw-semibold text-success mt-4 mb-2">Application form</p>
        <h1 class="display-6 fw-bold mb-1">{{ $program->program_name }}</h1>
        <p class="text-secondary mb-0">{{ $program->sponsor->company_organization_name }} · {{ $program->category->value }}
        </p>
    </div>
    <div class="row g-4">
        <div class="col-xl-8">
            <form class="card border-0 shadow-sm rounded-4" method="POST" action="{{ route('student.applications.store') }}"
                enctype="multipart/form-data">
                <div class="card-body p-4 p-lg-5">@csrf<input type="hidden" name="sponsorship_program_id"
                        value="{{ $program->id }}">
                    <h2 class="h5 fw-bold mb-3">Academic and financial profile</h2>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label" for="gpa_submitted">Current GWA</label><input
                                class="form-control" id="gpa_submitted" name="gpa_submitted" type="number" step="0.01"
                                min="1" max="5" value="{{ old('gpa_submitted') }}" required>
                            <div class="form-text">Use the Philippine grading scale, where 1.00 is highest.</div>
                        </div>
                        <div class="col-md-6"><label class="form-label" for="address_submitted">Address for this
                                application</label><input class="form-control" id="address_submitted"
                                name="address_submitted" value="{{ old('address_submitted', $profile?->address) }}"
                                required></div>
                        <div class="col-12">
                            <div class="form-check"><input class="form-check-input" id="is_rural_submitted" type="checkbox"
                                    name="is_rural_submitted" value="1" @checked(old('is_rural_submitted', $profile?->is_rural))><label
                                    class="form-check-label" for="is_rural_submitted">I confirm this is a rural residency
                                    application</label></div>
                        </div>
                    </div>
                    <hr class="my-4">
                    <h2 class="h5 fw-bold mb-3">Required documents</h2>
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label" for="certificate_of_grades">Certificate of
                                Grades</label><input class="form-control" id="certificate_of_grades"
                                name="certificate_of_grades" type="file" accept=".pdf,.jpg,.jpeg,.png" required></div>
                        <div class="col-md-4"><label class="form-label" for="proof_of_residence">Proof of
                                Residence</label><input class="form-control" id="proof_of_residence"
                                name="proof_of_residence" type="file" accept=".pdf,.jpg,.jpeg,.png" required></div>
                        <div class="col-md-4"><label class="form-label" for="barangay_cert">Barangay
                                Certificate</label><input class="form-control" id="barangay_cert" name="barangay_cert"
                                type="file" accept=".pdf,.jpg,.jpeg,.png" required></div>
                    </div><button class="btn btn-success mt-4 px-4" type="submit"><i class="bi bi-send me-2"></i>Submit
                        application</button>
                </div>
            </form>
        </div>
        <div class="col-xl-4">
            <div class="bg-white border rounded-4 p-4">
                <h2 class="h5 fw-bold">Before you submit</h2>
                <ul class="text-secondary ps-3 mb-0">
                    <li class="mb-2">Review your GWA and address carefully.</li>
                    <li class="mb-2">Upload clear, readable files.</li>
                    <li>FASSG will verify your records before approval.</li>
                </ul>
            </div>
        </div>
    </div>
@endsection
