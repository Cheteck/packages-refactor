# TODO for Auction System Package (Improvements)

## 🚀 Core Functionality Enhancements

-   **Implement `AuctionService` Logic:**
    -   [ ] **Auction Creation & Validation:**
        -   Implement `createAuction(array $data, User $creator)`: Validate start/end dates, pricing (starting, reserve), bid increment. Ensure `product_id` (from `ijideals/commerce`) is valid and auctionable.
        -   Set default status (e.g., `Auction::STATUS_PENDING` or `Auction::STATUS_ACTIVE` if start date is now/past).
    -   [ ] **Bid Placement Logic:**
        -   Implement `placeBid(Auction $auction, User $bidder, float $amount, bool $isAutoBid = false, ?float $maxAutoBidAmount = null)`:
            -   Validate auction is active and open for bidding.
            -   Validate bid amount: `>` current price + increment amount.
            -   Prevent user from outbidding themselves unless it's to increase their auto-bid max or if specifically allowed.
            -   Handle auto-bidding: If a new manual bid is placed, check existing auto-bids. If an auto-bid is triggered and outbids the new manual bid, place bids for the auto-bidder up to their `max_auto_bid_amount` or until they are the highest bidder by one increment.
            -   Update `Auction->current_price` and `Auction->bids_count`.
            -   Set the new bid's status to `Bid::STATUS_ACTIVE` (or `Bid::STATUS_WINNING` if it's now the highest). Mark previous highest bid as `Bid::STATUS_OUTBID`.
            -   Dispatch `NewBidPlaced` event.
    -   [ ] **Anti-Sniping Logic:**
        -   If `Auction->auto_extend_on_bid` is true, and a bid is placed within `Auction->extension_time_minutes` of `Auction->end_date`, extend `Auction->end_date`.
    -   [ ] **Winner Determination Process (called by `DetermineAuctionWinnerJob` or manually):**
        -   Refine logic in `DetermineAuctionWinnerJob` or move core parts to `AuctionService->determineWinner(Auction $auction)`.
        -   Ensure correct handling of reserve price.
        -   Update `Auction->winner_id`, `Auction->winning_bid_amount`, `Auction->status`.
        -   Update status of all bids on the auction (winning, lost).
        -   Dispatch `AuctionEnded` event.
-   **Refine `DetermineAuctionWinnerJob`:**
    -   [ ] Ensure it correctly handles auctions that might have been processed manually or by a previous run.
    -   [ ] Add more logging for edge cases.
    -   [ ] Make job frequency configurable via `config('auction-system.winner_job_frequency')` and ensure `AuctionSystemServiceProvider` uses this config.
-   **Implement Order Creation Listener:**
    -   [ ] Create `Listeners/CreateOrderFromWinningBid` listener for the `AuctionEnded` event.
    -   [ ] If `AuctionEnded->status` indicates a sale (e.g., `Auction::STATUS_ENDED_SOLD`):
        -   Interact with `ijideals/commerce`'s `OrderService` to create an order for the `Auction->winner_id` for the `Auction->product_id` at the `Auction->winning_bid_amount`.
        -   Handle potential failures in order creation (e.g., product out of stock - though auction implies unique item, payment failure if payment is processed at this stage).
-   **Real-time Updates (Laravel Echo):**
    -   [ ] Ensure `NewBidPlaced` event is properly configured for broadcasting (e.g., on a private channel `auction.{auction_id}`).
    -   [ ] Consider broadcasting `AuctionUpdated` for changes like price, end time.
    -   [ ] Document frontend Echo setup in README.

## 🔧 API & Configuration

