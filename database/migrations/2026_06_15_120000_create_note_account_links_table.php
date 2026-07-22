<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('note_account_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('financial_statement_note_id')->constrained()->cascadeOnDelete();
            $table->foreignId('chart_of_account_id')->constrained()->cascadeOnDelete();
            // +1 adds the account's figure to the note total, -1 subtracts it.
            $table->integer('sign')->default(1);
            // Optional label override; defaults to the account name when blank.
            $table->string('label')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['financial_statement_note_id', 'chart_of_account_id'], 'note_account_unique');
            $table->index('chart_of_account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('note_account_links');
    }
};
