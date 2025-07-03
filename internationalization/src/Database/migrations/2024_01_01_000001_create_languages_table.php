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
        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 5)->unique(); // ISO 639-1 language code (e.g., 'en', 'fr')
            $table->string('name'); // Full language name (e.g., 'English', 'French')
            $table->boolean('is_default')->default(false); // Whether this is the default language
            $table->enum('direction', ['ltr', 'rtl'])->default('ltr'); // Text direction
            $table->boolean('status')->default(true); // Whether the language is active
            $table->string('flag_icon')->nullable(); // Flag icon class or path
            $table->timestamps();

            $table->index(['status', 'is_default']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('languages');
    }
};
