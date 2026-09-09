@extends('layouts.app')

@section('title', 'Create Program')
@section('eyebrow', 'FASSG Office · Programs')
@section('page-title', 'Create Sponsorship Program')

@push('styles')
    <style>
        /* Primary Navy Styling for Create Button */
        .btn-navy-primary,
        button.btn-navy-primary {
            background-color: #0F2942 !important;
            border-color: #0F2942 !important;
            color: #ffffff !important;
            font-weight: 600;
            box-shadow: none !important;
            transition: all 0.2s ease-in-out;
        }

        .btn-navy-primary:hover,
        .btn-navy-primary:focus,
        button.btn-navy-primary:hover,
        button.btn-navy-primary:focus {
            background-color: #0A1E31 !important;
            border-color: #0A1E31 !important;
            color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(15, 41, 66, 0.15) !important;
        }

        /* Active Checkbox Accent */
        .form-check-input:checked {
            background-color: #0F2942 !important;
            border-color: #0F2942 !important;
        }
    </style>
@endpush

@section('content')

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <form method="POST" action="{{ route('fassg.programs.store') }}" class="card sf-card">
                @csrf
                <div class="card-body p-4">

                    <h2 class="h6 sf-heading mb-1">Program Details</h2>
                    <p class="small text-secondary mb-4">These fields define the criteria used for filtering-only decision
                        support — GPA, course, and address checks. Final decisions remain manual.</p>

                    <div class="row g-3 mb-4">
                        <div class="col-md-8">
                            <label class="form-label small text-secondary">Program Name <span
                                    class="text-danger">*</span></label>
                            <input type="text" name="program_name" value="{{ old('program_name') }}"
                                class="form-control @error('program_name') is-invalid @enderror" required>
                            @error('program_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-secondary">Category <span
                                    class="text-danger">*</span></label>
                            <select name="category" id="category" class="form-select @error('category') is-invalid @enderror" required>
                                <option value="">Select…</option>
                                @foreach (['Group', 'Individual', 'Employee-Based'] as $cat)
                                    <option value="{{ $cat }}" @selected(old('category') === $cat)>{{ $cat }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12" id="employeeRelativeBlock" style="display: none;">
                            <div class="form-check py-1">
                                <input class="form-check-input" type="checkbox"
                                    name="requires_relative_verification" value="1" id="requires_relative_verification">
                                <label class="form-check-label" for="requires_relative_verification">
                                    Requires Institutional Relative Employee Verification
                                </label>
                                <small class="d-block text-muted">Students must be verified as institutional relative
                                    employees of the sponsoring organization.</small>
                            </div>
                        </div>
                        <div class="col-12" id="individualAdvisoryBlock" style="display: none;">
                            <div class="alert alert-warning py-2 px-3 mb-0 small">
                                <i class="bi bi-info-circle me-1"></i>
                                For Individual programs, eligibility filtering rules are advisory — the sponsor directly
                                matches applicants for final selection.
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small text-secondary">Sponsor <span
                                    class="text-danger">*</span></label>
                            <select name="sponsor_id" class="form-select @error('sponsor_id') is-invalid @enderror"
                                required>
                                <option value="">Select sponsor…</option>
                                @foreach ($sponsors as $sponsor)
                                    <option value="{{ $sponsor->id }}" @selected(old('sponsor_id') == $sponsor->id)>
                                        {{ $sponsor->company_organization_name ?: $sponsor->user?->name }}</option>
                                @endforeach
                            </select>
                            @error('sponsor_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-secondary">Total Slots <span
                                    class="text-danger">*</span></label>
                            <input type="number" min="1" name="total_slots" id="total_slots"
                                value="{{ old('total_slots') }}"
                                class="form-control @error('total_slots') is-invalid @enderror" required>
                            @error('total_slots')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-secondary">Available Slots <span
                                    class="text-danger">*</span></label>
                            <input type="number" min="0" name="available_slots" id="available_slots"
                                value="{{ old('available_slots', 0) }}"
                                class="form-control @error('available_slots') is-invalid @enderror" readonly required>
                            @error('available_slots')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Set automatically to match total slots when opening a new
                                program.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold" for="end_date">End Date (Optional)</label>
                            <input class="form-control @error('end_date') is-invalid @enderror" id="end_date"
                                type="date" name="end_date" value="{{ old('end_date') }}">
                            <small class="text-muted">Programs past this date automatically transition to Expired on page
                                refresh.</small>
                            @error('end_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold" for="application_deadline">Application Deadline
                                (Optional)</label>
                            <input class="form-control @error('application_deadline') is-invalid @enderror"
                                id="application_deadline" type="date" name="application_deadline"
                                value="{{ old('application_deadline', $program->application_deadline ?? '') }}">
                            <small class="text-muted">Students cannot submit new applications past this date. The program
                                remains active for FASSG/Sponsor review.</small>
                            @error('application_deadline')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <hr class="my-4">

                    <h2 class="h6 sf-heading mb-1">Eligibility Filtering Criteria</h2>
                    <p class="small text-secondary mb-3">Used only to filter and shortlist applicants — not to auto-decide.
                        FASSG and the sponsor make the final call.</p>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small text-secondary">Minimum GPA</label>
                            <input type="number" step="0.01" min="1" max="5" name="min_gpa"
                                value="{{ old('min_gpa') }}" class="form-control @error('min_gpa') is-invalid @enderror">
                            @error('min_gpa')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-secondary">Address Requirement</label>
                            <select name="address_requirement"
                                class="form-select @error('address_requirement') is-invalid @enderror">
                                <option value="">No preference</option>
                                <option value="Rural" @selected(old('address_requirement') === 'Rural')>Rural only</option>
                                <option value="Urban" @selected(old('address_requirement') === 'Urban')>Urban only</option>
                            </select>
                            @error('address_requirement')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="form-label fw-bold">Eligible Year Levels</label>
                        <p class="text-muted small mb-2">Leave all unchecked to allow all year levels.</p>
                        @php
                            $selectedYearLevels = is_array(old('eligible_year_levels')) ? old('eligible_year_levels') : [];
                        @endphp
                        <div class="d-flex flex-wrap gap-3">
                            @foreach ([1, 2, 3, 4] as $yr)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox"
                                        name="eligible_year_levels[]"
                                        value="{{ $yr }}"
                                        id="yr_create_{{ $yr }}"
                                        {{ in_array((string) $yr, array_map('strval', $selectedYearLevels)) ? 'checked' : '' }}>
                                    <label class="form-check-label small" for="yr_create_{{ $yr }}">Year {{ $yr }}</label>
                                </div>
                            @endforeach
                        </div>
                        @error('eligible_year_levels')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    @php
                        $campusOptions = [
                            'Main Campus (City of Mati)',
                            'Baganga Campus',
                            'Banaybanay Campus',
                            'Cateel Campus',
                            'San Isidro Campus',
                            'Tarragona Campus',
                        ];
                        $documentOptions = [
                            'Report Card / Certificate of Grades',
                            'Certificate of Indigency',
                            'Certificate of Registration (COR)',
                            'Proof of Residence / Barangay Cert',
                            'Employee ID / Proof of Kinship',
                        ];
                        $selectedCampuses = is_array(old('eligible_campuses')) ? old('eligible_campuses') : [];
                        $selectedDocuments = is_array(old('required_documents')) ? old('required_documents') : [];
                    @endphp

                    <div class="mt-4">
                        <label class="form-label fw-bold">Eligible Campuses</label>
                        <p class="text-muted small mb-2">Leave all unchecked to allow students from all campuses.</p>
                        <div class="d-flex flex-wrap gap-3">
                            @foreach ($campusOptions as $campus)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="eligible_campuses[]"
                                        value="{{ $campus }}" id="campus_create_{{ $loop->index }}"
                                        {{ in_array($campus, $selectedCampuses) ? 'checked' : '' }}>
                                    <label class="form-check-label small" for="campus_create_{{ $loop->index }}">
                                        {{ $campus }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        @error('eligible_campuses')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mt-4">
                        <label class="form-label fw-bold">Required Documents Checklist</label>
                        <p class="text-muted small mb-2">Select the supporting documents applicants must submit for
                            this program (leave all unchecked if none are required).</p>
                        <div class="d-flex flex-wrap gap-3">
                            @foreach ($documentOptions as $doc)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="required_documents[]"
                                        value="{{ $doc }}" id="doc_create_{{ $loop->index }}"
                                        {{ in_array($doc, $selectedDocuments) ? 'checked' : '' }}>
                                    <label class="form-check-label small" for="doc_create_{{ $loop->index }}">
                                        {{ $doc }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        @error('required_documents')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mt-4">
                        <label class="form-label fw-bold">Eligible Courses / Academic Programs</label>
                        <p class="text-muted small mb-2">Select the courses eligible for this sponsorship program (leave
                            empty to allow all courses).</p>

                        @php
                            $selectedProgramIds = is_array(old('academic_program_ids'))
                                ? old('academic_program_ids')
                                : [];
                        @endphp

                        <div class="d-flex gap-2 mb-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-select-all-courses>
                                Select All
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-clear-all-courses>
                                Clear All
                            </button>
                        </div>

                        <div class="card p-3 border rounded-3" style="max-height: 250px; overflow-y: auto;">
                            <div class="row g-2">
                                @forelse ($academicPrograms as $academicProg)
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="academic_program_ids[]"
                                                value="{{ $academicProg->program_id }}"
                                                id="prog_{{ $academicProg->program_id }}"
                                                {{ in_array($academicProg->program_id, $selectedProgramIds) ? 'checked' : '' }}>
                                            <label class="form-check-label small"
                                                for="prog_{{ $academicProg->program_id }}">
                                                <strong>{{ $academicProg->code }}</strong> — {{ $academicProg->name }}
                                            </label>
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-12 text-muted small">No active academic programs are available. Please
                                        add
                                        academic programs first.</div>
                                @endforelse
                            </div>
                        </div>
                        @error('academic_program_ids')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="card-footer bg-white border-top p-4 d-flex justify-content-end gap-2">
                    <a href="{{ route('fassg.programs.index') }}" class="btn btn-outline-danger"><i class="bi bi-x-lg me-1"></i> Cancel</a>
                    <button type="submit" class="btn btn-navy-primary"><i class="bi bi-check-lg me-1"></i>Create &amp; Open
                        Program</button>
                </div>
            </form>

            @push('scripts')
                <script>
                    const categorySelect = document.getElementById('category');
                    const employeeRelativeBlock = document.getElementById('employeeRelativeBlock');
                    const individualAdvisoryBlock = document.getElementById('individualAdvisoryBlock');

                    const syncCategoryGuidance = () => {
                        const value = categorySelect ? categorySelect.value : '';
                        if (employeeRelativeBlock) employeeRelativeBlock.style.display = value === 'Employee-Based' ? 'block' : 'none';
                        if (individualAdvisoryBlock) individualAdvisoryBlock.style.display = value === 'Individual' ? 'block' : 'none';
                    };

                    if (categorySelect) {
                        categorySelect.addEventListener('change', syncCategoryGuidance);
                        syncCategoryGuidance();
                    }

                    const totalSlotsInput = document.getElementById('total_slots');
                    const availableSlotsInput = document.getElementById('available_slots');

                    const syncSlots = () => {
                        availableSlotsInput.value = totalSlotsInput.value;
                    };

                    totalSlotsInput.addEventListener('input', syncSlots);
                    document.querySelector('form').addEventListener('submit', syncSlots);

                    const courseCheckboxes = () => document.querySelectorAll('input[name="academic_program_ids[]"]');
                    document.querySelectorAll('[data-select-all-courses]').forEach((btn) => {
                        btn.addEventListener('click', () => courseCheckboxes().forEach((cb) => { cb.checked = true; }));
                    });
                    document.querySelectorAll('[data-clear-all-courses]').forEach((btn) => {
                        btn.addEventListener('click', () => courseCheckboxes().forEach((cb) => { cb.checked = false; }));
                    });
                </script>
            @endpush
        </div>
    </div>

@endsection
