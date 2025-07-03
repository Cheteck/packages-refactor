# TODO for IJIDeals Subscriptions Package

## Phase 1: Foundation & `laravel-soulbscription` Integration

- [ ] **Define Core Purpose & Scope:**
    - [ ] Confirm primary use cases: Shop subscriptions, user premium memberships, sponsorship tiers.
    - [ ] Review `lucasdotvin/laravel-soulbscription` documentation thoroughly to understand its capabilities and integration points.
- [X] **Initial Setup:**
    - [X] Create `composer.json`:
        - [X] Set license to "proprietary".
        - [X] Set author to "IJIDeals" and email "contact@ijideals.com".
        - [X] Add dependencies: PHP, Laravel framework, `lucasdotvin/laravel-soulbscription`, `ijideals/user-management`, `ijideals/payments`.
        - [X] Define PSR-4 autoloading for `IJIDeals\\Subscriptions\\`.
    - [ ] Create `src/Providers/SubscriptionServiceProvider.php`.
        - Implement `register()` to merge config.
        - Implement `boot()` to load migrations (if any beyond what Soulbscription provides), routes (for subscription management API), and publish config.
    - [ ] Create `config/subscriptions.php` (at package root).
        - Include any package-specific configurations, potentially wrappers or default settings for Soulbscription if needed.
- [ ] **Integrate `laravel-soulbscription`:**
    - [ ] Install `lucasdotvin/laravel-soulbscription` via Composer.
    - [ ] Publish Soulbscription's migrations and configuration: `php artisan vendor:publish --provider="LucasDotVin\Soulbscription\SoulbscriptionServiceProvider"`.
    - [ ] Run migrations: `php artisan migrate`.
    - [ ] Configure Soulbscription (e.g., user model, payment gateway integration if it handles that directly or through Cashier - this needs to align with `ijideals/payments`).
- [ ] **Define Subscribable Entities & Plans:**
    - [ ] Make the `User` model (from `ijideals/user-management`) implement `Subscribable` from Soulbscription.
    - [ ] If Shops can have subscriptions (e.g., for different feature tiers), make `Shop` model (from `ijideals/commerce`) implement `Subscribable`.
    - [ ] Define initial subscription `Plan` models (using Soulbscription's `Plan` model) via seeders or an admin interface:
        - E.g., "Basic Shop Plan", "Premium Shop Plan", "User Gold Membership".
        - Define features for each plan (using Soulbscription's `Feature` model).
- [ ] **Payment Integration:**
    - [ ] Clarify how `laravel-soulbscription` handles payments. It often relies on Laravel Cashier or direct gateway interaction.
    - [ ] Ensure this aligns with the `ijideals/payments` package strategy. This `subscriptions` package might need to:
        - Trigger payment processing via `ijideals/payments` when Soulbscription indicates a charge is due.
        - Listen for payment success/failure events from `ijideals/payments` to update subscription statuses in Soulbscription.

## Phase 2: IJIDeals Specific Logic & API

- [ ] **Service Layer (Optional Wrappers):**
    - [ ] Create `SubscriptionService` if needed to provide a simplified interface over Soulbscription's methods or to add IJIDeals-specific business logic (e.g., tying sponsorship tiers to subscription plans).
- [ ] **API for Subscription Management:**
    - [ ] Create `src/Http/Controllers/SubscriptionController.php`.
    - [ ] Define routes in `routes/api.php` for users/shops to:
        - View available plans.
        - Subscribe to a plan.
        - View their current subscription.
        - Update payment method for subscription (integrating with `ijideals/payments`).
        - Cancel subscription.
        - Potentially upgrade/downgrade plans.
    - [ ] Implement Form Requests for validation.
    - [ ] Implement Policies for authorization.
- [ ] **Integration with other IJIDeals Packages:**
    - **Sponsorship:** If `ijideals/sponsorship` uses this package for tier subscriptions, define how `Tier` models in `sponsorship` map to `Plan` models here.
    - **User Roles/Permissions:** Potentially link subscription plans to specific roles or permissions in `ijideals/user-management`.
- [ ] **Events & Listeners:**
    - [ ] Listen to Soulbscription's events (e.g., `SubscriptionRenewed`, `SubscriptionCancelled`) if needed for IJIDeals-specific actions.
    - [ ] Dispatch IJIDeals-specific events (e.g., `UserSubscribedToPremium`, `ShopSubscriptionActivated`).
- [ ] **Testing:**
    - [ ] Write feature tests for subscription lifecycle (subscribe, cancel, payment success/failure).
    - [ ] Test API endpoints.
    - [ ] Mock interactions with `laravel-soulbscription` and `ijideals/payments` where necessary.
- [ ] **Documentation:**
    - [ ] Update `README.md` with full setup instructions, including `laravel-soulbscription` configuration within the IJIDeals context.
    - [ ] Document how to define plans and features.
    - [ ] Document API usage.

## Phase 3: Advanced Features (Future)

- [ ] **Grace Periods & Dunning Management:** Implement or configure logic for handling failed payments and subscription recovery.
- [ ] **Metered Billing / Usage-Based Features:** If plans include features with usage limits.
- [ ] **Admin Interface:** UI for managing plans, subscriptions, and viewing subscriber data.

This package will act as a crucial bridge between the user-facing features requiring subscriptions and the underlying subscription mechanics provided by `laravel-soulbscription` and payment processing by `ijideals/payments`.
