<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\CentralUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Login for the product owner (Chenthur Info Tech) control plane.
 * Runs on the central domain — no tenant context.
 */
class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = CentralUser::where('email', $data['email'])->first();
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => ['Invalid credentials.']]);
        }

        return response()->json([
            'token' => $user->createToken('control-plane')->plainTextToken,
            'user'  => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'role' => $user->role],
            'vendor' => \App\Models\License::VENDOR,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $u = $request->user();
        return response()->json(['id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'role' => $u->role]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Signed out.']);
    }
}
