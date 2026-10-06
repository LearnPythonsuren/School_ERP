<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Role-wise access for school routes. Usage in routes:
 *   ->middleware(EnsureRole::class.':admin,teacher')
 * `super-admin` always passes.
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasAnyRole([...$roles, 'super-admin'])) {
            return response()->json(['message' => 'Your role does not have access to this.'], 403);
        }

        return $next($request);
    }
}
