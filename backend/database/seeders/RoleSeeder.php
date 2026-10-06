<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * Run this INSIDE a tenant (it seeds that school's roles). Idempotent, so
 * re-run it after upgrading to pick up new roles:
 * `php artisan tenants:seed --class=RoleSeeder`
 */
class RoleSeeder extends Seeder
{
    public const ROLES = ['super-admin', 'admin', 'accountant', 'teacher', 'driver', 'student', 'parent'];

    public function run(): void
    {
        foreach (self::ROLES as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
    }
}
