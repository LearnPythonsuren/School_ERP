<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('block');
            $table->string('room_no');
            $table->unsignedInteger('capacity')->default(2);
            $table->unsignedInteger('occupied')->default(0);
            $table->string('warden')->nullable();
            $table->timestamps();

            $table->unique(['block', 'room_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
