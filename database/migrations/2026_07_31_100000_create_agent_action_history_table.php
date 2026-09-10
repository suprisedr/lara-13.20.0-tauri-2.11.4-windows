<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_action_history', function (Blueprint $table) {
            $table->id();
            $table->string('agent_type', 80);
            $table->string('entity_type', 80);
            $table->unsignedBigInteger('entity_id');
            $table->text('last_prompt');
            $table->text('last_response');
            $table->timestamps();

            $table->unique(['agent_type', 'entity_type', 'entity_id'], 'agent_entity_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_action_history');
    }
};
