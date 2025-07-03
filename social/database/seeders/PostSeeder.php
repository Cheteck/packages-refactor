<?php

namespace IJIDeals\Social\Database\Seeders;

use Illuminate\Database\Seeder;
use IJIDeals\Social\Models\Post;
use IJIDeals\UserManagement\Models\User;

class PostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure there are users to associate posts with
        if (User::count() === 0) {
            // You might want to call the UserSeeder here if it's not guaranteed to run before this.
            // $this->call(UserSeeder::class);
            echo "\nWARNING: No users found. Please run UserSeeder first or ensure users exist.\n";
            return;
        }

        $users = User::all();

        // Create 50 posts
        Post::factory()->count(50)->make()->each(function ($post) use ($users) {
            $user = $users->random();
            $post->author_id = $user->id;
            $post->author_type = User::class;
            $post->save();
        });

        echo "\nSeeded 50 posts.\n";
    }
}
