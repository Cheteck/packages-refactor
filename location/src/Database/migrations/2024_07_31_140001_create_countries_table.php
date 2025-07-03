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
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            // 'name' will be in the translations table
            $table->string('slug')->unique();
            $table->string('iso2_code', 2)->unique()->comment('ISO 3166-1 alpha-2 code');
            $table->string('iso3_code', 3)->unique()->comment('ISO 3166-1 alpha-3 code');
            $table->string('phone_code')->nullable()->comment('International phone dialing code');
            $table->string('currency_code', 3)->nullable()->comment('ISO 4217 currency code');
            $table->string('flag_emoji', 10)->nullable(); // Emoji usually 2-4 chars, but give some room
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
