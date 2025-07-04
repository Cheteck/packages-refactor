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
        Schema::create('taggable_products', function (Blueprint $table) {
            $table->unsignedBigInteger('post_id');
            $table->morphs('taggable'); // This will create 'taggable_id' (unsignedBigInteger) and 'taggable_type' (string)
            $table->timestamps();

            $table->foreign('post_id')->references('id')->on('posts')->onDelete('cascade');
            // It's good practice to have a primary key for the pivot table for easier management,
            // especially if you ever need to reference a specific tagging instance.
            $table->primary(['post_id', 'taggable_id', 'taggable_type'], 'post_taggable_primary');

            // Adding individual indexes can be beneficial for performance if you query by taggable alone often.
            // $table->index('taggable_id');
            // $table->index('taggable_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('taggable_products');
    }
};
