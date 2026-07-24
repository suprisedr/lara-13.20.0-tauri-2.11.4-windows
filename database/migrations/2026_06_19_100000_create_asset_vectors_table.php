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

        if (Schema::connection('pgsql')->hasTable('asset_vectors')) {
            return;
        }

        Schema::connection('pgsql')->create('asset_vectors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('mysql_asset_id')->unique();
            $table->decimal('cost', 15, 2);
            $table->string('currency', 3)->default('ZAR');
            $table->string('asset_name');
            $table->string('asset_class')->nullable();
            $table->string('accounting_policy', 20)->default('cost');
            $table->date('acquisition_date');
            $table->timestamps();
        });

        DB::connection('pgsql')->statement('ALTER TABLE asset_vectors ADD COLUMN embedding vector(768)');
        DB::connection('pgsql')->statement('CREATE INDEX asset_vectors_embedding_hnsw_idx ON asset_vectors USING hnsw (embedding vector_cosine_ops)');
    }

    public function down(): void
    {
        try {
            Schema::connection('pgsql')->dropIfExists('asset_vectors');
        } catch (\Throwable) {}
    }
};
