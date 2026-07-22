<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('biological_asset_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('category', 20)->default('consumable');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('biological_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('biological_asset_class_id')->nullable()->constrained('biological_asset_classes')->nullOnDelete();
            $table->string('name', 150);
            $table->string('reference', 60)->nullable();
            $table->string('location', 200)->nullable();
            $table->date('acquisition_date');
            $table->decimal('quantity', 14, 2)->default(0);
            $table->string('unit', 30)->default('head');
            $table->decimal('cost', 14, 2)->default(0);
            $table->decimal('fair_value', 14, 2)->nullable();
            $table->date('fair_value_date')->nullable();
            $table->decimal('fair_value_gain_loss', 14, 2)->default(0);
            $table->decimal('accumulated_impairment', 14, 2)->default(0);
            $table->string('status', 20)->default('active');
            $table->date('disposal_date')->nullable();
            $table->decimal('disposal_proceeds', 14, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
        });

        Schema::create('biological_asset_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('biological_asset_id')->constrained('biological_assets')->cascadeOnDelete();
            $table->string('event_type', 30);
            $table->date('event_date');
            $table->decimal('amount', 14, 2)->default(0);
            $table->decimal('quantity_change', 14, 2)->default(0);
            $table->text('description')->nullable();
            $table->string('journal_status', 20)->default('pending');
            $table->timestamps();

            $table->index('biological_asset_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('biological_asset_events');
        Schema::dropIfExists('biological_assets');
        Schema::dropIfExists('biological_asset_classes');
    }
};
