<?php

namespace App\Http\Controllers\Auth;

class StudentLoginController extends PortalLoginController
{
    /** @var list<string> */
    protected array $allowedRoles = ['student'];

    protected string $loginView = 'auth.student-login';

    protected string $redirectRoute = 'student.verification.show';

    protected string $accessDeniedMessage = 'Access denied. Staff and Admin users must use their designated portal logins.';
}