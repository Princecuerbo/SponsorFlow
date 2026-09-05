<form method="POST" action="{{ $action }}" class="row g-3">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="col-md-6"><label class="form-label" for="sponsor_id">Sponsor</label><select class="form-select"
            id="sponsor_id" name="sponsor_id" required>
            @foreach ($sponsors as $sponsor)
                <option value="{{ $sponsor->id }}" @selected(old('sponsor_id', $program->sponsor_id) == $sponsor->id)>
                    {{ $sponsor->company_organization_name ?: $sponsor->user?->name }}
                </option>
            @endforeach
        </select></div>
    <div class="col-md-6"><label class="form-label" for="program_name">Program name</label><input class="form-control"
            id="program_name" name="program_name" value="{{ old('program_name', $program->program_name) }}" required>
    </div>
    <div class="col-md-6"><label class="form-label" for="category">Category</label><select class="form-select"
            id="category" name="category" required>
            @foreach (\App\Enums\ProgramCategory::cases() as $category)
                <option value="{{ $category->value }}" @selected(old('category', $program->category?->value) === $category->value)>
                    {{ $category->value }}
                </option>
            @endforeach
        </select></div>
    <div class="col-md-6"><label class="form-label" for="available_slots">Available slots</label><input
            class="form-control" id="available_slots" type="number" min="0" name="available_slots"
            value="{{ old('available_slots', $program->available_slots ?? 1) }}" required></div>
    @if ($method !== 'POST')
        <div class="col-md-6"><label class="form-label fw-bold" for="status">Program Status</label><select
                class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                @foreach (\App\Enums\ProgramStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected(old('status', $program->status?->value ?? 'Open') === $status->value)>
                        {{ match ($status->value) {'Open' => 'Open (Accepting Applications)','Closed' => 'Closed (In Review / Processing)','Expired' => 'Expired (Term Ended / Completed)'} }}
                    </option>
                @endforeach
            </select>
            @error('status')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    @endif
    <div class="col-md-6"><label class="form-label fw-bold" for="end_date">End Date (Optional)</label><input
            class="form-control @error('end_date') is-invalid @enderror" id="end_date" type="date" name="end_date"
            value="{{ old('end_date', $program->end_date?->format('Y-m-d')) }}"><small class="text-muted">Programs past
            this date automatically transition to Expired on page refresh.</small>
        @error('end_date')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-6"><label class="form-label fw-semibold" for="min_gpa">Minimum GPA</label><input
            class="form-control" id="min_gpa" type="number" step="0.01" min="1" max="5"
            name="min_gpa" value="{{ old('min_gpa', $program->min_gpa ?? '') }}"></div>
    <div class="col-md-6"><label class="form-label fw-semibold" for="address_requirement">Address
            Requirement</label>
        @php
            $selectedAddress = (string) old('address_requirement', $program->address_requirement ?? '');
            $standardAddressOptions = ['', 'Rural', 'Urban'];
        @endphp
        <select class="form-select" id="address_requirement" name="address_requirement">
            <option value="" @selected($selectedAddress === '')>No preference</option>
            <option value="Rural" @selected($selectedAddress === 'Rural')>Rural only</option>
            <option value="Urban" @selected($selectedAddress === 'Urban')>Urban only</option>
            @if ($selectedAddress !== '' && !in_array($selectedAddress, $standardAddressOptions, true))
                <option value="{{ $selectedAddress }}" selected>{{ $selectedAddress }}</option>
            @endif
        </select>
    </div>
    <div class="col-12">
        <div class="mb-4 mt-2">
            <label class="form-label fw-bold">Eligible Courses / Academic Programs</label>
            <p class="text-muted small mb-2">Select the courses eligible for this scholarship program (leave empty to
                allow all courses).</p>

            @php
                $selectedProgramIds = is_array(old('academic_program_ids'))
                    ? old('academic_program_ids')
                    : ($program->exists
                        ? $program->academicPrograms->pluck('program_id')->toArray()
                        : []);
            @endphp

            <div class="card p-3 border rounded-3" style="max-height: 250px; overflow-y: auto;">
                <div class="row g-2">
                    @forelse ($academicPrograms as $academicProg)
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="academic_program_ids[]"
                                    value="{{ $academicProg->program_id }}" id="prog_{{ $academicProg->program_id }}"
                                    {{ in_array($academicProg->program_id, $selectedProgramIds) ? 'checked' : '' }}
                                    style="cursor: pointer;">
                                <label class="form-check-label small" for="prog_{{ $academicProg->program_id }}">
                                    <strong>{{ $academicProg->code }}</strong> — {{ $academicProg->name }}
                                </label>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 text-muted small">No active academic programs are available. Please add
                            academic
                            programs first.</div>
                    @endforelse
                </div>
            </div>
            @error('academic_program_ids')
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-12">
        <div class="d-flex align-items-center gap-2 mt-4">
            <button type="submit" class="btn fw-bold text-white px-4"
                style="background-color: #0F2942 !important; border-color: #0F2942 !important;">
                <i class="bi bi-check-lg me-1"></i> Save program
            </button>
            <a href="{{ route('fassg.programs.index') }}" class="btn btn-light border">Cancel</a>
        </div>
    </div>
</form>
