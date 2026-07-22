<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            // Running total of impairment charges recognised in P&L (IAS 36)
            $table->decimal('accumulated_impairment', 15, 2)->default(0)->after('residual_value');
            // Running total of revaluation surplus still in OCI (IAS 16.39)
            $table->decimal('revaluation_surplus', 15, 2)->default(0)->after('accumulated_impairment');
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn(['accumulated_impairment', 'revaluation_surplus']);
        });
    }
};
