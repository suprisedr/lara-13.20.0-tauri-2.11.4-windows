<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $t) {
            $t->string('status', 16)->default('active')->index();
            $t->timestamp('posted_at')->nullable();
            $t->foreignId('posting_transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $t->foreignId('disposal_transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $t->date('last_depreciation_posted_on')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $t) {
            $t->dropConstrainedForeignId('posting_transaction_id');
            $t->dropConstrainedForeignId('disposal_transaction_id');
            $t->dropIndex(['status']);
            $t->dropColumn(['status', 'posted_at', 'last_depreciation_posted_on']);
        });
    }
};
