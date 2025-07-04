<?php

return [
    /**
     * Cache duration in minutes for platform settings.
     * Null or 0 means remember forever.
     */
    'cache_duration' => null,

    /**
     * Cache prefix for platform settings keys.
     */
    'cache_prefix' => 'platform_setting.',

    /**
     * Default settings to seed into the database.
     * These can be used by an Artisan command to initialize settings.
     * Format: 'key' => [
     *     'value' => mixed,
     *     'type' => 'string'|'integer'|'boolean'|'json'|'array', (optional, defaults to string if not set)
     *     'group' => 'general', (optional)
     *     'label' => 'Site Name', (optional, for UI)
     *     'description' => 'The official name of the platform.', (optional, for UI)
     *     'is_encrypted' => false, (optional)
     * ]
     */
    'default_settings' => [
        'site.name' => [
            'value' => 'IJIDeals Platform',
            'type' => 'string',
            'group' => 'general',
            'label' => 'Site Name',
            'description' => 'The official name of the platform.',
        ],
        'site.contact_email' => [
            'value' => 'contact@example.com',
            'type' => 'string',
            'group' => 'general',
            'label' => 'Contact Email',
            'description' => 'Public contact email address.',
        ],
        'maintenance_mode' => [
            'value' => false,
            'type' => 'boolean',
            'group' => 'general',
            'label' => 'Maintenance Mode',
            'description' => 'Enable to put the site into maintenance mode.',
        ],
        // Example for an encrypted setting
        // 'some_api.key' => [
        // 'value' => 'your_secret_api_key_here',
        // 'type' => 'string',
        // 'group' => 'integrations',
        // 'label' => 'Some API Key',
        // 'description' => 'API key for Some Service integration.',
        // 'is_encrypted' => true,
        // ],
    ],

    /**
     * If 'is_encrypted' column is not used on the model,
     * alternatively, list all keys here that should always be encrypted.
     * This is less flexible if new settings are added via UI.
     * e.g., ['payment_gateway.secret_key', 'some_api.key']
     */
    'force_encrypt_keys' => [
        // 'payment_gateway.secret_key',
    ],

    /**
     * Database table name for platform settings.
     */
    'table_name' => 'platform_settings',
];
