<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            // Human label, e.g. "FY2025" or "1 Mar 2024 – 28 Feb 2025".
            $table->string('label');
            $table->date('start_date');
            $table->date('end_date');
            // Once closed the opening balances are locked; reports still read them.
            $table->boolean('is_closed')->default(false);
            $table->timestamps();

            $table->unique(['company_id', 'start_date']);
            $table->index(['company_id', 'start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_periods');
    }
};
