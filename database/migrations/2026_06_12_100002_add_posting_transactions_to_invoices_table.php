<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('posting_transaction_id')->nullable()->after('status')->constrained('transactions')->nullOnDelete();
            $table->foreignId('payment_transaction_id')->nullable()->after('posting_transaction_id')->constrained('transactions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('posting_transaction_id');
            $table->dropConstrainedForeignId('payment_transaction_id');
        });
    }
};
