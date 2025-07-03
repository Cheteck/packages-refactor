<?php

namespace IJIDeals\Social\Database\Factories;

use IJIDeals\Social\Models\Post;
use IJIDeals\Social\Models\Reaction; // For interactable_type Post
use IJIDeals\UserManagement\Models\User; // For user_id
use Illuminate\Database\Eloquent\Factories\Factory;

class ReactionFactory extends Factory
{
    /**
     * @var class-string<\IJIDeals\Social\Models\Reaction>
     */
    protected $model = Reaction::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $interactable = Post::factory()->create(); // Default to reacting to a Post

        return [
            'user_id' => User::factory(),
            'interactable_id' => $interactable->id,
            'interactable_type' => get_class($interactable), // Or Post::class directly if always Post
            'type' => $this->faker->randomElement(config('reactions.valid_types', ['like', 'heart', 'smile', 'sad', 'angry'])),
        ];
    }

    /**
     * Indicate the reaction is for a specific interactable model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function forInteractable(\Illuminate\Database\Eloquent\Model $interactable)
    {
        return $this->state([
            'interactable_id' => $interactable->id,
            'interactable_type' => get_class($interactable),
        ]);
    }

    /**
     * Indicate the type of reaction.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function ofType(string $type)
    {
        return $this->state([
            'type' => $type,
        ]);
    }
}
