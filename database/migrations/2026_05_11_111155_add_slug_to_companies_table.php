<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('registered_name');
        });

        // Populate slugs for existing companies.
        DB::table('companies')->orderBy('id')->each(function (object $company) {
            $base = Str::slug($company->registered_name);
            $slug = $base;
            $i    = 2;

            while (DB::table('companies')->where('slug', $slug)->where('id', '!=', $company->id)->exists()) {
                $slug = $base . '-' . $i++;
            }

            DB::table('companies')->where('id', $company->id)->update(['slug' => $slug]);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
