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
        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique()->nullable(); // Nullable for automatic/product-specific discounts
            $table->text('description')->nullable();
            $table->string('type')->index(); // 'percentage', 'fixed_amount', 'buy_x_get_y_item', 'free_shipping'
            $table->json('value'); // For percentage, fixed amount, or complex rules (e.g., BOGO item IDs and quantities)

            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('max_uses_per_user')->nullable();
            $table->unsignedInteger('total_uses')->default(0);

            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable()->index();
            $table->string('status')->default('inactive')->index(); // active, inactive, expired, scheduled

            $table->boolean('is_combinable')->default(false);
            $table->integer('priority')->default(0); // For resolving conflicts between non-combinable discounts
            $table->string('conditions_match_type')->default('all'); // 'all' (AND) or 'any' (OR) for rules

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discounts');
    }
};
