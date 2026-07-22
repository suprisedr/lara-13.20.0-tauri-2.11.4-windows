<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('invoice_account_settings');
    }

    public function down(): void
    {
        Schema::create('invoice_account_settings', function ($table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('accounts_receivable_account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->foreignId('sales_account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->foreignId('vat_output_account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->foreignId('bank_account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->timestamps();
            $table->unique('company_id');
        });
    }
};
