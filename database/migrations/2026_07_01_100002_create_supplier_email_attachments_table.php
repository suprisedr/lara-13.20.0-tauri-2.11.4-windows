<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_email_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_email_id')->constrained()->cascadeOnDelete();
            $table->string('filename');
            $table->string('mime_type');
            $table->string('file_path');
            $table->unsignedBigInteger('file_size')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_email_attachments');
    }
};
