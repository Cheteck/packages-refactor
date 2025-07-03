<?php

namespace IJIDeals\Social\Database\Factories;

use IJIDeals\Social\Models\Comment;
use IJIDeals\Social\Models\Post; // For commentable_type Post
use IJIDeals\UserManagement\Models\User; // For author_type User
use Illuminate\Database\Eloquent\Factories\Factory;

class CommentFactory extends Factory
{
    /**
     * @var class-string<\IJIDeals\Social\Models\Comment>
     */
    protected $model = Comment::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'author_id' => User::factory(),
            'author_type' => User::class,
            'commentable_id' => Post::factory(), // Default to commenting on a Post
            'commentable_type' => Post::class,
            'content' => $this->faker->sentence,
            'parent_id' => null, // Default to a top-level comment
        ];
    }

    /**
     * Indicate that the comment is a reply to another comment.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function replyTo(int $parentId)
    {
        return $this->state(function (array $attributes) use ($parentId) {
            return [
                'parent_id' => $parentId,
            ];
        });
    }

    /**
     * Indicate that the comment is for a specific commentable model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function forCommentable(\Illuminate\Database\Eloquent\Model $commentable)
    {
        return $this->state([
            'commentable_id' => $commentable->id,
            'commentable_type' => get_class($commentable),
        ]);
    }
}
