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
        Schema::create('follows', function (Blueprint $table) {
            $table->id();
            // User who is following
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Polymorphic relation for the entity being followed
            $table->unsignedBigInteger('followable_id');
            $table->string('followable_type');

            $table->timestamps(); // To know when the follow action occurred

            // A user can only follow a specific followable entity once.
            $table->unique(['user_id', 'followable_id', 'followable_type'], 'user_followable_unique');
            $table->index(['followable_id', 'followable_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('follows');
    }
};
