<?php

namespace IJIDeals\Internationalization\Database\factories;

use IJIDeals\Internationalization\Models\Translation;
use Illuminate\Database\Eloquent\Factories\Factory;

class TranslationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Translation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'translatable_type' => 'App\\Models\\Product',
            'translatable_id' => $this->faker->numberBetween(1, 100),
            'attribute' => $this->faker->randomElement(['name', 'description', 'title', 'content']),
            'language_code' => $this->faker->randomElement(['en', 'fr', 'es', 'de']),
            'value' => $this->faker->sentence(),
        ];
    }

    /**
     * Create a translation for a specific attribute.
     */
    public function forAttribute(string $attribute): static
    {
        return $this->state(fn (array $attributes) => [
            'attribute' => $attribute,
        ]);
    }

    /**
     * Create a translation for a specific language.
     */
    public function forLanguage(string $languageCode): static
    {
        return $this->state(fn (array $attributes) => [
            'language_code' => $languageCode,
        ]);
    }

    /**
     * Create a translation for a specific model.
     */
    public function forModel(string $modelClass, int $modelId): static
    {
        return $this->state(fn (array $attributes) => [
            'translatable_type' => $modelClass,
            'translatable_id' => $modelId,
        ]);
    }

    /**
     * Create a name translation.
     */
    public function name(): static
    {
        return $this->forAttribute('name');
    }

    /**
     * Create a description translation.
     */
    public function description(): static
    {
        return $this->forAttribute('description');
    }

    /**
     * Create an English translation.
     */
    public function english(): static
    {
        return $this->forLanguage('en');
    }

    /**
     * Create a French translation.
     */
    public function french(): static
    {
        return $this->forLanguage('fr');
    }

    /**
     * Create a Spanish translation.
     */
    public function spanish(): static
    {
        return $this->forLanguage('es');
    }

    /**
     * Create a German translation.
     */
    public function german(): static
    {
        return $this->forLanguage('de');
    }
}
