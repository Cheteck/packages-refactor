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
        Schema::create('discount_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discount_id')->constrained('discounts')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // Assuming 'users' table
            $table->unsignedBigInteger('order_id')->nullable()->index(); // Link to an order in IJICommerce package
            // If discounts can apply to things other than orders, make this polymorphic:
            // $table->morphs('applicable');
            $table->timestamp('used_at')->useCurrent();

            // No updated_at needed for a log table.
            // $table->unique(['discount_id', 'user_id', 'order_id'], 'discount_user_order_unique'); // If a user can apply a discount only once per order
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discount_usage');
    }
};
