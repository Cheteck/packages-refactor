<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Sponsorship Settings
    |--------------------------------------------------------------------------
    */

    // Default duration for a sponsored post campaign in days, if not specified.
    'default_duration_days' => 7,

    // Default status for newly created sponsored post campaigns.
    // Options: 'pending', 'active' (if start date is immediate and budget is funded)
    'default_status' => 'pending',

    // Minimum budget required to start a campaign
    'min_budget' => 1.00, // Example: 1 unit of virtual coin or currency

    // Maximum budget allowed for a single campaign
    'max_budget' => 10000.00, // Example

    // Default cost_per_impression if not specified by user
    'default_cpi' => 0.005, // Example: 0.005 virtual coins per impression

    // Default cost_per_click if not specified by user
    'default_cpc' => 0.05, // Example: 0.05 virtual coins per click

    // Available targeting options that can be presented to the user in the UI
    // This is more for UI generation and validation rather than direct use in backend logic here.
    'targeting_options' => [
        'age_ranges' => [
            '18-24',
            '25-34',
            '35-44',
            '45-54',
            '55-64',
            '65+',
        ],
        'interests' => [
            // These would likely come from a central taxonomy or user profile data
            'technology', 'sports', 'music', 'travel', 'fashion', 'food', 'gaming', 'art',
        ],
        // 'locations' => ['US', 'CA', 'GB', 'DE', 'FR'], // Example if geo-targeting is implemented
    ],

    /*
    |--------------------------------------------------------------------------
    | Virtual Coin Integration
    |--------------------------------------------------------------------------
    */
    // Transaction types used when interacting with the virtual coin system
    'virtual_coin_transaction_types' => [
        'funding' => 'sponsorship_funding',
        'impression_spend' => 'spend_sponsorship_impression',
        'click_spend' => 'spend_sponsorship_click',
        'refund' => 'sponsorship_refund',
    ],

    /*
    |--------------------------------------------------------------------------
    | Long-term: Tier-based Sponsorship (Placeholder for future Option A)
    |--------------------------------------------------------------------------
    | These settings would be relevant if the package evolves to include
    | creator/tier-based sponsorships.
    */
    'enable_tier_sponsorship' => false, // Controls if tier-based features are active

    'tier_defaults' => [
        // 'min_levels' => 1,
        // 'max_levels' => 5,
        // 'default_benefits' => ['Exclusive Content', 'Early Access'],
    ],

];
