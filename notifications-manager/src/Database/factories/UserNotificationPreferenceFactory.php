<?php

namespace IJIDeals\NotificationsManager\Database\factories;

use IJIDeals\NotificationsManager\Models\UserNotificationPreference;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\IJIDeals\NotificationsManager\Models\UserNotificationPreference>
 */
class UserNotificationPreferenceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = UserNotificationPreference::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $userModel = config('notifications-manager.user_model', \App\Models\User::class);
        $notificationTypes = array_keys(config('notifications-manager.notification_types', []));
        $availableChannels = array_keys(config('notifications-manager.available_channels', []));

        return [
            'user_id' => $userModel::factory(),
            'notification_type' => $this->faker->randomElement($notificationTypes ?: ['default_type']),
            'channel' => $this->faker->randomElement($availableChannels ?: ['default_channel']),
            'is_enabled' => $this->faker->boolean,
        ];
    }
}
