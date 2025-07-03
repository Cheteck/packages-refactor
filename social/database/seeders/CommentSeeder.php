<?php

namespace IJIDeals\Social\Database\Seeders;

use Illuminate\Database\Seeder;
use IJIDeals\Social\Models\Comment;
use IJIDeals\Social\Models\Post;
use IJIDeals\UserManagement\Models\User;

class CommentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (User::count() === 0) {
            echo "\nWARNING: No users found. Please run UserSeeder first or ensure users exist.\n";
            return;
        }

        if (Post::count() === 0) {
            echo "\nWARNING: No posts found. Please run PostSeeder first or ensure posts exist.\n";
            return;
        }

        $users = User::all();
        $posts = Post::all();

        // Create 100 comments, randomly attached to posts and users
        Comment::factory()->count(100)->make()->each(function ($comment) use ($posts) {
            // The factory already sets a random User as author_id and author_type.
            // We only need to ensure the commentable entity is set correctly if not already handled by a specific factory state.
            if ($posts->isNotEmpty()) {
                $comment->commentable()->associate($posts->random());
            } else {
                // Fallback or skip if no posts exist, though the initial check should prevent this.
                \Illuminate\Support\Facades\Log::warning('CommentSeeder: No posts available to associate comments with. Skipping a comment.');
                return; // Skip this comment
            }
            $comment->save();
        });

        echo "\nSeeded 100 comments.\n";
        \Illuminate\Support\Facades\Log::info('CommentSeeder: Successfully seeded comments.');
    }
}
