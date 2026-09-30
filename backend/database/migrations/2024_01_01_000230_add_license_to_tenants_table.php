<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('tenants', function (Blueprint $t) {
            $t->string('licensee')->nullable()->after('is_active');
            $t->string('license_key')->nullable()->after('licensee');
            $t->string('license_status')->default('none')->after('license_key'); // none|active|suspended|expired
            $t->timestamp('license_expires_at')->nullable()->after('license_status');
        });
    }
    public function down(): void {
        Schema::table('tenants', function (Blueprint $t) {
            $t->dropColumn(['licensee','license_key','license_status','license_expires_at']);
        });
    }
};
