<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.settings.index', ['settings' => SystemSetting::query()->orderBy('setting_key')->get()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*' => ['nullable', 'string', 'max:5000'],
        ]);

        // Normalize maintenance_mode: checkbox submits 'true' when checked,
        // hidden fallback submits 'false' when unchecked
        if (array_key_exists('maintenance_mode', $validated['settings'])) {
            $validated['settings']['maintenance_mode'] = in_array(
                strtolower((string) $validated['settings']['maintenance_mode']),
                ['true', '1', 'yes', 'on'],
                true
            ) ? 'true' : 'false';
        }

        foreach ($validated['settings'] as $key => $value) {
            SystemSetting::query()->where('setting_key', $key)->update(['setting_value' => $value]);
        }

        AuditLog::record('admin.settings.updated', 'system_settings', $request->user(), $request->ip());

        return back()->with('status', 'System settings updated.');
    }
}
