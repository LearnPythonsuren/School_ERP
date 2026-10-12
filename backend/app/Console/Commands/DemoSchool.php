<?php

namespace App\Console\Commands;

use App\Models\License;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

/**
 * A disposable demo school for sales meetings. Re-running wipes it and builds
 * it again with fresh data (attendance for the past week, fees, results…),
 * so the dashboard always looks alive. Never touches any other school.
 *
 *   php artisan scholar:demo                     random password, printed once
 *   php artisan scholar:demo --password=secret   choose the password
 */
class DemoSchool extends Command
{
    protected $signature = 'scholar:demo
        {--id=demo : School ID of the demo school}
        {--password= : Password for every demo login (random if omitted)}';

    protected $description = 'Create or reset the demo school used for sales demos';

    public function handle(): int
    {
        $id = (string) $this->option('id');
        $password = (string) ($this->option('password') ?: Str::password(10, symbols: false));
        $name = 'Greenfield Public School (Demo)';

        if ($existing = Tenant::find($id)) {
            if (! str_contains((string) $existing->licensee, 'Demo')) {
                $this->error("School '{$id}' exists and is not a demo school. Refusing to touch it.");
                return self::FAILURE;
            }
            $this->info("Removing the old demo school '{$id}'…");
            License::where('tenant_id', $id)->delete();
            $existing->delete(); // also drops its database
        }

        $license = License::create([
            'key' => License::generateKey(), 'licensee' => 'Demo school (Chenthur Info Tech)',
            'plan' => 'pro', 'status' => 'active', 'tenant_id' => $id, 'issued_by' => License::VENDOR,
        ]);
        $tenant = Tenant::create([
            'id' => $id, 'name' => $name, 'plan' => 'pro', 'is_active' => true,
            'licensee' => $license->licensee, 'license_key' => $license->key, 'license_status' => 'active',
        ]);
        $tenant->domains()->create(['domain' => $id]);

        $tenant->run(function () use ($tenant, $password, $name) {
            app(PermissionRegistrar::class)->cacheKey = 'spatie.permission.cache.tenant.'.$tenant->getTenantKey();
            (new RoleSeeder())->run();
            (new DemoDataSeeder())->run();
            User::create(['name' => 'Principal (Demo)', 'email' => 'admin@greenfield.test', 'password' => Hash::make($password)])
                ->assignRole('admin');
            User::query()->update(['password' => Hash::make($password)]);
            Setting::updateOrCreate(['key' => 'school_name'], ['value' => $name]);
        });

        $this->newLine();
        $this->info("Demo school ready. School ID: {$id}");
        $this->table(['Login', 'Role'], [
            ['admin@greenfield.test', 'admin — everything'],
            ['accountant@greenfield.test', 'accountant — fees, reports'],
            ['teacher@greenfield.test', 'teacher — attendance, exams, library'],
            ['driver@greenfield.test', 'driver — bus GPS'],
            ['parent@greenfield.test', 'parent — timetable, announcements'],
        ]);
        $this->line("Password for all of them: {$password}");

        return self::SUCCESS;
    }
}
