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
        Schema::create('interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            $table->unsignedBigInteger('interactable_id');
            $table->string('interactable_type');

            $table->string('type')->comment('e.g., like, love, haha, wow, sad, angry'); // Type of reaction

            $table->timestamps();

            // A user can only have one type of reaction per interactable item.
            // Or, if a user can have multiple different reactions (e.g. like AND love), this unique key is not needed.
            // For now, assume one reaction (of any type) per user per item.
            // If a user changes their reaction (e.g. from like to love), the existing record's 'type' is updated.
            $table->unique(['user_id', 'interactable_id', 'interactable_type'], 'user_interactable_unique');

            $table->index(['interactable_id', 'interactable_type']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interactions');
    }
};
