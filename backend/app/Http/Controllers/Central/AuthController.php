<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\CentralUser;
use App\Models\License;
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
    /** The seeded default; the UI nags until it is changed. */
    private const DEFAULT_PASSWORD = 'ChangeMe123!';

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
            'token'  => $user->createToken('control-plane', ['*'], $this->tokenExpiry())->plainTextToken,
            'user'   => $this->profile($user) + ['must_change_password' => $data['password'] === self::DEFAULT_PASSWORD],
            'vendor' => License::VENDOR,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($this->profile($request->user()));
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Signed out.']);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password'     => ['required', 'string', 'min:10', 'confirmed', 'different:current_password'],
        ]);
        $user = $request->user();
        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages(['current_password' => ['Your current password is incorrect.']]);
        }
        $user->update(['password' => Hash::make($data['new_password'])]);
        $user->tokens()->delete();
        return response()->json(['message' => 'Password updated. Please sign in again.']);
    }

    private function profile(CentralUser $u): array
    {
        return ['id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'role' => $u->role];
    }
}
