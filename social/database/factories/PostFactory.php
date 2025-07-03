<?php

namespace IJIDeals\Social\Database\Factories;

use IJIDeals\Social\Enums\PostTypeEnum;
use IJIDeals\Social\Enums\VisibilityType;
use IJIDeals\Social\Models\Post;
use IJIDeals\UserManagement\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\IJIDeals\Social\Models\Post>
     */
    protected $model = Post::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        // Adapted to @Post.php model structure and fillable/casts
        return [
            'author_id' => User::factory(),
            'author_type' => User::class,
            'content' => $this->faker->paragraph,
            'type' => $this->faker->randomElement(PostTypeEnum::cases())->value,
            'visibility' => $this->faker->randomElement(VisibilityType::cases())->value,
            'metadata' => ['source' => $this->faker->word],
            'status' => $this->faker->randomElement(['draft', 'published', 'archived', 'pending', 'rejected']),
            'location' => null,
            'scheduled_at' => null,
            'expires_at' => null,
            'comment_settings' => $this->faker->randomElement(['everyone', 'followers', 'disabled']),
            'reaction_settings' => $this->faker->randomElement(['enabled', 'disabled']),
            'engagement_score' => $this->faker->randomFloat(2, 0, 100),
            'is_published' => true,
            'title' => $this->faker->optional()->sentence,
            // 'product_id', 'poll_id', 'reach_estimate_id' are nullable and can be set in states if needed
        ];
    }

    /**
     * Indicate that the post is a draft.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function draft()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'draft',
                'is_published' => false,
            ];
        });
    }

    /**
     * Indicate that the post is of a specific type.
     *
     * @param PostTypeEnum $type
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function ofType(PostTypeEnum $type)
    {
        return $this->state([
            'type' => $type->value,
        ]);
    }
}
