<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\License;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;
use Stancl\Tenancy\Database\Models\Domain;
use Throwable;

class TenantController extends Controller
{
    private const COLUMNS = ['id', 'name', 'plan', 'is_active', 'licensee', 'license_key', 'license_status', 'license_expires_at', 'created_at'];

    public function index()
    {
        return Tenant::with('domains:id,domain,tenant_id')->orderBy('created_at', 'desc')->get(self::COLUMNS)
            ->map(fn (Tenant $t) => $this->shape($t));
    }

    public function show(Tenant $tenant): JsonResponse
    {
        return response()->json($this->shape($tenant->load('domains')));
    }

    /** Headline numbers for the owner's control-plane home screen. */
    public function overview(): JsonResponse
    {
        $soon = now()->addDays(30);

        return response()->json([
            'schools_total'      => Tenant::count(),
            'schools_active'     => Tenant::where('license_status', 'active')->where('is_active', true)->count(),
            'schools_suspended'  => Tenant::where('license_status', '!=', 'active')->count(),
            'licenses_total'     => License::count(),
            'licenses_unused'    => License::whereNull('tenant_id')->where('status', 'active')->count(),
            'licenses_expiring'  => License::where('status', 'active')->whereNotNull('expires_at')
                                        ->whereBetween('expires_at', [now(), $soon])->count(),
            'plans'              => Tenant::selectRaw('plan, count(*) as total')->groupBy('plan')->pluck('total', 'plan'),
        ]);
    }

    /**
     * Provision a school. Requires a valid, unused license key issued by
     * the vendor. Creates the isolated DB, seeds roles, and the first admin.
     * If any step fails the half-built school is removed and the key freed.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id'             => ['required', 'alpha_dash', 'lowercase', 'max:40', 'unique:tenants,id'],
            'name'           => ['required', 'string', 'max:150'],
            'license_key'    => ['required', 'string', 'exists:licenses,key'],
            'admin_name'     => ['required', 'string', 'max:120'],
            'admin_email'    => ['required', 'email'],
            'admin_password' => ['required', 'string', 'min:8'],
            'domain'         => ['nullable', 'string', 'max:190'],
        ]);

        $license = License::where('key', $data['license_key'])->first();

        if (! $license->isUsable()) {
            return response()->json(['message' => 'This license key is not active or has expired.'], 422);
        }
        if ($license->tenant_id) {
            return response()->json(['message' => 'This license key is already assigned to a school.'], 422);
        }
        $domain = $data['domain'] ?? $data['id'];
        if (Domain::where('domain', $domain)->exists()) {
            return response()->json(['message' => "The domain '{$domain}' is already in use."], 422);
        }

        $tenant = null;
        try {
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
            $tenant->domains()->create(['domain' => $domain]);

            // Seed roles + create the school's first admin inside its own DB.
            $tenant->run(function () use ($data, $tenant) {
                app(PermissionRegistrar::class)->cacheKey = 'spatie.permission.cache.tenant.'.$tenant->getTenantKey();
                (new RoleSeeder())->run();
                $user = User::create([
                    'name'     => $data['admin_name'],
                    'email'    => $data['admin_email'],
                    'password' => Hash::make($data['admin_password']),
                ]);
                $user->assignRole('admin');

                // Starting school profile (editable later in Settings).
                $year = (int) now()->format('Y') - (now()->month < 6 ? 1 : 0);
                foreach (['school_name' => $data['name'], 'currency' => 'INR', 'timezone' => 'Asia/Kolkata', 'academic_year' => $year.'-'.substr((string) ($year + 1), 2)] as $key => $value) {
                    Setting::updateOrCreate(['key' => $key], ['value' => $value]);
                }
            });

            $license->update(['tenant_id' => $tenant->id]);
        } catch (Throwable $e) {
            report($e);
            $tenant?->delete();
            return response()->json(['message' => 'Provisioning failed: '.$e->getMessage()], 500);
        }

        return response()->json([
            'message' => 'School provisioned and licensed.',
            'tenant'  => $this->shape($tenant->load('domains')),
        ], 201);
    }

    /** Suspend a school. Its license key is suspended too so the two never disagree. */
    public function suspend(Tenant $tenant): JsonResponse
    {
        $tenant->update(['license_status' => 'suspended', 'is_active' => false]);
        License::where('tenant_id', $tenant->id)->where('status', 'active')->update(['status' => 'suspended']);
        return response()->json(['message' => 'School suspended.']);
    }

    /** Re-activate a school — only if its license key is not revoked or expired. */
    public function activate(Tenant $tenant): JsonResponse
    {
        $license = License::where('tenant_id', $tenant->id)->first();

        if ($license?->status === 'revoked') {
            return response()->json(['message' => 'This school\'s license key was revoked. Issue a new key and renew.'], 422);
        }
        if ($license?->expires_at && $license->expires_at->isPast()) {
            return response()->json(['message' => 'This school\'s license has expired. Renew it on the Licenses screen first.'], 422);
        }

        $license?->update(['status' => 'active']);
        $tenant->update(['license_status' => 'active', 'is_active' => true]);
        return response()->json(['message' => 'School re-activated.']);
    }

    private function shape(Tenant $t): array
    {
        return [
            'id'                 => $t->id,
            'name'               => $t->name,
            'plan'               => $t->plan,
            'is_active'          => (bool) $t->is_active,
            'licensee'           => $t->licensee,
            'license_key'        => $t->license_key,
            'license_status'     => $t->licenseIsActive() ? 'active' : ($t->license_status === 'active' ? 'expired' : $t->license_status),
            'license_expires_at' => $t->license_expires_at,
            'domains'            => $t->relationLoaded('domains') ? $t->domains->pluck('domain') : [],
            'created_at'         => $t->created_at,
        ];
    }
}
