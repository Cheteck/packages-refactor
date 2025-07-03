<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Spatie Permission Configuration Notes for Teams
    |--------------------------------------------------------------------------
    |
    | This package relies on Spatie's Laravel Permission package and its "teams"
    | feature to scope roles and permissions to individual shops.
    |
    | To enable team functionality with this package, you MUST configure
    | Spatie's permission package in your application's `config/permission.php`:
    |
    | 1. Enable Teams:
    |    'teams' => true,
    |
    | 2. Set the Team Foreign Key:
    |    This key will be used in the `model_has_roles`, `model_has_permissions`,
    |    and `role_has_permissions` pivot tables to link permissions/roles
    |    to a specific shop. We recommend using `shop_id`.
    |
    |    'team_foreign_key' => 'shop_id',
    |
    | After publishing Spatie's configuration (`php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"`),
    | make sure to update these settings in your `config/permission.php`.
    |
    | The `Shop` model provided by this package will then be used as the "team"
    | instance when assigning roles or permissions (e.g., $user->assignRole('Owner', $shop)).
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Default Roles
    |--------------------------------------------------------------------------
    |
    | Define the default roles that are typically associated with a shop.
    | You can manage these roles in your application or seed them.
    | This array is for informational purposes or could be used by a package-provided seeder.
    |
    */
    'default_roles' => [
        'Owner'         => 'Full control over the shop, including billing and team management.',
        'Administrator' => 'Can manage most aspects of the shop, including products, orders, and team members (except Owners).',
        'Editor'        => 'Can manage products and shop content.',
        'Support'       => 'Can manage customer interactions and orders.',
        'Viewer'        => 'Read-only access to shop data.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Permissions (Examples - to be defined more granularly later)
    |--------------------------------------------------------------------------
    |
    | Define permissions that can be assigned to roles.
    | Examples:
    |   - 'view shop dashboard'
    |   - 'manage shop settings'
    |   - 'manage products'
    |   - 'manage orders'
    |   - 'manage shop team'
    |   - 'delete shop'
    */
    'default_permissions' => [
        // General Shop Management
        'view shop data',
        'manage shop settings',
        'manage shop team members',
        'delete shop', // Typically only for Owner

        // E-commerce specific (for future phases)
        // 'manage products',
        // 'manage orders',
        // 'manage discounts',
        // 'view shop reports',
    ],

    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | Specify the User model class to be used by the package for relationships
    | if it needs to interact with Users directly (beyond Spatie's handling).
    | Defaults to Laravel's base User model.
    |
    */
    'user_model' => \App\Models\User::class,

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
        'shops' => 'shops',
    ],

    /*
    |--------------------------------------------------------------------------
    | API Route Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the prefix and middleware for the API routes registered by this package.
    |
    */
    'route_prefix' => 'api/ijicommerce', // Example: 'api/v1/commerce'
    'route_middleware' => ['api'], // Ensures API middleware group (e.g., Sanctum) is applied
];
