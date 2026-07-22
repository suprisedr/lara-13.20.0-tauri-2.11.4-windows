<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('employee_number', 20);
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('id_number', 13)->nullable();
            $table->string('passport_number', 30)->nullable();
            $table->string('tax_reference_number', 20)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other', 'prefer_not_to_say'])->nullable();
            $table->enum('employment_type', ['permanent', 'contract', 'part_time', 'casual'])->default('permanent');
            $table->string('job_title', 100)->nullable();
            $table->string('department', 100)->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->enum('pay_frequency', ['monthly', 'weekly', 'fortnightly'])->default('monthly');
            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->unsignedTinyInteger('medical_aid_members')->default(1);
            $table->decimal('medical_aid_employee_contribution', 10, 2)->default(0);
            $table->decimal('retirement_fund_contribution', 10, 2)->default(0);
            $table->string('bank_name', 100)->nullable();
            $table->string('bank_account_number', 30)->nullable();
            $table->enum('bank_account_type', ['current', 'savings'])->nullable();
            $table->string('bank_branch_code', 10)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'employee_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
