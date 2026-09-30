<?php

namespace App\Models;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;

/**
 * A Tenant is one school. Database-per-tenant isolation. The license_*
 * columns are the enforcement point: a school can only be used while its
 * license (issued by Chenthur Info Tech) is active and unexpired.
 */
class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'plan',
            'is_active',
            'licensee',
            'license_key',
            'license_status',
            'license_expires_at',
        ];
    }

    public function licenseIsActive(): bool
    {
        if ($this->license_status !== 'active') {
            return false;
        }
        if ($this->license_expires_at && now()->greaterThan($this->license_expires_at)) {
            return false;
        }
        return true;
    }
}
