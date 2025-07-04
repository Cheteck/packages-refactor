# IJIDeals - Platform Settings Package (`ijideals/ijisettings`)

## Overview

The IJISettings package provides a centralized system for managing global platform settings within the IJIDeals application. These settings are typically those that an administrator might want to change dynamically via an admin interface without requiring code deployments. Examples include site name, contact email, API keys for third-party services, feature flags, quotas, etc.

Settings are stored in a database table and accessed via a `PlatformSettingService`, which includes caching for performance and optional encryption for sensitive values.

## Features

-   Key-value store for platform settings in the database.
-   `PlatformSettingService` for easy get/set operations.
-   Caching layer to reduce database load.
-   Optional encryption for sensitive setting values.
-   Ability to group settings for better organization in UIs.
-   Seedable default settings from a configuration file.
-   Artisan commands for basic setting management (planned).

## Installation

1.  **Require the package via Composer:**
    ```bash
    composer require ijideals/ijisettings
    ```

2.  **Publish Assets:**
    Publish the configuration file and migration:
    ```bash
    php artisan vendor:publish --provider="IJIDeals\IJISettings\Providers\IJISettingsServiceProvider" --tag="ijisettings-config"
    php artisan vendor:publish --provider="IJIDeals\IJISettings\Providers\IJISettingsServiceProvider" --tag="ijisettings-migrations"
    ```

3.  **Run Migrations:**
    ```bash
    php artisan migrate
    ```
    This will create the `platform_settings` table (or the name configured in `config/ijisettings.php`).

## Configuration

The main configuration file is located at `config/ijisettings.php` after publishing.

Key options:

-   **`table_name`**: Database table name (default: `platform_settings`).
-   **`cache_duration`**: Cache duration in minutes (null for forever).
-   **`cache_prefix`**: Prefix for cache keys.
-   **`default_settings`**: An array of settings to be seeded into the database, including their key, value, type, group, label, description, and encryption status.
-   **`force_encrypt_keys`**: An alternative list of keys that should always be treated as encrypted if not using the `is_encrypted` field in the database/defaults.
-   **`bcmath_scale`**: (If precision is critical for some settings, though less common for platform settings than for currency) Scale for BCMath operations if numeric settings require high precision handling directly within this package.

## Usage

### Accessing Settings via Service

The `PlatformSettingService` is the primary way to interact with settings. It handles caching and decryption automatically.

```php
use IJIDeals\IJISettings\Services\PlatformSettingService;

// Resolve the service from the container
$settingsService = app(PlatformSettingService::class);
// or use the alias if registered:
// $settingsService = app('ijisettings.platform');

// Get a setting value
$siteName = $settingsService->get('site.name', 'Default Site Name');
$contactEmail = $settingsService->get('site.contact_email');

// Get a boolean setting
$maintenanceMode = $settingsService->get('maintenance_mode', false); // Returns boolean

// Get an encrypted setting (value is automatically decrypted)
// Assuming 'some_api.key' was set as encrypted:
// $apiKey = $settingsService->get('some_api.key');

// Check if a setting exists (checks cache then DB)
if ($settingsService->has('feature.new_feature_flag')) {
    // ...
}

// Get all settings (primarily for admin or debugging)
$allSettings = $settingsService->all();
// dump($allSettings);

// Get settings by group
$generalSettings = $settingsService->getByGroup('general');
// dump($generalSettings);
```

### Setting Values (Typically from an Admin Panel or Artisan Command)

The `set` method allows creating or updating settings.

```php
// Set a simple string setting
$settingsService->set('site.slogan', 'Your favorite deals, daily!', 'string', 'general', 'Site Slogan');

// Set a boolean setting
$settingsService->set('feature.new_feature_flag', true, 'boolean', 'features', 'Enable New Feature');

// Set an integer setting
$settingsService->set('shop.max_products', 100, 'integer', 'shop_limits', 'Max Products per Shop');

// Set an encrypted setting (e.g., an API key)
$settingsService->set(
    'third_party.mail_service.api_key',
    'your_actual_api_key_here',
    'string',        // The underlying type of the value being encrypted
    'integrations',
    'Mail Service API Key',
    'API Key for the transactional mail service.',
    true             // Set is_encrypted to true
);

// To update just the value:
// $settingsService->set('site.slogan', 'Your best deals, every day!');
// To update other attributes like label, group, or encryption status, pass them to set()
```

### Helper Function (Recommended)

For convenience, you can define a global helper function in your application (e.g., in `app/Helpers/settings.php` and autoload it):

```php
// app/Helpers/settings.php (or similar)
if (!function_exists('platform_setting')) {
    function platform_setting(string $key, $default = null) {
        return resolve(IJIDeals\IJISettings\Services\PlatformSettingService::class)->get($key, $default);
    }
}

// Then use it anywhere in your application:
// $siteName = platform_setting('site.name', 'My Awesome Site');
// if (platform_setting('maintenance_mode', false)) {
//     // ...
// }
```

## Default Settings and Seeding

The `config/ijisettings.php` file includes a `default_settings` array. These settings are **not automatically inserted** into the database. You should create an Artisan command or a seeder to populate the `platform_settings` table with these defaults during initial setup or when new settings are added.

**Example Seeder Logic (conceptual):**
```php
// In a DatabaseSeeder or a dedicated PlatformSettingsSeeder
use IJIDeals\IJISettings\Services\PlatformSettingService;

// ...
public function run()
{
    $defaultSettings = config('ijisettings.default_settings', []);
    $settingService = app(PlatformSettingService::class);

    foreach ($defaultSettings as $key => $details) {
        if (!$settingService->has($key)) {
            $settingService->set(
                $key,
                $details['value'],
                $details['type'] ?? 'string',
                $details['group'] ?? null,
                $details['label'] ?? null,
                $details['description'] ?? null,
                $details['is_encrypted'] ?? false
            );
        }
    }
}
```

## Encryption

-   If a setting's `is_encrypted` flag is set to `true` when using `$settingsService->set()` (or in the `default_settings` and seeded), its value will be automatically encrypted using Laravel's `Crypt` facade before being saved to the database. This is handled by the `PlatformSetting` model's mutator.
-   When retrieved using `$settingsService->get()`, if the setting is marked as encrypted in the database, its value will be automatically decrypted by the `PlatformSetting` model's accessor.
-   The `type` for encrypted settings should still reflect the underlying data type (e.g., 'string' for an encrypted API key). The model handles the encryption/decryption transparently around this original type.

## Caching

-   The `PlatformSettingService` automatically caches retrieved settings to improve performance.
-   The cache duration and prefix are configurable in `config/ijisettings.php`.
-   Setting or forgetting a key automatically clears its specific cache entry and potentially a cache for "all settings".

## Artisan Commands (Planned)

-   `php artisan ijisettings:get <key>`
-   `php artisan ijisettings:set <key> <value> [--type=string] [--group=general] [--label="My Label"] [--description="Desc"] [--encrypt]`
-   `php artisan ijisettings:list [--group=...]`
-   `php artisan ijisettings:seed-defaults`

---
This package provides a robust foundation for managing dynamic platform-wide configurations.
