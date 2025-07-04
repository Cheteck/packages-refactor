# IJIDeals - User Settings Package (`ijideals/ijiusersettings`)

## Overview

The IJIUserSettings package provides a flexible and extensible system for managing user-specific settings and preferences within the IJIDeals application. It allows users to customize their experience and enables other packages to define and manage their own user-specific settings in a consistent way.

Settings are stored in a database table (key-value per user) and accessed primarily via a `HasUserSettings` trait on the User model, which in turn uses a `UserSettingsService` for more complex logic, default value resolution, validation, and caching.

## Features

-   **Flexible Key-Value Store:** User settings are stored with a key, value, type, and group.
-   **`HasUserSettings` Trait:** Easily attach user setting management to your User model.
-   **`UserSettingsService`:** Central service for resolving settings (user-specific or default), validation, and managing declared setting types.
-   **`SettingRegistry`:** Discovers and registers setting declarations from various packages.
-   **Declarative Settings:** Other packages can "declare" the user settings they use, including their type, default value, validation rules, label, description, and options for select/radio types. This allows for dynamic UI generation and centralized management.
-   **Type Casting:** Values are cast to their appropriate PHP types (`boolean`, `integer`, `string`, `array`, `json`, `encrypted_string`) based on the declared type.
-   **Caching:** User settings are cached for improved performance.
-   **Grouping:** Settings can be organized into groups for better UI presentation and management.

## Installation

