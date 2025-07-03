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
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->morphs('stockable'); // stockable_id, stockable_type (e.g., Product, Variant)
            $table->foreignId('location_id')->constrained('inventory_locations')->cascadeOnDelete();
            $table->integer('quantity')->default(0);
            $table->integer('reserved_quantity')->default(0);
            $table->timestamp('last_stock_update')->nullable();
            $table->timestamps();

            $table->unique(['stockable_id', 'stockable_type', 'location_id'], 'stockable_location_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
