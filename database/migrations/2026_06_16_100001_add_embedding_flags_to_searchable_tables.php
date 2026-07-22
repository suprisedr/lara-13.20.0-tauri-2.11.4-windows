<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = ['transactions', 'invoices', 'quotations', 'journal_lines'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->boolean('is_embedded')->default(false)->index();
                $t->timestamp('embedded_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropIndex(['is_embedded']);
                $t->dropColumn(['is_embedded', 'embedded_at']);
            });
        }
    }
};
