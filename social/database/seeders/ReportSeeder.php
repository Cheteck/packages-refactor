<?php

namespace IJIDeals\Social\Database\Seeders;

use Illuminate\Database\Seeder;
use IJIDeals\Social\Models\Report;
use IJIDeals\Social\Models\Post;
use IJIDeals\Social\Models\Comment;
use IJIDeals\UserManagement\Models\User;

class ReportSeeder extends Seeder
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

        if (Post::count() === 0 && Comment::count() === 0) {
            echo "\nWARNING: No posts or comments found. Please run PostSeeder and CommentSeeder first.\n";
            return;
        }

        $users = User::all();
        $posts = Post::all();
        $comments = Comment::all();

        $reasons = [
            'Spam',
            'Inappropriate Content',
            'Harassment',
            'Hate Speech',
            'Misinformation',
        ];

        // Create 50 reports
        for ($i = 0; $i < 50; $i++) {
            $reporter = $users->random();
            $reportable = null;

            if (rand(0, 1) === 0 && $posts->isNotEmpty()) {
                $reportable = $posts->random();
            } elseif ($comments->isNotEmpty()) {
                $reportable = $comments->random();
            }

            if ($reportable) {
                Report::create([
                    'reporter_id' => $reporter->id,
                    'reportable_type' => $reportable::class,
                    'reportable_id' => $reportable->id,
                    'reason' => collect($reasons)->random(),
                    'status' => collect(['pending', 'reviewed', 'resolved'])->random(),
                ]);
            }
        }

        echo "\nSeeded reports.\n";
    }
}
