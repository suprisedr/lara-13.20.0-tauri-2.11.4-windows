<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->enum('type', ['earning', 'deduction', 'employer_contribution']);
            $table->enum('category', [
                'basic', 'overtime', 'commission', 'bonus',
                'travel_allowance', 'housing_allowance', 'car_allowance', 'meal_allowance',
                'medical_aid', 'pension_fund', 'retirement_annuity', 'loan_repayment', 'other',
            ])->default('other');
            $table->boolean('is_taxable')->default(true);
            $table->boolean('is_pensionable')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_components');
    }
};
