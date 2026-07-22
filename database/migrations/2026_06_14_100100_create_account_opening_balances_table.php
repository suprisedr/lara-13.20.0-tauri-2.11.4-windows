<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_opening_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('financial_period_id')->constrained('financial_periods')->cascadeOnDelete();
            $table->foreignId('chart_of_account_id')->constrained('chart_of_accounts')->cascadeOnDelete();
            // Signed balance in the account's normal direction (matches the legacy
            // chart_of_accounts.opening_balance semantics): positive = a normal
            // debit balance for assets/expenses, a normal credit balance otherwise.
            $table->decimal('amount', 15, 2)->default(0);
            $table->timestamps();

            $table->unique(['financial_period_id', 'chart_of_account_id'], 'aob_period_account_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_opening_balances');
    }
};
