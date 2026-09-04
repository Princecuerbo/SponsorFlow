<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use ValueError;

class CheckRole
{
    /**
     * @param  string  ...$roles  student, fassg, sponsor, accounting, admin
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return redirect()->guest(route('login'));
        }

        if (! $user->isActive()) {
            abort(403, 'Your account is not active.');
        }

        $allowedRoles = $this->resolveRoles($roles);

        if (! $user->hasAnyRole(...$allowedRoles)) {
            abort(403, 'You are not authorized to access this resource.');
        }

        if ($this->mustBeGetOnly($request, $allowedRoles) && ! $this->isGetOrHead($request)) {
            abort(405, 'Accounting access is read-only. Only GET requests are allowed.');
        }

        return $next($request);
    }

    /**
     * @param  list<string>  $roles
     * @return list<UserRole>
     */
    private function resolveRoles(array $roles): array
    {
        $normalized = [];

        foreach ($roles as $role) {
            foreach (preg_split('/[|,]/', $role) ?: [] as $value) {
                $value = trim($value);

                if ($value === '') {
                    continue;
                }

                $normalized[] = $value;
            }
        }

        if ($normalized === []) {
            abort(500, 'CheckRole middleware requires at least one role.');
        }

        try {
            return array_map(
                static fn (string $role): UserRole => UserRole::from($role),
                $normalized,
            );
        } catch (ValueError) {
            abort(500, 'CheckRole middleware received an invalid role.');
        }
    }

    /**
     * @param  list<UserRole>  $allowedRoles
     */
    private function mustBeGetOnly(Request $request, array $allowedRoles): bool
    {
        $isAccountingOnlyGroup = count($allowedRoles) === 1
            && $allowedRoles[0] === UserRole::Accounting;

        return $isAccountingOnlyGroup || $request->routeIs('accounting.*');
    }

    private function isGetOrHead(Request $request): bool
    {
        return in_array($request->method(), ['GET', 'HEAD'], true);
    }
}
