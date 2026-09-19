<?php

use App\Http\Middleware\CheckMaintenanceMode;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\EnsureUserRole;
use App\Http\Middleware\EnsurePrivacyConsent;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'role' => CheckRole::class,
            'EnsureUserRole' => EnsureUserRole::class,
            'maintenance' => CheckMaintenanceMode::class,
            'student' => EnsurePrivacyConsent::class,
        ]);

        // Add this line to run maintenance mode globally on all web requests
        $middleware->web(append: [
            CheckMaintenanceMode::class,
        ]);

        $middleware->redirectUsersTo(function (Request $request): string {
            $user = $request->user();

            return match ($user?->role?->value) {
                'admin' => route('admin.dashboard'),
                'fassg' => route('fassg.dashboard'),
                'sponsor' => route('sponsor.dashboard'),
                'accounting' => route('accounting.dashboard'),
                'student' => route('student.dashboard'),
                default => route('dashboard'),
            };
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $handleSessionExpired = function (Request $request) {
            $staffPath = (string) config('app.staff_login_path', 'dorsu-staff-gate');
            $adminPath = (string) config('app.admin_login_path', 'dorsu-sysadmin-gate');

            $currentPath = trim($request->path(), '/');
            $referer = (string) $request->headers->get('referer', '');
            $refererPath = '';
            if ($referer !== '') {
                $refererPath = trim((string) parse_url($referer, PHP_URL_PATH), '/');
            }

            $user = $request->user();
            $targetRoute = 'login';

            if ($user) {
                if ($user->isAdmin()) {
                    $targetRoute = 'admin.login';
                } elseif ($user->isFassg() || $user->isSponsor() || $user->isAccounting()) {
                    $targetRoute = 'staff.login';
                } else {
                    $targetRoute = 'login';
                }
            } else {
                $checkPaths = array_filter([$currentPath, $refererPath]);

                foreach ($checkPaths as $path) {
                    if (
                        str_starts_with($path, 'admin') ||
                        str_starts_with($path, $adminPath) ||
                        preg_match('/(^|\/)(' . preg_quote($adminPath, '/') . '|admin)(\/|$)/i', $path)
                    ) {
                        $targetRoute = 'admin.login';
                        break;
                    }

                    if (
                        str_starts_with($path, 'fassg') ||
                        str_starts_with($path, 'sponsor') ||
                        str_starts_with($path, 'accounting') ||
                        str_starts_with($path, 'staff') ||
                        str_starts_with($path, $staffPath) ||
                        preg_match('/(^|\/)(' . preg_quote($staffPath, '/') . '|fassg|sponsor|accounting|staff)(\/|$)/i', $path)
                    ) {
                        $targetRoute = 'staff.login';
                        break;
                    }

                    if (str_starts_with($path, 'student') || preg_match('/(^|\/)student(\/|$)/i', $path)) {
                        $targetRoute = 'login';
                        break;
                    }
                }
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Your session has expired due to inactivity. Please sign in again.',
                    'redirect' => route($targetRoute, ['session_expired' => 1]),
                ], 419);
            }

            return redirect()
                ->route($targetRoute, ['session_expired' => 1])
                ->with('warning', 'Your session has expired due to inactivity. Please sign in again.');
        };

        $exceptions->render(function (TokenMismatchException $e, Request $request) use ($handleSessionExpired) {
            return $handleSessionExpired($request);
        });

        $exceptions->render(function (HttpException $e, Request $request) use ($handleSessionExpired) {
            if ($e->getStatusCode() === 419) {
                return $handleSessionExpired($request);
            }
        });
    })->create();
