<?php

namespace IJIDeals\Social\Database\factories;

use IJIDeals\Social\Models\Post;
use IJIDeals\Social\Models\Reaction;
use IJIDeals\UserManagement\Models\User;
// You'll need to import a concrete model that can be reacted to, or use a generic approach
// For example, if Posts can be reacted to:
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\IJIDeals\Social\Models\Reaction>
 */
class ReactionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Reaction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Defaulting to Post as an example interactable type
        $interactable = Post::factory()->create();

        return [
            'user_id' => User::factory(),
            'interactable_id' => $interactable->id,
            'interactable_type' => $interactable->getMorphClass(),
            'type' => $this->faker->randomElement(['like', 'love', 'haha', 'wow', 'sad', 'angry']),
        ];
    }

    /**
     * Indicate that the reaction is for a specific model.
     */
    public function forInteractable(\Illuminate\Database\Eloquent\Model $interactable): static
    {
        return $this->state(fn (array $attributes) => [
            'interactable_type' => $interactable->getMorphClass(),
            'interactable_id' => $interactable->getKey(),
        ]);
    }

    public function type(string $reactionType): static
    {
        return $this->state(fn (array $attributes) => ['type' => $reactionType]);
    }
}
