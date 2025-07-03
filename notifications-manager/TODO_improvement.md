# TODO for NotificationsManager Package (Improvements)

## 🚀 Core Functionality Enhancements

-   **Refine User Preference Logic (`UserNotificationPreferenceService`):**
    -   [ ] **`getFormattedPreferences`**: Ensure this method correctly falls back to `default_channels` from `config('notifications-manager.notification_types')` if a user has no specific preference saved for a type/channel combination.
    -   [ ] **`isNotificationEnabled`**: Similarly, ensure fallback to default channel configuration for a type if no explicit user preference exists.
    -   [ ] **Seeding Preferences (`seedUserPreferences`)**:
        -   Test thoroughly, especially the `seed_default_preferences_for_new_type` logic for existing users when a new notification type is added to the config (this might require a command or a listener on app update).
-   **Integration with Laravel's Notification System:** (From original TODO - Phase 2)
    -   [ ] **`ShouldNotifyTrait` or Helper Method**:
        -   Create a trait (e.g., `ChecksNotificationPreferences`) or enhance `UserNotificationPreferenceService` with a method like `shouldSend(Notifiable $notifiable, string $notificationTypeKey, string $channelKey): bool`.
        -   This method will be used by Laravel Notification classes (in other packages) within their `via()` method or before dispatching to check if the user has enabled that specific notification type and channel.
        -   Example usage in a Notification class:
            ```php
            // In a theoretical NewOrderNotification.php
            public function via($notifiable)
            {
                $preferenceService = app(UserNotificationPreferenceServiceInterface::class);
                $channels = [];
                if ($preferenceService->shouldSend($notifiable, 'order_updates', 'mail')) {
                    $channels[] = 'mail';
                }
                if ($preferenceService->shouldSend($notifiable, 'order_updates', 'database')) {
                    $channels[] = 'database';
                }
                return $channels;
            }
            ```
-   **Dynamic Notification Type Registration:** (From original TODO - Phase 2)
    -   [ ] Develop a system for other packages to register their notification types and default preferences.
        -   **Option A (Config Publishing):** Each package publishes a config snippet to a specific directory (e.g., `config/notifications-manager/types.d/`) which are then merged by this package's service provider.
        -   **Option B (Service Provider Method):** Provide a static method in `NotificationsManagerServiceProvider` or a dedicated service that other packages can call in their `boot` method to register types (e.g., `NotificationTypeRegistry::register('new_message', [...details...])`).
-   **User Interface Considerations (Documentation):** (From original TODO - Phase 2)
    -   [ ] Document how a user interface for managing these preferences should interact with the API endpoints (`GET /preferences`, `PUT /preferences`).
    -   [ ] Provide example payload for updating preferences.

## 🔧 API & Configuration

-   **Refine API (`NotificationPreferenceController`):**
    -   [ ] **Error Handling**: Ensure consistent error responses and logging.
    -   [ ] **Authorization**: Double-check that `UserNotificationPreferencePolicy` is effective and covers all edge cases for viewing/updating preferences (should only be for the authenticated user unless an admin role is considered).
-   **Expand `config/notifications-manager.php`:**
    -   [ ] **User Model**: Ensure `user_model` correctly points to `IJIDeals\UserManagement\Models\User` or is easily configurable.
    -   [ ] **Translatable Display Names**: Make `display_name` and `description` for notification types and channels translatable using Laravel's translation system (e.g., `__('notifications-manager::types.new_message')`). This package would need to publish its language files.
    -   [ ] **Channel Specifics**: Add more configuration options per channel if needed (e.g., specific mailers for certain notification types, or specific push notification topics/tags).

## 🧹 Code Quality & Maintenance

-   **Enums for Keys**:
    -   [ ] Consider using PHP Enums for `notification_type` keys and `channel` keys for better type safety and auto-completion, if the set of types/channels becomes relatively stable. Config would then reference `NotificationTypeEnum::NewMessage->value`.
-   **Service `UserNotificationPreferenceService`:**
    -   [ ] Review for any potential N+1 query issues when fetching or formatting preferences, especially if the number of notification types/channels grows large.

## 📚 Documentation & Testing

-   **README Update:** (From original TODO - Phase 2)
    -   [ ] Document the API for managing preferences.
    -   [ ] Explain how to define new notification types and channels in `config/notifications-manager.php`.
    -   [ ] **Crucial**: Provide clear instructions and examples for other package developers on how to integrate with this service to check user preferences before sending notifications (using the `ShouldNotifyTrait` or service method).
    -   [ ] Document the dynamic notification type registration mechanism once implemented.
-   **Testing Strategy:** (From original TODO - Phase 2)
    -   [ ] Unit tests for `UserNotificationPreferenceService` (getting, setting, checking preferences, default fallbacks, seeding logic).
    -   [ ] Feature tests for the API endpoints managing preferences.
    -   [ ] Tests for the `ShouldNotifyTrait` or helper method (mocking the service).

## 💡 Advanced Features (Future - Phase 3 from original TODO)

*   **Centralized Notification Templating:**
    *   [ ] Design models/storage for notification templates (mail, database, push formats per notification type per channel).
    *   [ ] Create a service for rendering notifications using these templates, allowing for DB-driven template overrides.
*   **Centralized Notification Dispatch Service:**
    *   [ ] Create a `NotificationDispatchService` that:
        -   Accepts a generic notification event or data object.
        -   Determines the `notification_type` key.
        -   Fetches relevant users.
        -   For each user, checks their preferences via `UserNotificationPreferenceService`.
        -   For enabled channels, fetches the appropriate template.
        -   Renders and dispatches the notification via Laravel's `Notification::send()` or directly to channels.
    *   This would abstract notification sending logic from individual packages.
*   **Digest Notifications:**
    *   [ ] Implement logic for collecting certain types of notifications over a period (e.g., daily, weekly).
    *   [ ] User preference for digest frequency per notification type.
    *   [ ] Scheduled job to compile and send digests.
*   **Do Not Disturb (DND) Settings:**
    *   [ ] Model/settings for users to define DND periods or global DND status.
    *   [ ] `UserNotificationPreferenceService` and/or `NotificationDispatchService` to respect DND settings.
*   **In-App Notification Center UI Backend:**
    -   [ ] If this package is also responsible for providing the data for a UI-based notification center (for 'database' channel notifications), add API endpoints to fetch, mark as read/unread, and delete database notifications for the authenticated user.

This focuses on making the preference management solid and then integrating it with the actual notification sending process. Advanced features can follow.
