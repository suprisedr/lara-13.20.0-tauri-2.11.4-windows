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
        Schema::table('journal_lines', function (Blueprint $table) {
            $table->boolean('is_vat_line')->default(false)->after('description');
            $table->decimal('vat_rate', 5, 2)->nullable()->after('is_vat_line')
                ->comment('VAT rate percentage applied (e.g. 15.00 for 15%)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journal_lines', function (Blueprint $table) {
            $table->dropColumn(['is_vat_line', 'vat_rate']);
        });
    }
};
