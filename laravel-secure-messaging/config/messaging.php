<?php

return [
    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | This is the Eloquent model that should be used to represent users.
    | It will be used for relationships and authentication.
    | This will now default to the model specified in the UserManagement package's config.
    |
    */
    'user_model' => config('user-management.model', \App\Models\User::class),

    /*
    |--------------------------------------------------------------------------
    | Authentication Driver
    |--------------------------------------------------------------------------
    |
    | Specify the authentication driver for the API.
    | Supported: "sanctum", "jwt" (JWT may require additional setup)
    |
    */
    'auth_driver' => 'sanctum',

    /*
    |--------------------------------------------------------------------------
    | Real-time Notifications
    |--------------------------------------------------------------------------
    |
    | Configure the broadcasting driver for real-time notifications.
    | Ensure you have configured your broadcasting connections in config/broadcasting.php.
    |
    */
    'notifications' => [
        'driver' => env('MESSAGING_NOTIFICATION_DRIVER', 'pusher'), // Example: pusher, redis, log
        'queue_connection' => env('MESSAGING_QUEUE_CONNECTION', 'sync'), // Connection for async notifications
    ],

    /*
    |--------------------------------------------------------------------------
    | Encryption Settings
    |--------------------------------------------------------------------------
    |
    | Settings related to end-to-end encryption.
    | Sodium is used by default.
    |
    */
    'encryption' => [
        'key_storage_path' => storage_path('app/secure-messaging/keys'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Routes Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the prefix and middleware for the package routes.
    |
    */
    'routes' => [
        'prefix' => 'api/messaging',
        'middleware' => ['api'], // Default API middleware group
        'auth_middleware' => 'auth:sanctum', // Middleware for authenticated routes
    ],

    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific features of the messaging system.
    |
    */
    'features' => [
        'ephemeral_messages' => [
            'enabled' => true,
            'default_ttl_seconds' => 60 * 60 * 24, // 1 day
        ],
        'attachments' => [
            'enabled' => true,
            'max_size_kb' => 10240, // 10MB
            'allowed_mime_types' => ['image/jpeg', 'image/png', 'application/pdf'],
            'storage_disk' => 'local', // Laravel filesystem disk
            'storage_path_prefix' => 'messaging_attachments',
        ],
        'typing_indicators' => true,
        'read_receipts' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting for API endpoints.
    | The name should correspond to a limiter defined in your App\Providers\RouteServiceProvider.
    | Set to null to disable package-specific rate limiting (global limiters may still apply).
    |
    */
    'rate_limiting' => [
        'send_message' => env('MESSAGING_RL_SEND_MESSAGE', '60,1'),    // 60 attempts per 1 minute
        'create_group' => env('MESSAGING_RL_CREATE_GROUP', '10,1'),    // 10 attempts per 1 minute
        'upload_attachment' => env('MESSAGING_RL_UPLOAD_ATTACHMENT', '30,1'), // 30 attempts per 1 minute
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    |
    | Configure caching for frequently accessed data to improve performance.
    |
    */
    'caching' => [
        'enabled' => true,
        'store' => env('CACHE_DRIVER', 'file'), // Cache store to use
        'prefix' => 'secure_messaging',
        'ttl_seconds' => [
            'conversations' => 3600, // 1 hour
            'group_members' => 3600, // 1 hour
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Model Public Columns
    |--------------------------------------------------------------------------
    |
    | When fetching user data for participants or senders, these are the
    | columns that will be selected. This is to avoid exposing sensitive
    | user data unintentionally. 'id' is usually always needed.
    |
    */
    'user_model_public_columns' => ['id', 'name', 'email'], // Customize as needed, ensure 'public_key' is available if used directly on user model

    /*
    |--------------------------------------------------------------------------
    | Pagination Limits
    |--------------------------------------------------------------------------
    |
    | Default pagination limits for messages and conversations.
    |
    */
    'pagination_limit' => 25, // For messages
    'pagination_limit_conversations' => 15, // For conversations list

];
