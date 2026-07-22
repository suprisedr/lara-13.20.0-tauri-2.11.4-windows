<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecl_rate_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('current_rate', 6, 4)->default(0.0100);
            $table->decimal('days_31_60_rate', 6, 4)->default(0.0500);
            $table->decimal('days_61_90_rate', 6, 4)->default(0.2500);
            $table->decimal('days_91_plus_rate', 6, 4)->default(0.5000);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecl_rate_settings');
    }
};
