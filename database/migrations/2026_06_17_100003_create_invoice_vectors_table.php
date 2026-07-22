<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function getConnection(): ?string
    {
        if (config('database.default') === 'sqlite') {
            return null;
        }
        return 'pgsql';
    }

    public function up(): void
    {
        try {
            DB::connection('pgsql')->statement('CREATE EXTENSION IF NOT EXISTS vector');
        } catch (\Throwable) {
            return;
        }

        Schema::connection('pgsql')->create('invoice_vectors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('mysql_invoice_id')->unique();
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3);
            $table->string('customer_name')->nullable();
            $table->string('invoice_number')->nullable();
            $table->date('invoice_date');
            $table->timestamps();
        });

        DB::connection('pgsql')->statement('ALTER TABLE invoice_vectors ADD COLUMN embedding vector(768)');
        DB::connection('pgsql')->statement('CREATE INDEX invoice_vectors_embedding_hnsw_idx ON invoice_vectors USING hnsw (embedding vector_cosine_ops)');
    }

    public function down(): void
    {
        try {
            Schema::connection('pgsql')->dropIfExists('invoice_vectors');
        } catch (\Throwable) {}
    }
};
