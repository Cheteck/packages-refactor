<?php

namespace IJIDeals\NotificationsManager\Services;

use IJIDeals\NotificationsManager\Models\UserNotificationPreference;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UserNotificationPreferenceService implements UserNotificationPreferenceServiceInterface
{
    public function getPreferences(mixed $user): Collection
    {
        return UserNotificationPreference::where('user_id', $user->id)->get();
    }

    public function getFormattedPreferences(mixed $user): array
    {
        $preferences = $this->getPreferences($user);
        $definedTypes = config('notifications-manager.notification_types', []);
        $definedChannels = config('notifications-manager.available_channels', []);

        $formatted = [];

        foreach ($definedTypes as $typeKey => $typeDetails) {
            $formatted[$typeKey] = [
                'display_name' => $typeDetails['display_name'] ?? $typeKey,
                'description' => $typeDetails['description'] ?? '',
                'channels' => [],
            ];
            foreach ($definedChannels as $channelKey => $channelDetails) {
                $preference = $preferences->first(function ($pref) use ($typeKey, $channelKey) {
                    return $pref->notification_type === $typeKey && $pref->channel === $channelKey;
                });

                $formatted[$typeKey]['channels'][$channelKey] = [
                    'display_name' => $channelDetails['display_name'] ?? $channelKey,
                    'is_enabled' => $preference ? $preference->is_enabled : ($typeDetails['default_channels'] && in_array($channelKey, $typeDetails['default_channels'])),
                ];
            }
        }

        return $formatted;
    }

    public function updatePreference(mixed $user, string $notificationType, string $channel, bool $isEnabled): UserNotificationPreference
    {
        $this->validatePreference($notificationType, $channel);

        return UserNotificationPreference::updateOrCreate(
            [
                'user_id' => $user->id,
                'notification_type' => $notificationType,
                'channel' => $channel,
            ],
            ['is_enabled' => $isEnabled]
        );
    }

    public function updateMultiplePreferences(mixed $user, array $preferences): bool
    {
        // Validate the structure of the preferences array
        $definedTypes = array_keys(config('notifications-manager.notification_types', []));
        $definedChannels = array_keys(config('notifications-manager.available_channels', []));

        foreach ($preferences as $typeKey => $channels) {
            if (! in_array($typeKey, $definedTypes)) {
                throw ValidationException::withMessages(["preferences.{$typeKey}" => "Invalid notification type: {$typeKey}"]);
            }
            if (! is_array($channels)) {
                throw ValidationException::withMessages(["preferences.{$typeKey}" => "Channels for {$typeKey} must be an array."]);
            }
            foreach ($channels as $channelKey => $isEnabled) {
                if (! in_array($channelKey, $definedChannels)) {
                    throw ValidationException::withMessages(["preferences.{$typeKey}.{$channelKey}" => "Invalid channel: {$channelKey} for type {$typeKey}"]);
                }
                if (! is_bool($isEnabled)) {
                    throw ValidationException::withMessages(["preferences.{$typeKey}.{$channelKey}" => "Enabled status for {$typeKey} / {$channelKey} must be a boolean."]);
                }
            }
        }

        foreach ($preferences as $notificationType => $channels) {
            foreach ($channels as $channel => $isEnabled) {
                $this->updatePreference($user, $notificationType, $channel, $isEnabled);
            }
        }

        return true;
    }

    public function isNotificationEnabled(mixed $user, string $notificationType, string $channel): bool
    {
        $this->validatePreference($notificationType, $channel);

        $preference = UserNotificationPreference::where('user_id', $user->id)
            ->where('notification_type', $notificationType)
            ->where('channel', $channel)
            ->first();

        if ($preference) {
            return $preference->is_enabled;
        }

        // If no specific preference, check default for the type
        $typeConfig = config("notifications-manager.notification_types.{$notificationType}");

        return $typeConfig && isset($typeConfig['default_channels']) && in_array($channel, $typeConfig['default_channels']);
    }

    public function seedUserPreferences(mixed $user): void
    {
        if (! config('notifications-manager.seed_default_preferences_for_new_user', true)) {
            return;
        }

        $notificationTypes = config('notifications-manager.notification_types', []);

        foreach ($notificationTypes as $typeKey => $typeDetails) {
            $defaultChannels = $typeDetails['default_channels'] ?? [];
            foreach ($defaultChannels as $channelKey) {
                // Only seed if the channel is valid
                if (isset(config('notifications-manager.available_channels', [])[$channelKey])) {
                    UserNotificationPreference::firstOrCreate(
                        [
                            'user_id' => $user->id,
                            'notification_type' => $typeKey,
                            'channel' => $channelKey,
                        ],
                        ['is_enabled' => true] // Default to enabled
                    );
                }
            }
        }
    }

    /**
     * Validate if the notification type and channel are defined in config.
     *
     * @throws ValidationException
     */
    protected function validatePreference(string $notificationType, string $channel): void
    {
        $validator = Validator::make(
            ['type' => $notificationType, 'channel' => $channel],
            [
                'type' => 'required|string|in:'.implode(',', array_keys(config('notifications-manager.notification_types', []))),
                'channel' => 'required|string|in:'.implode(',', array_keys(config('notifications-manager.available_channels', []))),
            ]
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
