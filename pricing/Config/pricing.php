<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | This option defines the default currency used throughout the pricing
    | system. It should be an ISO 4217 currency code (e.g., 'USD', 'EUR', 'GBP').
    |
    */
    'currency' => env('PRICING_CURRENCY', 'USD'),

    /*
    |--------------------------------------------------------------------------
    | Price Precision
    |--------------------------------------------------------------------------
    |
    | This defines the number of decimal places to use when storing and
    | displaying prices. For most currencies, 2 is standard.
    |
    */
    'price_precision' => env('PRICING_PRICE_PRECISION', 2),

    /*
    |--------------------------------------------------------------------------
    | Tax Configuration
    |--------------------------------------------------------------------------
    |
    | Configure default tax rates or enable/disable tax calculations.
    | You might expand this to include tax zones, types, etc.
    |
    */
    'tax' => [
        'enabled' => env('PRICING_TAX_ENABLED', false),
        'default_rate' => env('PRICING_DEFAULT_TAX_RATE', 0.0), // As a decimal (e.g., 0.05 for 5%)
        'tax_inclusive' => env('PRICING_TAX_INCLUSIVE', false), // Are prices stored inclusive of tax?
    ],

    /*
    |--------------------------------------------------------------------------
    | Discount Configuration
    |--------------------------------------------------------------------------
    |
    | Options related to discount application and validation.
    |
    */
    'discounts' => [
        'enabled' => env('PRICING_DISCOUNTS_ENABLED', true),
        'coupon_code_length' => env('PRICING_COUPON_CODE_LENGTH', 8),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pricing Models/Strategies
    |--------------------------------------------------------------------------
    |
    | Define different pricing models or strategies if your system supports
    | various ways of calculating prices (e.g., subscription, per-unit, tiered).
    | This could be an array of class names or identifiers.
    |
    */
    'models' => [
        // 'default' => \App\Pricing\StandardPricingModel::class,
        // 'subscription' => \App\Pricing\SubscriptionPricingModel::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Price Caching
    |--------------------------------------------------------------------------
    |
    | Options for caching calculated prices to improve performance.
    |
    */
    'caching' => [
        'enabled' => env('PRICING_CACHING_ENABLED', false),
        'ttl' => env('PRICING_CACHING_TTL', 60 * 24), // Time to live in minutes (e.g., 24 hours)
    ],
];
