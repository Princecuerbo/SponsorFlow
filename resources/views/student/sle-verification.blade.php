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
        $lockedProvince = $verifiedComponents['province'] ?? '';
        $lockedCity = $verifiedComponents['municipality_city'] ?? '';
        $lockedBarangay = $verifiedComponents['barangay'] ?? '';
        $lockedStreet = $verifiedComponents['street_purok'] ?? '';
        $selectedProvince = old('province', $requestedAddress?->province ?: $lockedProvince);
        $selectedCity = old('municipality_city', $requestedAddress?->municipality_city ?: $lockedCity);
    @endphp

    <div>
        @if (!$isVerified && $profile?->sleFheVerification)
            <div class="alert alert-warning border rounded-3 p-3 mb-4 shadow-sm">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-triangle-fill text-warning fs-5"></i>
                    <span>An existing verification record needs FASSG evidence review. It is preserved and cannot be replaced through this form.</span>
                </div>
            </div>
        @endif

        {{-- Main Banner Notification for Verified Students --}}
        @if ($isVerified)
            <div class="alert border-0 border-start border-4 rounded-3 p-3 mb-4 shadow-sm"
                style="background-color: #ECFEFF; border-left-color: #06b6d4 !important;">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <i class="bi bi-patch-check-fill fs-5" style="color: #0891b2;"></i>
                    <h3 class="h6 sf-heading mb-0 text-dark fw-bold">Verification Status: Verified</h3>
                </div>
                <p class="small mb-0 ms-md-4 text-secondary">
                    Your SLE-FHE status has been verified. You are eligible to apply for open sponsorship programs.
                </p>
            </div>
        @endif

        {{-- Balanced Two-Column Layout (align-items-start prevents empty card stretching) --}}
        <div class="row g-4 align-items-start">

            {{-- Left Column: Profile Details & Address Form (col-lg-7) --}}
            <div class="col-12 col-lg-7">

                {{-- 1. Student Profile Details Card --}}
                <div class="card border-0 shadow-sm rounded-4 bg-white mb-4" style="border: 1px solid var(--sf-border-color, #E1E8EC) !important;">
                    <div class="card-header bg-white border-bottom px-4 py-3">
                        <div class="d-flex align-items-center gap-3">
                            <span class="rounded-3 d-inline-flex align-items-center justify-content-center flex-shrink-0"
                                style="width: 40px; height: 40px; background-color: #E8F0F6; color: #0F2942;">
                                <i class="bi bi-person-badge fs-5"></i>
                            </span>
                            <div>
                                <h3 class="h6 sf-heading mb-0 fw-bold" style="color: #0F2942;">Student Profile Details</h3>
                                <p class="text-secondary small mb-0 mt-0.5" style="font-size: 0.775rem;">Your institutional student records</p>
                            </div>
                        </div>
                    </div>

                    <div class="card-body p-4">
                        {{-- Identity Fields --}}
                        <div class="row g-3">
                            <div class="col-6 col-md-3">
                                <span class="text-secondary text-uppercase fw-semibold d-block mb-1"
                                    style="font-size: 0.68rem; letter-spacing: 0.05em; color: #64748B;">First Name</span>
                                <span class="fw-semibold text-dark" style="font-size: 0.875rem;">
                                    {{ $profile?->first_name ?? '—' }}
                                </span>
                            </div>
                            <div class="col-6 col-md-3">
                                <span class="text-secondary text-uppercase fw-semibold d-block mb-1"
                                    style="font-size: 0.68rem; letter-spacing: 0.05em; color: #64748B;">Middle Name</span>
                                <span class="fw-semibold text-dark" style="font-size: 0.875rem;">
                                    {{ $profile?->middle_name ?? '—' }}
                                </span>
                            </div>
                            <div class="col-6 col-md-3">
                                <span class="text-secondary text-uppercase fw-semibold d-block mb-1"
                                    style="font-size: 0.68rem; letter-spacing: 0.05em; color: #64748B;">Last Name</span>
                                <span class="fw-semibold text-dark" style="font-size: 0.875rem;">
                                    {{ $profile?->last_name ?? '—' }}
                                </span>
                            </div>
                            <div class="col-6 col-md-3">
                                <span class="text-secondary text-uppercase fw-semibold d-block mb-1"
                                    style="font-size: 0.68rem; letter-spacing: 0.05em; color: #64748B;">Ext. Name</span>
                                <span class="fw-semibold text-dark" style="font-size: 0.875rem;">
                                    {{ $profile?->extension_name ?? '—' }}
                                </span>
                            </div>
                        </div>

                        <hr class="my-3.5" style="border-color: #E1E8EC;">

                        {{-- Academic Fields --}}
                        <div class="row g-3">
                            <div class="col-6 col-md-3">
                                <span class="text-secondary text-uppercase fw-semibold d-block mb-1"
                                    style="font-size: 0.68rem; letter-spacing: 0.05em; color: #64748B;">Student ID</span>
                                <span class="fw-semibold text-dark font-monospace" style="font-size: 0.875rem;">
                                    {{ $profile?->student_id_number ?? '—' }}
                                </span>
                            </div>
                            <div class="col-6 col-md-3">
                                <span class="text-secondary text-uppercase fw-semibold d-block mb-1"
                                    style="font-size: 0.68rem; letter-spacing: 0.05em; color: #64748B;">Year Level</span>
                                <span class="fw-semibold text-dark" style="font-size: 0.875rem;">
                                    {{ $profile?->year_level ? 'Year ' . $profile->year_level : '—' }}
                                </span>
                            </div>
                            <div class="col-12 col-md-6">
                                <span class="text-secondary text-uppercase fw-semibold d-block mb-1"
                                    style="font-size: 0.68rem; letter-spacing: 0.05em; color: #64748B;">Academic Program</span>
                                <span class="fw-semibold text-dark" style="font-size: 0.875rem; word-break: break-word;">
                                    {{ $profile?->display_course ?? '—' }}
                                </span>
                            </div>
                            <div class="col-6 col-md-6">
                                <span class="text-secondary text-uppercase fw-semibold d-block mb-1"
                                    style="font-size: 0.68rem; letter-spacing: 0.05em; color: #64748B;">Campus</span>
                                <span class="fw-semibold text-dark" style="font-size: 0.875rem;">
                                    {{ $profile?->campus ?? 'Not Assigned' }}
                                </span>
                            </div>
                            <div class="col-6 col-md-3">
                                <span class="text-secondary text-uppercase fw-semibold d-block mb-1"
                                    style="font-size: 0.68rem; letter-spacing: 0.05em; color: #64748B;">Birthdate</span>
                                <span class="fw-semibold text-dark" style="font-size: 0.875rem;">
                                    {{ $profile?->birthdate ? \Carbon\Carbon::parse($profile->birthdate)->format('F d, Y') : '—' }}
                                </span>
                            </div>
                            <div class="col-6 col-md-3">
                                <span class="text-secondary text-uppercase fw-semibold d-block mb-1"
                                    style="font-size: 0.68rem; letter-spacing: 0.05em; color: #64748B;">Sex / Gender</span>
                                <span class="fw-semibold text-dark" style="font-size: 0.875rem;">
                                    {{ $profile?->gender ?? 'N/A' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 2. Residential Address Form Card --}}
                <div class="card border-0 shadow-sm rounded-4 bg-white" style="border: 1px solid var(--sf-border-color, #E1E8EC) !important;">
                    <div class="card-header bg-white border-bottom px-4 py-3">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                            <div class="d-flex align-items-center gap-3">
                                <span class="rounded-3 d-inline-flex align-items-center justify-content-center flex-shrink-0"
                                    style="width: 40px; height: 40px; background-color: #E8F0F6; color: #0F2942;">
                                    <i class="bi bi-geo-alt fs-5"></i>
                                </span>
                                <div>
                                    <h3 class="h6 sf-heading mb-0 fw-bold" style="color: #0F2942;">Residential Address</h3>
                                    <p class="text-secondary small mb-0 mt-0.5" style="font-size: 0.775rem;">Address details for SLE-FHE matching</p>
                                </div>
                            </div>
                            @if ($isVerified)
                                <span class="badge rounded-pill d-inline-flex align-items-center gap-1 fw-semibold bg-cyan-50 text-cyan-700 border border-cyan-200"
                                    style="font-size: 0.72rem; padding: 0.25rem 0.65rem;">
                                    <i class="bi bi-lock-fill"></i> Verification Status: Verified - Profile Locked
                                </span>
                            @elseif ($isPending)
                                <span class="badge rounded-pill d-inline-flex align-items-center gap-1 fw-semibold"
                                    style="font-size: 0.72rem; padding: 0.25rem 0.65rem; background-color: #FFF8EA; color: #986515; border: 1px solid #F6DDA1;">
                                    <i class="bi bi-lock-fill"></i> Under Review
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="card-body p-4">
                        @if ($isPending || $isVerified)
                            <div class="d-flex align-items-center gap-2 mb-3 p-3 rounded-3 small {{ $isPending ? 'bg-warning-subtle text-warning-emphasis border border-warning-subtle' : 'bg-info-subtle text-info-emphasis border border-info-subtle' }}">
                                <i class="bi bi-lock-fill fs-5"></i>
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
                                <label class="form-label text-secondary small fw-semibold" for="province-select">Province</label>
                                <select class="form-select rounded-3" id="province-select" name="province"
                                    style="min-height: 42px; border-color: #E1E8EC; background-color: #F8FAFC;"
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
                                <label class="form-label text-secondary small fw-semibold" for="city-select">Municipality / City</label>
                                <select class="form-select rounded-3" id="city-select" name="municipality_city"
                                    style="min-height: 42px; border-color: #E1E8EC; background-color: #F8FAFC;"
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
                                <label class="form-label text-secondary small fw-semibold" for="barangay">Barangay</label>
                                <input type="text" class="form-control rounded-3" id="barangay" name="barangay"
                                    style="min-height: 42px; border-color: #E1E8EC; background-color: #F8FAFC;"
                                    value="{{ old('barangay', $requestedAddress?->barangay ?: $lockedBarangay) }}"
                                    placeholder="Enter Barangay"
                                    @disabled(! $isEditable) required>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label text-secondary small fw-semibold" for="street_purok">Street / Purok</label>
                                <input type="text" class="form-control rounded-3" id="street_purok" name="street_purok"
                                    style="min-height: 42px; border-color: #E1E8EC; background-color: #F8FAFC;"
                                    value="{{ old('street_purok', $requestedAddress?->street_purok ?: $lockedStreet) }}"
                                    placeholder="Enter Street / Purok (Optional)"
                                    @disabled(! $isEditable)>
                            </div>
                            <div class="col-12 pt-3">
                                @if ($isEditable)
                                    <button type="submit" class="btn btn-primary px-4 py-2.5 fw-semibold rounded-3 shadow-sm"
                                        style="background-color: #0F2942 !important; border-color: #0F2942 !important; min-height: 42px;">
                                        <i class="bi bi-shield-check me-1"></i> Request Verification
                                    </button>
                                @else
                                    <button type="submit" class="btn btn-secondary px-4 py-2.5 fw-semibold rounded-3" disabled
                                        style="min-height: 42px;">
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

            {{-- Right Column: Institutional Eligibility & Guidelines (col-lg-5) --}}
            <div class="col-12 col-lg-5">

                {{-- 1. Institutional Eligibility Status Card (Compact & Content-Driven) --}}
                <div class="card border-0 shadow-sm rounded-4 bg-white mb-4" style="border: 1px solid var(--sf-border-color, #E1E8EC) !important;">
                    <div class="card-header bg-white border-bottom px-4 py-3">
                        <div class="d-flex align-items-center gap-3">
                            <span class="rounded-3 d-inline-flex align-items-center justify-content-center flex-shrink-0"
                                style="width: 40px; height: 40px; background-color: #E8F0F6; color: #0F2942;">
                                <i class="bi bi-shield-check fs-5"></i>
                            </span>
                            <div>
                                <h3 class="h6 sf-heading mb-0 fw-bold" style="color: #0F2942;">Institutional Eligibility Status</h3>
                                <p class="text-secondary small mb-0 mt-0.5" style="font-size: 0.775rem;">Masterlist matching overview</p>
                            </div>
                        </div>
                    </div>

                    <div class="card-body p-4">
                        {{-- Status Block --}}
                        <div class="d-flex align-items-start gap-3 mb-3 p-3 rounded-3" style="background-color: #F8FAFC; border: 1px solid #E1E8EC;">
                            {{-- Icon Container (46x46px matching metric cards) --}}
                            @if ($isVerified)
                                <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                                    style="width: 46px; height: 46px; border-radius: 12px; background-color: #ECFDF5; color: #047857; border: 1px solid #A7F3D0;">
                                    <i class="bi bi-patch-check fs-5"></i>
                                </div>
                            @elseif ($isPending)
                                <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                                    style="width: 46px; height: 46px; border-radius: 12px; background-color: #FFF7E6; color: #B88219; border: 1px solid #F6DDA1;">
                                    <i class="bi bi-clock fs-5"></i>
                                </div>
                            @else
                                <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                                    style="width: 46px; height: 46px; border-radius: 12px; background-color: #F1F5F9; color: #64788A; border: 1px solid #E1E8EC;">
                                    <i class="bi bi-shield fs-5"></i>
                                </div>
                            @endif

                            <div class="min-w-0 flex-fill">
                                <div class="text-secondary text-uppercase fw-semibold mb-1" style="font-size: 0.68rem; letter-spacing: 0.05em; color: #64748B;">
                                    Masterlist Verification
                                </div>
                                @if ($isVerified)
                                    <x-status-badge :status="'Verified'" class="mb-1" />
                                    <p class="text-secondary small mb-0 mt-1" style="font-size: 0.8rem; line-height: 1.45;">Your profile is active and verified for the current academic term.</p>
                                @elseif ($isPending)
                                    <span class="badge rounded-pill d-inline-flex align-items-center gap-1 fw-semibold mb-1"
                                        style="font-size: 0.75rem; padding: 0.25rem 0.65rem; background-color: #FFF8EA; color: #986515; border: 1px solid #F6DDA1;">
                                        <span style="width: 7px; height: 7px; border-radius: 50%; background-color: #D4A332; display: inline-block;"></span>
                                        Pending Review
                                    </span>
                                    <p class="text-secondary small mb-0 mt-1" style="font-size: 0.8rem; line-height: 1.45;">Your request is awaiting review by FASSG.</p>
                                @else
                                    <span class="badge rounded-pill d-inline-flex align-items-center gap-1 fw-semibold bg-secondary-subtle text-secondary border border-secondary-subtle mb-1"
                                        style="font-size: 0.75rem; padding: 0.25rem 0.65rem;">
                                        <i class="bi bi-lock-fill"></i> Not Verified
                                    </span>
                                    <p class="text-secondary small mb-0 mt-1" style="font-size: 0.8rem; line-height: 1.45;">Submit your residential address to request verification.</p>
                                @endif
                            </div>
                        </div>

                        {{-- Action Button --}}
                        @if ($isVerified)
                            <a href="{{ route('student.programs.index') }}" class="btn btn-primary w-100 fw-semibold py-2.5 rounded-3 shadow-sm mt-3"
                                style="background-color: #0F2942 !important; border-color: #0F2942 !important; min-height: 42px;">
                                <i class="bi bi-search me-1"></i> Browse Sponsorship Opportunities
                            </a>
                        @elseif ($isPending)
                            <button type="button" id="btn-pending-modal-trigger"
                                class="btn btn-outline-secondary w-100 fw-semibold py-2.5 rounded-3 mt-3" data-bs-toggle="modal"
                                data-bs-target="#verificationPendingModal" style="min-height: 42px;">
                                <i class="bi bi-lock-fill me-1"></i> Browse Sponsorship Opportunities
                            </button>
                        @endif
                    </div>
                </div>

                {{-- 2. Verification Guidelines Card (Dedicated & Balanced) --}}
                <div class="card border-0 shadow-sm rounded-4 bg-white" style="border: 1px solid var(--sf-border-color, #E1E8EC) !important;">
                    <div class="card-header bg-white border-bottom px-4 py-3">
                        <div class="d-flex align-items-center gap-3">
                            <span class="rounded-3 d-inline-flex align-items-center justify-content-center flex-shrink-0"
                                style="width: 40px; height: 40px; background-color: #E8F0F6; color: #0F2942;">
                                <i class="bi bi-card-checklist fs-5"></i>
                            </span>
                            <div>
                                <h3 class="h6 sf-heading mb-0 fw-bold" style="color: #0F2942;">Verification Guidelines</h3>
                                <p class="text-secondary small mb-0 mt-0.5" style="font-size: 0.775rem;">Institutional validation process</p>
                            </div>
                        </div>
                    </div>

                    <div class="card-body p-4">
                        <div class="d-flex flex-column gap-3">
                            {{-- Guideline 1 --}}
                            <div class="d-flex align-items-start gap-3">
                                <span class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                    style="width: 32px; height: 32px; background-color: #E8F0F6; color: #173B57; border: 1px solid #D2E1ED;">
                                    <i class="bi bi-shield-check" style="font-size: 0.9rem;"></i>
                                </span>
                                <div>
                                    <div class="small fw-semibold text-dark">FASSG Institutional Review</div>
                                    <p class="small text-secondary mb-0" style="line-height: 1.5;">
                                        FASSG reviews your submitted details against institutional records and evidence.
                                    </p>
                                </div>
                            </div>

                            <hr class="my-1.5" style="border-color: #F1F5F9;">

                            {{-- Guideline 2 --}}
                            <div class="d-flex align-items-start gap-3">
                                <span class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                    style="width: 32px; height: 32px; background-color: #E8F0F6; color: #173B57; border: 1px solid #D2E1ED;">
                                    <i class="bi bi-person-check" style="font-size: 0.9rem;"></i>
                                </span>
                                <div>
                                    <div class="small fw-semibold text-dark">Profile Details</div>
                                    <p class="small text-secondary mb-0" style="line-height: 1.5;">
                                        Ensure your birthdate and address match your university profile.
                                    </p>
                                </div>
                            </div>

                            <hr class="my-1.5" style="border-color: #F1F5F9;">

                            {{-- Guideline 3 --}}
                            <div class="d-flex align-items-start gap-3">
                                <span class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                    style="width: 32px; height: 32px; background-color: #E8F0F6; color: #173B57; border: 1px solid #D2E1ED;">
                                    <i class="bi bi-arrow-right-circle" style="font-size: 0.9rem;"></i>
                                </span>
                                <div>
                                    <div class="small fw-semibold text-dark">Next Steps</div>
                                    <p class="small text-secondary mb-0" style="line-height: 1.5;">
                                        Once verified, available sponsorship opportunities will become active in your portal.
                                    </p>
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
                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="modal-body p-4 text-center">
                        <div class="mb-3 d-inline-flex align-items-center justify-content-center rounded-3"
                            style="width: 52px; height: 52px; background-color: #FFF7E6; color: #B88219; border: 1px solid #F6DDA1;">
                            <i class="bi bi-clock fs-3"></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-2" id="verificationPendingModalLabel">Verification Underway</h5>
                        <p class="text-secondary small mb-4" style="line-height: 1.6;">
                            Your student record is currently being cross-checked against the official institutional
                            masterlist. Sponsorship browsing will unlock automatically as soon as FASSG completes the
                            review.
                        </p>
                        <div class="d-flex flex-column gap-2">
                            <button type="button" class="btn fw-semibold w-100 py-2.5 rounded-3 text-white shadow-sm"
                                style="background-color: #0F2942; border: none;" data-bs-dismiss="modal">
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
