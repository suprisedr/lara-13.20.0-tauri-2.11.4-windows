<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intangible_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('useful_life_years', 5, 2)->nullable();
            $table->string('amortisation_method', 30)->nullable();
            $table->string('accounting_policy', 20)->default('cost');     // cost | revaluation (IAS 38.72)
            $table->boolean('indefinite_life')->default(false);            // IAS 38.107 — no amortisation
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['company_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intangible_classes');
    }
};
