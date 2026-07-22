<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subsidiary_ownership_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('subsidiary_company_id')->constrained('companies')->cascadeOnDelete();
            $table->date('event_date');
            // acquisition (obtain control) | increase | decrease | disposal (lose control)
            $table->string('type');
            $table->decimal('ownership_before', 5, 2)->default(0);
            $table->decimal('ownership_after', 5, 2)->default(0);
            // Consideration paid (acquisition/increase) or received (decrease/disposal).
            $table->decimal('consideration', 15, 2)->default(0);
            // Subsidiary's identifiable net assets (equity) at the event date.
            $table->decimal('equity_at_event', 15, 2)->nullable();
            // Step acquisition: fair value & carrying amount of the previously held interest.
            $table->decimal('fair_value_previously_held', 15, 2)->nullable();
            $table->decimal('carrying_previously_held', 15, 2)->nullable();
            // Disposal: fair value of any interest retained after losing control.
            $table->decimal('fair_value_retained', 15, 2)->nullable();
            // Goodwill carried for the subsidiary at the disposal date (to derecognise).
            $table->decimal('goodwill_derecognised', 15, 2)->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['parent_company_id', 'subsidiary_company_id', 'event_date']);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->date('control_acquired_date')->nullable()->after('equity_at_acquisition');
            $table->date('control_lost_date')->nullable()->after('control_acquired_date');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['control_acquired_date', 'control_lost_date']);
        });

        Schema::dropIfExists('subsidiary_ownership_events');
    }
};
