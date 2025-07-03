<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Table Names
    |--------------------------------------------------------------------------
    |
    | To prevent conflicts with other packages or your own tables, you can
    | prefix the table names used by this package.
    |
    */
    'tables' => [
        'orders' => 'orders',
        'order_items' => 'order_items',
    ],

    /*
    |--------------------------------------------------------------------------
    | API Route Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the prefix, middleware, and other settings for the API routes.
    |
    */
    'routes' => [
        'prefix' => 'api/v1',
        'middleware' => ['api'],
        'shop_middleware' => ['auth:sanctum', 'role:shop-admin'],
        'customer_middleware' => ['auth:sanctum'],
    ],
];
