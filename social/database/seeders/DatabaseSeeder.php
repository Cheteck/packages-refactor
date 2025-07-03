<?php

namespace IJIDeals\Social\Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([ // Call other seeders
            PostSeeder::class,
            CommentSeeder::class,
            ReactionSeeder::class,
            FollowSeeder::class,
            ActivitySeeder::class,
            ReportSeeder::class,
        ]);
    }
}
