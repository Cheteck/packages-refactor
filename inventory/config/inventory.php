<?php

return [
    // This is the configuration file for the Inventory package.
    // You can add your inventory-specific configurations here.

    /*
    |--------------------------------------------------------------------------
    | Default Inventory Location
    |--------------------------------------------------------------------------
    |
    | Define the name or ID of the default inventory location to be used
    | when a specific location is not provided for an inventory operation.
    |
    */
    'default_location_name' => 'Default Warehouse',

    /*
    |--------------------------------------------------------------------------
    | Low Stock Threshold
    |--------------------------------------------------------------------------
    |
    | Define the default threshold at which a LowStockAlert event is triggered.
    | This can potentially be overridden per product or category later.
    |
    */
    'low_stock_threshold' => 10,

    /*
    |--------------------------------------------------------------------------
    | Allow Negative Stock
    |--------------------------------------------------------------------------
    |
    | Determine if the system should allow stock quantities to go negative.
    | Setting this to false will prevent operations that would result in negative stock.
    |
    */
    'allow_negative_stock' => false,

    /*
    |--------------------------------------------------------------------------
    | Stock Movement Types
    |--------------------------------------------------------------------------
    |
    | Define recognized types for stock movements. This can be used for filtering
    | or categorizing stock history.
    |
    */
    'movement_types' => [
        'initial_stock' => 'Initial Stock',
        'sale' => 'Sale',
        'return' => 'Return',
        'adjustment_in' => 'Manual Adjustment (In)',
        'adjustment_out' => 'Manual Adjustment (Out)',
        'transfer_in' => 'Transfer In',
        'transfer_out' => 'Transfer Out',
        'damage' => 'Damaged Goods',
        'shrinkage' => 'Shrinkage',
        // Add other types as needed
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */
    'pagination' => [
        'inventory_list' => 25,
        'stock_movements' => 50,
    ],
];
