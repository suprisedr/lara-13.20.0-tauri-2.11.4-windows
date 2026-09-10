<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Narrative sections of the business plan, one row per heading.
        // Deliberately shaped like financial_statement_notes: the business plan
        // is the same problem — per-company prose the user edits, rendered in
        // a fixed order — and sharing the shape keeps the two seeders,
        // controllers and renderers recognisably the same.
        Schema::create('business_plan_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('slug', 80);
            $table->unsignedSmallInteger('section_number');
            $table->string('title');
            $table->longText('body')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            // Whether the computed statistics tables are appended to this
            // section. Only the financial-performance section sets it.
            $table->boolean('include_statistics')->default(false);
            $table->timestamps();

            $table->unique(['company_id', 'slug']);
            $table->index(['company_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_plan_sections');
    }
};
