<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\License;
use App\Models\Tenant;
use Illuminate\Http\Request;

/**
 * The vendor (Chenthur Info Tech) issues and manages license keys here.
 * Suspending/revoking a key immediately blocks the linked school.
 */
class LicenseController extends Controller
{
    public function index(Request $request)
    {
        $q = License::query();
        if ($status = $request->query('status')) {
            $q->where('status', $status);
        }
        if ($request->boolean('unassigned')) {
            $q->whereNull('tenant_id');
        }
        if ($s = $request->query('search')) {
            $q->where(fn ($w) => $w->where('key', 'like', "%{$s}%")->orWhere('licensee', 'like', "%{$s}%"));
        }
        return $q->latest()->paginate($this->perPage($request));
    }

    public function show(License $license)
    {
        return response()->json($license);
    }

    /** Issue a new key for a customer. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'licensee'   => ['required', 'string', 'max:150'],
            'plan'       => ['nullable', 'in:starter,pro,enterprise'],
            'expires_at' => ['nullable', 'date', 'after:today'],
        ]);

        $license = License::create([
            'key'        => License::generateKey(),
            'licensee'   => $data['licensee'],
            'plan'       => $data['plan'] ?? 'starter',
            'status'     => 'active',
            'issued_by'  => License::VENDOR,
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        return response()->json($license, 201);
    }

    /** Renew (new expiry) or change plan / licensee. Mirrors onto the linked school. */
    public function update(Request $request, License $license)
    {
        $data = $request->validate([
            'licensee'   => ['sometimes', 'string', 'max:150'],
            'plan'       => ['sometimes', 'in:starter,pro,enterprise'],
            'expires_at' => ['nullable', 'date'],
        ]);
        $license->update($data);

        if ($tenant = $this->tenantOf($license)) {
            $tenant->update([
                'plan'               => $license->plan,
                'licensee'           => $license->licensee,
                'license_expires_at' => $license->expires_at,
            ]);
        }

        return response()->json($license->fresh());
    }

    public function suspend(License $license) { return $this->setStatus($license, 'suspended'); }
    public function revoke(License $license)  { return $this->setStatus($license, 'revoked'); }

    public function activate(License $license)
    {
        abort_if($license->status === 'revoked', 422, 'A revoked key cannot be re-activated. Issue a new key instead.');
        return $this->setStatus($license, 'active');
    }

    /** Flip the license status AND mirror it onto the linked school. */
    private function setStatus(License $license, string $status)
    {
        $license->update(['status' => $status]);

        if ($tenant = $this->tenantOf($license)) {
            $tenant->update([
                'license_status' => $status === 'active' ? 'active' : 'suspended',
                'is_active'      => $status === 'active',
            ]);
        }

        return response()->json($license);
    }

    private function tenantOf(License $license): ?Tenant
    {
        return $license->tenant_id ? Tenant::find($license->tenant_id) : null;
    }
}
