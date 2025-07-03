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
        Schema::create('trackable_stats_daily', function (Blueprint $table) {
            $table->id();
            $table->morphs('trackable'); // The item being tracked
            $table->date('date');

            $table->unsignedBigInteger('views_count')->default(0);
            $table->unsignedBigInteger('unique_views_count')->default(0); // Optional: if you track unique views separately

            // Interaction counts - make these generic or specific as needed
            $table->unsignedBigInteger('likes_count')->default(0); // Example
            $table->unsignedBigInteger('shares_count')->default(0); // Example
            $table->unsignedBigInteger('comments_count')->default(0); // Example
            // Add other specific interaction counts if common, e.g., 'add_to_cart_count'

            $table->json('interaction_summary')->nullable(); // Store counts of various interaction_types, e.g., {"like": 10, "share": 5}

            $table->decimal('engagement_score', 10, 2)->default(0)->nullable(); // Optional calculated score

            $table->timestamps(); // For record creation/update, not the stat date

            $table->unique(['trackable_id', 'trackable_type', 'date'], 'trackable_daily_stats_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trackable_stats_daily');
    }
};
