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
        if (!Schema::hasTable('post_analytics')) {
            Schema::create('post_analytics', function (Blueprint $table) {
                $table->id();
                $table->foreignId('post_id')->constrained('posts')->onDelete('cascade');
                $table->integer('impressions')->default(0);
                $table->integer('engagements')->default(0);
                $table->integer('shares_count')->default(0);
                $table->float('engagement_rate')->default(0);
                $table->float('estimated_reach')->default(0);
                $table->timestamps();

                $table->index('post_id');
            });
            \Illuminate\Support\Facades\Log::info('Created post_analytics table.');
        } else {
            \Illuminate\Support\Facades\Log::info('post_analytics table already exists. Migration skipped.');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_analytics');
        \Illuminate\Support\Facades\Log::info('Dropped post_analytics table.');
    }
};
