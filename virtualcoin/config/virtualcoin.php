<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Virtual Coin System Settings
    |--------------------------------------------------------------------------
    */

    // The default currency code for virtual coins.
    // This can be purely symbolic (e.g., "VC", "POINTS", "CREDITS").
    'default_currency_code' => 'VC',

    // Precision for storing and displaying virtual coin balances.
    // Typically, virtual coins might not need many decimal places, but adjust as needed.
    'balance_precision' => 2, // Number of decimal places for balance storage/display

    // Transaction types are now managed by IJIDeals\VirtualCoin\Enums\TransactionType Enum.
    // The Enum provides labels and validation. This section can be removed or kept for documentation.
    // 'transaction_types' => [
    //     'deposit_purchase' => 'Deposit (Purchase)',
    //     // ... (other types as previously defined) ...
    //     'other' => 'Other Transaction',
    // ],

    // Default status for new transactions if not specified.
    // Options: 'pending', 'completed', 'failed', 'cancelled'
    // Consider using an Enum for statuses as well if they become more complex.
    'default_transaction_status' => 'completed',

    // Settings for the HasVirtualWallet trait and other parts of the package
    // It's recommended to use the User model defined by the UserManagement package,
    // or fallback to the application's default User model if UserManagement is not used.
    'user_model' => config('user-management.user_model', config('auth.providers.users.model', \App\Models\User::class)),

    /*
    |--------------------------------------------------------------------------
    | Idempotency settings for transactions
    |--------------------------------------------------------------------------
    | If you plan to use the 'reference' field for idempotency, you might add
    | settings related to how long a reference is considered "active" for checks.
    */
    'idempotency_enabled' => true, // Set to false to disable idempotency checks
    'idempotency_window_minutes' => 1440, // 24 hours (1 day)

    // Scale for BCMath operations. This should be at least the number of decimal places
    // you intend to store and operate with precisely.
    'bcmath_scale' => 4,

    /*
    |--------------------------------------------------------------------------
    | Transaction Statuses
    |--------------------------------------------------------------------------
    | Consider using an Enum for these as well for consistency if they grow.
    */
    'statuses' => [
        'pending' => 'pending',
        'completed' => 'completed',
        'failed' => 'failed',
        'cancelled' => 'cancelled',
        'refunded' => 'refunded', // If applicable
    ],

];
