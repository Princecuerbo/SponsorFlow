@extends('layouts.app')

@section('title', 'SLE-FHE Verification')
@section('eyebrow', 'Student Portal · SLE-FHE Verification')
@section('page-title', 'SLE-FHE Student Verification')
@section('subtitle', 'Submit and manage your verification records for Davao Oriental State University sponsorship eligibility.')

@section('content')
    @php
        $sleFheStatus = $profile?->sle_fhe_status;
        $isVerified = $sleFheStatus === 'Verified';
        $isPending = ! $isVerified && $sleFheStatus === 'Pending Review';
        $isEditable = ! $isVerified && ! $isPending;
        $requestedAddress = $profile?->sleFheRequest;
        $selectedProvince = old('province', $requestedAddress?->province ?? '');
        $selectedCity = old('municipality_city', $requestedAddress?->municipality_city ?? '');
    @endphp

    <div class="container-fluid px-3 px-md-4 py-4 mb-5" style="background-color: #f8fafc; min-height: calc(100vh - 70px);">

        @if (session('status'))
            <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4" role="alert"
                style="font-size: 0.875rem;">
                <i class="bi bi-check-circle me-1"></i> {{ session('status') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4" role="alert"
                style="font-size: 0.875rem;">
                <i class="bi bi-exclamation-triangle me-1"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- Main Banner Notification --}}
        @if ($isVerified)
            <div class="alert border-0 border-start border-4 rounded-3 p-3 mb-4"
                style="background-color: #ECFEFF; border-left-color: #06b6d4;">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <i class="bi bi-patch-check-fill fs-5" style="color: #0891b2;"></i>
                    <h3 class="h6 sf-heading mb-0">Verification Status: Verified</h3>
                </div>
                <p class="small mb-0 ms-md-4" style="color: #475569;">
                    Your SLE-FHE status has been verified. You are eligible to apply for open sponsorship programs.
                </p>
            </div>
        @endif

        <div class="row g-4 align-items-stretch">

            {{-- Academic Profile Details Card --}}
            <div class="col-12 col-lg-5">
                <div class="card h-100 shadow-sm border-0 rounded-3 bg-white">
                    <div class="card-header bg-white border-bottom-0 pt-3 pb-0">
                        <h3 class="h6 sf-heading mb-0 text-slate-800 d-flex align-items-center gap-2">
                            <i class="fa-solid fa-id-card text-primary"></i> Student Profile Details
                        </h3>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3 mb-3">
                            <div class="col-6 pb-2 border-bottom">
                                <span class="text-secondary extra-small text-uppercase d-block mb-1"
                                    style="font-size: 0.7rem; letter-spacing: 0.05em;">First Name</span>
                                <span class="fw-bold text-dark"
                                    style="font-size: 0.925rem;">{{ $profile?->first_name ?? '—' }}</span>
                            </div>

                            <div class="col-6 pb-2 border-bottom">
                                <span class="text-secondary extra-small text-uppercase d-block mb-1"
                                    style="font-size: 0.7rem; letter-spacing: 0.05em;">Middle Name</span>
                                <span class="fw-bold text-dark"
                                    style="font-size: 0.925rem;">{{ $profile?->middle_name ?? '—' }}</span>
                            </div>

                            <div class="col-6 pb-2 border-bottom">
                                <span class="text-secondary extra-small text-uppercase d-block mb-1"
                                    style="font-size: 0.7rem; letter-spacing: 0.05em;">Last Name</span>
                                <span class="fw-bold text-dark"
                                    style="font-size: 0.925rem;">{{ $profile?->last_name ?? '—' }}</span>
                            </div>

                            <div class="col-6 pb-2 border-bottom">
                                <span class="text-secondary extra-small text-uppercase d-block mb-1"
                                    style="font-size: 0.7rem; letter-spacing: 0.05em;">Ext. Name</span>
                                <span class="fw-bold text-dark"
                                    style="font-size: 0.925rem;">{{ $profile?->extension_name ?? '—' }}</span>
                            </div>

                            <div class="col-6 pb-2 border-bottom">
                                <span class="text-secondary extra-small text-uppercase d-block mb-1"
                                    style="font-size: 0.7rem; letter-spacing: 0.05em;">Student ID</span>
                                <span class="fw-semibold text-dark font-monospace"
                                    style="font-size: 0.875rem;">{{ $profile?->student_id_number ?? '—' }}</span>
                            </div>

                            <div class="col-6 pb-2 border-bottom">
                                <span class="text-secondary extra-small text-uppercase d-block mb-1"
                                    style="font-size: 0.7rem; letter-spacing: 0.05em;">Year Level</span>
                                <span class="fw-semibold text-dark"
                                    style="font-size: 0.875rem;">{{ $profile?->year_level ? 'Year ' . $profile->year_level : '—' }}</span>
                            </div>

                            <div class="col-6 pb-2 border-bottom">
                                <span class="text-secondary extra-small text-uppercase d-block mb-1"
                                    style="font-size: 0.7rem; letter-spacing: 0.05em;">Academic Program</span>
                                <span class="fw-semibold text-dark"
                                    style="font-size: 0.875rem;">{{ $profile?->display_course ?? '—' }}</span>
                            </div>

                            <div class="col-6 pb-2 border-bottom">
                                <span class="text-secondary extra-small text-uppercase d-block mb-1"
                                    style="font-size: 0.7rem; letter-spacing: 0.05em;">Campus</span>
                                <span class="fw-semibold text-dark"
                                    style="font-size: 0.875rem;">{{ $profile?->campus ?? 'Not Assigned' }}</span>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-6 pb-2 border-bottom">
                                <span class="text-secondary extra-small text-uppercase d-block mb-1"
                                    style="font-size: 0.7rem; letter-spacing: 0.05em;">Birthdate</span>
                                <span class="fw-semibold text-dark" style="font-size: 0.875rem;">
                                    {{ $profile?->birthdate ? \Carbon\Carbon::parse($profile->birthdate)->format('F d, Y') : '—' }}
                                </span>
                            </div>
                            <div class="col-6 pb-2 border-bottom">
                                <span class="text-secondary extra-small text-uppercase d-block mb-1"
                                    style="font-size: 0.7rem; letter-spacing: 0.05em;">Sex / Gender</span>
                                <span class="fw-semibold text-dark" style="font-size: 0.875rem;">
                                    {{ $profile?->gender ?? 'N/A' }}
                                </span>
                            </div>
                        </div>

                        <hr class="my-3">

                        {{-- Residential Address — Verification Request --}}
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                            <h3 class="h6 sf-heading mb-0 d-flex align-items-center gap-2">
                                <i class="fa-solid fa-location-dot text-primary"></i> Residential Address
                            </h3>
                            @if ($isVerified)
                                <span class="badge rounded-pill d-inline-flex align-items-center gap-1 fw-semibold bg-cyan-50 text-cyan-700 border border-cyan-200"
                                    style="font-size: 0.75rem; font-weight: 600; padding: 0.3125rem 0.75rem;">
                                    <i class="bi bi-lock-fill"></i> Verification Status: Verified - Profile Locked
                                </span>
                            @endif
                        </div>

                        @if ($isPending || $isVerified)
                            <div class="d-flex align-items-center gap-2 mb-3 text-secondary small">
                                <i class="bi bi-lock-fill"></i>
                                <span>
                                    @if ($isPending)
                                        Your submitted address is under review and can no longer be edited.
                                    @else
                                        Your residential address is locked because your SLE-FHE status is already verified.
                                    @endif
                                </span>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('student.sle-fhe.request') }}" class="row g-3">
                            @csrf
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="province-select">Province</label>
                                <select class="form-select" id="province-select" name="province"
                                    @disabled(! $isEditable) required>
                                    <option value="">Select Province</option>
                                    @foreach ($provinces as $province)
                                        <option value="{{ $province }}" @selected($province === $selectedProvince)>
                                            {{ $province }}
                                        </option>
                                    @endforeach
                                    @if ($selectedProvince !== '' && ! $provinces->contains($selectedProvince))
                                        <option value="{{ $selectedProvince }}" selected>{{ $selectedProvince }}</option>
                                    @endif
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="city-select">Municipality / City</label>
                                <select class="form-select" id="city-select" name="municipality_city"
                                    @disabled(! $isEditable) required>
                                    <option value="">Select Municipality / City</option>
                                    @foreach ($municipalities as $municipality)
                                        <option value="{{ $municipality }}" @selected($municipality === $selectedCity)>
                                            {{ $municipality }}
                                        </option>
                                    @endforeach
                                    @if ($selectedCity !== '' && ! $municipalities->contains($selectedCity))
                                        <option value="{{ $selectedCity }}" selected>{{ $selectedCity }}</option>
                                    @endif
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="barangay">Barangay</label>
                                <input type="text" class="form-control" id="barangay" name="barangay"
                                    value="{{ old('barangay', $requestedAddress?->barangay) }}"
                                    @disabled(! $isEditable) required>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="street_purok">Street / Purok</label>
                                <input type="text" class="form-control" id="street_purok" name="street_purok"
                                    value="{{ old('street_purok', $requestedAddress?->street_purok) }}"
                                    @disabled(! $isEditable)>
                            </div>
                            <div class="col-12 pt-2 d-flex flex-wrap align-items-center gap-2">
                                @if ($isEditable)
                                    <button type="submit" class="btn btn-sf-navy px-4 py-2 fw-semibold"
                                        style="border-radius: 8px;">
                                        <i class="bi bi-shield-check me-1"></i> Request Verification
                                    </button>
                                @else
                                    <button type="submit" class="btn btn-sf-navy px-4 py-2 fw-semibold" disabled
                                        style="border-radius: 8px;">
                                        <i class="bi bi-lock-fill me-1"></i>
                                        {{ $isPending ? 'Awaiting FASSG Review' : 'Profile Locked' }}
                                    </button>
                                @endif
                            </div>
                        </form>

                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                var provinceSelect = document.getElementById('province-select');
                                var citySelect = document.getElementById('city-select');
                                var citiesUrl = @json(route('student.api.municipalities', 'PROVINCE'));

                                if (!provinceSelect || !citySelect) {
                                    return;
                                }

                                var selectedCity = citySelect.value;

                                function loadMunicipalities() {
                                    var province = provinceSelect.value;
                                    var current = selectedCity;
                                    citySelect.length = 0;

                                    var placeholder = document.createElement('option');
                                    placeholder.value = '';
                                    placeholder.textContent = 'Select Municipality / City';
                                    citySelect.appendChild(placeholder);

                                    if (!province) {
                                        selectedCity = current;
                                        return;
                                    }

                                    fetch(citiesUrl.replace('PROVINCE', encodeURIComponent(province)), {
                                        headers: {
                                            'Accept': 'application/json',
                                            'X-Requested-With': 'XMLHttpRequest'
                                        }
                                    })
                                        .then(function(response) {
                                            return response.json();
                                        })
                                        .then(function(cities) {
                                            cities.forEach(function(city) {
                                                var option = document.createElement('option');
                                                option.value = city;
                                                option.textContent = city;
                                                citySelect.appendChild(option);
                                            });
                                            if (current !== '') {
                                                citySelect.value = current;
                                                selectedCity = current;
                                            }
                                        });
                                }

                                provinceSelect.addEventListener('change', loadMunicipalities);
                                loadMunicipalities();
                            });
                        </script>
                    </div>
                </div>
            </div>

            {{-- Institutional Eligibility Status Card --}}
            <div class="col-12 col-lg-7">
                <div class="card h-100 shadow-sm border-0 rounded-3 bg-white">
                    <div class="card-header bg-white border-bottom pt-3 px-4 pb-3">
                        <h3 class="h6 sf-heading mb-0 d-flex align-items-center gap-2">
                            <i class="bi bi-shield-check text-primary"></i> Institutional Eligibility Status
                        </h3>
                    </div>
                    <div class="card-body p-4">
                        <div class="d-flex align-items-start gap-3 mb-4">
                            <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                                style="width: 48px; height: 48px; background-color: {{ $isVerified ? '#ecfeff' : '#FFF8E7' }}; color: {{ $isVerified ? '#0e7490' : '#0f172a' }}; {{ $isVerified ? '' : 'border: 1px solid #FDE68A;' }}">
                                @if ($isVerified)
                                    <i class="bi bi-patch-check fs-5"></i>
                                @else
                                    <x-sf-hourglass class="fs-5" />
                                @endif
                            </div>
                            <div>
                                <h3 class="h6 sf-heading mb-3">Masterlist Verification</h3>
                                @if ($isVerified)
                                    <x-status-badge :status="'Verified'" class="mb-2" />
                                    <p class="text-secondary small mb-0">Your profile is active and verified for the current
                                        academic term.</p>
                                @elseif ($isPending)
                                    <span class="badge rounded-pill d-inline-flex align-items-center gap-1 fw-semibold bg-cream border text-slate-900"
                                        style="font-size: 0.75rem; font-weight: 600; padding: 0.125rem 0.75rem; border-color: #FCD34D; margin-bottom: 0.5rem;">
                                        <x-sf-hourglass filled style="width: 0.85em; height: 0.85em;" />
                                        Pending Review
                                    </span>
                                    <p class="text-secondary small mb-0">Your request is awaiting review by FASSG.</p>
                                @else
                                    <span class="badge rounded-pill d-inline-flex align-items-center gap-1 fw-semibold bg-secondary-subtle text-secondary border border-secondary-subtle"
                                        style="font-size: 0.75rem; font-weight: 600; padding: 0.125rem 0.75rem; margin-bottom: 0.5rem;">
                                        <i class="bi bi-lock-fill"></i> Not Verified
                                    </span>
                                    <p class="text-secondary small mb-0">Submit your residential address to request
                                        verification.</p>
                                @endif
                            </div>
                        </div>

                        {{-- Action Button --}}
                        @if ($isVerified)
                            <a href="{{ route('student.programs.index') }}" class="btn btn-sf-navy w-100 fw-semibold py-2"
                                style="border-radius: 8px;">
                                <i class="bi bi-search me-1"></i> Browse Sponsorship Opportunities
                            </a>
                        @elseif ($isPending)
                            <button type="button" id="btn-pending-modal-trigger"
                                class="btn btn-outline-secondary w-100 fw-semibold py-2" data-bs-toggle="modal"
                                data-bs-target="#verificationPendingModal" style="border-radius: 8px;">
                                <i class="bi bi-lock-fill me-1"></i> Browse Sponsorship Opportunities
                            </button>
                        @endif

                        <div class="mt-4 pt-3 border-top">
                            <h3 class="h6 sf-heading mb-3">Verification Guidelines</h3>
                            <div class="d-flex flex-column gap-3">
                                <div class="d-flex align-items-start gap-2">
                                    <i class="bi bi-check-circle-fill text-primary mt-1"></i>
                                    <div>
                                        <div class="small fw-semibold text-dark">Masterlist Verification</div>
                                        <p class="small text-secondary mb-0">Automatically checks your Student ID against
                                            institutional records.</p>
                                    </div>
                                </div>
                                <div class="d-flex align-items-start gap-2">
                                    <i class="bi bi-check-circle-fill text-primary mt-1"></i>
                                    <div>
                                        <div class="small fw-semibold text-dark">Profile Details</div>
                                        <p class="small text-secondary mb-0">Ensure your birthdate and address match your
                                            university profile.</p>
                                    </div>
                                </div>
                                <div class="d-flex align-items-start gap-2">
                                    <i class="bi bi-check-circle-fill text-primary mt-1"></i>
                                    <div>
                                        <div class="small fw-semibold text-dark">Next Steps</div>
                                        <p class="small text-secondary mb-0">Once verified, available sponsorship
                                            opportunities will become active in your portal.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    {{-- Verification Pending Modal --}}
    @if ($isPending)
        <div class="modal fade" id="verificationPendingModal" tabindex="-1"
            aria-labelledby="verificationPendingModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow rounded-4">
                    <div class="modal-body p-4 text-center">
                        <div class="mb-3 d-inline-flex align-items-center justify-content-center rounded-3"
                            style="width: 48px; height: 48px; background-color: #FFF8E7; color: #0f172a; border: 1px solid #FDE68A;">
                            <x-sf-hourglass />
                        </div>
                        <h5 class="fw-bold text-dark mb-2" id="verificationPendingModalLabel">Verification Underway</h5>
                        <p class="text-secondary small mb-4">
                            Your student record is currently being cross-checked against the official institutional
                            masterlist. Sponsorship browsing will unlock automatically as soon as FASSG completes the
                            review.
                        </p>
                        <div class="d-flex flex-column gap-2">
                            <button type="button" class="btn fw-semibold w-100 py-2 rounded-3 text-white"
                                style="background-color: #0f294a; border: none;" data-bs-dismiss="modal">
                                Got It, I'll Wait
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var btn = document.getElementById('btn-pending-modal-trigger');
                if (btn) {
                    var instance = bootstrap.Tooltip.getInstance(btn);
                    if (instance) {
                        instance.dispose();
                    }
                    btn.removeAttribute('title');
                    btn.removeAttribute('data-bs-original-title');
                }
            });
        </script>
    @endif
@endsection
