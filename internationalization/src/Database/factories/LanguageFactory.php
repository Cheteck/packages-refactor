<?php

namespace IJIDeals\Internationalization\Database\factories;

use IJIDeals\Internationalization\Models\Language;
use Illuminate\Database\Eloquent\Factories\Factory;

class LanguageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Language::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $languages = [
            ['code' => 'en', 'name' => 'English', 'direction' => 'ltr', 'flag_icon' => 'flag-icon-us'],
            ['code' => 'fr', 'name' => 'French', 'direction' => 'ltr', 'flag_icon' => 'flag-icon-fr'],
            ['code' => 'es', 'name' => 'Spanish', 'direction' => 'ltr', 'flag_icon' => 'flag-icon-es'],
            ['code' => 'de', 'name' => 'German', 'direction' => 'ltr', 'flag_icon' => 'flag-icon-de'],
            ['code' => 'it', 'name' => 'Italian', 'direction' => 'ltr', 'flag_icon' => 'flag-icon-it'],
            ['code' => 'pt', 'name' => 'Portuguese', 'direction' => 'ltr', 'flag_icon' => 'flag-icon-pt'],
            ['code' => 'ru', 'name' => 'Russian', 'direction' => 'ltr', 'flag_icon' => 'flag-icon-ru'],
            ['code' => 'zh', 'name' => 'Chinese', 'direction' => 'ltr', 'flag_icon' => 'flag-icon-cn'],
            ['code' => 'ja', 'name' => 'Japanese', 'direction' => 'ltr', 'flag_icon' => 'flag-icon-jp'],
            ['code' => 'ko', 'name' => 'Korean', 'direction' => 'ltr', 'flag_icon' => 'flag-icon-kr'],
            ['code' => 'ar', 'name' => 'Arabic', 'direction' => 'rtl', 'flag_icon' => 'flag-icon-sa'],
            ['code' => 'he', 'name' => 'Hebrew', 'direction' => 'rtl', 'flag_icon' => 'flag-icon-il'],
        ];

        $language = $this->faker->randomElement($languages);

        return [
            'code' => $language['code'],
            'name' => $language['name'],
            'is_default' => false,
            'direction' => $language['direction'],
            'status' => true,
            'flag_icon' => $language['flag_icon'],
        ];
    }

    /**
     * Indicate that the language is the default language.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }

    /**
     * Indicate that the language is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => false,
        ]);
    }

    /**
     * Create a specific language.
     */
    public function english(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'en',
            'name' => 'English',
            'direction' => 'ltr',
            'flag_icon' => 'flag-icon-us',
        ]);
    }

    /**
     * Create a specific language.
     */
    public function french(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'fr',
            'name' => 'French',
            'direction' => 'ltr',
            'flag_icon' => 'flag-icon-fr',
        ]);
    }

    /**
     * Create a specific language.
     */
    public function arabic(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'ar',
            'name' => 'Arabic',
            'direction' => 'rtl',
            'flag_icon' => 'flag-icon-sa',
        ]);
    }
}
