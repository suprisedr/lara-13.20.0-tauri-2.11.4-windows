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

        Schema::connection('pgsql')->create('account_vectors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('mysql_account_id')->unique();
            $table->unsignedBigInteger('mysql_company_id')->index();
            $table->string('account_code', 20);
            $table->string('account_name');
            $table->string('account_type', 50);
            $table->string('category')->nullable();
            $table->timestamps();
        });

        DB::connection('pgsql')->statement('ALTER TABLE account_vectors ADD COLUMN embedding vector(768)');
        DB::connection('pgsql')->statement('CREATE INDEX account_vectors_embedding_hnsw_idx ON account_vectors USING hnsw (embedding vector_cosine_ops)');
        DB::connection('pgsql')->statement('CREATE INDEX account_vectors_company_idx ON account_vectors (mysql_company_id)');
    }

    public function down(): void
    {
        try {
            Schema::connection('pgsql')->dropIfExists('account_vectors');
        } catch (\Throwable) {}
    }
};
