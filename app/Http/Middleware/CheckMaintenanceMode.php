<?php

namespace App\Http\Middleware;

use App\Models\SystemSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! app('db')->getSchemaBuilder()->hasTable('system_settings')) {
            return $next($request);
        }

        $override = SystemSetting::get('maintenance_mode', 'false');
        $isEnabled = in_array(strtolower((string) $override), ['1', 'true', 'yes', 'on'], true);

        if (! $isEnabled) {
            return $next($request);
        }

        $user = $request->user();

        if ($user !== null && $user->isAdmin()) {
            return $next($request);
        }

        if ($request->is('login') || $request->is('admin/login') || $request->is('staff/login') || $request->routeIs('login') || $request->routeIs('admin.login') || $request->routeIs('staff.login') || $request->routeIs('logout')) {
            return $next($request);
        }

        abort(503, 'The system is currently in maintenance mode.');
    }
}