1.  **Require the package via Composer:**
    ```bash
    composer require ijideals/ijiusersettings
    ```
    *(Ensure `ijideals/user-management` or your application's User model provider is also set up and configured in `config/ijiusersettings.php` if needed).*

2.  **Publish Assets:**
    Publish the configuration file and migration:
    ```bash
    php artisan vendor:publish --provider="IJIDeals\IJIUserSettings\Providers\IJIUserSettingsServiceProvider" --tag="ijiusersettings-config"
    php artisan vendor:publish --provider="IJIDeals\IJIUserSettings\Providers\IJIUserSettingsServiceProvider" --tag="ijiusersettings-migrations"
    ```
    Other packages can publish their setting declarations to `config/user_settings_declarations/` by using the `ijiusersettings-declarations` tag (though this tag doesn't publish files from this package itself, it establishes a convention for consumers).

3.  **Run Migrations:**
    ```bash
    php artisan migrate
    ```
    This will create the `user_settings` table (or the name configured in `config/ijiusersettings.php`).

4.  **Use the Trait:**
    Add the `HasUserSettings` trait to your User model (e.g., `app/Models/User.php` or the model configured in `ijideals/user-management`):
    ```php
    namespace App\Models; // Or your User model's namespace

    use Illuminate\Foundation\Auth\User as Authenticatable;
    use IJIDeals\IJIUserSettings\Traits\HasUserSettings; // Import the trait

    class User extends Authenticatable
    {
        use HasUserSettings; // Use the trait
        // ... other User model code
    }
    ```

## Configuration

The main configuration file is `config/ijiusersettings.php`. Key options:

-   **`user_setting_model`**: The Eloquent model for user settings (default: `IJIDeals\IJIUserSettings\Models\UserSetting::class`).
-   **`table_name`**: Database table name (default: `user_settings`).
-   **`cache_prefix`**: Prefix for cache keys (user ID will be appended).
-   **`cache_duration_user_settings`**: Cache duration in minutes.
-   **`setting_declarations`**: Configuration for how setting declarations are discovered by the `SettingRegistry`.
    *   **`discovery_method`**:
        *   `'directory'`: Scans a path (defined in `declarations_path`) for PHP files that return arrays of setting definitions. This is the recommended way for packages to declare their settings.
        *   `'config'`: Loads declarations directly from the `declarations_config_array` within this config file.
        *   `'programmatic'`: Allows other service providers to register settings directly with the `SettingRegistry` service.
    *   **`declarations_path`**: Path relative to `config_path()` (e.g., `user_settings_declarations`) for the `'directory'` discovery method.
    *   **`declarations_config_array`**: An array for direct declarations if using the `'config'` discovery method.

## Declaring Settings (by Consuming Packages)

Packages that introduce user-configurable settings should declare them. If using the `'directory'` discovery method (recommended), a package should publish a PHP configuration file into the `config/[declarations_path]/` directory of the main application.

**Example Declaration File (e.g., `config/user_settings_declarations/my_package_settings.php`):**
```php
<?php

return [
    'my_package.notifications.feature_abc.enabled' => [
        'label' => 'Enable Notifications for Feature ABC',
        'description' => 'Receive notifications related to Feature ABC.',
        'type' => 'boolean',        // Supported types: string, integer, boolean, float, array, json, select, encrypted_string
        'group' => 'notifications.my_package', // Used for grouping in UI
        'default' => true,
        'rules' => ['boolean'],     // Laravel validation rules for the setting's value
    ],
    'my_package.ui.items_per_page' => [
        'label' => 'Items Per Page for My Feature',
        'type' => 'integer',
        'group' => 'preferences.my_package_ui',
        'default' => 15,
        'rules' => ['integer', 'min:5', 'max:100'],
    ],
    'my_package.preferences.default_view' => [
        'label' => 'Default View',
        'type' => 'select',
        'group' => 'preferences.my_package_ui',
        'default' => 'grid',
        'options' => [ // Options for 'select' or 'radio' types
            ['value' => 'grid', 'label' => 'Grid View'],
            ['value' => 'list', 'label' => 'List View'],
        ],
        'rules' => ['string', \Illuminate\Validation\Rule::in(['grid', 'list'])],
    ],
    // Example of a setting whose options are dynamically populated
    // 'my_package.preferences.favorite_category' => [
    // 'label' => 'Favorite Category',
    // 'type' => 'select',
    // 'group' => 'preferences.my_package_data',
    // 'default' => null,
    // 'options_callback' => [\App\Services\CategoryService::class, 'getCategoryOptionsForSelect'],
    // 'rules' => ['nullable', 'integer', 'exists:categories,id'],
    // ],
];
```
The `SettingRegistry` service loads these declarations. The `UserSettingsService` then uses this registry to provide default values and validate settings.

## Usage

### Accessing Settings (on User Model)

The `HasUserSettings` trait provides convenient methods:

```php
$user = Auth::user();

// Get a setting. Returns user's value, then declared default, then the provided fallback.
$enableFeatureEmails = $user->getSetting('my_package.feature.enable_email', true);

// Get setting with no fallback (will use declared default or null if none)
$itemsPerPage = $user->getSetting('my_package.profile.items_per_page');

// Get all settings for a group (resolved values including defaults)
$notificationPreferences = $user->getSettingsByGroup('notifications.my_package');
// $notificationPreferences will be a Collection keyed by setting key,
// with each item being an array: ['label' => ..., 'value' => ..., 'type' => ...]
```

### Setting Values (on User Model)

```php
$user = Auth::user();

// Set a single setting. Value will be validated against declared rules.
try {
    $user->setSetting('my_package.feature.enable_email', false);
    $user->setSetting('my_package.profile.items_per_page', 20);
} catch (\Illuminate\Validation\ValidationException $e) {
    // Handle validation errors
}


// Set multiple settings
// Can be an associative array or an array of arrays
$newSettings = [
    'my_package.feature.enable_email' => true,
    'my_package.profile.items_per_page' => 10,
];
// or
// $newSettings = [
//    ['key' => 'my_package.feature.enable_email', 'value' => true],
//    ['key' => 'my_package.profile.items_per_page', 'value' => 10],
// ];
try {
    $user->setSettings($newSettings);
} catch (\Illuminate\Validation\ValidationException $e) {
    // Handle validation errors (key in error bag will be the setting key)
}

// Forget a user-specific setting (reverts to declared default)
$user->forgetSetting('my_package.profile.items_per_page');
```

### Using the `UserSettingsService` Directly

For scenarios outside the User model or for accessing declaration details:

```php
use IJIDeals\IJIUserSettings\Services\UserSettingsService;
use App\Models\User; // Your User model

$service = app(UserSettingsService::class); // or app('ijiusersettings.user')
$user = User::find(1);

// Get a resolved setting for a user
$settingValue = $service->getResolvedSetting($user, 'my_package.feature.enable_email');

// Get all declared settings (useful for building a settings UI)
$allDeclarations = $service->getAllDeclaredSettings();
/*
$allDeclarations might look like:
[
    'my_package.feature.enable_email' => [
        'label' => 'Enable Email Notifications for Feature X',
        'description' => 'Receive emails when certain actions related to Feature X occur.',
        'type' => 'boolean',
        'group' => 'notifications.my_package',
        'default' => true,
        'rules' => ['boolean'],
        'options' => null,
    ],
    // ... more settings
]
*/

// Get declared settings for a specific group, augmented with the user's current values
$uiSettingsForGroup = $service->getResolvedSettingsForUserByGroup($user, 'notifications.my_package');
/*
$uiSettingsForGroup might look like:
collect([
    'my_package.feature.enable_email' => [
        'label' => 'Enable Email Notifications for Feature X',
        'description' => 'Receive emails when certain actions related to Feature X occur.',
        'type' => 'boolean',
        'value' => false, // User's current value
        'options' => null,
    ]
])
*/
```

## Building a User Settings UI

1.  Use `UserSettingsService::getResolvedSettingsForUserByGroup($user, $group)` or loop through `UserSettingsService::getAllDeclaredSettings()` and then fetch user values with `$user->getSetting()`.
2.  For each setting declaration, use the `label`, `description`, `type`, and `options` (if `type` is `select` or `radio`) to render the appropriate HTML form input.
3.  The `value` obtained from `getResolvedSetting` or `getResolvedSettingsForUserByGroup` will be the current value for the user (either their saved preference or the default).
4.  When the form is submitted, pass the data to a controller method that calls `$user->setSettings($request->all())` or similar. The service will handle validation based on the declared rules.

## Encryption

If a user setting needs to be stored encrypted (e.g., a user-specific API token they provide), you can declare its `type` as `'encrypted_string'`. The `UserSetting` model's accessor and mutator will handle encryption and decryption automatically using Laravel's `Crypt` facade.

## Caching

User settings are cached by the `UserSettingsService` to reduce database queries. The cache is invalidated when settings are updated. Cache prefix and duration are configurable.

---
This package provides a comprehensive and extensible solution for managing user-specific settings and preferences.
