<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->nullable()->constrained('students')->cascadeOnDelete();
            $table->string('exam_name');
            $table->string('class_name')->nullable();
            $table->unsignedTinyInteger('maths')->default(0);
            $table->unsignedTinyInteger('science')->default(0);
            $table->unsignedTinyInteger('english')->default(0);
            $table->string('grade', 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_results');
    }
};
