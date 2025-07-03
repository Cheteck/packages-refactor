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
        Schema::create('trackable_views', function (Blueprint $table) {
            $table->id();
            $table->morphs('trackable'); // The item being viewed (e.g., Product, Post, Shop)
            $table->foreignId('user_id')->nullable()->constrained(config('analytics.user_table_name', 'users'))->onDelete('set null');

            $table->string('source')->nullable()->comment('e.g., "organic_search", "referral", "campaign_xyz"');
            $table->string('session_id')->nullable()->index();
            $table->string('device_type')->nullable()->comment('e.g., "desktop", "mobile", "tablet"');
            $table->string('ip_address')->nullable(); // Will be anonymized by model
            $table->string('referrer')->nullable(); // Referring URL

            $table->timestamp('created_at')->useCurrent(); // Only created_at is typically needed for views

            $table->index(['trackable_type', 'trackable_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trackable_views');
    }
};
