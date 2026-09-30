<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Runs AFTER `php artisan tenancy:install` has created the base
 * `tenants` table (id + data json). We promote a few real columns so a
 * school has a name, a subscription plan, and an active flag you can
 * gate access on (useful for the SaaS model — suspend a school that
 * hasn't paid).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('name')->nullable()->after('id');
            $table->string('plan')->default('starter')->after('name'); // starter | pro | enterprise
            $table->boolean('is_active')->default(true)->after('plan');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['name', 'plan', 'is_active']);
        });
    }
};
