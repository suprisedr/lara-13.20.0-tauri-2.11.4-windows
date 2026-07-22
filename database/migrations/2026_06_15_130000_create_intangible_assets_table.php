<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intangible_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('reference', 60)->nullable();
            $table->string('category', 120)->nullable();
            $table->date('acquisition_date');
            $table->decimal('cost', 14, 2);
            $table->decimal('residual_value', 14, 2)->default(0);
            $table->decimal('useful_life_years', 5, 2)->nullable();
            $table->string('amortisation_method', 30)->nullable();
            $table->date('disposal_date')->nullable();
            $table->decimal('disposal_proceeds', 14, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intangible_assets');
    }
};
