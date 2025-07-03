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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained(config('analytics.user_table_name', 'users'))->onDelete('set null');
            $table->morphs('loggable'); // Target model for the activity
            $table->string('event')->index(); // e.g., 'created', 'updated', 'viewed', 'clicked'
            $table->json('properties')->nullable(); // Store any additional context or changes
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
