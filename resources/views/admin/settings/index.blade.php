@extends('layouts.app')
@section('title', 'System Settings')
@section('eyebrow')
    <span class="admin-original-copy">System Administrator · Settings</span>
    <span class="admin-desktop-copy d-none">System Administrator · Settings</span>
@endsection
@section('page-title')
    <span class="admin-original-copy">Security Settings</span>
    <span class="admin-desktop-copy d-none">Security Settings</span>
@endsection
@section('subtitle')
    <span class="admin-original-copy">Configure maintenance mode and portal security options.</span>
    <span class="admin-desktop-copy d-none">Configure maintenance mode and portal security options.</span>
@endsection

@push('styles')
    @include('admin.partials.desktop-styles')
@endpush

@section('content')
    @php
        $settingsKeyed = $settings->keyBy('setting_key');
        $mSetting = $settingsKeyed->get('maintenance_mode');
        $isMaintenanceOn = in_array(strtolower((string) old('settings.maintenance_mode', $mSetting?->setting_value ?? 'false')), ['true', '1', 'yes', 'on'], true);
        $attemptsSetting = $settingsKeyed->get('max_login_attempts');
        $timeoutSetting = $settingsKeyed->get('session_timeout_minutes');
    @endphp

    <form method="POST" action="{{ route('admin.settings.update') }}">
        @csrf

        {{-- Section 1: Maintenance & Access --}}
        <div class="card sf-card admin-card mb-4">
            <div class="p-4 border-bottom bg-white">
                <div class="d-flex align-items-center gap-3">
                    <div class="admin-metric-icon admin-icon-navy" style="width: 40px; height: 40px; border-radius: 10px; font-size: 18px;">
                        <i class="bi bi-shield-lock"></i>
                    </div>
                    <div>
                        <h3 class="admin-section-title mb-1">Maintenance & Access</h3>
                        <p class="admin-section-desc">Manage system-wide maintenance mode and institutional user access.</p>
                    </div>
                </div>
            </div>
            <div class="p-4 bg-white">
                <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="fw-semibold text-dark" style="font-size: 14px;">Maintenance Mode</span>
                            <span id="maintenanceModeBadge">
                                @if ($isMaintenanceOn)
                                    <span class="badge bg-amber-50 text-amber-800 border border-amber-300 px-2.5 py-1" style="font-size: 12px; font-weight: 600;">
                                        <i class="bi bi-exclamation-triangle me-1"></i>Maintenance Mode
                                    </span>
                                @else
                                    <span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200 px-2.5 py-1" style="font-size: 12px; font-weight: 600;">
                                        <i class="bi bi-check-circle me-1"></i>System Active
                                    </span>
                                @endif
                            </span>
                        </div>
                        <p class="text-secondary small mb-0" style="max-width: 680px; line-height: 1.5;">
                            When enabled, student and staff access is temporarily restricted while administrators retain full portal access to perform system updates and database maintenance.
                        </p>
                    </div>
                    <div class="form-check form-switch d-flex align-items-center gap-2 m-0 flex-shrink-0">
                        {{-- Hidden field so 'false' is submitted when unchecked --}}
                        <input type="hidden" name="settings[maintenance_mode]" value="false">
                        <input class="form-check-input" type="checkbox" role="switch"
                            id="maintenanceModeSwitch"
                            name="settings[maintenance_mode]"
                            value="true"
                            {{ $isMaintenanceOn ? 'checked' : '' }}
                            style="width: 3.2rem; height: 1.6rem; cursor: pointer;">
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 2: Login & Session Controls --}}
        <div class="card sf-card admin-card mb-4">
            <div class="p-4 border-bottom bg-white">
                <div class="d-flex align-items-center gap-3">
                    <div class="admin-metric-icon admin-icon-navy" style="width: 40px; height: 40px; border-radius: 10px; font-size: 18px;">
                        <i class="bi bi-sliders"></i>
                    </div>
                    <div>
                        <h3 class="admin-section-title mb-1">Login & Session Controls</h3>
                        <p class="admin-section-desc">Configure authentication throttling thresholds and user session timeouts.</p>
                    </div>
                </div>
            </div>
            <div class="p-4 bg-white">
                <div class="row g-4">
                    {{-- Max Login Attempts --}}
                    @if ($attemptsSetting)
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary small d-flex align-items-center gap-2" for="setting_{{ $attemptsSetting->id }}">
                                <i class="bi bi-shield-lock text-muted"></i>
                                <span>Maximum Login Attempts</span>
                            </label>
                            <input class="form-control" type="number" required step="1"
                                min="{{ $limits['max_login_attempts'][0] }}" max="{{ $limits['max_login_attempts'][1] }}"
                                id="setting_{{ $attemptsSetting->id }}" name="settings[max_login_attempts]"
                                value="{{ old('settings.max_login_attempts', $attemptsSetting->setting_value) }}"
                                aria-describedby="setting_help_{{ $attemptsSetting->id }}" style="border-radius: 8px; height: 40px;">
                            <div class="form-text mt-1 text-muted small" id="setting_help_{{ $attemptsSetting->id }}">
                                Consecutive failed login attempts before temporary account throttling (range: {{ $limits['max_login_attempts'][0] }}–{{ $limits['max_login_attempts'][1] }}).
                            </div>
                        </div>
                    @endif

                    {{-- Session Timeout Minutes --}}
                    @if ($timeoutSetting)
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary small d-flex align-items-center gap-2" for="setting_{{ $timeoutSetting->id }}">
                                <i class="bi bi-clock text-muted"></i>
                                <span>Session Timeout (Minutes)</span>
                            </label>
                            <input class="form-control" type="number" required step="1"
                                min="{{ $limits['session_timeout_minutes'][0] }}" max="{{ $limits['session_timeout_minutes'][1] }}"
                                id="setting_{{ $timeoutSetting->id }}" name="settings[session_timeout_minutes]"
                                value="{{ old('settings.session_timeout_minutes', $timeoutSetting->setting_value) }}"
                                aria-describedby="setting_help_{{ $timeoutSetting->id }}" style="border-radius: 8px; height: 40px;">
                            <div class="form-text mt-1 text-muted small" id="setting_help_{{ $timeoutSetting->id }}">
                                Inactivity duration before a user is prompted to extend their session or signed out (range: {{ $limits['session_timeout_minutes'][0] }}–{{ $limits['session_timeout_minutes'][1] }}).
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Footer Actions --}}
        <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-3 p-3 bg-white rounded-3 border" style="box-shadow: 0 1px 3px rgba(15, 41, 66, .025);">
            <div class="d-flex align-items-center gap-2 text-secondary small">
                <i class="bi bi-info-circle text-primary" style="font-size: 15px;"></i>
                <span>Changes apply to subsequent login attempts and new user sessions upon saving.</span>
            </div>
            <button class="btn fw-semibold admin-btn-primary px-4 flex-shrink-0" type="submit" style="height: 40px;">
                <i class="bi bi-check-lg"></i>Save Settings
            </button>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var toggle = document.getElementById('maintenanceModeSwitch');
        if (!toggle) return;

        var originalState = toggle.checked;

        toggle.addEventListener('change', function () {
            var badgeCont = document.getElementById('maintenanceModeBadge');
            if (!badgeCont) return;

            var isChanged = (toggle.checked !== originalState);
            var changeNotice = isChanged ? ' <span class="fw-normal text-muted" style="font-size: 11px;">(Unsaved)</span>' : '';

            if (toggle.checked) {
                badgeCont.innerHTML = '<span class="badge bg-amber-50 text-amber-800 border border-amber-300 px-2.5 py-1" style="font-size: 12px; font-weight: 600;"><i class="bi bi-exclamation-triangle me-1"></i>Maintenance Mode' + changeNotice + '</span>';
            } else {
                badgeCont.innerHTML = '<span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200 px-2.5 py-1" style="font-size: 12px; font-weight: 600;"><i class="bi bi-check-circle me-1"></i>System Active' + changeNotice + '</span>';
            }
        });
    });
</script>
@endpush
