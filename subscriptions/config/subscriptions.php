<?php

return [
    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | The model class to use for users, typically from ijideals/user-management.
    | This will be used by Soulbscription configuration as well.
    |
    */
    'user_model' => config('auth.providers.users.model', \App\Models\User::class),

    /*
    |--------------------------------------------------------------------------
    | Shop Model (Example of another Subscribable)
    |--------------------------------------------------------------------------
    |
    | If shops can have subscriptions (e.g., for different feature tiers).
    |
    */
    'shop_model' => \IJIDeals\IJICommerce\Models\Shop::class, // Assuming this model exists

    /*
    |--------------------------------------------------------------------------
    | Default Payment Gateway for Subscriptions
    |--------------------------------------------------------------------------
    |
    | Specify the default payment gateway to be used for subscriptions if needed,
    | or let ijideals/payments handle its default.
    | This might be useful if subscriptions always use a specific gateway.
    |
    */
    'default_payment_gateway' => env('SUBSCRIPTIONS_PAYMENT_GATEWAY', config('payments.default_gateway', 'stripe')),

    /*
    |--------------------------------------------------------------------------
    | API Route Configuration
    |--------------------------------------------------------------------------
    */
    'api_routes' => [
        'prefix' => 'api/subscriptions',
        'middleware' => ['auth:sanctum'], // Adjust as needed
    ],

    /*
    |--------------------------------------------------------------------------
    | Plan Definitions (Example Structure - Actual plans managed via Soulbscription)
    |--------------------------------------------------------------------------
    |
    | This section is for documentation or for seeding default plans if needed.
    | Soulbscription uses its own Plan model and database table.
    |
    | 'plans' => [
    |     'user_premium' => [
    |         'name' => 'User Premium Membership',
    |         'periodicity_type' => 'month', // 'day', 'week', 'month', 'year'
    |         'periodicity' => 1,
    |         'grace_days' => 0, // Days after due date before subscription is marked inactive
    |         'features' => [ // Features are also managed by Soulbscription
    |             'ads_free_browsing' => ['value' => true],
    |             'advanced_search_filters' => ['value' => true],
    |             'priority_support' => ['value' => 10] // Value can be boolean, int, or null for simple existence
    |         ],
    |         'price' => 9.99, // This would likely map to a price in your payment system or ijideals/pricing
    |         'currency' => 'USD',
    |         'metadata' => ['description' => 'Unlock premium features for users.'],
    |     ],
    |     'shop_basic' => [
    |         'name' => 'Shop Basic Plan',
    |         'periodicity_type' => 'month',
    |         'periodicity' => 1,
    |         'features' => [
    |             'max_products' => ['value' => 50],
    |             'commission_rate' => ['value' => '10%']
    |         ],
    |         'price' => 19.99,
    |         'currency' => 'USD',
    |     ],
    | ],
    */

    /*
    |--------------------------------------------------------------------------
    | Integration with IJIDeals/Payments
    |--------------------------------------------------------------------------
    |
    | Configuration related to how this package interacts with ijideals/payments.
    |
    */
    'payments_integration' => [
        // If subscriptions create a specific type of payable description or metadata
        'payable_description_prefix' => 'Subscription for plan: ',
    ],
];
