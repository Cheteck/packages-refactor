<?php

namespace IJIDeals\Social\Database\Seeders;

use Illuminate\Database\Seeder;
use IJIDeals\Social\Models\Activity;
use IJIDeals\UserManagement\Models\User;
use IJIDeals\Social\Models\Post;
use IJIDeals\Social\Models\Comment;
use IJIDeals\Social\Models\Reaction;
use IJIDeals\Social\Enums\ActivityTypeEnum;

class ActivitySeeder extends Seeder
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

        $users = User::all();
        $posts = Post::all();
        $comments = Comment::all();
        $reactions = Reaction::all();

        // User created activities
        $users->each(function (User $user) {
            Activity::create([
                'user_id' => $user->id, // Changed from actor_id
                'event' => ActivityTypeEnum::USER_CREATED->value, // Changed from event, use Enum value
                'subject_type' => $user->getMorphClass(),      // Changed from loggable_type
                'subject_id' => $user->id,                // Changed from loggable_id
                'properties' => ['user_name' => $user->name, 'user_email' => $user->email],
            ]);
        });

        // Post created activities
        if ($posts->isNotEmpty()) {
            $posts->each(function (Post $post) {
                Activity::create([
                    'user_id' => $post->user_id,
                    'event' => ActivityTypeEnum::POST_CREATED->value,
                    'subject_type' => $post->getMorphClass(),
                    'subject_id' => $post->id,
                    'properties' => ['post_title' => $post->title ?? substr($post->content, 0, 30).'...', 'user_id' => $post->user_id],
                ]);
            });
        } else {
            echo "\nINFO: No posts found, skipping post_created activities.\n";
        }

        // Comment created activities
        if ($comments->isNotEmpty()) {
            $comments->each(function (Comment $comment) {
                Activity::create([
                    'user_id' => $comment->user_id,
                    'event' => ActivityTypeEnum::COMMENT_CREATED->value,
                    'subject_type' => $comment->getMorphClass(),
                    'subject_id' => $comment->id,
                    'properties' => ['comment_content' => substr($comment->content, 0, 50) . '...', 'post_id' => $comment->commentable_id],
                ]);
            });
        } else {
            echo "\nINFO: No comments found, skipping comment_created activities.\n";
        }

        // Reaction created activities
        if ($reactions->isNotEmpty()) {
            $reactions->each(function (Reaction $reaction) {
                Activity::create([
                    'user_id' => $reaction->user_id,
                    'event' => ActivityTypeEnum::REACTION_CREATED->value,
                    'subject_type' => $reaction->getMorphClass(), // The reaction itself is the subject
                    'subject_id' => $reaction->id,
                    'properties' => [
                        'reaction_type' => $reaction->type,
                        'reacted_to_type' => $reaction->interactable_type, // Assuming Reaction model still has interactable_type/id
                        'reacted_to_id' => $reaction->interactable_id,
                    ],
                ]);
            });
        } else {
            echo "\nINFO: No reactions found, skipping reaction_created activities.\n";
        }

        echo "\nSeeded activities.\n";
    }
}