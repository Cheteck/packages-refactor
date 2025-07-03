<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Location Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for the IJIDeals Location package.
    |
    */

    'default_distance_unit' => env('LOCATION_DEFAULT_DISTANCE_UNIT', 'km'), // 'km' or 'miles'

    'geocoding' => [
        'enabled' => env('GEOCODING_ENABLED', false),
        'provider' => env('GEOCODING_PROVIDER', 'google'), // 'google', 'openstreetmap', 'mock'
        'providers' => [
            'google' => [
                'api_key' => env('GOOGLE_GEOCODING_API_KEY'),
            ],
            'openstreetmap' => [
                'api_url' => env('OPENSTREETMAP_NOMINATIM_URL', 'https://nominatim.openstreetmap.org/'),
            ],
        ],
    ],

    'postal_code_validation' => [
        'enabled' => false,
        // Add country-specific regex patterns here if needed
        // 'patterns' => [
        //     'US' => '/^\d{5}(-\d{4})?$/',
        //     'CA' => '/^[A-Za-z]\d[A-Za-z][ -]?\d[A-Za-z]\d$/',
        // ],
    ],

    'default_country_code' => env('LOCATION_DEFAULT_COUNTRY_CODE', 'US'),

    // Table names (if you need to override defaults)
    'table_names' => [
        'countries' => 'countries',
        'regions' => 'regions',
        'cities' => 'cities',
        'addresses' => 'addresses',
    ],
];
