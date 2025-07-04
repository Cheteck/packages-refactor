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
        Schema::create('shipping_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('rule_type'); // e.g., 'by_country', 'by_distance', 'by_zone'
            $table->json('parameters')->nullable(); // JSON for rule specifics
            $table->string('cost_type'); // e.g., 'fixed', 'per_item', 'per_kg', 'per_km'
            $table->decimal('cost_value', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_rules');
    }
};
