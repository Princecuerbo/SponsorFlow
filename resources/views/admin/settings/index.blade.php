@extends('layouts.app')
@section('title', 'System Settings')
@section('eyebrow', 'System Administrator')
@section('page-title', 'Security Settings')

@section('content')
    <div class="card sf-card">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('admin.settings.update') }}">
                @csrf
                <div class="row g-4">
                    @forelse($settings as $setting)
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="setting_{{ $setting->id }}" style="font-size: 0.85rem; color: #475569;">
                                {{ str($setting->setting_key)->headline() }}
                            </label>
                            <input class="form-control" id="setting_{{ $setting->id }}" name="settings[{{ $setting->setting_key }}]" value="{{ $setting->setting_value }}" style="border-radius: 8px; background-color: #f8fafc;">
                            <div class="form-text">{{ $setting->description }}</div>
                        </div>
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
