<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('intangible_assets', function (Blueprint $table) {
            $table->foreignId('intangible_class_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
            $table->string('status', 20)->default('active')->after('notes');         // active | disposed
            $table->boolean('useful_life_indefinite')->default(false)->after('useful_life_years');
            $table->decimal('accumulated_impairment', 15, 2)->default(0)->after('residual_value');
            $table->decimal('revaluation_surplus', 15, 2)->default(0)->after('accumulated_impairment');
            $table->foreignId('posting_transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->foreignId('disposal_transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->date('last_amortisation_posted_on')->nullable();
            $table->boolean('is_embedded')->default(false);
            $table->timestamp('embedded_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('intangible_assets', function (Blueprint $table) {
            $table->dropForeign(['intangible_class_id']);
            $table->dropForeign(['posting_transaction_id']);
            $table->dropForeign(['disposal_transaction_id']);
            $table->dropColumn([
                'intangible_class_id',
                'status',
                'useful_life_indefinite',
                'accumulated_impairment',
                'revaluation_surplus',
                'posting_transaction_id',
                'disposal_transaction_id',
                'posted_at',
                'last_amortisation_posted_on',
                'is_embedded',
                'embedded_at',
            ]);
        });
    }
};
