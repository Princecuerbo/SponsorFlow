<form method="POST" action="{{ $action }}" class="card sf-card sfp-card-form">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="card-body sfp-form-body">
        {{-- Section 1: Program Details --}}
        <div class="sfp-section-title">Program Details</div>
        <p class="sfp-section-desc">These fields define core program parameters, capacity, and schedule across the portal.</p>

        <div class="row g-3 g-xl-4">
            <div class="col-md-6">
                <label class="form-label" for="program_name">Program Name <span class="text-danger">*</span></label>
                <input class="form-control @error('program_name') is-invalid @enderror" id="program_name"
                    name="program_name" value="{{ old('program_name', $program->program_name) }}"
                    placeholder="e.g. Tertiary Educational Assistance Program" required>
                @error('program_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label" for="category">Category <span class="text-danger">*</span></label>
                <select class="form-select @error('category') is-invalid @enderror" id="category" name="category" required>
                    <option value="">Select category…</option>
                    @foreach (\App\Enums\ProgramCategory::cases() as $category)
                        <option value="{{ $category->value }}" @selected(old('category', $program->category?->value ?? $program->category) === $category->value)>
                            {{ $category->value }}
                        </option>
                    @endforeach
                </select>
                @error('category')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12" id="employeeRelativeBlock" style="display: none;">
                <div class="form-check py-1">
                    <input class="form-check-input" type="checkbox" name="requires_relative_verification" value="1"
                        id="requires_relative_verification"
                        @checked((bool) old('requires_relative_verification', (bool) ($program->requires_relative_verification ?? false)))>
                    <label class="form-check-label fw-semibold" for="requires_relative_verification">
                        Requires Institutional Relative Employee Verification
                    </label>
                    <small class="text-muted d-block mt-1">Students must be verified as institutional relative employees of the sponsoring organization.</small>
                </div>
            </div>

            <div class="col-12" id="individualAdvisoryBlock" style="display: none;">
                <div class="sfp-alert-advisory">
                    <i class="bi bi-info-circle me-1"></i>
                    <strong>Advisory:</strong> For Individual programs, eligibility filtering rules are advisory — the sponsor directly matches applicants for final selection.
                </div>
            </div>

            <div class="col-md-6">
                <label class="form-label" for="sponsor_id">Sponsor <span class="text-danger">*</span></label>
                <select class="form-select @error('sponsor_id') is-invalid @enderror" id="sponsor_id" name="sponsor_id" required>
                    <option value="">Select sponsor…</option>
                    @foreach ($sponsors as $sponsor)
                        <option value="{{ $sponsor->id }}" @selected(old('sponsor_id', $program->sponsor_id) == $sponsor->id)>
                            {{ $sponsor->company_organization_name ?: $sponsor->user?->name }}
                        </option>
                    @endforeach
                </select>
                @error('sponsor_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            @if ($method !== 'POST')
                <div class="col-md-6">
                    <label class="form-label" for="status">Program Status <span class="text-danger">*</span></label>
                    <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                        @foreach (\App\Enums\ProgramStatus::cases() as $status)
                            <option value="{{ $status->value }}" @selected(old('status', $program->status?->value ?? $program->status ?? 'Open') === $status->value)>
                                {{ match ($status->value) {
                                    'Open' => 'Open (Accepting Applications)',
                                    'Closed' => 'Closed (In Review / Processing)',
                                    'Expired' => 'Expired (Term Ended / Completed)',
                                } }}
                            </option>
                        @endforeach
                    </select>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            @endif

            <div class="col-md-6">
                <label class="form-label" for="total_slots">Total Slots <span class="text-danger">*</span></label>
                <input class="form-control @error('total_slots') is-invalid @enderror" id="total_slots" type="number"
                    min="1" name="total_slots" value="{{ old('total_slots', $program->total_slots ?? 1) }}" required>
                <small class="text-muted">Adjusting total slots updates available slots automatically and never reduces already approved grants.</small>
                @error('total_slots')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label" for="available_slots">Available Slots <span class="text-danger">*</span></label>
                <input class="form-control @error('available_slots') is-invalid @enderror" id="available_slots"
                    type="number" min="0" name="available_slots"
                    value="{{ old('available_slots', $program->available_slots ?? 1) }}" readonly required>
                <small class="text-muted">
                    Calculated automatically: Total Slots (<span id="helpTotalSlots">{{ (int) ($program->total_slots ?? 1) }}</span>) − Approved Applications (<span id="helpApprovedCount">{{ $approvedCount ?? 0 }}</span>) = <span id="helpAvailableSlots">{{ (int) ($program->available_slots ?? 1) }}</span>
                </small>
                @error('available_slots')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label" for="end_date">End Date (Optional)</label>
                <input class="form-control @error('end_date') is-invalid @enderror" id="end_date" type="date"
                    name="end_date" value="{{ old('end_date', $program->end_date?->format('Y-m-d')) }}">
                <small class="text-muted">Programs past this date automatically transition to Expired on page refresh.</small>
                @error('end_date')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label" for="application_deadline">Application Deadline (Optional)</label>
                <input class="form-control @error('application_deadline') is-invalid @enderror" id="application_deadline"
                    type="date" name="application_deadline"
                    value="{{ old('application_deadline', $program->application_deadline?->format('Y-m-d') ?? '') }}">
                <small class="text-muted">Students cannot submit new applications past this date. The program remains active for FASSG/Sponsor review.</small>
                @error('application_deadline')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <hr class="sfp-divider">

        {{-- Section 2: Eligibility Filtering Criteria --}}
        <div class="sfp-section-title">Eligibility Filtering Criteria</div>
        <p class="sfp-section-desc">Used only to filter and shortlist applicants — not to auto-decide. FASSG and the sponsor make the final call.</p>

        <div class="row g-3 g-xl-4">
            <div class="col-md-4">
                <label class="form-label" for="min_gpa">Minimum GPA</label>
                <input class="form-control @error('min_gpa') is-invalid @enderror" id="min_gpa" type="number"
                    step="0.01" min="1" max="5" name="min_gpa"
                    placeholder="e.g. 2.00"
                    value="{{ old('min_gpa', $program->min_gpa ?? '') }}">
                <small class="text-muted">Leave blank if no GPA cutoff is required.</small>
                @error('min_gpa')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label" for="target_province">Target Province</label>
                @php
                    $selectedProvince = (string) old('target_province', $program->target_province ?? '');
                @endphp
                <select class="form-select @error('target_province') is-invalid @enderror" id="target_province"
                    name="target_province">
                    <option value="" @selected($selectedProvince === '')>All / No Preference</option>
                    @foreach ($provinces as $province)
                        <option value="{{ $province }}" @selected($selectedProvince === $province)>{{ $province }}</option>
                    @endforeach
                    @if ($selectedProvince !== '' && !$provinces->contains($selectedProvince))
                        <option value="{{ $selectedProvince }}" selected>{{ $selectedProvince }}</option>
                    @endif
                </select>
                <small class="text-muted">Restrict applicants to a province, or accept all.</small>
                @error('target_province')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label" for="target_municipality">Target Municipality / City</label>
                @php
                    $selectedMunicipality = (string) old('target_municipality', $program->target_municipality ?? '');
                    $knownMunicipality = $selectedMunicipality !== ''
                        && ($municipalitiesGrouped[$selectedProvince] ?? collect())->contains($selectedMunicipality);
                @endphp
                <select class="form-select @error('target_municipality') is-invalid @enderror" id="target_municipality"
                    name="target_municipality">
                    <option value="" @selected($selectedMunicipality === '')>All / No Preference</option>
                    @foreach ($municipalitiesGrouped as $groupProvince => $municipalities)
                        @foreach ($municipalities as $municipality)
                            <option value="{{ $municipality }}" data-province="{{ $groupProvince }}"
                                @selected($selectedMunicipality === $municipality && $groupProvince === $selectedProvince)>
                                {{ $municipality }}
                            </option>
                        @endforeach
                    @endforeach
                    @if ($selectedMunicipality !== '' && !$knownMunicipality)
                        <option value="{{ $selectedMunicipality }}" selected>{{ $selectedMunicipality }}</option>
                    @endif
                </select>
                <small class="text-muted">Options filter automatically based on province.</small>
                @error('target_municipality')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <hr class="sfp-divider">

        {{-- Section 3: Eligible Year Levels --}}
        <div class="sfp-checkbox-group">
            <div class="sfp-checkbox-group-title">Eligible Year Levels</div>
            <div class="sfp-checkbox-group-desc">Leave all unchecked to allow students from all year levels.</div>
            @php
                $selectedYearLevels = is_array(old('eligible_year_levels'))
                    ? old('eligible_year_levels')
                    : ($program->exists
                        ? array_map('strval', (array) ($program->eligible_year_levels ?? []))
                        : []);
            @endphp
            <div class="d-flex flex-wrap gap-4">
                @foreach ([1, 2, 3, 4, 5] as $yr)
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox"
                            name="eligible_year_levels[]"
                            value="{{ $yr }}"
                            id="yr_form_{{ $yr }}"
                            {{ in_array((string) $yr, $selectedYearLevels) ? 'checked' : '' }}>
                        <label class="form-check-label" for="yr_form_{{ $yr }}">Year {{ $yr }}</label>
                    </div>
                @endforeach
            </div>
            @error('eligible_year_levels')
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
        </div>

        <hr class="sfp-divider">

        {{-- Section 4: Eligible Campuses --}}
        <div class="sfp-checkbox-group">
            <div class="sfp-checkbox-group-title">Eligible Campuses</div>
            <div class="sfp-checkbox-group-desc">Leave all unchecked to allow students from all campuses.</div>
            @php
                $campusOptions = [
                    'Main Campus (City of Mati)',
                    'Baganga Campus',
                    'Banaybanay Campus',
                    'Cateel Campus',
                    'San Isidro Campus',
                    'Tarragona Campus',
                ];
                $selectedCampuses = is_array(old('eligible_campuses'))
                    ? old('eligible_campuses')
                    : ($program->exists
                        ? (array) ($program->eligible_campuses ?? [])
                        : []);
            @endphp
            <div class="row g-2">
                @foreach ($campusOptions as $campus)
                    <div class="col-md-4 col-sm-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox"
                                name="eligible_campuses[]"
                                value="{{ $campus }}"
                                id="campus_form_{{ $loop->index }}"
                                {{ in_array($campus, $selectedCampuses) ? 'checked' : '' }}>
                            <label class="form-check-label" for="campus_form_{{ $loop->index }}">
                                {{ $campus }}
                            </label>
                        </div>
                    </div>
                @endforeach
            </div>
            @error('eligible_campuses')
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
        </div>

        <hr class="sfp-divider">

        {{-- Section 5: Required Documents Checklist --}}
        <div class="sfp-checkbox-group">
            <div class="sfp-checkbox-group-title">Required Documents Checklist</div>
            <div class="sfp-checkbox-group-desc">Select the supporting documents applicants must submit for this program (leave all unchecked if none are required).</div>
            @php
                $documentOptions = [
                    'Report Card / Certificate of Grades',
                    'Certificate of Indigency',
                    'Certificate of Registration (COR)',
                    'Proof of Residence / Barangay Cert',
                    'Employee ID / Proof of Kinship',
                ];
                $selectedDocuments = is_array(old('required_documents'))
                    ? old('required_documents')
                    : ($program->exists
                        ? (array) ($program->required_documents ?? [])
                        : []);
            @endphp
            <div class="row g-2">
                @foreach ($documentOptions as $doc)
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox"
                                name="required_documents[]"
                                value="{{ $doc }}"
                                id="doc_form_{{ $loop->index }}"
                                {{ in_array($doc, $selectedDocuments) ? 'checked' : '' }}>
                            <label class="form-check-label" for="doc_form_{{ $loop->index }}">
                                {{ $doc }}
                            </label>
                        </div>
                    </div>
                @endforeach
            </div>
            @error('required_documents')
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
        </div>

        <hr class="sfp-divider">

        {{-- Section 6: Eligible Academic Programs --}}
        <div class="sfp-checkbox-group">
            <div class="sfp-checkbox-group-title">Eligible Academic Programs</div>
            <div class="sfp-checkbox-group-desc">Select the academic programs eligible for this sponsorship program (leave empty to allow all academic programs).</div>

            @php
                $selectedProgramIds = is_array(old('academic_program_ids'))
                    ? old('academic_program_ids')
                    : ($program->exists
                        ? $program->academicPrograms->pluck('program_id')->toArray()
                        : []);
            @endphp

            <div class="d-flex align-items-center gap-2 mb-3">
                <button type="button" class="btn btn-sm sfp-btn-bulk" data-select-all-courses>
                    <i class="bi bi-check-all me-1"></i>Select All
                </button>
                <button type="button" class="btn btn-sm sfp-btn-bulk" data-clear-all-courses>
                    <i class="bi bi-x me-1"></i>Clear All
                </button>
            </div>

            <div class="sfp-academic-box">
                <div class="row g-2">
                    @forelse ($academicPrograms as $academicProg)
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="academic_program_ids[]"
                                    value="{{ $academicProg->program_id }}" id="prog_{{ $academicProg->program_id }}"
                                    {{ in_array($academicProg->program_id, $selectedProgramIds) ? 'checked' : '' }}>
                                <label class="form-check-label" for="prog_{{ $academicProg->program_id }}">
                                    <strong>{{ $academicProg->code }}</strong> — {{ $academicProg->name }}
                                </label>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 text-muted small py-2">No active academic programs are available. Please add academic programs first.</div>
                    @endforelse
                </div>
            </div>
            @error('academic_program_ids')
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
        </div>
    </div>

    {{-- Form Footer --}}
    <div class="sfp-form-footer">
        <a href="{{ route('fassg.programs.index') }}" class="btn btn-outline-danger">
            <i class="bi bi-x-lg me-1"></i> Cancel
        </a>
        <button type="submit" class="btn btn-sf-navy">
            <i class="bi bi-check-lg me-1"></i> {{ $submitLabel ?? 'Save program' }}
        </button>
    </div>
</form>

@push('scripts')
    <script>
        (function () {
            const category = document.getElementById('category');
            const employeeBlock = document.getElementById('employeeRelativeBlock');
            const advisoryBlock = document.getElementById('individualAdvisoryBlock');

            const syncCategoryGuidance = () => {
                const value = category ? category.value : '';
                if (employeeBlock) employeeBlock.style.display = value === 'Employee-Based' ? 'block' : 'none';
                if (advisoryBlock) advisoryBlock.style.display = value === 'Individual' ? 'block' : 'none';
            };

            if (category) {
                category.addEventListener('change', syncCategoryGuidance);
                syncCategoryGuidance();
            }

            const isCreateForm = {{ $method === 'POST' ? 'true' : 'false' }};
            const totalSlotsInput = document.getElementById('total_slots');
            const availableSlotsInput = document.getElementById('available_slots');
            const approvedCountEl = document.getElementById('helpApprovedCount');
            const helpTotalSlotsEl = document.getElementById('helpTotalSlots');
            const helpAvailableSlotsEl = document.getElementById('helpAvailableSlots');
            const approvedCount = approvedCountEl ? parseInt(approvedCountEl.textContent, 10) || 0 : 0;

            const syncAvailableSlots = () => {
                const total = parseInt(totalSlotsInput.value, 10) || 0;
                let available = total;
                if (!isCreateForm) {
                    available = Math.max(0, total - approvedCount);
                }
                if (availableSlotsInput) availableSlotsInput.value = available;
                if (helpTotalSlotsEl) helpTotalSlotsEl.textContent = total;
                if (helpAvailableSlotsEl) helpAvailableSlotsEl.textContent = available;
            };

            if (totalSlotsInput) {
                totalSlotsInput.addEventListener('input', syncAvailableSlots);
                syncAvailableSlots();
            }

            const courseCheckboxes = () => document.querySelectorAll('input[name="academic_program_ids[]"]');
            document.querySelectorAll('[data-select-all-courses]').forEach((btn) => {
                btn.addEventListener('click', () => courseCheckboxes().forEach((cb) => { cb.checked = true; }));
            });
            document.querySelectorAll('[data-clear-all-courses]').forEach((btn) => {
                btn.addEventListener('click', () => courseCheckboxes().forEach((cb) => { cb.checked = false; }));
            });

            const targetProvince = document.getElementById('target_province');
            const targetMunicipality = document.getElementById('target_municipality');

            if (targetProvince && targetMunicipality) {
                const filterMunicipalities = () => {
                    const province = targetProvince.value;
                    targetMunicipality.querySelectorAll('option[data-province]').forEach((opt) => {
                        opt.hidden = province !== '' && opt.dataset.province !== province;
                    });
                    const current = targetMunicipality.selectedOptions[0];
                    if (current && current.dataset.province && current.hidden) {
                        targetMunicipality.value = '';
                    }
                };
                targetProvince.addEventListener('change', filterMunicipalities);
                filterMunicipalities();
            }
        })();
    </script>
@endpush
