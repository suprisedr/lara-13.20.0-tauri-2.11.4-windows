<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('afs_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            // General information
            $table->string('country_of_incorporation')->default('South Africa');
            $table->string('nature_of_business')->nullable();
            $table->json('directors')->nullable();            // list of director names
            $table->text('registered_office')->nullable();
            $table->text('business_address')->nullable();
            $table->text('postal_address')->nullable();

            // Practitioner / preparer (compilation report + general information)
            $table->string('practitioner_name')->nullable();
            $table->string('practitioner_qualification')->nullable();   // e.g. Professional Accountants (SA)
            $table->string('practitioner_membership')->nullable();      // e.g. SAIPA
            $table->text('practitioner_contact')->nullable();           // letterhead block
            $table->string('compilation_directors')->nullable();        // names signing the compilation report

            // Approval / assurance
            $table->date('approval_date')->nullable();
            $table->text('level_of_assurance')->nullable();

            $table->timestamps();

            $table->unique('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('afs_settings');
    }
};
