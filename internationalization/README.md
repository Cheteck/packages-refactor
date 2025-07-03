# Internationalization Package

The Internationalization (i18n) package provides the essential tools for making the IJIDeals platform a truly global application. It handles multi-language support, content translation, and localization.

## Core Features

-   **Translatable Models**: A simple-to-use Trait (`IsTranslatable`) that makes any Eloquent model's attributes translatable.
-   **Language Management**: RESTful API for adding, removing, and managing supported languages.
-   **Auto-Translation (Optional)**: Integration with third-party translation services (like DeepL, Google Translate, Azure) to automatically translate content.
-   **Localization**: Helpers for formatting dates, numbers, and currencies according to the user's locale.
-   **Route Localization**: `Route::trans()` helper to easily create localized routes.
-   **Locale Middleware**: Middleware to set the locale from route/user/default.
-   **Caching & Fallback**: Translations are cached and fallback to default language if missing.

## Key Components

### Models

-   `Language`: Represents a supported language in the platform (e.g., English, French).
-   `Translation`: The model that stores the translated strings for any translatable model.

### Traits

-   `IsTranslatable`: Add this trait to any model to make its attributes translatable. The package will handle the relationships and data storage automatically.

### Services

-   `TranslationService`: Handles auto-translation using pluggable providers (Google, DeepL, Azure).

### Helpers

-   `LocalizationHelper`: Format dates, numbers, and currencies by locale.
-   `RouteLocalizationHelper`: Generate localized route URLs.

### Middleware

-   `SetLocale`: Sets the app locale from the route prefix, user preference, or default.

## How It Works

When the `IsTranslatable` trait is added to a model (e.g., `Product`), you define which attributes are translatable in a `$translatable` array.

```php
class Product extends Model
{
    use IsTranslatable;

    public $translatable = ['name', 'description'];
}
```

Now, you can get and set translations with ease:
```php
$product->setTranslation('name', 'fr', 'Produit Incroyable');
$product->name; // returns 'Produit Incroyable' when app locale is 'fr'
```

## Language Management API

- **Base Route:** `/api/internationalization/languages`
- **Methods:**
    - `GET /languages` — List all languages
    - `GET /languages/{id}` — Show a language
    - `POST /languages` — Create a language
    - `PUT/PATCH /languages/{id}` — Update a language
    - `DELETE /languages/{id}` — Delete a language

**Example:**
```http
POST /api/internationalization/languages
{
  "code": "es",
  "name": "Spanish",
  "direction": "ltr",
  "is_default": false,
  "status": true
}
```

> See Scribe-generated API docs for full details and examples.

## SetLocale Middleware

Register `\IJIDeals\Internationalization\Http\Middleware\SetLocale` in your HTTP kernel or route group to automatically set the locale from the route prefix, user preference, or fallback to default.

## Auto-Translation

Configure your provider in `config/internationalization.php`:
```php
'auto_translation' => [
    'enabled' => true,
    'provider' => 'google', // or 'deepl', 'azure'
    'api_key' => env('AUTO_TRANSLATION_API_KEY'),
],
```
Use `TranslationService::translate($text, $from, $to)` to translate content programmatically.

## Route Localization

Use `RouteLocalizationHelper::trans('route.name', [...], $locale)` to generate localized URLs. Register your routes with a `{locale}` prefix if needed.

## Localization Helpers

Use `LocalizationHelper` to format dates, numbers, and currencies:
```php
LocalizationHelper::formatDate($date);
LocalizationHelper::formatCurrency(1234.56, 'EUR');
```

## Caching & Fallback

Translations are cached for performance. If a translation is missing, the default language is used as a fallback.

## Testing

Factories are provided for `Language` and `Translation` models. Run tests with:
```bash
composer test
```

## Contributing

- Follow Laravel and package best practices.
- Add tests for new features.
- Document all new endpoints and helpers.

## TODOs
- Translation Management API (CRUD for translations)
- Batch translation command/job
- Admin middleware for language/translation management
- Scribe annotations for all endpoints
- Advanced caching and invalidation
- More usage examples in documentation

## Security

If you discover any security-related issues, please email security@ijideals.com instead of using the issue tracker. 
