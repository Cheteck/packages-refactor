# TODO for Location Package (Improvements)

## 🚀 Core Functionality Enhancements

-   **Address Model (`Address.php`):**
    -   [ ] **Validation**: The `validate()` method in `Address.php` performs validation but might be better handled by Form Requests if addresses are managed via API. Clarify its intended use or integrate its rules into Form Requests.
    -   [ ] **Formatting**: The `formatAddress()` method is good. Consider making the format string configurable or adaptable to different country address formats if internationalization of address display is a requirement.
    -   [ ] **Geocoding**: Implement a service (`GeocodingService`?) to automatically fetch latitude/longitude from an address string (e.g., using Google Geocoding API, OpenStreetMap Nominatim) when an address is created/updated, if `latitude`/`longitude` are not provided. This would likely be an optional feature configurable via API keys.
    -   [ ] **Distance Calculation Helpers**: (From original TODO) Implement helper methods or a service for distance calculation between two addresses or sets of coordinates (e.g., Haversine formula).
-   **Model Relationships & Consistency:**
    -   [ ] **`Address` Relationships**: Ensure `city()`, `region()`, `country()` relationships in `Address.php` correctly point to the models within this package and that foreign keys (`city_id`, `region_id`, `country_id`) are consistently used and indexed.
    -   [ ] **`Country`, `Region`, `City` Relationships**: Verify `HasMany` relationships (e.g., `Country->regions()`, `Country->cities()`, `Region->cities()`) are correctly defined and functional.
-   **Trait `HasAddress.php`:** (File exists but content not reviewed in previous steps)
    -   [ ] **Review and Implement**: This trait is likely intended for models that can have one or multiple addresses (e.g., User, Shop). Implement its logic to provide easy methods for attaching/detaching/retrieving addresses (e.g., `addAddress(array $data)`, `getPrimaryAddress()`, `addresses() MorphMany` relation).
-   **Trait `HasCountries.php`:**
    -   [ ] **Usage Review**: Determine which models (if any) should use this `BelongsToMany` trait for countries. If no clear use case emerges across other packages, consider deprecating or removing it. If used, ensure a pivot table migration is provided or documented.

## 🔧 API & Configuration

-   **API Endpoints:**
    -   [ ] **CRUD for Countries, Regions, Cities**: If these are to be managed dynamically:
        -   Create `CountryController`, `RegionController`, `CityController`.
        -   Implement API endpoints for listing (with filters like country for regions/cities), showing, creating, updating, deleting.
        -   Use Form Requests for validation and API Resources for responses.
        -   Implement Policies (`CountryPolicy`, `RegionPolicy`, `CityPolicy`).
    -   [ ] **Address Autocomplete/Suggestion API**: Enhance `Address::suggest()` or move to a dedicated controller endpoint (e.g., `GET /api/location/addresses/suggest?query=...`).
-   **Configuration File (`config/location.php`):** (From original TODO)
    -   [ ] Create `config/location.php`.
    -   [ ] Add settings for default distance units (km/miles).
    -   [ ] Configuration for geocoding service (provider, API key).
    -   [ ] Patterns for postal code validation per country (if advanced validation is needed).
    -   [ ] Default country/region if applicable for the application.
    -   [ ] Update `LocationServiceProvider` to load and publish this config.

## 🧹 Code Quality & Model Refinements

-   **Internal Model TODOs:** (From original TODO)
    -   [ ] Review and remove obsolete TODO comments from `Region.php` (e.g., "Move this model...").
-   **Sluggable Configuration:**
    -   [ ] For `Country`, `Region`, `City`, ensure the `sluggable()` configuration (source field, uniqueness) is optimal, especially with translated `name` fields. Test slug generation and updates thoroughly.
-   **Enums for Statuses:**
    -   [ ] Use PHP Enums for `status` fields in `Country`, `Region`, `City` (e.g., `LocationStatusEnum::ACTIVE`).
-   **Database Seeder:** (From original TODO)
    -   [ ] Create `LocationDatabaseSeeder` (and potentially separate seeders for Countries, Regions, Cities).
    -   [ ] Populate with a good default set of countries, and optionally regions/cities for common areas.
    -   [ ] Make seeders publishable via `LocationServiceProvider`.
    -   [ ] Provide options to seed a small, medium, or full dataset.

## 📚 Documentation & Testing

-   **README Update:**
    -   [ ] Document all models and their (translatable) fields and relationships.
    -   [ ] Explain usage of `HasAddress` and `HasCountries` traits.
    -   [ ] Detail any helper functions (distance calculation, address formatting).
    -   [ ] Document API endpoints if created.
    -   [ ] Explain configuration options in `location.php`.
-   **Testing Strategy:**
    -   [ ] Unit tests for model relationships, scopes, accessors, translatable attributes, and sluggable behavior.
    -   [ ] Tests for `Address::validate()`, `suggest()`, `formatAddress()`.
    -   [ ] Tests for distance calculation helpers.
    -   [ ] Feature tests for API endpoints and policies.
    -   [ ] Test seeder execution.

## 💡 Remodularization Suggestions

*   **`GeocodingService` / `AddressVerificationService`**: If geocoding, address validation against external APIs (e.g., USPS, Google Address Validation), or complex address formatting based on country rules become significant, these could be extracted into a more specialized service, potentially even its own small utility package if used by many other top-level services.
*   **`LocationDataImportService`**: For importing countries, regions, cities from standard datasets (e.g., ISO files, Geonames), a dedicated import service/command would be beneficial.

This list should guide the further development of the Location package.
