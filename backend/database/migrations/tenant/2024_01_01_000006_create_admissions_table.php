<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admissions', function (Blueprint $table) {
            $table->id();
            $table->string('application_no')->unique();
            $table->string('applicant_name');
            $table->string('class_applied');
            $table->string('guardian_name')->nullable();
            $table->string('guardian_phone')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'enrolled'])->default('pending');
            $table->date('applied_on')->nullable();
            $table->foreignId('enrolled_student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admissions');
    }
};
