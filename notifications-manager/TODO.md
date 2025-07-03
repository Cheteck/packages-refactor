# TODO for IJIDeals Notifications Manager Package

## Phase 1: Foundation & User Preferences

- [ ] **Define Core Purpose & Scope:**
    - [ ] Clarify primary goal: Is it mainly for managing user preferences, or also for centralizing notification templating and dispatch?
    - [ ] List initial notification types from various packages (e.g., `NewMessage` from Messaging, `OrderShipped` from Commerce, `NewFollower` from Social).
    - [ ] Define supported notification channels (e.g., `mail`, `database`, `broadcast` for push, potentially `sms`).
- [X] **Initial Setup:**
    - [X] Create `composer.json`:
        - [X] Set license to "proprietary".
        - [X] Set author to "IJIDeals" and email "contact@ijideals.com".
        - [X] Add basic dependencies (PHP, Laravel framework, `ijideals/user-management`).
        - [X] Define PSR-4 autoloading for `IJIDeals\\NotificationsManager\\`.
    - [ ] Create `src/Providers/NotificationsManagerServiceProvider.php`.
        - Implement `register()` to merge config.
        - Implement `boot()` to load migrations, routes (if any for preference management), and publish config.
    - [ ] Create `config/notifications-manager.php` (at package root).
        - Define available notification types/categories (key, display name, default channels).
        - Define available channels.
- [ ] **Core Models & Migrations:**
    - [ ] **`UserNotificationPreference` Model:**
        - Fields: `id`, `user_id` (FK), `notification_type` (string/enum key), `channel` (string/enum: mail, database, push, sms), `is_enabled` (boolean).
        - Unique constraint on `(user_id, notification_type, channel)`.
    - [ ] Create migration for `user_notification_preferences` table.
- [ ] **Service Contracts (Interfaces):**
    - [ ] `UserNotificationPreferenceServiceInterface`: Methods like `getPreferences(User $user)`, `updatePreference(User $user, string $notificationType, string $channel, bool $isEnabled)`, `isNotificationEnabled(User $user, string $notificationType, string $channel)`.
- [ ] **Basic Service Implementation:**
    - [ ] Create `src/Services/UserNotificationPreferenceService.php` implementing the interface.
- [ ] **API for Preferences:**
    - [ ] Create `src/Http/Controllers/NotificationPreferenceController.php`.
    - [ ] Define routes in `routes/api.php` for users to get and update their preferences (e.g., `GET /notifications/preferences`, `PUT /notifications/preferences`).
    - [ ] Create FormRequests for validation.
    - [ ] Implement Policies for authorization.

## Phase 2: Integration & Helper Functionality

- [ ] **Integration with Laravel's Notification System:**
    - [ ] Provide guidance or helpers for other packages to check user preferences before sending a notification.
    - Example: A `ShouldNotify` trait or a method in `UserNotificationPreferenceService` that can be used within a Notification class's `via()` method or before dispatching.
    - `if ($preferenceService->shouldSend($notifiable, 'new_message_received', 'mail')) { // send mail }`
- [ ] **Notification Type Registration:**
    - [ ] Develop a system for other packages to register their notification types and default preferences into `config/notifications-manager.php` (perhaps via a service provider method or a dedicated config key they can publish to).
- [ ] **User Interface Considerations (Documentation):**
    - [ ] Document how a user interface for managing these preferences could be structured.
- [ ] **Testing:**
    - [ ] Unit tests for `UserNotificationPreferenceService`.
    - [ ] Feature tests for API endpoints managing preferences.
- [ ] **Documentation:**
    - [ ] Update `README.md` with setup, configuration of notification types, and usage examples for checking preferences.

## Phase 3: Advanced Features (Future - if scope expands)

- [ ] **Centralized Notification Templating:**
    - [ ] Models/storage for notification templates (mail, database, push formats).
    - [ ] Service for rendering notifications using these templates.
- [ ] **Centralized Notification Dispatch:**
    - [ ] A `NotificationDispatchService` that takes a generic notification event, checks user preferences, fetches templates, and sends via appropriate channels.
    - [ ] This would make other packages dispatch a generic event rather than concrete Laravel Notifications.
- [ ] **Digest Notifications:**
    - [ ] Logic for collecting notifications over a period and sending as a digest.
    - [ ] User preferences for digest frequency.
- [ ] **Do Not Disturb (DND) Settings:**
    - [ ] User settings for DND periods.
    - [ ] `UserNotificationPreferenceService` to respect DND settings.
- [ ] **In-App Notification Center:**
    - [ ] If this package also manages the "database" channel notifications for display in a UI.

This stub outlines a package primarily focused on user preferences first, with potential expansion.
