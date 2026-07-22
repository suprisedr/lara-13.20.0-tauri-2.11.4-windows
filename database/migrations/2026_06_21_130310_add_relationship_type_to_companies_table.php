<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('relationship_type', 30)->nullable()->after('group_ownership_percentage');
            $table->boolean('is_joint_venture')->default(false)->after('relationship_type');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['relationship_type', 'is_joint_venture']);
        });
    }
};
