<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_elimination_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_elimination_id')->constrained()->cascadeOnDelete();
            // Consolidated line bucket this adjustment hits, e.g.
            // current_assets, non_current_assets, current_liabilities,
            // non_current_liabilities, equity, revenue, cost_of_sales,
            // operating_expenses, other_income, goodwill.
            $table->string('bucket');
            $table->string('label')->nullable();
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_elimination_lines');
    }
};
