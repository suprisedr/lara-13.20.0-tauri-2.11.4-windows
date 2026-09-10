<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('government_grants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name', 200);
            $table->string('grant_type', 20);            // income | asset
            $table->string('grant_reference', 80)->nullable();
            $table->string('granting_authority', 200)->nullable();
            $table->date('grant_date');
            $table->decimal('total_amount', 15, 2);
            $table->decimal('recognised_amount', 15, 2)->default(0);
            $table->decimal('deferred_amount', 15, 2)->default(0);
            $table->string('related_asset_type', 100)->nullable();
            $table->unsignedBigInteger('related_asset_id')->nullable();
            $table->string('recognition_method', 20)->default('systematic'); // systematic | immediate
            $table->text('conditions_text')->nullable();
            $table->string('status', 20)->default('active'); // active | fulfilled | refunded
            $table->date('fulfilment_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('government_grant_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('government_grant_id')->constrained()->cascadeOnDelete();
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
        Schema::dropIfExists('government_grant_events');
        Schema::dropIfExists('government_grants');
    }
};
