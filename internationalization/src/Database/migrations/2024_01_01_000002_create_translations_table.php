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
        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->morphs('translatable'); // Creates translatable_type and translatable_id
            $table->string('attribute'); // The attribute being translated (e.g., 'name', 'description')
            $table->string('language_code', 5); // ISO 639-1 language code
            $table->text('value'); // The translated value
            $table->timestamps();

            // Ensure unique translations per model, attribute, and language
            $table->unique(['translatable_type', 'translatable_id', 'attribute', 'language_code'], 'unique_translation');

            // Indexes for better performance
            // $table->index(['translatable_type', 'translatable_id']); // morphs already creates this
            $table->index(['attribute', 'language_code']);
            $table->index('language_code');

            // Foreign key constraint to languages table
            $table->foreign('language_code')
                ->references('code')
                ->on('languages')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};
