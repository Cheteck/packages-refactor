<?php

namespace IJIDeals\NotificationsManager\Services;

interface UserNotificationPreferenceServiceInterface
{
    /**
     * Get all notification preferences for a user.
     *
     * @param  mixed  $user  The user instance.
     * @return \Illuminate\Support\Collection
     */
    public function getPreferences(mixed $user);

    /**
     * Get preferences for a user, structured by type and channel.
     *
     * @param  mixed  $user  The user instance.
     */
    public function getFormattedPreferences(mixed $user): array;

    /**
     * Update a specific notification preference for a user.
     *
     * @param  mixed  $user  The user instance.
     * @param  string  $notificationType  The key of the notification type.
     * @param  string  $channel  The key of the channel.
     * @param  bool  $isEnabled  Whether the notification should be enabled.
     * @return \IJIDeals\NotificationsManager\Models\UserNotificationPreference
     */
    public function updatePreference(mixed $user, string $notificationType, string $channel, bool $isEnabled);

    /**
     * Update multiple preferences for a user.
     * Input $preferences should be an array like:
     * [
     *     'notification_type_key' => [
     *         'channel_key' => true, // true for enabled, false for disabled
     *         // ... other channels
     *     ],
     *     // ... other notification types
     * ]
     *
     * @param  mixed  $user  The user instance.
     * @param  array  $preferences  An array of preferences to update.
     */
    public function updateMultiplePreferences(mixed $user, array $preferences): bool;

    /**
     * Check if a specific notification type and channel is enabled for a user.
     *
     * @param  mixed  $user  The user instance.
     * @param  string  $notificationType  The key of the notification type.
     * @param  string  $channel  The key of the channel.
     */
    public function isNotificationEnabled(mixed $user, string $notificationType, string $channel): bool;

    /**
     * Seed default preferences for a new user or when new types/channels are added.
     *
     * @param  mixed  $user  The user instance.
     */
    public function seedUserPreferences(mixed $user): void;
}
