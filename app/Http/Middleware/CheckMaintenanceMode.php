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

        // 1. Authenticated administrators bypass maintenance checks across all routes
        $user = $request->user();
        if ($user !== null && $user->isAdmin()) {
            return $next($request);
        }

        // 2. Exclude the public landing page
        if ($request->is('/') || $request->routeIs('landing') || $request->routeIs('home')) {
            return $next($request);
        }

        // 3. Exclude admin login gate and admin routes so administrators can log in and manage settings
        $adminPath = (string) config('app.admin_login_path', 'dorsu-sysadmin-gate');
        if (
            $request->is('admin*') ||
            $request->is($adminPath . '*') ||
            $request->is('admin/login*') ||
            $request->routeIs('admin.*') ||
            $request->routeIs('admin.login*')
        ) {
            return $next($request);
        }

        // Exclude system health check
        if ($request->is('up')) {
            return $next($request);
        }

        // 4. Enforce maintenance on Student, Staff, and portal routes using the custom friendly view
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'SponsorFlow is currently undergoing scheduled maintenance to improve services. Please check back shortly or contact the FASSG office for urgent inquiries.',
            ], 503);
        }

        return response()->view('errors.maintenance', [], 503);
    }
}
