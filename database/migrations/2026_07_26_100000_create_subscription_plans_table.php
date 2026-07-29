<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('paystack_plan_code')->nullable()->unique();
            $table->unsignedInteger('price');
            $table->string('currency', 3)->default('ZAR');
            $table->string('interval')->default('monthly');
            $table->unsignedInteger('interval_months');
            $table->unsignedInteger('monthly_equivalent');
            $table->unsignedInteger('discount_percent')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
