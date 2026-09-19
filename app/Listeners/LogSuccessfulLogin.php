<?php

namespace App\Listeners;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Request;

class LogSuccessfulLogin
{
    public function handle(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        try {
            $user = $event->user;
            $role = $user->role instanceof \BackedEnum
                ? $user->role->value
                : (string) ($user->role ?? 'user');

            $userAgent = Request::userAgent() ?? '';
            $userAgent = strlen($userAgent) > 255 ? mb_substr($userAgent, 0, 255) : $userAgent;

            AuditLog::record(
                action: $role.'.user.login',
                targetModule: 'authentication',
                user: $user,
                ipAddress: Request::ip(),
                details: 'User logged into system: '.$user->email,
                userAgent: $userAgent !== '' ? $userAgent : null,
                role: $role,
            );
        } catch (\Throwable) {
            // Login auditing is best-effort: a failed audit write must never
            // crash the authentication response or drop the browser connection.
        }
    }
}