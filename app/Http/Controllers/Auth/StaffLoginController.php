<?php

namespace App\Http\Controllers\Auth;

class StaffLoginController extends PortalLoginController
{
    /** @var list<string> */
    protected array $allowedRoles = ['fassg', 'sponsor', 'accounting'];

    protected string $loginView = 'auth.staff-login';

    protected string $redirectRoute = 'dashboard';

    protected string $accessDeniedMessage = 'Access denied. Students and System Administrators must use their designated portal logins.';

    protected function destinationRoute(\App\Models\User $user): string
    {
        return match ($user->role->value) {
            'fassg' => 'fassg.dashboard',
            'sponsor' => 'sponsor.dashboard',
            'accounting' => 'accounting.dashboard',
        };
    }
}
