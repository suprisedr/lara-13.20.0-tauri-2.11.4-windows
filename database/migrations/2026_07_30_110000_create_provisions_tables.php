<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provision_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('provision_class_account_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provision_class_id')->constrained()->cascadeOnDelete();
            $table->foreignId('chart_of_account_id')->constrained()->cascadeOnDelete();
            $table->string('role', 50);
            $table->timestamps();
        });

        Schema::create('provisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provision_class_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 200);
            $table->string('provision_type', 30);
            $table->string('status', 20)->default('active');
            $table->date('recognition_date');
            $table->date('expected_settlement_date')->nullable();
            $table->decimal('initial_estimate', 15, 2);
            $table->decimal('current_estimate', 15, 2);
            $table->decimal('discount_rate', 8, 4)->nullable();
            $table->decimal('present_value', 15, 2)->nullable();
            $table->string('probability', 20)->nullable();
            $table->date('settlement_date')->nullable();
            $table->decimal('settlement_amount', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('posted_at')->nullable();
            $table->foreignId('posting_transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->date('last_unwinding_posted_on')->nullable();
            $table->timestamps();
        });

        Schema::create('provision_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provision_id')->constrained()->cascadeOnDelete();
            $table->string('event_type', 30);
            $table->date('event_date');
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->string('journal_status', 10)->default('pending');
            $table->foreignId('transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provision_events');
        Schema::dropIfExists('provisions');
        Schema::dropIfExists('provision_class_account_links');
        Schema::dropIfExists('provision_classes');
    }
};
