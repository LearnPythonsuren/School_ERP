<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Partial payments, due dates and receipts. Existing paid invoices are
 * back-filled so totals stay correct after the upgrade.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_invoices', function (Blueprint $table) {
            $table->string('description')->nullable()->after('class_name');
            $table->decimal('paid_amount', 10, 2)->default(0)->after('amount');
            $table->date('due_on')->nullable()->after('status');
            $table->string('payment_mode')->nullable()->after('paid_on'); // cash | upi | card | bank | cheque
            $table->string('receipt_no')->nullable()->after('payment_mode');
        });

        DB::table('fee_invoices')->where('status', 'paid')->update(['paid_amount' => DB::raw('amount')]);
    }

    public function down(): void
    {
        Schema::table('fee_invoices', function (Blueprint $table) {
            $table->dropColumn(['description', 'paid_amount', 'due_on', 'payment_mode', 'receipt_no']);
        });
    }
};
