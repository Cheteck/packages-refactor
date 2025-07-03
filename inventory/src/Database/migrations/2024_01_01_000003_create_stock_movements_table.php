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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->morphs('stockable'); // stockable_id, stockable_type
            $table->foreignId('inventory_id')->constrained('inventories')->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('inventory_locations')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // Assuming 'users' table from user-management

            $table->nullableMorphs('reference'); // reference_id, reference_type (e.g., Order, ReturnRequest)

            $table->string('type'); // E.g., 'sale', 'return', 'restock', 'adjustment', 'transfer_in', 'transfer_out', 'damage'
            $table->integer('quantity_change');
            $table->integer('quantity_before');
            $table->integer('quantity_after');
            $table->text('description')->nullable();

            $table->timestamp('created_at')->useCurrent();
            // No updated_at for movement logs typically

            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
