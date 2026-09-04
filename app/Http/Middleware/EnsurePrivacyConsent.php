<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePrivacyConsent
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return $next($request);
        }

        $user = auth()->user();

        if ($user && ($user->role === 'student' || (method_exists($user, 'isStudent') && $user->isStudent()))) {
            $isLogoutRoute = $request->routeIs('logout') || $request->is('logout');

            if ($isLogoutRoute) {
                return $next($request);
            }

            $hasConsentedThisSession = session()->get('privacy_consented_session', false);

            if (! $hasConsentedThisSession) {
                return redirect()->route('student.dashboard');
            }
        }

        return $next($request);
    }
}
