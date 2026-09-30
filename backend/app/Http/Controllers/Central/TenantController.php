<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\License;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class TenantController extends Controller
{
    public function index()
    {
        return Tenant::orderBy('created_at', 'desc')->get([
            'id', 'name', 'plan', 'is_active', 'licensee', 'license_key', 'license_status', 'license_expires_at',
        ]);
    }

    /**
     * Provision a school. Requires a valid, unused license key issued by
     * the vendor. Creates the isolated DB, seeds roles, and the first admin.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id'             => ['required', 'alpha_dash', 'unique:tenants,id'],
            'name'           => ['required', 'string'],
            'license_key'    => ['required', 'string', 'exists:licenses,key'],
            'admin_name'     => ['required', 'string'],
            'admin_email'    => ['required', 'email'],
            'admin_password' => ['required', 'string', 'min:8'],
            'domain'         => ['nullable', 'string'],
        ]);

        $license = License::where('key', $data['license_key'])->first();

        if (! $license->isUsable()) {
            return response()->json(['message' => 'This license key is not active.'], 422);
        }
        if ($license->tenant_id) {
            return response()->json(['message' => 'This license key is already assigned to a school.'], 422);
        }

        $tenant = Tenant::create([
            'id'                 => $data['id'],
            'name'               => $data['name'],
            'plan'               => $license->plan,
            'is_active'          => true,
            'licensee'           => $license->licensee,
            'license_key'        => $license->key,
            'license_status'     => 'active',
            'license_expires_at' => $license->expires_at,
        ]);
        $tenant->domains()->create(['domain' => $data['domain'] ?? $data['id']]);

        $license->update(['tenant_id' => $tenant->id]);

        // Seed roles + create the school's first admin inside its own DB.
        $tenant->run(function () use ($data) {
            (new RoleSeeder())->run();
            $user = User::create([
                'name'     => $data['admin_name'],
                'email'    => $data['admin_email'],
                'password' => Hash::make($data['admin_password']),
            ]);
            $user->assignRole('admin');
        });

        return response()->json([
            'message' => 'School provisioned and licensed.',
            'tenant'  => ['id' => $tenant->id, 'name' => $tenant->name, 'plan' => $tenant->plan, 'license_status' => 'active'],
        ], 201);
    }

    public function suspend(Tenant $tenant): JsonResponse
    {
        $tenant->update(['license_status' => 'suspended', 'is_active' => false]);
        return response()->json(['message' => 'School suspended.']);
    }

    public function activate(Tenant $tenant): JsonResponse
    {
        $tenant->update(['license_status' => 'active', 'is_active' => true]);
        return response()->json(['message' => 'School re-activated.']);
    }
}
