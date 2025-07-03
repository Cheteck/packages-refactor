# TODO for Sponsorship Package (Improvements) - Post Boosting Focus

## 🚀 Core Functionality Enhancements (Post Boosting)

-   **Implement `SponsorshipService` Logic (for Post Boosting):**
    -   [ ] **`createSponsoredPost(User $user, Post $post, array $data)`**:
        -   Validate budget, CPC/CPI (cost_per_impression), start/end dates, targeting options.
        -   Ensure user has sufficient funds in `ijideals/virtualcoin` wallet if that's the payment method.
        -   Deduct initial setup fee or pre-authorize budget from user's wallet if applicable.
        -   Create `SponsoredPost` record with status 'pending' or 'active'.
        -   Dispatch `SponsoredPostCreated` event.
    -   [ ] **`updateSponsoredPost(SponsoredPost $sponsoredPost, array $data)`**: Allow updating non-critical fields (e.g., title, description, potentially budget increase, end_date extension).
    -   [ ] **`pauseSponsoredPost(SponsoredPost $sponsoredPost)`**: Change status to 'paused'.
    -   [ ] **`resumeSponsoredPost(SponsoredPost $sponsoredPost)`**: Change status to 'active' (if budget and dates allow).
    -   [ ] **`cancelSponsoredPost(SponsoredPost $sponsoredPost)`**: Change status to 'cancelled'. Handle potential refunds for unspent budget.
    -   [ ] **Budget Tracking & Status Updates (in `SponsoredPost` model or Service):**
        -   Refine `recordImpression()` and `recordClick()` in `SponsoredPost.php`:
            -   Ensure `cost_per_impression` and `cost_per_click` are correctly used.
            -   When `spent_amount` >= `budget`, change `SponsoredPost->status` to `exhausted_budget`.
            -   When `end_date` is reached, change status to `completed`.
        -   Consider a scheduled job to periodically check for campaigns کمپین whose end_date has passed and update their status to 'completed'.
-   **Integration with Ad Serving Logic (from `ijideals/AdsManager`):**
    -   [ ] Clarify how "Sponsored Posts" are prioritized or integrated into the general ad serving logic of `AdsManager`.
    -   [ ] Are `SponsoredPost` records a type of `Ad` or `AdSet` within `AdsManager`, or do they feed into it differently?
    -   [ ] This might involve `AdsManager`'s `AdServingService` considering active `SponsoredPost` records based on their targeting and budget.
-   **Targeting Options:**
    -   [ ] Define and implement a structure for `SponsoredPost->targeting` JSON (e.g., user demographics, interests). This should align with or reuse targeting capabilities being built for `AdsManager`.
-   **Virtual Coin Integration:**
    -   [ ] **Transaction Linking**:
        -   The current `SponsoredPost->transactions()` relation via `metadata->sponsored_post_id` on `CoinTransaction` is fragile.
        -   **Recommended**: Add a direct, nullable `sponsored_post_id` (FK) to the `coin_transactions` table in `ijideals/virtualcoin`. Update `CoinTransaction` model and `SponsoredPost` to use this direct FK for the relationship.
        -   Alternatively, make `CoinTransaction` use a polymorphic `transactionable` relationship, where `SponsoredPost` can be a `transactionable` type.
    -   [ ] Ensure `recordImpression()` and `recordClick()` correctly and robustly create `CoinTransaction` records with appropriate types (e.g., 'spend_sponsorship_impression', 'spend_sponsorship_click') and metadata.
    -   [ ] Handle cases where user's `virtualCoin` wallet might be missing or interaction fails.

## 🔧 API & Controller Enhancements

-   **Create `SponsoredPostController`:**
    -   [ ] `POST /sponsored-posts`: Create a new sponsored post campaign.
    -   [ ] `GET /sponsored-posts`: List user's sponsored posts.
    -   [ ] `GET /sponsored-posts/{sponsoredPost}`: View details of a specific campaign.
    -   [ ] `PUT /sponsored-posts/{sponsoredPost}`: Update a campaign.
    -   [ ] `POST /sponsored-posts/{sponsoredPost}/pause`: Pause a campaign.
    -   [ ] `POST /sponsored-posts/{sponsoredPost}/resume`: Resume a campaign.
    -   [ ] `DELETE /sponsored-posts/{sponsoredPost}`: Cancel a campaign.
-   **Form Requests & API Resources:**
    -   [ ] Create Form Requests for validation (e.g., `StoreSponsoredPostRequest`).
    -   [ ] Implement API Resources for responses (e.g., `SponsoredPostResource`).
-   **Policies:**
    -   [ ] Create and implement `SponsoredPostPolicy` (user can only manage their own campaigns, admin overrides). Enforce in controller.

## ⚙️ Configuration (`config/sponsorship.php`)

-   **Refine Configuration:**
    -   [ ] Define default CPC/CPI values or ranges.
    -   [ ] Set default budget limits or campaign duration limits.
    -   [ ] Define available statuses for sponsored posts (as an Enum preferably).
    -   [ ] Configuration for interaction with `ijideals/virtualcoin` (e.g., transaction types).
    -   [ ] Table name for `sponsored_posts`.

## 🧹 Code Quality & Model Refinements

-   **Model `SponsoredPost.php`:**
    -   [ ] Add `HasFactory` trait if not already present and link to `SponsoredPostFactory`. (Done)
    -   [ ] Implement scopes: `active()`, `pending()`, `completed()`, `byUser(User $user)`.
    -   [ ] Consider an Enum for `status` field.
-   **Error Handling:**
    -   [ ] Robust error handling in service methods and model actions (e.g., when virtual coin transactions fail).

## 📚 Documentation & Testing

-   **README Update (Crucial):**
    -   [ ] **Rewrite README to accurately describe the Post Boosting functionality.** Remove all references to tier-based creator sponsorships if that's not the direction.
    -   [ ] Document the API for managing sponsored post campaigns.
    -   [ ] Explain how it integrates with `Social` (for `Post` model) and `VirtualCoin`.
    -   [ ] Detail configuration options.
-   **Testing Strategy:**
    -   [ ] Feature tests for API endpoints.
    -   [ ] Unit tests for `SponsorshipService` and `SponsoredPost` model logic (budgeting, status changes, impression/click recording, coin transactions).
    -   [ ] Test Policy authorizations.

## 💡 Remodularization Suggestions (If Scope Changes Back to Tier-Based Sponsorship)

*   If the package were to revert to or also include **tier-based creator sponsorships (like Patreon)**, then the following would be needed, potentially as a separate sub-module or even a distinct package if "Post Boosting" remains:
    *   **Models**: `Sponsorable` (Interface/Trait for User/Shop), `Sponsorship` (User's subscription to a Sponsorable), `Tier` (levels defined by Sponsorable), `Benefit` (perks per Tier).
    *   **Services**: `SubscriptionManagementService` (integrating with `ijideals/subscriptions` and `ijideals/payments`), `TierService`, `BenefitService`.
    *   **Logic**: Handling recurring payments, granting benefits (exclusive content access, roles via `user-management`, discounts via `pricing`).
    *   This would be a significant expansion and likely warrant its own detailed TODO and planning.

This focuses on the "Post Boosting" aspect as decided.
