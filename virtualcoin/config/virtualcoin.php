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

    // Transaction types
    // You can define a list of recognized transaction types here if you want to validate against them
    // or use them for display purposes.
    'transaction_types' => [
        'deposit_purchase' => 'Deposit (Purchase)',
        'deposit_bonus' => 'Deposit (Bonus)',
        'spend_item' => 'Spend (Item Purchase)',
        'spend_service' => 'Spend (Service Fee)',
        'spend_sponsorship_funding' => 'Spend (Sponsorship Funding)',
        'spend_sponsorship_impression' => 'Spend (Sponsorship Impression Cost)',
        'spend_sponsorship_click' => 'Spend (Sponsorship Click Cost)',
        'earn_reward_activity' => 'Earn (Activity Reward)',
        'earn_referral_bonus' => 'Earn (Referral Bonus)',
        'refund_item' => 'Refund (Item Return)',
        'refund_sponsorship' => 'Refund (Sponsorship Cancellation)',
        'withdrawal_cash_out' => 'Withdrawal (Cash Out)', // If converting virtual to real currency
        'adjustment_credit' => 'Adjustment (Credit by Admin)',
        'adjustment_debit' => 'Adjustment (Debit by Admin)',
        'other' => 'Other Transaction',
    ],

    // Default status for new transactions if not specified.
    // Options: 'pending', 'completed', 'failed', 'cancelled'
    'default_transaction_status' => 'completed',

    // Settings for the HasVirtualWallet trait
    'user_model' => \App\Models\User::class, // TODO: Change to IJIDeals\UserManagement\Models\User after confirmation

    /*
    |--------------------------------------------------------------------------
    | Idempotency settings for transactions
    |--------------------------------------------------------------------------
    | If you plan to use the 'reference' field for idempotency, you might add
    | settings related to how long a reference is considered "active" for checks.
    */
    // 'idempotency_window_minutes' => 1440, // 24 hours example

];
