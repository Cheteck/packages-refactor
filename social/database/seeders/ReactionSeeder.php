<?php

namespace IJIDeals\Social\Database\Seeders;

use Illuminate\Database\Seeder;
use IJIDeals\Social\Models\Reaction;
use IJIDeals\Social\Models\Post;
use IJIDeals\Social\Models\Comment;
use IJIDeals\UserManagement\Models\User;

class ReactionSeeder extends Seeder
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
        $comments = Comment::all();

        // Create 200 reactions, randomly attached to posts/comments and users
        for ($i = 0; $i < 200; $i++) {
            $reactable = (rand(0, 1) === 0 && $comments->isNotEmpty()) ? $comments->random() : $posts->random();
            $user = $users->random();

            // Ensure unique reaction per user per reactable
            if (!Reaction::where('user_id', $user->id)
                ->where('interactable_type', $reactable->getMorphClass()) // Changed reactable_type to interactable_type and use getMorphClass()
                ->where('interactable_id', $reactable->id) // Changed reactable_id to interactable_id
                ->exists()) {
                Reaction::create([
                    'user_id' => $user->id,
                    'interactable_type' => $reactable->getMorphClass(), // Changed reactable_type to interactable_type and use getMorphClass()
                    'interactable_id' => $reactable->id, // Changed reactable_id to interactable_id
                    'type' => collect(['like', 'love', 'haha', 'wow', 'sad', 'angry'])->random(),
                ]);
            }
        }

        echo "\nSeeded reactions.\n";
    }
}
