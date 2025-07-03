<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $tableName = config('socialinkmanager.table_name', 'social_links');

        Schema::create($tableName, function (Blueprint $table) {
            $table->id();
            $table->morphs('social_linkable'); // Adds `social_linkable_id` (unsignedBigInteger) and `social_linkable_type` (string)

            $table->string('platform_key'); // e.g., 'facebook', 'twitter', 'custom_platform_key'
            $table->string('url', 2048); // Increased length for URLs that might contain query params
            $table->string('label')->nullable(); // Optional custom label for the link

            $table->integer('sort_order')->default(0);
            $table->boolean('is_public')->default(true);

            // Optional: Add more metadata fields if needed in the future, like 'verified_at' or 'click_count'
            // $table->unsignedInteger('click_count')->default(0);
            // $table->timestamp('verified_at')->nullable();

            $table->timestamps();

            // Indexes
            // Index for quick lookup of links for a specific model and platform
            $table->index(['social_linkable_id', 'social_linkable_type', 'platform_key'], 'social_linkable_platform_idx');
            // Index for platform_key alone if you often query by platform across all models
            // $table->index('platform_key');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $tableName = config('socialinkmanager.table_name', 'social_links');
        Schema::dropIfExists($tableName);
    }
};
