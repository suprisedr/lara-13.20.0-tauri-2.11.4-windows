<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_email_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('message_id');
            $table->string('from_email');
            $table->string('from_name')->nullable();
            $table->string('subject')->nullable();
            $table->longText('body_text')->nullable();
            $table->timestamp('received_at');
            $table->boolean('is_reviewed')->default(false);
            $table->string('status')->default('pending_review'); // pending_review, matched, new_supplier
            $table->timestamps();

            $table->unique(['company_email_account_id', 'message_id']);
            $table->index('from_email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_emails');
    }
};
