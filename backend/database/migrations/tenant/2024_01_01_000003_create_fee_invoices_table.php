<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no')->unique();
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->string('class_name')->nullable();
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['paid', 'partial', 'due'])->default('due');
            $table->date('paid_on')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_invoices');
    }
};
