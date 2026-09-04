<?php

use App\Http\Middleware\CheckMaintenanceMode;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\EnsureUserRole;
use App\Http\Middleware\EnsurePrivacyConsent;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

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
        //
    })->create();
