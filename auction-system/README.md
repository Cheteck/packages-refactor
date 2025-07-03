# Auction System Package

The Auction System package brings the excitement of competitive bidding to the IJIDeals platform. It provides a complete, real-time auctioning system for special items, limited editions, or unique experiences.

## Core Features

-   **Real-time Bidding**: Uses WebSockets to provide instant updates on new bids to all participants.
-   **Multiple Auction Types**: Supports different auction formats, such as `English` (highest bid wins) and `Sealed` (blind auction).
-   **Scheduled Auctions**: Set start and end times for auctions.
-   **Bid Management**: Handles bid placement, validation (e.g., bid must be higher than current), and history.
-   **Automatic Winner Determination**: Automatically identifies and notifies the winner when the auction ends.
-   **Anti-Sniping (Optional)**: Extends the auction time by a few minutes if a bid is placed in the final moments, ensuring fairness.

## Key Components

### Models

-   `Auction`: Represents the auction event for a specific product. Contains details like start/end times, starting price, and status.
-   `Bid`: Records a single bid made by a user on an auction.

### Events

-   `NewBidPlaced`: Broadcast in real-time to all participants when a new bid is made.
-   `AuctionEnded`: Fired when an auction concludes, triggering winner notification and order creation.

## How It Works

1.  A `Shop` owner creates an `Auction` for a `Product` via the **`commerce`** package.
2.  Users place `Bid`s on the auction. Each new bid is broadcast to other bidders.
3.  When the auction's end time is reached, a scheduled job determines the highest bidder.
4.  The `AuctionEnded` event is fired. A listener then converts the winning bid into an `Order` within the **`commerce`** package for payment and fulfillment.

## Dependencies

-   **`ijideals/commerce`**: To link auctions to products and to create an order for the winner.
-   **`ijideals/user-management`**: To identify bidders.
-   **A Laravel Echo-compatible setup**: For the real-time bidding experience.

## Structure

```
src/
├── Models/           # Auction models
├── Database/
│   ├── factories/    # Model factories for testing
│   └── migrations/   # Database migrations
├── Providers/        # Service providers
└── Config/          # Package configuration
```

## Models

- Auction
- Bid
- AuctionItem
- AuctionRule
- AuctionParticipant
- BidHistory
- AutoBid
- AuctionWinner
- AuctionPayment
- AuctionDispute

## Features

- Different auction types (English, Dutch, etc.)
- Real-time bidding
- Automatic bidding
- Bid history tracking
- Auction rules and validation
- Time management
- Winner determination
- Payment processing
- Dispute handling
- Notifications
- Analytics integration

## Installation

```bash
composer require ijideals/auction-system
```

## Configuration

Publish the configuration:

```bash
php artisan vendor:publish --tag=auction-system-config
``` 
