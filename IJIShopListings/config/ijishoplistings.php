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
        'shop_products' => 'shop_products',
        'shop_product_variations' => 'shop_product_variations',
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
    ],

    /*
    |--------------------------------------------------------------------------
    | Media Library Collections
    |--------------------------------------------------------------------------
    |
    | Define the names of the media collections used for models.
    |
    */
    'media_collections' => [
        'shop_product_additional_images' => 'shop_product_additional_images',
    ],
];
