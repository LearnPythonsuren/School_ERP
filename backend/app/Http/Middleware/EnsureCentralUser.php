<?php

namespace App\Http\Middleware;

use App\Models\CentralUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Only product-owner (control plane) accounts may use /api/central/*. */
class EnsureCentralUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() instanceof CentralUser) {
            return response()->json(['message' => 'Control-plane access only.'], 403);
        }

        return $next($request);
    }
}
