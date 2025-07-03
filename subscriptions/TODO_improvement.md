# TODO for Subscriptions Package (Improvements)

## 🚀 Core Functionality Enhancements (Integrating `lucasdotvin/laravel-soulbscription`)

-   **Complete `laravel-soulbscription` Integration:** (From original TODO - Phase 1)
    -   [ ] **Installation & Migration**: Ensure `lucasdotvin/laravel-soulbscription` is installed via Composer in the main application. Publish its migrations and configuration (`php artisan vendor:publish --provider="LucasDotVin\Soulbscription\SoulbscriptionServiceProvider"`) and run `php artisan migrate`. This step is crucial and done in the main app context.
    -   [ ] **Configure Soulbscription**: In the main app's `config/soulbscription.php`:
        -   Set the `user_model` to `config('subscriptions.user_model', \App\Models\User::class)` (which should point to `IJIDeals\UserManagement\Models\User`).
        -   Configure payment gateway integration if Soulbscription handles it directly or via Cashier. This needs careful alignment with `ijideals/payments` strategy.
-   **Define Subscribable Entities & Plans:** (From original TODO - Phase 1)
    -   [ ] **Make Models Subscribable**:
        -   Ensure `IJIDeals\UserManagement\Models\User` uses `LucasDotVin\Soulbscription\Models\Concerns\HasSubscriptions` trait.
        -   If `IJIDeals\IJICommerce\Models\Shop` can have subscriptions, ensure it also uses the `HasSubscriptions` trait.
        -   Consider an interface `IJIDeals\Subscriptions\Contracts\Subscribable` that these models could implement for clarity, though Soulbscription uses its own internal checks.
    -   [ ] **Seed Subscription Plans**:
        -   Create database seeders (e.g., `SubscriptionPlanSeeder`) to populate Soulbscription's `plans` and `features` tables with initial offerings (e.g., "User Premium Membership", "Shop Basic Plan").
        -   These seeders should use `LucasDotVin\Soulbscription\Models\Plan` and `LucasDotVin\Soulbscription\Models\Feature`.
        -   Make these seeders publishable via `SubscriptionServiceProvider`.
-   **Payment Integration with `ijideals/payments`:** (From original TODO - Phase 1)
    -   [ ] **Clarify Payment Flow**:
        -   **Scenario 1 (Soulbscription handles payment via Cashier/its own logic):** If Soulbscription is configured to use something like Laravel Cashier, ensure Cashier is set up to use `ijideals/payments` as its underlying payment processor if possible, or document how Cashier's transactions relate to `PaymentTransaction` records in `ijideals/payments`. This can be complex.
        -   **Scenario 2 (This package triggers `ijideals/payments`):**
            -   When a user subscribes (`SubscriptionController@subscribe`), before calling `$user->subscribeTo($plan)`, use `PaymentServiceInterface` from `ijideals/payments` to:
                -   Create/get a customer on the payment gateway.
                -   Set up a recurring payment method or charge the first invoice.
            -   Store the gateway's subscription ID or payment method ID in Soulbscription's `Subscription` model (it has `gateway_id` and `gateway_plan_id` which might be usable, or use its metadata).
            -   The `HandleSuccessfulPayment` listener (if used) would confirm subscription activation in Soulbscription based on events from `ijideals/payments`.
        -   **Decision Needed**: This flow is critical and needs a clear architectural decision. Scenario 2 offers more control via `ijideals/payments`.
    -   [ ] **Webhook Handling for Recurring Payments**: Ensure webhooks from the payment gateway (handled by `ijideals/payments`) correctly trigger renewal/failure logic for subscriptions managed by Soulbscription (e.g., `SubscriptionRenewed`, `SubscriptionPaymentFailed` events from Soulbscription or custom logic).
-   **Implement `SubscriptionService` (Optional Wrapper):** (From original TODO - Phase 2)
    -   [ ] Create `SubscriptionService` to provide a cleaner API over Soulbscription methods and integrate IJIDeals-specific logic:
        -   `subscribeUserToPlan(User $user, Plan $plan, array $paymentDetails)`
        -   `cancelUserSubscription(User $user, bool $immediately = false)`
        -   `switchUserPlan(User $user, Plan $newPlan, bool $prorate = true)`
        -   `getUserActiveSubscription(User $user)`
        -   This service would handle interactions with both Soulbscription and `ijideals/payments`.

