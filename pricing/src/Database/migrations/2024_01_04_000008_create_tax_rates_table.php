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
        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('rate_percentage', 8, 4); // e.g., 20.0000 for 20%
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0)->comment('Order of application for compound taxes');

            // Geographic applicability
            $table->string('country_code', 2)->nullable()->comment('ISO 3166-1 alpha-2 country code');
            $table->string('region')->nullable()->comment('State, province, etc.');
            $table->string('city')->nullable();
            $table->string('zip_code')->nullable();

            $table->text('description')->nullable();
            $table->timestamps();

            // It might be useful to have indexes on geographic columns if you query by them often
            $table->index(['country_code', 'region', 'city']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_rates');
    }
};
