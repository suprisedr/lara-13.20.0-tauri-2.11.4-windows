<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('note_account_links', function (Blueprint $table) {
            $table->dropUnique('note_account_unique');
            $table->unique(
                ['financial_statement_note_id', 'chart_of_account_id', 'balance_point'],
                'note_account_balance_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('note_account_links', function (Blueprint $table) {
            $table->dropUnique('note_account_balance_unique');
            $table->unique(['financial_statement_note_id', 'chart_of_account_id'], 'note_account_unique');
        });
    }
};
