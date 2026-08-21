<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deferred_tax_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name', 200);
            $table->string('source_type', 30);
            $table->unsignedBigInteger('source_id')->nullable();
            $table->decimal('tax_base', 15, 2);
            $table->decimal('carrying_amount', 15, 2);
            $table->decimal('temporary_difference', 15, 2)->default(0);
            $table->decimal('deferred_tax_asset', 15, 2)->default(0);
            $table->decimal('deferred_tax_liability', 15, 2)->default(0);
            $table->decimal('tax_rate', 5, 2);
            $table->boolean('is_taxable')->default(true);
            $table->date('measurement_date');
            $table->string('status', 20)->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('deferred_tax_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deferred_tax_item_id')->constrained()->cascadeOnDelete();
            $table->string('event_type', 30);
            $table->date('event_date');
            $table->decimal('amount', 15, 2);
            $table->decimal('previous_balance', 15, 2)->nullable();
            $table->decimal('new_balance', 15, 2)->nullable();
            $table->text('description');
            $table->string('journal_status', 10)->default('pending');
            $table->foreignId('transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deferred_tax_events');
        Schema::dropIfExists('deferred_tax_items');
    }
};
