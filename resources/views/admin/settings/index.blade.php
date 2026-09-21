@extends('layouts.app')
@section('title', 'System Settings')
@section('eyebrow', 'System Administrator · Settings')
@section('page-title', 'Security Settings')
@section('subtitle', 'Configure maintenance mode and portal security options.')

@section('content')
    <div class="card sf-card">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('admin.settings.update') }}">
                @csrf
                <div class="row g-4">
                    @forelse($settings as $setting)
                        @if($setting->setting_key === 'maintenance_mode')
                            {{-- Maintenance Mode Toggle Switch --}}
                            @php
                                $isMaintenanceOn = in_array(strtolower((string) $setting->setting_value), ['true', '1', 'yes', 'on'], true);
                            @endphp
                            <div class="col-md-6">
                                <label class="form-label fw-semibold d-block" style="font-size: 0.85rem; color: #475569;">
                                    Maintenance Mode
                                </label>
                                <div class="form-check form-switch d-flex align-items-center gap-2 mt-1">
                                    {{-- Hidden field so 'false' is submitted when unchecked --}}
                                    <input type="hidden" name="settings[maintenance_mode]" value="false">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                        id="maintenanceModeSwitch"
                                        name="settings[maintenance_mode]"
                                        value="true"
                                        {{ $isMaintenanceOn ? 'checked' : '' }}
                                        style="width: 3rem; height: 1.5rem; cursor: pointer;">
                                    <label class="form-check-label ms-1" for="maintenanceModeSwitch" id="maintenanceModeBadge">
                                        <span class="badge {{ $isMaintenanceOn ? 'bg-danger' : 'bg-success' }}" style="font-size: 0.75rem;">
                                            {{ $isMaintenanceOn ? 'Active (System Restricted)' : 'Disabled (System Active)' }}
                                        </span>
                                    </label>
                                </div>
                                <small class="text-muted d-block mt-1">{{ $setting->description ?? 'Toggle to enable or disable system maintenance mode across student and staff portals.' }}</small>
                            </div>
                        @else
                            {{-- Standard text input for other settings --}}
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="setting_{{ $setting->id }}" style="font-size: 0.85rem; color: #475569;">
                                    {{ str($setting->setting_key)->headline() }}
                                </label>
                                <input class="form-control" id="setting_{{ $setting->id }}" name="settings[{{ $setting->setting_key }}]" value="{{ $setting->setting_value }}" style="border-radius: 8px; background-color: #f8fafc;">
                                <div class="form-text">{{ $setting->description }}</div>
                            </div>
                        @endif
                    @empty
                        <div class="col-12 text-secondary">No system settings have been configured.</div>
                    @endforelse
                </div>
                <div class="mt-4">
                    <button class="btn fw-semibold" type="submit" style="background-color: #0f294a; color: #fff; border: none; border-radius: 8px; padding: 0.6rem 1.25rem;">
                        <i class="bi bi-save me-2"></i>Save settings
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var toggle = document.getElementById('maintenanceModeSwitch');
        if (!toggle) return;

        toggle.addEventListener('change', function () {
            var badge = document.querySelector('#maintenanceModeBadge .badge');
            if (!badge) return;

            if (toggle.checked) {
                badge.className = 'badge bg-danger';
                badge.style.fontSize = '0.75rem';
                badge.textContent = 'Active (System Restricted)';
            } else {
                badge.className = 'badge bg-success';
                badge.style.fontSize = '0.75rem';
                badge.textContent = 'Disabled (System Active)';
            }
        });
    });
</script>
@endpush

