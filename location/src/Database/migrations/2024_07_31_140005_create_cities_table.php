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
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained('countries')->onDelete('cascade');
            $table->foreignId('region_id')->nullable()->constrained('regions')->onDelete('set null');
            // 'name' will be in translations table
            $table->string('slug')->unique();
            $table->string('postal_code')->nullable()->index();
            $table->decimal('latitude', 10, 7)->nullable(); // Precision for lat/lng
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('status')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['country_id', 'region_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};
