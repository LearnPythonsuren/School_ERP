<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Setting::pluck('value', 'key'));
    }

    public function update(Request $request): JsonResponse
    {
        abort_unless(
            $request->user()->hasAnyRole(['admin', 'super-admin']),
            403, 'Only a school admin can change settings.'
        );
        $data = $request->validate([
            'settings'   => ['required', 'array'],
            'settings.*' => ['nullable', 'string'],
        ]);
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
            'licensee'   => $t?->licensee,
            'key'        => $t?->license_key,
            'status'     => $t?->license_status,
            'plan'       => $t?->plan,
            'expires_at' => $t?->license_expires_at,
            'vendor'     => \App\Models\License::VENDOR,
        ]);
    }
}
