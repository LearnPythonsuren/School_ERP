<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Product-owner staff (Chenthur Info Tech) who run the control plane.
return new class extends Migration {
    public function up(): void {
        Schema::create('central_users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->unique();
            $t->string('password');
            $t->string('role')->default('super-admin');
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('central_users'); }
};
