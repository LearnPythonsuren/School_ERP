<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\License;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SettingsController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Setting::pluck('value', 'key'));
    }

    /** Admin-only (EnsureRole). Keys are snake_case, values plain strings. */
    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'settings'   => ['required', 'array', 'max:50'],
            'settings.*' => ['nullable', 'string', 'max:1000'],
        ]);
        foreach (array_keys($data['settings']) as $key) {
            if (! preg_match('/^[a-z][a-z0-9_]{0,59}$/', (string) $key)) {
                throw ValidationException::withMessages(['settings' => ["Invalid setting name: {$key}"]]);
            }
        }
        $tz = $data['settings']['timezone'] ?? null;
        if ($tz !== null && ! in_array($tz, timezone_identifiers_list(), true)) {
            throw ValidationException::withMessages(['settings.timezone' => ["Unknown timezone: {$tz}"]]);
        }
        foreach ($data['settings'] as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        return response()->json(Setting::pluck('value', 'key'));
    }

    /** The school's own license status (from Chenthur Info Tech) for the UI. */
    public function license(): JsonResponse
    {
        $t = tenant();
        return response()->json([
            'school'     => $t?->name,
            'licensee'   => $t?->licensee,
            'key'        => $t?->license_key,
            'status'     => $t?->licenseIsActive() ? 'active' : $t?->license_status,
            'plan'       => $t?->plan,
            'expires_at' => $t?->license_expires_at,
            'vendor'     => License::VENDOR,
        ]);
    }
}
