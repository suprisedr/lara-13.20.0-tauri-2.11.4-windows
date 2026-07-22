<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->string('action')->comment('receive | issue | adjust | transfer');
            $table->decimal('quantity', 15, 4)
                ->comment('Signed delta: positive to increase on-hand, negative to decrease');
            $table->decimal('unit_cost', 15, 2)->nullable()
                ->comment('Cost per unit at the time of movement (used for FIFO lot costing)');
            $table->string('reference')->nullable()
                ->comment('Source document reference, e.g. PO number or invoice number');
            $table->text('notes')->nullable();
            $table->timestamp('moved_at');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()
                ->comment('User who recorded the movement');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
