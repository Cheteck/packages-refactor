# IJIDeals Subscriptions Package

This package will manage user subscriptions for various services or features within the IJIDeals platform, such as premium memberships, shop subscriptions, or access to sponsored content tiers.

It will be built upon the `lucasdotvin/laravel-soulbscription` library to handle the core subscription logic (plans, features, recurring billing cycles). This package will primarily focus on integrating `laravel-soulbscription` into the IJIDeals ecosystem and providing any necessary wrappers, additional models, or services specific to IJIDeals' needs.

## Core Features (Leveraging `laravel-soulbscription` and extended by this package)

-   **Subscription Plan Management:** Define various subscription plans with different features, prices, and billing intervals.
-   **Feature Entitlement:** Control access to features based on a user's active subscription plan.
-   **Recurring Billing Cycle Management:** Handled by `laravel-soulbscription` (which itself often integrates with a payment gateway or Cashier).
-   **Grace Periods & Dunning:** (If supported by `laravel-soulbscription` or implemented here)
-   **User Subscription Management:** Allow users to subscribe, upgrade, downgrade, or cancel subscriptions.
-   **Integration with IJIDeals Payments:** Link subscription payments to the `ijideals/payments` package.
-   **Integration with IJIDeals Sponsorship:** Provide the subscription mechanism for creator/shop sponsorship tiers.

## Key Dependencies

-   `lucasdotvin/laravel-soulbscription`
-   `ijideals/user-management` (for subscribable users)
-   `ijideals/payments` (for processing subscription payments)
-   Potentially `ijideals/sponsorship` (if this package provides the subscription engine for it)
