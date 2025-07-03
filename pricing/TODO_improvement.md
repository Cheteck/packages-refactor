# TODO for Pricing Package (Improvements)

## 🚀 Core Functionality Enhancements

-   **Implement `PricingService` Logic:**
    -   [ ] **Price Calculation (`calculatePrice`):**
        -   Implement `calculatePrice(Model $priceable, User $user = null, array $context = [])`:
            -   Fetch base price(s) from `Price` model for the `$priceable` entity.
            -   Apply relevant `PricingTier` adjustments based on `$user` roles/membership (requires integration with `UserManagement`).
            -   Apply active `Discounts` (checking `DiscountRule` conditions: date range, user segment, product/category, cart conditions via `$context`).
            -   Apply `TaxRate` (based on context: shipping address, shop location, product tax category). Handle tax-inclusive vs. tax-exclusive pricing.
            -   Handle currency conversions using `ExchangeRate` model if context currency differs from base price currency.
            -   Return a structured price object (e.g., with base price, discounts applied, taxes, final price, currency).
    -   [ ] **Cart Pricing (`calculateCartTotal`):**
        -   Method to calculate total for a collection of cart items, applying item-level calculations and then cart-level discounts/taxes. This will be heavily used by `ijideals/commerce`.
    -   [ ] **Discount Management:**
        -   Methods to get applicable discounts for a product/cart/user.
        -   Logic for coupon code validation and application (linking `Discount` to coupon codes).
        -   Track `DiscountUsage` (increment count, check usage limits per user/globally).
    -   [ ] **Currency Conversion:**
        -   Implement `convertCurrency(float $amount, string $fromCurrencyCode, string $toCurrencyCode, ?Carbon $date = null)` using `ExchangeRate` data.
        -   Consider a scheduled job to update `ExchangeRate` from an external API.
    -   [ ] **Tiered Pricing Application:**
        -   Logic to determine a user's pricing tier.
        -   Apply tier-specific adjustments (e.g., percentage discount, fixed price override) from `Price` or a dedicated `TierPrice` model.
-   **Scheduled Promotions & Price Changes:**
    -   [ ] Implement logic for `Discount` and `Price` models to have `active_from` and `active_until` dates.
    -   [ ] Use Laravel's scheduler to activate/deactivate discounts or update prices based on schedules.
-   **Tax Management:**
    -   [ ] **Tax Zones/Rules**: Enhance `TaxRate` to support more complex tax rules (e.g., different rates for different product types, compound taxes).
    -   [ ] `PricingService` to correctly identify and apply taxes based on context (e.g., shipping address from `ijideals/location`, customer group).
-   **Price History Tracking:**
    -   [ ] When a `Price` record is updated, automatically create a `PriceHistory` entry. This could be done via model observers on `Price`.

## 🔧 API & Configuration

-   **API Endpoints (Optional but Recommended for Admin):**
    -   [ ] **Currency Management**: `GET /currencies`, `POST /currencies`, `PUT /currencies/{currency}`.
    -   [ ] **Exchange Rate Management**: `GET /exchange-rates`, `POST /exchange-rates` (or automate updates).
    -   [ ] **Discount Management**: CRUD for `Discounts` and `DiscountRules`.
    -   [ ] **Pricing Tier Management**: CRUD for `PricingTiers`.
    -   [ ] **Tax Rate Management**: CRUD for `TaxRates`.
    -   [ ] **Price Management**: API to view/update prices for specific `priceable` items.
    -   [ ] Create necessary Controllers, Form Requests, API Resources, and Policies.
-   **Refine `config/pricing.php`:**
    -   [ ] Ensure all settings are actively used (`currency`, `price_precision`, `tax.*`, `discounts.*`).
    -   [ ] Add configuration for default currency conversion provider if rates are fetched automatically.
    -   [ ] Settings for tiered pricing behavior (e.g., how tiers are assigned or recognized).
    -   [ ] Configuration for scheduled promotion job (if applicable).
    -   [ ] Define precision for calculations and storage throughout the system.

## 🧹 Code Quality & Model Refinements

-   **Model `Price.php`:**
    -   [ ] Ensure `priceable()` morphTo relationship is robust.
    -   [ ] Add relationship to `Currency`.
    -   [ ] Add relationship to `PricingTier` (nullable) if prices can be tier-specific.
    -   [ ] Add `histories()` HasMany relationship to `PriceHistory`.
-   **Model `Discount.php`:**
    -   [ ] Add relationships to `DiscountRule` and `DiscountUsage`.
    -   [ ] Add scopes for active discounts, type-specific discounts (e.g., `ofType('percentage')`).
    -   [ ] Fields for `max_uses`, `max_uses_per_user`, `coupon_code`.
-   **Model `Currency.php`:**
    -   [ ] Add `exchange_rate` field if not relying solely on `ExchangeRate` table for *current* rate.
    -   [ ] Consider methods for formatting amounts in this currency.
-   **Enums for Types/Statuses:**
    -   [ ] `DiscountTypeEnum` (e.g., `PERCENTAGE`, `FIXED_AMOUNT`, `FREE_SHIPPING`).
    -   [ ] `DiscountRuleTypeEnum` (e.g., `CART_SUBTOTAL`, `PRODUCT_QUANTITY`, `USER_ROLE`).
    -   [ ] `TaxRateTypeEnum` (e.g., `PERCENTAGE`, `FIXED`).
-   **Priceable Interface/Trait:**
    -   [ ] Create a `PriceableInterface` (e.g., with `getPrices()`, `getBasePrice()`).
    -   [ ] Models like `Product`, `Variant` (from `ijideals/commerce`), `SubscriptionPlan` (from `ijideals/subscriptions`) should implement this.
    -   [ ] Potentially a `HasPrices` trait for these models.

## 📚 Documentation & Testing

-   **README Update:**
    -   [ ] Document all models and their roles in the pricing system.
    -   [ ] Explain how to use `PricingService` to get final prices for items and carts.
    -   [ ] Detail how to manage currencies, exchange rates, discounts, tiers, and taxes.
    -   [ ] Explain integration points with `Commerce`, `UserManagement`, etc.
    -   [ ] Document configuration options in `pricing.php`.
-   **Testing Strategy:**
    -   [ ] **Crucial**: Write extensive unit tests for `PricingService` covering various scenarios:
        -   Different discount types and combinations.
        -   Tiered pricing application.
        -   Tax calculation (inclusive/exclusive).
        -   Currency conversions.
    -   [ ] Test model relationships and scopes.
    -   [ ] Test scheduled jobs for promotions or exchange rate updates.
    -   [ ] Feature tests for any API endpoints.

## 💡 Remodularization Suggestions

*   **`TaxManagementService`**: If tax rules become extremely complex (e.g., integration with Avalara, TaxJar, or very dynamic regional tax laws), this could be a separate service or even a dedicated package.
*   **`CurrencyExchangeService`**: If fetching and managing exchange rates from multiple providers with historical data becomes a core, complex feature, it could be spun off.
*   **`PromotionEngine`**: If the discount and promotion system evolves to include complex rule combinations, stackable discounts, loyalty points integration, etc., it might warrant its own engine/package.

This package is central to e-commerce functionality and requires robust logic and testing.
