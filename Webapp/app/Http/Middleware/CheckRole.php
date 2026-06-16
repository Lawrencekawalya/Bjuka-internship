<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! $request->user()) {
            abort(401);
        }

        $userRole = $request->user()->role;

        // Check if user has any of the required roles
        foreach ($roles as $role) {
            if ($userRole->value === $role) {
                return $next($request);
            }
        }

        abort(403, 'Unauthorized access.');
    }
}
