@extends('layouts.app')

@section('title', 'Create Program')
@section('eyebrow', 'FASSG Office · Programs')
@section('page-title', 'Create Sponsorship Program')

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
                            <select name="category" class="form-select @error('category') is-invalid @enderror" required>
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
                            <label class="form-label small text-secondary">Available Slots <span
                                    class="text-danger">*</span></label>
                            <input type="number" min="1" name="available_slots" value="{{ old('available_slots') }}"
                                class="form-control @error('available_slots') is-invalid @enderror" required>
                            @error('available_slots')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
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
                    </div>

                    <hr class="my-4">

                    <h2 class="h6 sf-heading mb-1">Eligibility Filtering Criteria</h2>
                    <p class="small text-secondary mb-3">Used only to filter and shortlist applicants — not to auto-decide.
                        FASSG and the sponsor make the final call.</p>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small text-secondary">Minimum GPA</label>
                            <input type="number" step="0.01" min="1" max="5" name="min_gpa"
                                value="{{ old('min_gpa') }}" class="form-control @error('min_gpa') is-invalid @enderror">
                            @error('min_gpa')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-secondary">Target Course</label>
                            <input type="text" name="target_course" placeholder="e.g. BS Information Technology"
                                value="{{ old('target_course') }}"
                                class="form-control @error('target_course') is-invalid @enderror">
                            @error('target_course')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
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
                        <label class="form-label fw-bold">Eligible Courses / Academic Programs</label>
                        <p class="text-muted small mb-2">Select the courses eligible for this scholarship program (leave
                            empty to allow all courses).</p>

                        @php
                            $selectedProgramIds = is_array(old('academic_program_ids')) ? old('academic_program_ids') : [];
                        @endphp

                        <div class="card p-3 border rounded-3" style="max-height: 250px; overflow-y: auto;">
                            <div class="row g-2">
                                @forelse ($academicPrograms as $academicProg)
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="academic_program_ids[]"
                                                value="{{ $academicProg->program_id }}"
                                                id="prog_{{ $academicProg->program_id }}"
                                                {{ in_array($academicProg->program_id, $selectedProgramIds) ? 'checked' : '' }}>
                                            <label class="form-check-label small" for="prog_{{ $academicProg->program_id }}">
                                                <strong>{{ $academicProg->code }}</strong> — {{ $academicProg->name }}
                                            </label>
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-12 text-muted small">No active academic programs are available. Please add
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
                    <a href="{{ route('fassg.programs.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-sf-gold"><i class="bi bi-check-lg me-1"></i>Create &amp; Open
                        Program</button>
                </div>
            </form>
        </div>
    </div>

@endsection
