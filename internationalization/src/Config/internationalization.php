<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Language
    |--------------------------------------------------------------------------
    |
    | This is the default language that will be used when no specific
    | language is requested or when a translation is not available.
    |
    */
    'default_language' => env('DEFAULT_LANGUAGE', 'en'),

    /*
    |--------------------------------------------------------------------------
    | Supported Languages
    |--------------------------------------------------------------------------
    |
    | This array contains all the languages that are supported by the
    | application. Each language should have a unique code.
    |
    */
    'supported_languages' => [
        'en' => [
            'name' => 'English',
            'direction' => 'ltr',
            'flag_icon' => 'flag-icon-us',
            'is_default' => true,
        ],
        'fr' => [
            'name' => 'French',
            'direction' => 'ltr',
            'flag_icon' => 'flag-icon-fr',
            'is_default' => false,
        ],
        'es' => [
            'name' => 'Spanish',
            'direction' => 'ltr',
            'flag_icon' => 'flag-icon-es',
            'is_default' => false,
        ],
        'de' => [
            'name' => 'German',
            'direction' => 'ltr',
            'flag_icon' => 'flag-icon-de',
            'is_default' => false,
        ],
        'ar' => [
            'name' => 'Arabic',
            'direction' => 'rtl',
            'flag_icon' => 'flag-icon-sa',
            'is_default' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Locale Detection Order
    |--------------------------------------------------------------------------
    |
    | Define the order in which the application should attempt to detect the
    | user's preferred locale. The SetLocale middleware will iterate through
    | these methods in the specified order.
    | Available methods: 'url', 'session', 'user_preference', 'browser', 'default'
    |
    */
    'locale_detection_order' => [
        'url',          // Check for locale in URL segment (e.g., /en/home)
        'session',      // Check for locale in user's session
        'user_preference', // Check for locale in authenticated user's preferences
        'browser',      // Check Accept-Language header from browser
        // 'default' is always the final fallback (config('app.locale'))
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto Translation
    |--------------------------------------------------------------------------
    |
    | Configuration for automatic translation services.
    |
    */
    'auto_translation' => [
        'enabled' => env('AUTO_TRANSLATION_ENABLED', false),
        'provider' => env('AUTO_TRANSLATION_PROVIDER', 'google'), // 'google', 'deepl', 'azure'
        'api_key' => env('AUTO_TRANSLATION_API_KEY'),
        'fallback_language' => env('AUTO_TRANSLATION_FALLBACK_LANGUAGE', 'en'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Translation Services Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for different translation service providers.
    |
    */
    'translation_services' => [
        'google' => [
            'api_key' => env('GOOGLE_TRANSLATE_API_KEY'),
            'base_url' => 'https://translation.googleapis.com/language/translate/v2',
        ],
        'deepl' => [
            'api_key' => env('DEEPL_API_KEY'),
            'base_url' => 'https://api-free.deepl.com/v2',
        ],
        'azure' => [
            'api_key' => env('AZURE_TRANSLATOR_API_KEY'),
            'region' => env('AZURE_TRANSLATOR_REGION'),
            'base_url' => 'https://api.cognitive.microsofttranslator.com',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Localization Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for date, number, and currency formatting.
    |
    */
    'localization' => [
        'date_format' => [
            'en' => 'Y-m-d',
            'fr' => 'd/m/Y',
            'es' => 'd/m/Y',
            'de' => 'd.m.Y',
            'ar' => 'Y/m/d',
        ],
        'time_format' => [
            'en' => 'H:i',
            'fr' => 'H:i',
            'es' => 'H:i',
            'de' => 'H:i',
            'ar' => 'H:i',
        ],
        'datetime_format' => [
            'en' => 'Y-m-d H:i:s',
            'fr' => 'd/m/Y H:i:s',
            'es' => 'd/m/Y H:i:s',
            'de' => 'd.m.Y H:i:s',
            'ar' => 'Y/m/d H:i:s',
        ],
        'number_format' => [
            'decimal_separator' => [
                'en' => '.',
                'fr' => ',',
                'es' => ',',
                'de' => ',',
                'ar' => '.',
            ],
            'thousands_separator' => [
                'en' => ',',
                'fr' => ' ',
                'es' => '.',
                'de' => '.',
                'ar' => ',',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Localization
    |--------------------------------------------------------------------------
    |
    | Configuration for localized routes.
    |
    */
    'route_localization' => [
        'enabled' => true,
        'prefix' => true, // Add language prefix to routes
        'fallback' => true, // Fallback to default language if translation not found
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for caching translations.
    |
    */
    'cache' => [
        'enabled' => env('TRANSLATION_CACHE_ENABLED', true),
        'ttl' => env('TRANSLATION_CACHE_TTL', 3600), // 1 hour
        'prefix' => 'translations',
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for database tables and relationships.
    |
    */
    'database' => [
        'languages_table' => 'languages',
        'translations_table' => 'translations',
        'morph_key' => 'translatable',
    ],
];
