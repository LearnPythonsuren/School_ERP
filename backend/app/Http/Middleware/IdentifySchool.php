<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Stancl\Tenancy\Middleware\InitializeTenancyByRequestData;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the school from the `X-Tenant` header and switches every query
 * to that school's own database. Unlike the stock tenancy middleware, an
 * unknown or missing school ID returns a clear JSON error instead of a 500.
 *
 * Extends the stock middleware so it inherits tenancy's top middleware
 * priority — it must run before `auth:sanctum`, or tokens would be looked
 * up in the central database instead of the school's.
 */
class IdentifySchool extends InitializeTenancyByRequestData
{
    public const DEFAULT_TIMEZONE = 'Asia/Kolkata';

    public function handle($request, Closure $next): Response
    {
        /** @var Request $request */
        if ($request->isMethod('OPTIONS')) {
            return $next($request);
        }

        $id = trim((string) $request->header('X-Tenant', ''));

        if ($id === '') {
            return response()->json(['message' => 'Missing X-Tenant header (your School ID).'], 400);
        }

        $tenant = Tenant::find($id);
        if (! $tenant) {
            return response()->json(['message' => "School '{$id}' was not found. Check your School ID."], 404);
        }

        $this->tenancy->initialize($tenant);

        // Keep each school's cached roles/permissions separate.
        app(PermissionRegistrar::class)->cacheKey = 'spatie.permission.cache.tenant.'.$tenant->getTenantKey();

        // "Today" (attendance, dashboard, due dates) follows the school's own
        // clock — set in Settings, defaulting to the same zone the UI shows.
        $tz = Setting::where('key', 'timezone')->value('value');
        $tz = $tz && in_array($tz, timezone_identifiers_list(), true) ? $tz : self::DEFAULT_TIMEZONE;
        config(['app.timezone' => $tz]);
        date_default_timezone_set($tz);

        return $next($request);
    }
}
