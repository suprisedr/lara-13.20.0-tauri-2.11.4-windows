<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Step 1 — Company Identity & Statutory Info
            $table->string('registered_name');
            $table->string('company_type');
            $table->string('registration_number')->nullable();
            $table->unsignedTinyInteger('financial_year_end_month');
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('postal_code', 10)->nullable();

            // Step 2 — Tax & Compliance
            $table->string('income_tax_number', 10)->nullable();
            $table->string('vat_number', 10)->nullable();
            $table->string('paye_number')->nullable();
            $table->string('uif_number')->nullable();
            $table->string('sdl_number')->nullable();

            // Step 3 — Financial Setup
            $table->string('chart_of_accounts_type')->default('standard');
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_account_type')->nullable();
            $table->string('bank_branch_code', 6)->nullable();

            // Onboarding tracking
            $table->unsignedTinyInteger('onboarding_step')->default(1);
            $table->timestamp('onboarding_completed_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
