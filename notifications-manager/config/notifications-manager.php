<?php

return [
    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | The model class to use for users.
    |
    */
    'user_model' => \App\Models\User::class, // Or your specific user model path from ijideals/user-management

    /*
    |--------------------------------------------------------------------------
    | Notification Types
    |--------------------------------------------------------------------------
    |
    | Define the types of notifications users can manage preferences for.
    | Each type should have a unique key, a display name (translatable),
    | and default enabled channels.
    |
    | Example:
    | 'new_message' => [
    |     'display_name' => 'notifications_manager::types.new_message', // For translation
    |     'default_channels' => ['mail', 'database'],
    |     'description' => 'notifications_manager::descriptions.new_message', // Optional description
    | ],
    |
    */
    'notification_types' => [
        // Example types - these should be populated by the application or other packages
        'system_update' => [
            'display_name' => 'System Updates',
            'default_channels' => ['database'],
            'description' => 'Receive updates about new features and system maintenance.',
        ],
        'marketing_promo' => [
            'display_name' => 'Promotions & Marketing',
            'default_channels' => ['mail'],
            'description' => 'Receive promotional offers and marketing news.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Available Notification Channels
    |--------------------------------------------------------------------------
    |
    | Define the channels through which notifications can be sent.
    | Each channel should have a unique key and a display name.
    |
    */
    'available_channels' => [
        'mail' => [
            'display_name' => 'Email',
        ],
        'database' => [
            'display_name' => 'In-App Notification', // For on-site notifications
        ],
        'push' => [ // Example for web push or mobile push
            'display_name' => 'Push Notification',
        ],
        // 'sms' => [
        //     'display_name' => 'SMS',
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Preferences
    |--------------------------------------------------------------------------
    |
    | When a new user is created, or when a new notification type is added,
    | these can be used to seed their initial preferences.
    | If true, all defined 'default_channels' for a type will be enabled for a new user.
    | If false, users will have to opt-in.
    |
    */
    'seed_default_preferences_for_new_user' => true,
    'seed_default_preferences_for_new_type' => true, // For existing users when a new type is added

    /*
    |--------------------------------------------------------------------------
    | Table Names
    |--------------------------------------------------------------------------
    |
    | Table names used by the package.
    |
    */
    'table_names' => [
        'user_notification_preferences' => 'user_notification_preferences',
    ],

    /*
    |--------------------------------------------------------------------------
    | API Route Configuration
    |--------------------------------------------------------------------------
    */
    'api_routes' => [
        'prefix' => 'api/notifications',
        'middleware' => ['auth:sanctum'], // Adjust as needed for your auth setup
    ],
];
