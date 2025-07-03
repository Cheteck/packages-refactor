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
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            // $table->foreignId('post_id')->constrained()->onDelete('cascade'); // Replaced by polymorphic commentable
            $table->unsignedBigInteger('commentable_id');
            $table->string('commentable_type');
            // $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Replaced by polymorphic author
            $table->unsignedBigInteger('author_id');
            $table->string('author_type');
            $table->text('content'); // Renamed from contenu
            $table->unsignedBigInteger('parent_id')->nullable(); // For threaded comments
            $table->timestamps();

            $table->index(['commentable_id', 'commentable_type']);
            $table->index(['author_id', 'author_type']);
            $table->foreign('parent_id')->references('id')->on('comments')->onDelete('cascade'); // Self-referential foreign key
        });
        \Illuminate\Support\Facades\Log::info('comments table created with parent_id column.');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            if (Schema::hasColumn('comments', 'parent_id')) {
                $table->dropForeign(['parent_id']);
            }
            if (Schema::hasColumn('comments', 'commentable_id') && Schema::hasColumn('comments', 'commentable_type')) {
                $table->dropIndex(['commentable_id', 'commentable_type']);
            }
            if (Schema::hasColumn('comments', 'author_id') && Schema::hasColumn('comments', 'author_type')) {
                $table->dropIndex(['author_id', 'author_type']);
            }
        });
        Schema::dropIfExists('comments');
        \Illuminate\Support\Facades\Log::info('comments table dropped.');
    }
};
