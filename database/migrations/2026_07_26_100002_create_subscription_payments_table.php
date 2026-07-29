<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->string('paystack_reference')->unique();
            $table->unsignedInteger('amount');
            $table->string('currency', 3)->default('ZAR');
            $table->string('status');
            $table->timestamp('paid_at')->nullable();
            $table->json('paystack_data')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
    }
};
