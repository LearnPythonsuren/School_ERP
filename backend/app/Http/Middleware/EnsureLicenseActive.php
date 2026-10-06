<?php

namespace App\Http\Middleware;

use App\Models\License;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces the school's license on EVERY request, not just at login, so
 * suspending, revoking or letting a license expire blocks already
 * signed-in users immediately.
 */
class EnsureLicenseActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = tenant();

        if ($tenant && ! $tenant->licenseIsActive()) {
            return response()->json([
                'message' => "This school's license is not active. Please contact ".License::VENDOR.'.',
                'code'    => 'license_inactive',
            ], 403);
        }

        return $next($request);
    }
}
