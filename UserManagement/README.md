# User Management Package for Laravel

A simple Laravel package to manage users, designed to be easily integrated into existing Laravel applications.

## Features

- User model with extensive social network-related fields:
    - `username`, `profile_photo_path`, `cover_photo_path`, `bio`
    - `birthdate`, `gender`, `phone`, `preferred_language`, `location`, `website`
    - `followers_count`, `following_count` (managed via appropriate logic, not direct fillable for counts)
- Migrations for the `users` table including all above fields.
- `UserController` handling web (Blade views) and API (JSON) requests for:
    - Displaying user profiles
    - Editing user profiles
    - Updating user profiles
- Blade views for:
    - `show.blade.php`: A Facebook-inspired profile page layout, designed using Tailwind CSS classes and expecting a Blade `<x-app-layout>` component (typical in Laravel Jetstream/Breeze). For best results, the consuming application should have Tailwind CSS configured.
    - `edit.blade.php`: A form to edit all user profile fields (standard HTML, adaptable).
- Web routes namespaced under `user-management` and `user-management.` respectively.
- API routes namespaced under a configurable prefix (default `api`) and then `v1/user-management` (e.g., `api/v1/user-management/users/{id}`).
- Service provider with route loading, migration loading, view loading, and publishable assets (config, views, migrations).
- Configuration file (`config/user-management.php`) for API prefix and middleware customization.
- Basic test setup using Orchestra Testbench, with feature tests for UserController actions (web & API).

## Installation

1.  **Require the package via Composer:**
    ```bash
    composer require ijideals/user-management
    ```

2.  **Publish the package assets (optional but recommended):**
    The service provider is auto-discovered. You can publish the configuration, views, and migrations if you need to customize them:

    *   Publish configuration:
        ```bash
        php artisan vendor:publish --provider="IJIDeals\UserManagement\UserManagementServiceProvider" --tag="config"
        ```
        This will create a `config/user-management.php` file.

    *   Publish views:
        ```bash
        php artisan vendor:publish --provider="IJIDeals\UserManagement\UserManagementServiceProvider" --tag="views"
        ```
        This will place the views in `resources/views/vendor/user-management`.

    *   Publish migrations:
        ```bash
        php artisan vendor:publish --provider="IJIDeals\UserManagement\UserManagementServiceProvider" --tag="migrations"
        ```
        This will copy the migration file to `database/migrations`. If you publish the migrations, you might need to disable loading migrations from the package in the `UserManagementServiceProvider` if you don't want them to run twice, or if you plan to heavily modify the published one.

3.  **Run Migrations:**
    If you haven't published and modified the migrations, they will be run automatically when you run `php artisan migrate`. If you published them, ensure they are correct and then run:
    ```bash
    php artisan migrate
    ```

## Usage

### Routes

The package registers the following routes, prefixed with `/user-management` and named with `user-management.`:

-   `GET /users/{id}`: Show user profile (`user-management.users.show`)
-   `GET /users/{id}/edit`: Show form to edit user profile (`user-management.users.edit`)
-   `PUT /users/{id}`: Update user profile (`user-management.users.update`)

You can access these routes in your application, for example:
`route('user-management.users.show', ['id' => 1])`

### Views

The package provides basic views for showing and editing users. If you published the views, you can customize them in `resources/views/vendor/user-management/`.

### Configuration

If you published the configuration file (`config/user-management.php`), you can modify package settings there. (Currently, no specific configuration options are implemented in this basic version).

## Extending the Package

### User Model
The package uses the `IJIDeals\UserManagement\Models\User` model. If you need to customize this model (e.g., add more relationships or methods), you can:
1.  Extend it in your application's `App\Models\User` (or a similar location).
2.  If you want to use your own User model throughout your application, you might need to configure Laravel to use your custom model, especially if it relates to authentication. For package-specific interactions, ensure your custom model extends or correctly implements necessary features if replacing the package's default.

### Controllers & Routes
You can override routes by defining them in your application's route files *before* the package's service provider registers its routes, or by customizing the published routes if you choose that path. Controllers can be extended or replaced similarly.

## Testing the Package (Standalone)

If you are developing this package:
1.  Ensure you have `orchestra/testbench` in your `require-dev` dependencies.
2.  Run tests using PHPUnit:
    ```bash
    ./vendor/bin/phpunit
    ```

## Contributing

Please feel free to fork the repository, make changes, and submit pull requests.

## License

This package is open-source software licensed under the [MIT license](LICENSE.md).
