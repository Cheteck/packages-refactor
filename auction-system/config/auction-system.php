<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Auction System Settings
    |--------------------------------------------------------------------------
    */

    // Default duration for auctions in days, if not specified.
    'default_auction_duration_days' => 7,

    // Anti-sniping feature
    'anti_sniping' => [
        'enabled' => true,
        'extension_time_minutes' => 5, // How many minutes to extend the auction by if a bid is placed near the end.
        'bid_within_minutes' => 5,    // Extend if a bid is placed within these many minutes of the end time.
    ],

    // Default bid increment rules.
    // Could be 'fixed', 'percentage', or more complex strategies.
    'bid_increment' => [
        'type' => 'fixed', // or 'percentage'
        'amount' => 1.00,  // If type is 'fixed'
        // 'percentage_of_current_price' => 0.05, // 5% if type is 'percentage'
    ],

    // Supported auction types and their specific rule configurations.
    // For now, assuming 'english' auction type as default.
    'auction_types' => [
        'english' => [
            'name' => 'English Auction',
            'description' => 'Standard ascending price auction.',
        ],
        // 'sealed_bid' => [ ... ],
        // 'dutch' => [ ... ],
    ],

    // Default status for new auctions
    'default_auction_status' => 'pending',

    // Notification settings (placeholders, actual implementation would use a notification system)
    'notifications' => [
        'outbid' => [
            'enabled' => true,
            // 'channels' => ['mail', 'database'],
        ],
        'auction_ending_soon' => [
            'enabled' => true,
            'hours_before_end' => 24,
            // 'channels' => ['mail', 'database'],
        ],
        'auction_won' => [
            'enabled' => true,
            // 'channels' => ['mail', 'database'],
        ],
        'auction_lost' => [
            'enabled' => true,
            // 'channels' => ['mail', 'database'],
        ],
    ],

    // Laravel Echo channel naming conventions (if using real-time updates)
    'echo_channels' => [
        'auction_updates_prefix' => 'private-auction.', // e.g., private-auction.{auction_id}
    ],

    // Scheduled job settings
    'winner_job_frequency' => 'everyMinute', // Options: everyMinute, everyFiveMinutes, etc. (see Laravel Task Scheduling)

    // User model (if different from default Laravel User)
    'user_model' => config('user-management.model', \App\Models\User::class),

    // Product model (if different from a generic one, or to specify the exact model)
    // This needs to point to the actual Product model from the commerce/ijicommerce package.
    'product_model' => \IJIDeals\IJICommerce\Models\Product::class, // Example, adjust as needed

];
