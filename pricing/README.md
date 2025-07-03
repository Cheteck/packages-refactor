# Pricing Package

The Pricing package provides a dynamic and flexible engine to manage all aspects of pricing within the IJIDeals marketplace. It is designed to handle everything from simple product prices to complex, rule-based discounts and multi-currency scenarios.

## Core Features

-   **Multi-Currency Support**: Define prices in multiple currencies and manage exchange rates.
-   **Price Management**: Associate multiple price points with a single product (e.g., `default`, `sale`, `VIP`).
-   **Discount Engine**: A powerful rule-based system for creating discounts and promotions.
    -   Percentage-based discounts (`10% off`).
    -   Fixed amount discounts (`$5 off`).
    -   Buy X, Get Y promotions.
    -   Coupon codes.
-   **Tiered Pricing**: Offer different prices based on user roles or membership levels.
-   **Scheduled Promotions**: Automatically enable and disable promotions at specific times.

## Key Components

### Models

-   `Price`: Stores a price amount and currency for a "priceable" model (like a `Product`).
-   `Currency`: Represents a supported currency and its exchange rate.
-   `Discount`: Defines a promotion and its conditions (e.g., coupon code, active dates).
-   `DiscountRule`: The specific logic of a discount (e.g., "10% off on category X").

### Services

-   `PricingService`: The main entry point for calculating the final price of a product or cart. It takes a product and a user context, applies all relevant rules, and returns the final price.

## How It Works

The `PricingService` is the brain of the package. When a price is requested, it:
1.  Fetches the base price from the `Price` model for the given currency.
2.  Scans for all active `Discount` promotions.
3.  Evaluates the `DiscountRule`s against the product, cart, and user.
4.  Applies the best valid discount.
5.  Returns the calculated final price.

## Dependencies

-   **`ijideals/catalog`**: To apply prices and discounts to products and categories.
-   **`ijideals/user-management`**: To apply user-specific or role-based pricing.
-   **`ijideals/commerce`**: To apply discounts to shopping carts.

## Structure

```
src/
├── Models/           # Price, Discount, and related models
├── Database/
│   ├── factories/    # Model factories for testing
│   └── migrations/   # Database migrations
├── Providers/        # Service providers
└── Config/          # Package configuration
```

## Models

- Price
- PriceHistory
- Discount
- DiscountRule
- DiscountCondition
- Currency
- ExchangeRate
- PricingTier
- TaxRate

## Installation

```bash
composer require ijideals/pricing
```

## Configuration

Publish the configuration:

```bash
php artisan vendor:publish --tag=pricing-config
``` 
