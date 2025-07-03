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
        Schema::create('prices', function (Blueprint $table) {
            $table->id();
            $table->morphs('priceable'); // priceable_id, priceable_type
            $table->string('currency_code'); // Will be FK to currencies.code
            $table->decimal('amount', 15, 4); // High precision for calculations
            $table->string('price_type')->default('default')->index(); // e.g., default, sale, vip
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable()->index();
            $table->unsignedInteger('min_quantity')->default(1);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('currency_code')->references('code')->on('currencies')->onDelete('cascade');
            // Consider unique constraints, e.g., a priceable item should not have multiple 'default' prices for the same currency.
            // $table->unique(['priceable_id', 'priceable_type', 'currency_code', 'price_type', 'min_quantity'], 'price_unique_constraint');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prices');
    }
};
