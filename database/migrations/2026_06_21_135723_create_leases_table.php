<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name', 200);
            $table->string('asset_tag', 60)->nullable();
            $table->string('category', 40)->default('property');
            $table->string('counterparty', 200)->nullable();
            $table->date('commencement_date');
            $table->date('end_date');
            $table->integer('lease_term_months');
            $table->decimal('monthly_payment', 15, 2);
            $table->string('payment_frequency', 20)->default('monthly');
            $table->decimal('incremental_borrowing_rate', 6, 4);
            $table->decimal('initial_direct_costs', 15, 2)->default(0);
            $table->decimal('lease_liability_opening', 15, 2);
            $table->decimal('rou_asset_cost', 15, 2);
            $table->decimal('residual_value_guarantee', 15, 2)->default(0);
            $table->boolean('is_short_term')->default(false);
            $table->boolean('is_low_value')->default(false);
            $table->string('status', 20)->default('active');
            $table->date('termination_date')->nullable();
            $table->decimal('termination_gain_loss', 15, 2)->nullable();
            $table->decimal('accumulated_impairment', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->string('location', 200)->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
        });

        Schema::create('lease_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lease_id')->constrained()->cascadeOnDelete();
            $table->date('event_date');
            $table->string('type', 30);
            $table->decimal('amount', 15, 2)->default(0);
            $table->text('description')->nullable();
            $table->json('details')->nullable();
            $table->timestamps();

            $table->index(['lease_id', 'event_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lease_events');
        Schema::dropIfExists('leases');
    }
};
