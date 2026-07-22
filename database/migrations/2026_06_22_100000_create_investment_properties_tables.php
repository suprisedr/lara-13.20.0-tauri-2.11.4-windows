<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investment_property_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->decimal('useful_life_years', 5, 2)->nullable();
            $table->string('depreciation_method', 30)->nullable();
            $table->string('measurement_model', 20)->default('cost');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('investment_properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('investment_property_class_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 150);
            $table->string('property_reference', 60)->nullable();
            $table->string('location', 200)->nullable();
            $table->date('acquisition_date');
            $table->decimal('cost', 14, 2);
            $table->decimal('residual_value', 14, 2)->default(0);
            $table->decimal('useful_life_years', 5, 2)->nullable();
            $table->string('depreciation_method', 30)->nullable();
            $table->decimal('fair_value', 15, 2)->nullable();
            $table->date('fair_value_date')->nullable();
            $table->date('disposal_date')->nullable();
            $table->decimal('disposal_proceeds', 14, 2)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 16)->default('active');
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posting_transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->foreignId('disposal_transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->date('last_depreciation_posted_on')->nullable();
            $table->decimal('accumulated_impairment', 15, 2)->default(0);
            $table->decimal('fair_value_gain_loss', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('investment_property_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investment_property_id')->constrained()->cascadeOnDelete();
            $table->string('event_type', 40);
            $table->date('event_date');
            $table->decimal('amount', 15, 2)->nullable();
            $table->string('description', 500);
            $table->string('journal_status', 20)->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investment_property_events');
        Schema::dropIfExists('investment_properties');
        Schema::dropIfExists('investment_property_classes');
    }
};
