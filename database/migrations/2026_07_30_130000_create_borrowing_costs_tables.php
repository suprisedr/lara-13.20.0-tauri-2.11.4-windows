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
        Schema::create('borrowing_cost_capitalisations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('qualifying_asset_id');
            $table->string('qualifying_asset_type', 50);
            $table->string('borrowing_source', 200);
            $table->date('capitalisation_start_date');
            $table->date('capitalisation_end_date')->nullable();
            $table->string('status', 20)->default('active');
            $table->decimal('total_capitalised', 15, 2)->default(0);
            $table->decimal('borrowing_rate', 8, 4);
            $table->decimal('weighted_average_rate', 8, 4)->nullable();
            $table->text('notes')->nullable();
            $table->date('last_capitalisation_posted_on')->nullable();
            $table->timestamps();
        });

        Schema::create('borrowing_cost_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('borrowing_cost_capitalisation_id')->constrained()->cascadeOnDelete();
            $table->string('event_type', 30);
            $table->date('event_date');
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->string('journal_status', 10)->default('pending');
            $table->foreignId('transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('borrowing_cost_events');
        Schema::dropIfExists('borrowing_cost_capitalisations');
    }
};
