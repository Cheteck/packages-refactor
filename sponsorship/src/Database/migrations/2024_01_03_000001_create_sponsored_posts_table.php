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
        Schema::create('sponsored_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // From user-management
            $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete(); // From social

            $table->string('title')->nullable();
            $table->text('description')->nullable();

            $table->decimal('budget', 15, 2); // Total budget for the campaign
            $table->decimal('cost_per_impression', 10, 4)->default(0);
            $table->decimal('cost_per_click', 10, 4)->default(0);
            $table->decimal('spent_amount', 15, 2)->default(0);

            $table->json('targeting')->nullable(); // Store targeting criteria (e.g., demographics, interests)

            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();

            $table->string('status')->default('pending'); // pending, active, paused, completed, cancelled, exhausted_budget

            $table->unsignedBigInteger('impressions')->default(0);
            $table->unsignedBigInteger('clicks')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sponsored_posts');
    }
};
