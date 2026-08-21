<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('revenue_contracts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('contract_reference', 100);
            $table->string('name', 200);
            $table->string('status', 20)->default('active');
            $table->date('inception_date');
            $table->date('completion_date')->nullable();
            $table->decimal('total_transaction_price', 15, 2);
            $table->decimal('variable_consideration_estimate', 15, 2)->nullable();
            $table->decimal('variable_consideration_constraint', 15, 2)->nullable();
            $table->decimal('significant_financing_component', 15, 2)->nullable();
            $table->date('contract_modification_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
        });

        Schema::create('performance_obligations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('revenue_contract_id');
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->decimal('standalone_selling_price', 15, 2);
            $table->decimal('allocated_transaction_price', 15, 2);
            $table->string('recognition_method', 20);
            $table->string('over_time_method', 30)->nullable();
            $table->decimal('total_expected_cost', 15, 2)->nullable();
            $table->decimal('costs_incurred_to_date', 15, 2)->default(0);
            $table->decimal('percentage_complete', 8, 4)->default(0);
            $table->decimal('revenue_recognised', 15, 2)->default(0);
            $table->string('status', 20)->default('unsatisfied');
            $table->date('satisfaction_date')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('revenue_contract_id')->references('id')->on('revenue_contracts')->onDelete('cascade');
        });

        Schema::create('revenue_contract_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('revenue_contract_id');
            $table->unsignedBigInteger('performance_obligation_id')->nullable();
            $table->string('event_type', 30);
            $table->date('event_date');
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->string('journal_status', 10)->default('pending');
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->timestamps();

            $table->foreign('revenue_contract_id')->references('id')->on('revenue_contracts')->onDelete('cascade');
            $table->foreign('performance_obligation_id')->references('id')->on('performance_obligations')->onDelete('set null');
            $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('set null');
        });

        Schema::create('revenue_contract_account_links', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('chart_of_account_id');
            $table->string('role', 50);
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('chart_of_account_id')->references('id')->on('chart_of_accounts')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revenue_contract_account_links');
        Schema::dropIfExists('revenue_contract_events');
        Schema::dropIfExists('performance_obligations');
        Schema::dropIfExists('revenue_contracts');
    }
};
