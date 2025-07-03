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
        'brands' => 'brands',
        'categories' => 'categories',
        'product_attributes' => 'product_attributes',
        'product_attribute_values' => 'product_attribute_values',
        'master_products' => 'master_products',
        'master_product_variations' => 'master_product_variations',
        'product_proposals' => 'product_proposals',
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
        'admin_middleware' => ['auth:sanctum', 'role:admin'],
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
        'brand_logo' => 'brand_logo',
        'category_icon' => 'category_icon',
        'master_product_images' => 'master_product_images',
        'master_product_variation_images' => 'master_product_variation_images',
    ],
];
