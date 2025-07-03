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
        Schema::create('trackable_interactions', function (Blueprint $table) {
            $table->id();
            $table->morphs('trackable'); // The item being interacted with
            $table->foreignId('user_id')->nullable()->constrained(config('analytics.user_table_name', 'users'))->onDelete('set null');

            $table->string('interaction_type')->index(); // e.g., 'like', 'share', 'comment', 'add_to_cart', 'purchase_attempt'
            $table->json('details')->nullable(); // Store any specific details about the interaction (e.g., comment_id, shared_platform)

            $table->timestamp('created_at')->useCurrent();
            // No 'updated_at' for immutable interaction logs typically.

            $table->index(['trackable_type', 'trackable_id', 'interaction_type'], 'trackable_interactions_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trackable_interactions');
    }
};
