<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cash_flow_manual_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->string('section', 20); // operating, investing, financing
            $table->string('line_key', 30)->nullable(); // receipts|payments|interest|tax|cash_begin|cash_end; null = custom dynamic line
            $table->string('line_name');
            $table->decimal('current_amount', 15, 2)->default(0);
            $table->decimal('prior_amount', 15, 2)->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['company_id', 'period_start', 'period_end'], 'cfme_company_period_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_flow_manual_entries');
    }
};
