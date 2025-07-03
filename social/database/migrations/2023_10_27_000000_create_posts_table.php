<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for the posts table, adapted to match the @Post.php model.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();

            // Polymorphic author (author_id, author_type)
            $table->unsignedBigInteger('author_id');
            $table->string('author_type');

            // Post content and metadata
            $table->string('title')->nullable();
            $table->text('content')->nullable();
            $table->string('type')->default('text'); // See PostTypeEnum
            $table->string('visibility')->default('public'); // See VisibilityType
            $table->json('metadata')->nullable();

            // Status and settings
            $table->string('status')->default('published');
            $table->json('location')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('comment_settings')->default('everyone');
            $table->string('reaction_settings')->default('enabled');
            $table->float('engagement_score')->default(0);

            // Relations to other entities
            $table->unsignedBigInteger('product_id')->nullable();
            $table->boolean('is_published')->default(true);
            $table->unsignedBigInteger('poll_id')->nullable();
            $table->unsignedBigInteger('reach_estimate_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['author_id', 'author_type']);
            $table->index('type');
            $table->index('status');
            $table->index('visibility');
            $table->index('scheduled_at');
            $table->index('expires_at');
            $table->index('product_id');
            $table->index('poll_id');
            $table->index('reach_estimate_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            if (Schema::hasColumn('posts', 'author_id') && Schema::hasColumn('posts', 'author_type')) {
                $table->dropIndex(['author_id', 'author_type']);
            }
            foreach (['type', 'status', 'visibility', 'scheduled_at', 'expires_at', 'product_id', 'poll_id', 'reach_estimate_id'] as $col) {
                if (Schema::hasColumn('posts', $col)) {
                    $table->dropIndex([$col]);
                }
            }
        });
        Schema::dropIfExists('posts');
    }
};
