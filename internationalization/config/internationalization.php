<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Internationalization Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for the IJIDeals Internationalization package.
    |
    */

    'supported_languages' => [
        'en' => ['name' => 'English', 'native' => 'English', 'locale' => 'en_US', 'direction' => 'ltr'],
        'fr' => ['name' => 'French', 'native' => 'Français', 'locale' => 'fr_FR', 'direction' => 'ltr'],
        // Add more languages as needed
    ],

    'default_language' => env('APP_LOCALE', 'en'),

    'locale_detection_order' => [
        'url', // From URL prefix (e.g., /en/my-page)
        'session', // From user session
        'user_preference', // From authenticated user's preferred language setting
        'browser', // From Accept-Language header
        'default', // Fallback to default_language
    ],

    'route_localization' => [
        'prefix' => true, // Whether to prefix routes with locale (e.g., /en/dashboard)
        'hide_default_locale_prefix' => false, // Hide prefix for default language (e.g., /dashboard instead of /en/dashboard)
    ],

    'auto_translation' => [
        'enabled' => env('AUTO_TRANSLATION_ENABLED', false),
        'default_provider' => env('AUTO_TRANSLATION_PROVIDER', 'google'), // 'google', 'deepl', 'azure', 'mock'
        'providers' => [
            'google' => [
                'api_key' => env('GOOGLE_TRANSLATE_API_KEY'),
            ],
            'deepl' => [
                'api_key' => env('DEEPL_API_KEY'),
                'api_url' => env('DEEPL_API_URL', 'https://api-free.deepl.com/v2/'),
            ],
            'azure' => [
                'api_key' => env('AZURE_TRANSLATOR_API_KEY'),
                'region' => env('AZURE_TRANSLATOR_REGION'),
                'api_url' => env('AZURE_TRANSLATOR_API_URL', 'https://api.cognitive.microsofttranslator.com/'),
            ],
        ],
        // Auto-translate specific models/attributes
        'translatable_models' => [
            // \App\Models\Product::class => ['name', 'description'],
            // \IJIDeals\IJICommerce\Models\Brand::class => ['name', 'description'],
        ],
    ],

    // Table names (if you need to override defaults)
    'table_names' => [
        'languages' => 'languages',
    ],
];
