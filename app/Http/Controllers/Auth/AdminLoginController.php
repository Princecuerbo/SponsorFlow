<?php

namespace App\Http\Controllers\Auth;

class AdminLoginController extends PortalLoginController
{
    /** @var list<string> */
    protected array $allowedRoles = ['admin'];

    protected string $loginView = 'auth.admin-login';

    protected string $redirectRoute = 'admin.dashboard';

    protected string $accessDeniedMessage = 'Access denied. Only System Administrators may use this portal.';
}