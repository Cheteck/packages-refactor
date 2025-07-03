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
        Schema::create('friendships', function (Blueprint $table) {
            $table->id();
            // User who sent the friend request or initiated the relationship
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            // User who is the friend
            $table->foreignId('friend_id')->constrained('users')->onDelete('cascade');

            // Status of the friendship: 'pending', 'accepted', 'declined', 'blocked'
            $table->string('status')->default('pending');

            $table->timestamps(); // To track when the request was sent/accepted/updated

            // Ensure a pair of users can only have one friendship record in one direction.
            // The application logic should handle creating records consistently (e.g., user_id < friend_id).
            // Or, handle two-way checks if records can be (1,2) and (2,1).
            // For a simple request system, (user_id, friend_id) should be unique.
            $table->unique(['user_id', 'friend_id']);

            $table->index('status');
            $table->index('friend_id'); // To easily find friendships for a user
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('friendships');
    }
};
