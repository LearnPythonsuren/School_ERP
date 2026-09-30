<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('bus_no');
            $table->string('route_name')->nullable();
            $table->string('driver')->nullable();
            $table->unsignedInteger('capacity')->default(40);
            $table->enum('status', ['on_route', 'idle', 'maintenance'])->default('idle');
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->unsignedInteger('speed_kph')->default(0);
            $table->timestamp('last_ping')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
