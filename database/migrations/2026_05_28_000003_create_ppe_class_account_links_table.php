<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ppe_class_account_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ppe_class_id')->constrained('ppe_classes')->cascadeOnDelete();
            $table->foreignId('chart_of_account_id')->constrained()->cascadeOnDelete();
            $table->string('role', 30);
            $table->timestamps();

            $table->unique(['ppe_class_id', 'chart_of_account_id', 'role'], 'ppe_link_unique');
            $table->index(['chart_of_account_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ppe_class_account_links');
    }
};
