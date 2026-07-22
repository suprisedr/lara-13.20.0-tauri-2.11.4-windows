<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_age_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->date('as_of_date');

            // Aging buckets — outstanding balance per band (gross, IFRS 7 receivables ageing).
            $table->decimal('current_amount', 14, 2)->default(0);
            $table->decimal('days_31_60', 14, 2)->default(0);
            $table->decimal('days_61_90', 14, 2)->default(0);
            $table->decimal('days_91_plus', 14, 2)->default(0);
            $table->decimal('total_outstanding', 14, 2)->default(0);

            // IFRS 9 expected credit loss provision — rate applied and resulting allowance, per band.
            $table->decimal('ecl_rate_current', 6, 4)->default(0);
            $table->decimal('ecl_rate_31_60', 6, 4)->default(0);
            $table->decimal('ecl_rate_61_90', 6, 4)->default(0);
            $table->decimal('ecl_rate_91_plus', 6, 4)->default(0);

            $table->decimal('ecl_current', 14, 2)->default(0);
            $table->decimal('ecl_31_60', 14, 2)->default(0);
            $table->decimal('ecl_61_90', 14, 2)->default(0);
            $table->decimal('ecl_91_plus', 14, 2)->default(0);
            $table->decimal('total_ecl', 14, 2)->default(0);

            $table->timestamps();

            $table->unique(['company_id', 'customer_id', 'as_of_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_age_analyses');
    }
};
