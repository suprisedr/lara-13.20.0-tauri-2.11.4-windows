<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('related_parties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('relationship_type', 40);
            $table->text('description')->nullable();
            $table->string('contact_person', 150)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('related_party_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('related_party_id')->constrained()->cascadeOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->date('transaction_date');
            $table->string('transaction_type', 80);
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->decimal('outstanding_balance', 15, 2)->nullable();
            $table->text('terms_and_conditions')->nullable();
            $table->boolean('is_arm_length')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('related_party_transactions');
        Schema::dropIfExists('related_parties');
    }
};
