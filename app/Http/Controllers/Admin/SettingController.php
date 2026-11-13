<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSecuritySettingsRequest;
use App\Models\AuditLog;
use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.settings.index', [
            'settings' => SystemSetting::query()->whereIn('setting_key', ['maintenance_mode', ...array_keys(UpdateSecuritySettingsRequest::LIMITS)])->orderBy('setting_key')->get(),
            'limits' => UpdateSecuritySettingsRequest::LIMITS,
        ]);
    }

    public function update(UpdateSecuritySettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Normalize maintenance_mode: checkbox submits 'true' when checked,
        // hidden fallback submits 'false' when unchecked
        if (array_key_exists('maintenance_mode', $validated['settings'])) {
            $validated['settings']['maintenance_mode'] = in_array(
                strtolower((string) $validated['settings']['maintenance_mode']),
                ['true', '1', 'yes', 'on'],
                true
            ) ? 'true' : 'false';
        }

        DB::transaction(function () use ($request, $validated): void {
            $settings = SystemSetting::query()->whereIn('setting_key', array_keys($validated['settings']))->orderBy('id')->lockForUpdate()->get()->keyBy('setting_key');
            if ($settings->count() !== count($validated['settings'])) {
                throw ValidationException::withMessages(['settings' => 'A submitted setting no longer exists. Reload the settings page.']);
            }
            $changes = [];
            foreach ($validated['settings'] as $key => $value) {
                $setting = $settings[$key];
                $changes[$key] = ['before' => $setting->setting_value, 'after' => (string) $value];
                $setting->update(['setting_value' => (string) $value]);
            }
            AuditLog::record('admin.settings.updated', 'system_settings', $request->user(), $request->ip(), json_encode($changes, JSON_THROW_ON_ERROR));
        }, 3);

        return back()->with('status', 'System settings updated.');
    }
}
