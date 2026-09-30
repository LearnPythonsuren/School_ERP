<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * Run this INSIDE a tenant (it seeds that school's roles).
 * `php artisan tenants:seed --class=RoleSeeder`
 */
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['super-admin', 'admin', 'teacher', 'student', 'parent'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
    }
}
