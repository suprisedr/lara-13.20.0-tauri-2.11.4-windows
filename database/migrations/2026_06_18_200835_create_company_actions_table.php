<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('priority')->default('medium'); // low | medium | high
            $table->string('source')->default('manual');   // manual | ai-agent | system
            $table->string('related_type')->nullable();    // e.g. 'transaction', 'asset', 'invoice'
            $table->unsignedBigInteger('related_id')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'resolved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_actions');
    }
};
