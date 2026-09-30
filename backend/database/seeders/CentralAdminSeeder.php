<?php

namespace Database\Seeders;

use App\Models\CentralUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Creates the first product-owner (Chenthur Info Tech) super-admin.
 * Override via env: CENTRAL_ADMIN_EMAIL, CENTRAL_ADMIN_PASSWORD.
 * Run centrally:  php artisan db:seed --class=CentralAdminSeeder
 */
class CentralAdminSeeder extends Seeder
{
    public function run(): void
    {
        CentralUser::firstOrCreate(
            ['email' => env('CENTRAL_ADMIN_EMAIL', 'owner@chenthur.tech')],
            [
                'name'     => env('CENTRAL_ADMIN_NAME', 'Chenthur Owner'),
                'password' => Hash::make(env('CENTRAL_ADMIN_PASSWORD', 'ChangeMe123!')),
                'role'     => 'super-admin',
            ],
        );
    }
}
