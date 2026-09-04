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
    <div class="col-md-4"><label class="form-label" for="min_gpa">Minimum GWA</label><input class="form-control"
            id="min_gpa" type="number" step="0.01" min="1" max="5" name="min_gpa"
            value="{{ old('min_gpa', $program->min_gpa) }}"></div>
    <div class="col-md-4"><label class="form-label" for="target_course">Target course</label><input class="form-control"
            id="target_course" name="target_course" value="{{ old('target_course', $program->target_course) }}"></div>
    <div class="col-md-4"><label class="form-label" for="address_requirement">Address requirement</label><input
            class="form-control" id="address_requirement" name="address_requirement"
            value="{{ old('address_requirement', $program->address_requirement) }}"></div>
    <div class="col-12">
        <div class="d-flex align-items-center gap-2 mt-4"><button type="submit" class="btn btn-success fw-bold"><i
                    class="bi bi-check-lg"></i> Save program</button><a href="{{ route('fassg.programs.index') }}"
                class="btn btn-light border">Cancel</a></div>
    </div>
</form>
