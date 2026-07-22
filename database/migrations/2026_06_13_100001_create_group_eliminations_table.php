<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_eliminations', function (Blueprint $table) {
            $table->id();
            // The group parent that owns this consolidation adjustment.
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->date('elimination_date');
            // intercompany_balance | intercompany_trading | unrealised_profit |
            // intragroup_dividend | other
            $table->string('type')->default('other');
            $table->string('description');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_eliminations');
    }
};
