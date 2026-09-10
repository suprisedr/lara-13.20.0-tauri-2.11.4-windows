<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('share_based_payment_arrangements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name', 200);
            $table->string('arrangement_type', 20); // equity_settled, cash_settled, choice
            $table->date('grant_date');
            $table->date('vesting_start_date')->nullable();
            $table->date('vesting_end_date')->nullable();
            $table->integer('number_of_instruments');
            $table->decimal('exercise_price', 15, 2)->nullable();
            $table->decimal('fair_value_at_grant', 15, 2);
            $table->decimal('total_expense', 15, 2)->default(0);
            $table->text('vesting_conditions')->nullable();
            $table->string('status', 20)->default('active'); // active, vested, expired, forfeited
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('share_based_payment_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('share_based_payment_arrangement_id')->constrained()->cascadeOnDelete();
            $table->string('event_type', 30);
            $table->date('event_date');
            $table->decimal('amount', 15, 2);
            $table->integer('instruments_affected')->nullable();
            $table->text('description')->nullable();
            $table->string('journal_status', 10)->default('pending');
            $table->foreignId('transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('share_based_payment_events');
        Schema::dropIfExists('share_based_payment_arrangements');
    }
};
