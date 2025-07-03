<?php

return [
    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | This is the User model used by the UserManagement package and other
    | IJIDeals packages. You can override this if you have a custom User
    | model in your application that extends the base User model provided
    | by this package, or if you want to use your app's default User model.
    |
    */
    'model' => \IJIDeals\UserManagement\Models\User::class,

    /*
    |--------------------------------------------------------------------------
    | Other User Management Settings
    |--------------------------------------------------------------------------
    |
    | Add other package-specific settings below. For example:
    | - Options for password policies
    | - Default roles upon registration (if handled by this package)
    | - Profile picture settings, etc.
    |
    */
    // 'password_minimum_length' => 8,
    // 'profile_picture_disk' => 'public',
];
