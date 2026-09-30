<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// License keys issued by the vendor (Chenthur Info Tech) to customer schools.
return new class extends Migration {
    public function up(): void {
        Schema::create('licenses', function (Blueprint $t) {
            $t->id();
            $t->string('key')->unique();
            $t->string('licensee');                 // customer / school org name
            $t->string('plan')->default('starter'); // starter | pro | enterprise
            $t->string('status')->default('active'); // active | suspended | revoked
            $t->string('tenant_id')->nullable();     // linked school once activated
            $t->string('issued_by')->default('Chenthur Info Tech');
            $t->timestamp('expires_at')->nullable();
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('licenses'); }
};
