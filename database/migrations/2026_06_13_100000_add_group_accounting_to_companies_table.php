<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Self-referencing parent for group structures (IFRS 10).
            $table->foreignId('parent_company_id')->nullable()->after('user_id')
                ->constrained('companies')->nullOnDelete();

            // Flag enabling consolidated (group) reporting on a parent entity.
            $table->boolean('is_consolidation_parent')->default(false)->after('status');

            // Terms of the parent's holding in THIS company (when it is a subsidiary).
            $table->decimal('group_ownership_percentage', 5, 2)->nullable()->after('parent_company_id');
            $table->date('acquisition_date')->nullable()->after('group_ownership_percentage');
            // Carrying amount of the parent's investment in this subsidiary.
            $table->decimal('investment_cost', 15, 2)->nullable()->after('acquisition_date');
            // Subsidiary's total equity (net assets) at the acquisition date — used to
            // measure goodwill and the pre-acquisition reserves eliminated on consolidation.
            $table->decimal('equity_at_acquisition', 15, 2)->nullable()->after('investment_cost');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_company_id');
            $table->dropColumn([
                'is_consolidation_parent',
                'group_ownership_percentage',
                'acquisition_date',
                'investment_cost',
                'equity_at_acquisition',
            ]);
        });
    }
};
