# TODO for Internationalization Package (Improvements)

## 🚀 Core Functionality Enhancements

-   **Complete Auto-Translation Providers:** (From original TODO)
    -   [ ] **`DeepLTranslationProvider`**: Implement DeepL API call using `Http` facade and credentials from `config/internationalization.php`.
    -   [ ] **`AzureTranslationProvider`**: Implement Azure Translator API call using `Http` facade and credentials.
    -   [ ] **Error Handling & Resilience**: Improve error handling in all providers (e.g., specific exceptions, retry logic for transient network issues).
    -   [ ] **Usage Limits/Quotas**: Consider adding basic tracking or warnings if API usage limits are a concern (advanced).
-   **Enhance `TranslationService`:**
    -   [ ] **Batch Translations**: Add a method to translate an array of texts efficiently, potentially making fewer API calls if supported by the provider.
    -   [ ] **Language Detection**: Optionally, add a method to auto-detect the source language (`$from`) if not provided, using the chosen translation provider's capabilities.
    -   [ ] **Provider Fallback**: Implement a fallback mechanism if the primary auto-translation provider fails (e.g., try Google if DeepL fails, if configured).
-   **Refine `SetLocale` Middleware:**
    -   [ ] **User `preferred_language` Field**:
        -   Ensure the `User` model (from `ijideals/user-management`) actually has a `preferred_language` attribute/column.
        -   If not, a migration for `users` table in `user-management` package will be needed to add this field.
        -   Ensure it's easily settable by users via their profile settings.
    -   [ ] **Carbon Locale**: Automatically set Carbon's locale using `Carbon\Carbon::setLocale(App::getLocale())` (or a mapping if Carbon locale names differ) within the middleware for consistent date formatting throughout the application.
    -   [ ] **URL Generation**: Ensure that when locale is detected from URL, subsequent generated URLs (e.g., `route()`, `url()`) by default include the locale prefix if `route_localization.prefix` is true. This might involve a custom URL generator or ensuring Laravel's default behavior respects this.
-   **Language Management API (`LanguageController`):**
    -   [ ] **CRUD for Languages**: Review `LanguageController` to ensure full CRUD for managing supported languages if this is meant to be dynamic rather than just config-driven.
        -   Consider if `is_active` or `is_supported` flags on the `Language` model should be manageable via API.
    -   [ ] **Default Language Setting**: API endpoint to set the system's default language (updates `config/app.php` or a setting in `ijideals/settings`).
    -   [ ] **Policies**: Implement `LanguagePolicy` for authorization.
-   **Translatable Model Helper/Trait (Optional, for `astrotomic/laravel-translatable`):**
    -   [ ] While `astrotomic/laravel-translatable` is powerful, consider creating a simple helper trait within `ijideals/support` or this package that provides common boilerplate for translatable models (e.g., ensuring `$translationModel`, `$translationForeignKey` are set by convention, or common scopes). This is minor.

## 🔧 Configuration & Setup

-   **Refine `config/internationalization.php`:**
    -   [ ] **Supported Languages**: Clarify if `supported_languages` in config is the single source of truth, or if the `languages` database table (managed by `LanguageController`) takes precedence. If DB-driven, config might just define initial seed data.
    -   [ ] **Auto-Translation**: Add option to enable/disable auto-translation on a per-model or per-attribute basis if such granularity is needed.
    -   [ ] **Route Localization**: Add more options for URL prefixing if needed (e.g., hide prefix for default language).
-   **Service Provider (`InternationalizationServiceProvider`):**
    -   [ ] Ensure all necessary bindings and singletons are correctly registered (e.g., `TranslationService`).
    -   [ ] Register any commands (e.g., a command to sync/seed languages from config to DB).

## 🧹 Code Quality & Model Refinements

-   **Language Model (`Language.php`):** (From original TODO)
    -   [ ] Address internal TODOs (some might be obsolete now).
    -   [ ] Review the generic `translations()` HasMany relationship on `Language` model. This refers to the *custom* polymorphic system that was removed. This relationship should be removed from `Language.php` as `astrotomic/laravel-translatable` works differently (language model doesn't directly link to all translations of other models).
-   **Helper Classes (`LocalizationHelper`, `RouteLocalizationHelper`):**
    -   [ ] Review their current functionality.
    -   [ ] `LocalizationHelper`: Ensure methods for formatting dates, numbers, currency are robust and use locale settings from config or `Carbon`.
    -   [ ] `RouteLocalizationHelper`: Ensure it works correctly with the chosen `SetLocale` detection methods and Laravel's routing for generating localized URLs.

## 📚 Documentation & Testing

-   **README Update:** (From original TODO)
    -   [ ] **Crucial**: Document the standardized approach using `astrotomic/laravel-translatable`: how to make a model translatable, create translation models, and work with translated attributes.
    -   [ ] Document `TranslationService` usage for auto-translation.
    -   [ ] Explain `SetLocale` middleware behavior and configuration (`locale_detection_order`).
    -   [ ] Document `Language` model management (API or config-driven).
    -   [ ] Detail usage of `LocalizationHelper` and `RouteLocalizationHelper`.
    -   [ ] Remove references to the old custom polymorphic translation system.
-   **Testing Strategy:** (From original TODO)
    -   [ ] Test `Language` model CRUD (if API exists).
    -   [ ] Test `astrotomic/laravel-translatable` integration on a sample model from another package (e.g., `Brand` from `catalog`).
    -   [ ] Test `TranslationService` (mocking external API calls for Google, DeepL, Azure).
    -   [ ] Test `SetLocale` middleware with all detection scenarios (URL, session, user pref, browser, default).
    -   [ ] Test helper functions.

## 💡 Remodularization Suggestions

*   **`AutoTranslationProvider` Abstraction**: The current `TranslationProviderInterface` and its implementations are good. If more providers are added or if provider-specific configurations become very complex, each provider could live in its own sub-namespace or even be pluggable via configuration (allowing users to register their own custom providers).
*   **`LocaleManagementService`**: If managing supported languages, their properties (flags, direction), and UI for this becomes complex, parts of `LanguageController` and `Language` model logic could be moved to a dedicated service.

This list should guide further improvements to the internationalization capabilities.
