<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('class_name');
            $table->unsignedInteger('roll_no')->nullable();
            $table->enum('fee_status', ['paid', 'partial', 'due'])->default('due');
            $table->unsignedTinyInteger('attendance_pct')->default(100);
            $table->string('guardian_name')->nullable();
            $table->string('guardian_phone')->nullable();
            $table->timestamps();

            $table->index('class_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
