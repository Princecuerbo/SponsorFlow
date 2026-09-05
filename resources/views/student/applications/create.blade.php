@extends('layouts.app')

@section('title', 'New Application')
@section('eyebrow', 'Student Portal · ' . $program->program_name)
@section('page-title', 'Apply for Sponsorship')

@push('styles')
    <style>
        /* Primary Navy Styling for Step Badges */
        .sf-step-badge {
            background-color: #0F2942 !important;
            color: #ffffff !important;
            width: 26px;
            height: 26px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        /* Primary Navy Submit Button */
        .btn-navy-submit,
        button.btn-navy-submit {
            background-color: #0F2942 !important;
            border-color: #0F2942 !important;
            color: #ffffff !important;
            font-weight: 600;
            box-shadow: none !important;
            transition: all 0.2s ease-in-out;
        }

        .btn-navy-submit:hover,
        .btn-navy-submit:focus,
        button.btn-navy-submit:hover,
        button.btn-navy-submit:focus {
            background-color: #0A1E31 !important;
            border-color: #0A1E31 !important;
            color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(15, 41, 66, 0.15) !important;
        }

        /* Switch Focus/Checked Accent */
        .form-check-input:checked {
            background-color: #0F2942 !important;
            border-color: #0F2942 !important;
        }
    </style>
@endpush

@section('content')

    @php $profile = auth()->user()->studentProfile; @endphp

    @if ($profile->hasActiveSponsorship())
        <div class="alert alert-danger border-0 shadow-sm rounded-3">
            <i class="bi bi-exclamation-octagon-fill me-1"></i>
            You already have an active sponsorship. Only one active sponsorship is allowed at a time — you may re-apply once
            it expires.
            <a href="{{ route('student.applications.index') }}" class="alert-link">View my applications</a>
        </div>
    @else
        <form method="POST" action="{{ route('student.applications.store') }}" enctype="multipart/form-data" class="row g-4">
            @csrf
            <input type="hidden" name="sponsorship_program_id" value="{{ $program->id }}">

            <div class="col-lg-8">

                {{-- Section 1: Profile summary --}}
                <div class="card sf-card mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="badge rounded-circle sf-step-badge">1</span>
                            <h2 class="h6 sf-heading mb-0">Student &amp; SLE-FHE Profile</h2>
                        </div>
                        <p class="small text-secondary mb-3">Pulled from your verified profile. To change any of this,
                            update your profile before applying.</p>

                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label small text-secondary">Student ID Number</label>
                                <input type="text" class="form-control sf-mono" value="{{ $profile->student_id_number }}"
                                    disabled>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label small text-secondary">Full Name</label>
                                <input type="text" class="form-control" value="{{ auth()->user()->name }}" disabled>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label small text-secondary">Course</label>
                                <input type="text" class="form-control" value="{{ $profile->course }}" disabled>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label small text-secondary">SLE-FHE Status</label>
                                <div class="pt-2"><x-status-badge status="Verified" /></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Section 2: Encoded inputs --}}
                <div class="card sf-card mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="badge rounded-circle sf-step-badge">2</span>
                            <h2 class="h6 sf-heading mb-0">Application Details</h2>
                        </div>

                        <div class="row g-3">
                            <div class="col-sm-4">
                                <label for="current_gpa" class="form-label small text-secondary">Current GPA <span
                                        class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="1" max="5" name="current_gpa"
                                    id="current_gpa" value="{{ old('current_gpa', old('gpa_submitted')) }}"
                                    class="form-control @error('current_gpa') is-invalid @enderror" required>
                                @if ($program->min_gpa)
                                    <div class="form-text">Program requires {{ number_format($program->min_gpa, 2) }} or
                                        better.</div>
                                @endif
                                @error('current_gpa')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-sm-8">
                                <label for="current_address" class="form-label small text-secondary">Current Address <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="current_address" id="current_address"
                                    value="{{ old('current_address', old('address_submitted', $profile->address)) }}"
                                    class="form-control @error('current_address') is-invalid @enderror" required>
                                @error('current_address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" name="is_rural_submitted"
                                        id="is_rural_submitted" value="1"
                                        {{ old('is_rural_submitted', $profile->is_rural) ? 'checked' : '' }}>
                                    <label class="form-check-label small" for="is_rural_submitted">
                                        I confirm the address above is classified as a <strong>rural</strong> residence
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Section 3: Document uploads --}}
                <div class="card sf-card">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="badge rounded-circle sf-step-badge">3</span>
                            <h2 class="h6 sf-heading mb-0">Supporting Documents</h2>
                        </div>
                        <p class="small text-secondary mb-3">Accepted formats: PDF, JPG, PNG · Max 5MB each.</p>

                        @php
                            $docs = [
                                'grade_slip' => ['Grade Slip', 'bi-mortarboard'],
                                'proof_of_residence' => ['Proof of Residence', 'bi-house-door'],
                                'barangay_certification' => ['Barangay Certification', 'bi-file-earmark-check'],
                            ];
                        @endphp

                        <div class="row g-3">
                            @foreach ($docs as $field => [$label, $icon])
                                <div class="col-md-4">
                                    <label class="form-label small text-secondary d-block">{{ $label }} <span
                                            class="text-danger">*</span></label>
                                    <label for="{{ $field }}"
                                        class="d-block border border-2 border-dashed rounded-3 text-center p-4 bg-light"
                                        style="cursor:pointer; border-style:dashed !important;">
                                        <i class="bi {{ $icon }} fs-3 text-secondary d-block mb-2"></i>
                                        <span class="small fw-semibold d-block">Click to upload</span>
                                        <span class="small text-secondary" data-filename-for="{{ $field }}">or drag
                                            file here</span>
                                        <input type="file" name="{{ $field }}" id="{{ $field }}"
                                            class="d-none" accept=".pdf,.jpg,.jpeg,.png"
                                            onchange="updateSelectedFile(this)">
                                    </label>
                                    @error($field)
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- Side summary / submit --}}
            <div class="col-lg-4">
                <div class="card sf-card position-sticky" style="top:5.5rem;">
                    <div class="card-body p-4">
                        <h2 class="h6 sf-heading mb-3">{{ $program->program_name }}</h2>
                        <ul class="list-unstyled small mb-4">
                            <li class="d-flex justify-content-between py-1 border-bottom">
                                <span class="text-secondary">Sponsor</span>
                                <span class="fw-semibold">{{ $program->sponsor->company_organization_name }}</span>
                            </li>
                            <li class="d-flex justify-content-between py-1 border-bottom">
                                <span class="text-secondary">Category</span>
                                <span class="fw-semibold">{{ $program->category->value }}</span>
                            </li>
                            <li class="d-flex justify-content-between py-1">
                                <span class="text-secondary">Available slots</span>
                                <span class="fw-semibold">{{ $program->available_slots }}</span>
                            </li>
                        </ul>

                        <button type="submit" class="btn btn-navy-submit w-100 mb-2 py-2">
                            <i class="bi bi-send me-1"></i> Submit Application
                        </button>
                        <a href="{{ route('student.programs.index') }}"
                            class="btn btn-outline-secondary w-100">Cancel</a>

                        <div class="small text-secondary mt-3">
                            <i class="bi bi-shield-check me-1"></i>
                            Submitting locks this program's slot request into FASSG's verification queue. You'll be notified
                            at each stage.
                        </div>
                    </div>
                </div>
            </div>
        </form>

        @push('scripts')
            <script>
                function updateSelectedFile(input) {
                    const filename = input.files[0]?.name || 'or drag file here';
                    document.querySelector(`[data-filename-for="${input.id}"]`).textContent = filename;
                }

                document.querySelectorAll('label[for]').forEach(function(label) {
                    const input = document.getElementById(label.htmlFor);

                    label.addEventListener('dragover', function(event) {
                        event.preventDefault();
                    });
                    label.addEventListener('drop', function(event) {
                        event.preventDefault();
                        if (event.dataTransfer.files.length) {
                            const transfer = new DataTransfer();
                            transfer.items.add(event.dataTransfer.files[0]);
                            input.files = transfer.files;
                            updateSelectedFile(input);
                        }
                    });
                });
            </script>
        @endpush

    @endif

@endsection
