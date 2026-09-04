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

        $user = $event->user;
        $role = $user->role instanceof \BackedEnum
            ? $user->role->value
            : (string) ($user->role ?? 'user');

        AuditLog::record(
            action: $role.'.user.login',
            targetModule: 'authentication',
            user: $user,
            ipAddress: Request::ip(),
            details: 'User logged into system: '.$user->email,
            userAgent: Request::userAgent(),
            role: $role,
        );
    }
}