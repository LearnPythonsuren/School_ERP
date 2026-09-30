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
        return $q->latest()->paginate($request->integer('per_page', 25));
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
            'expires_at' => ['nullable', 'date'],
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

    public function suspend(License $license) { return $this->setStatus($license, 'suspended'); }
    public function activate(License $license) { return $this->setStatus($license, 'active'); }
    public function revoke(License $license)   { return $this->setStatus($license, 'revoked'); }

    /** Flip the license status AND mirror it onto the linked school. */
    private function setStatus(License $license, string $status)
    {
        $license->update(['status' => $status]);

        if ($license->tenant_id && $tenant = Tenant::find($license->tenant_id)) {
            $tenant->update([
                'license_status' => $status === 'active' ? 'active' : 'suspended',
                'is_active'      => $status === 'active',
            ]);
        }

        return response()->json($license);
    }
}