## 🔧 API & Controller Enhancements

-   **Refine `SubscriptionController.php`:**
    -   [ ] **`subscribe()` Method**: Fully implement payment processing logic as per the chosen payment flow (see above).
    -   [ ] **`switchPlan()` Endpoint**: Implement logic for plan upgrades/downgrades, handling proration, and payment adjustments. Soulbscription might have helpers for this.
    -   [ ] **`resumeSubscription()` Endpoint**: For users to resume a cancelled (but not yet expired) subscription.
    -   [ ] **`updatePaymentMethodForSubscription()` Endpoint**: Allow users to update the payment method for their active subscription (integrates with `ijideals/payments` to update gateway's customer payment method and then Soulbscription's records if needed).
    -   [ ] Use API Resources for responses (`PlanResource`, `SubscriptionResource`).
-   **Policies:**
    -   [ ] Create and implement `SubscriptionPolicy` (e.g., can user subscribe to a specific plan, can they cancel/switch). Enforce in controller.
    -   [ ] Create `PlanPolicy` (e.g., can user view this plan - mostly public).

## ⚙️ Configuration & Setup

-   **Refine `config/subscriptions.php`:**
    -   [ ] Add mapping for `ijideals/commerce` `Shop` model if it's subscribable.
    -   [ ] Configuration for payment plan IDs on the gateway if they need to map to Soulbscription Plan IDs.
    -   [ ] Settings for dunning management (retry attempts for failed recurring payments).
-   **Service Provider (`SubscriptionServiceProvider`):**
    -   [ ] **Event Listeners**: (From original TODO - Phase 2)
        -   Register listeners for Soulbscription events (e.g., `SubscriptionRenewed`, `SubscriptionCancelled`, `SubscriptionEnded`) to trigger IJIDeals-specific actions (e.g., update user roles, dispatch notifications).
        -   Register listeners for `ijideals/payments` events (e.g., `PaymentSucceeded` for a subscription `Payable`) to update Soulbscription statuses. (Example `HandleSuccessfulPayment` listener to be created/refined).

## 🔗 Inter-Package Integration

-   **`ijideals/sponsorship`**: If tier-based sponsorships use this package, `SponsorshipTier` models would map to `Plan` models here. `SponsorshipService` would use `SubscriptionService`.
-   **`ijideals/user-management`**: Link subscription plans to specific roles or permissions. When a subscription becomes active/inactive, update user's roles/permissions accordingly (via event listeners).
-   **`ijideals/commerce`**: If shops subscribe to plans for different feature sets (e.g., number of products, commission rates), `ShopPolicy` or feature checks would use Soulbscription's `$shop->hasFeature('feature_code')`.

## 📚 Documentation & Testing

-   **README Update:** (From original TODO - Phase 2)
    -   [ ] **Crucial**: Full setup instructions, including `lucasdotvin/laravel-soulbscription` configuration within the IJIDeals context.
    -   [ ] How to define plans and features using Soulbscription models and seeders.
    -   [ ] Detailed explanation of the chosen payment integration flow with `ijideals/payments`.
    -   [ ] Document API usage for managing subscriptions.
-   **Testing Strategy:**
    -   [ ] Feature tests for the full subscription lifecycle (subscribe, payment, renewal, cancellation, plan switch) using mocked payment gateway interactions.
    -   [ ] Test API endpoints and policies.
    -   [ ] Unit tests for `SubscriptionService` if implemented.
    -   [ ] Test event listeners and their impact (e.g., role changes on subscription).

## 💡 Remodularization Suggestions

*   **This package is primarily an integration layer.** Its main job is to make `lucasdotvin/laravel-soulbscription` work smoothly within the IJIDeals ecosystem, especially with `ijideals/payments` and `ijideals/user-management`. No major remodularization seems needed unless Soulbscription itself proves insufficient and a custom subscription engine is built.

Clear payment flow design is the most critical next step for this package's core logic.
