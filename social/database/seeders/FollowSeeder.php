<?php

namespace IJIDeals\Social\Database\Seeders;

use Illuminate\Database\Seeder;
use IJIDeals\Social\Models\Follow;
use IJIDeals\UserManagement\Models\User;
use IJIDeals\IJICommerce\Models\Shop; // Assuming shops can be followed

class FollowSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (User::count() < 2) {
            echo "\nWARNING: Not enough users found. Please run UserSeeder first or ensure at least 2 users exist.\n";
            return;
        }

        $users = User::all();
        $shops = Shop::all();

        // Create 100 follow relationships
        for ($i = 0; $i < 100; $i++) {
            $follower = $users->random();
            $followable = null;

            // Randomly choose to follow a user or a shop
            if (rand(0, 1) === 0 && $users->count() > 1) {
                $followable = $users->except($follower->id)->random(); // Don't follow self
            } elseif ($shops->isNotEmpty()) {
                $followable = $shops->random();
            }

            if ($followable) {
                // Ensure unique follow relationship
                if (!Follow::where('user_id', $follower->id) // Changed follower_id to user_id
                    ->where('followable_type', $followable->getMorphClass()) // Use getMorphClass()
                    ->where('followable_id', $followable->id)
                    ->exists()) {
                    Follow::create([
                        'user_id' => $follower->id, // Changed follower_id to user_id
                        'followable_type' => $followable->getMorphClass(), // Use getMorphClass()
                        'followable_id' => $followable->id,
                    ]);
                }
            }
        }

        echo "\nSeeded follow relationships.\n";
    }
}
