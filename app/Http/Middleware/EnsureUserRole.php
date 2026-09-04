<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole extends CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        return parent::handle($request, $next, ...$roles);
    }
}