-   **API Endpoints (`routes/api.php`):**
    -   [ ] **Auction Management (for creators/admins):**
        -   `POST /auctions` (Create)
        -   `PUT /auctions/{auction}` (Update - limited fields like description, reserve price before bids)
        -   `DELETE /auctions/{auction}` (Cancel/Archive - with appropriate checks)
    -   [ ] **Public Auction Viewing:**
        -   `GET /auctions` (List active/upcoming auctions, with filtering and pagination)
        -   `GET /auctions/{auction}` (View specific auction details, bid history if allowed)
    -   [ ] **Bidding:**
        -   `POST /auctions/{auction}/bids` (Place a bid)
    -   [ ] Create `AuctionController`, `BidController` in `src/Http/Controllers/`.
    -   [ ] Implement Form Requests for validation (e.g., `PlaceBidRequest`, `StoreAuctionRequest`).
    -   [ ] Implement Policies (`AuctionPolicy`, `BidPolicy`) for authorization.
-   **Refine `config/auction-system.php`:**
    -   [ ] Add default auction duration (e.g., 7 days).
    -   [ ] Configure anti-sniping settings (enabled by default, `extension_time_minutes`, `max_extensions`).
    -   [ ] Define bid increment rules/strategies (e.g., fixed amount, percentage of current price, tiered increments).
    -   [ ] List supported auction types (e.g., 'english', 'sealed_bid', 'dutch') and their specific rule configurations if expanding beyond simple English auctions.
    -   [ ] Configure notification settings (e.g., enable/disable outbid notifications, auction ending soon, auction won/lost notifications - requires integration with `ijideals/notifications-manager`).
    -   [ ] Define Laravel Echo channel naming conventions.
    -   [ ] Add `winner_job_frequency` setting.

## 🧹 Code Quality & Model Refinements

-   **Model `Auction.php`:**
    -   [ ] Clarify `user()` relationship: Rename to `creator()` or `owner()` if it represents the auction's creator. Ensure corresponding FK `creator_id` is used consistently.
    -   [ ] Add scopes: `active()`, `upcoming()`, `ended()`, `sold()`, `requiringWinnerProcessing()`.
    -   [ ] Add accessors/mutators for easier data handling (e.g., `isReservePriceMet()`, `canBeExtended()`).
-   **Model `Bid.php`:**
    -   [ ] Add scopes: `winning()`, `outbid()`, `byUser(User $user)`.
    -   [ ] Consider relationship `outbidBy(): BelongsTo` if storing the bid that outbid current one.
-   **Enums for Statuses:**
    -   [ ] Create `AuctionStatusEnum` and `BidStatusEnum` for better type safety and clarity. Update models and migrations to use them.
-   **Factories:**
    -   [ ] Create/Verify Model Factories for `Auction` and `Bid` in `database/factories/` (they should exist from previous steps). Ensure they are comprehensive.
-   **PHPStan:**
    -   [ ] Remove `Bid.phpstan-result.json` from version control. Run PHPStan and address reported issues.

## 📚 Documentation & Testing

-   **README Update:**
    -   [ ] Update "Models" list in "Key Components" accurately.
    -   [ ] Document all API endpoints.
    -   [ ] Detail setup for Laravel Echo.
    -   [ ] Explain different auction types if supported.
    -   [ ] Document the role and configuration of `DetermineAuctionWinnerJob`.
    -   [ ] Explain all settings in `config/auction-system.php`.
-   **Testing Strategy:**
    -   [ ] Write unit tests for `AuctionService` methods (bid placement, auto-bidding, anti-sniping, winner logic).
    -   [ ] Test model relationships, scopes, and helper methods.
    -   [ ] Test event dispatching and the `CreateOrderFromWinningBid` listener.
    -   [ ] Test `DetermineAuctionWinnerJob` functionality.
    -   [ ] Write feature tests for all API endpoints and policies.

## 💡 Remodularization Suggestions

*   **`AutoBiddingService`**: If auto-bidding logic becomes extremely complex with multiple strategies, it could be extracted.
*   **`AuctionTypeStrategy`**: If various auction types (Dutch, Sealed Bid, Vickrey) are introduced, a strategy pattern could be used, potentially with each type in its own class/service, managed by the main `AuctionService`.
*   **`AuctionNotificationService`**: If notifications become very detailed and specific to auctions (outbid, ending soon, won, lost, payment reminders), this could be a dedicated service using `ijideals/notifications-manager`.

This detailed list should help guide the maturation of the auction system.
