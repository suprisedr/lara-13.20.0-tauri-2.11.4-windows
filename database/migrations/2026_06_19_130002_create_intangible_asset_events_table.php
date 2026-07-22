<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intangible_asset_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('intangible_asset_id')->constrained()->cascadeOnDelete();
            $table->string('event_type', 40);  // acquisition, capitalisation, revaluation, impairment, impairment_reversal, disposal
            $table->date('event_date');
            $table->decimal('amount', 15, 2)->nullable();
            $table->string('description', 500)->nullable();
            $table->string('journal_status', 20)->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intangible_asset_events');
    }
};
