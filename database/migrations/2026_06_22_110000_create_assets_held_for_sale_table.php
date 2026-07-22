<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets_held_for_sale', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->date('reclassification_date');
            $table->decimal('carrying_amount_at_reclassification', 14, 2);
            $table->decimal('fair_value_less_costs_to_sell', 14, 2)->nullable();
            $table->decimal('impairment_on_reclassification', 14, 2)->default(0);
            $table->date('expected_sale_date')->nullable();
            $table->text('buyer_details')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('held_for_sale');
            $table->date('disposal_date')->nullable();
            $table->decimal('disposal_proceeds', 14, 2)->nullable();
            $table->foreignId('reclassification_transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->foreignId('disposal_transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets_held_for_sale');
    }
};
