<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timetable_slots', function (Blueprint $table) {
            $table->id();
            $table->string('class_name');
            $table->enum('day', ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']);
            $table->unsignedTinyInteger('period_no');
            $table->string('subject');
            $table->string('teacher')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->timestamps();

            // One subject per class per day per period slot.
            $table->unique(['class_name', 'day', 'period_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timetable_slots');
    }
};
