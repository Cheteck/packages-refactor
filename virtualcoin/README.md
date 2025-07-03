# Virtual Coin Package

The Virtual Coin package implements a complete virtual currency system for the IJIDeals platform. This internal economy allows users to earn, spend, and transfer virtual coins, driving engagement and enabling unique monetization strategies.

## Core Features

-   **User Wallets**: Every user automatically gets a wallet to store their virtual coins.
-   **Transaction Management**: A secure, double-entry accounting system for all coin transactions.
-   **Transactional Integrity**: All operations are wrapped in database transactions to prevent inconsistencies.
-   **Multiple Transaction Types**: Comes with predefined transaction types (e.g., `purchase`, `reward`, `tip`, `refund`) and the ability to add custom types.
-   **API for Balance & History**: Endpoints to check wallet balance and view transaction history.

## Key Components

### Models

-   `CoinWallet`: Represents a user's wallet, storing their current coin balance.
-   `CoinTransaction`: Records every movement of coins, including the type, amount, and related models.

### Traits

-   `HasVirtualWallet`: A trait to add wallet functionality to the `User` model. It provides easy access to the user's wallet and balance.

```php
// Add to User model
use HasVirtualWallet;

// Usage
$user->wallet->balance; // Get balance
$user->deposit(100, 'reward', 'For daily login');
$user->withdraw(50, 'purchase', 'For virtual item');
```

### Services

-   `WalletService`: A service class that orchestrates all wallet operations, ensuring business rules and integrity are maintained.

## Dependencies

-   **`ijideals/user-management`**: To link wallets to users.

## Use Cases

-   **Rewarding Users**: Give coins for specific actions like daily logins, content creation, or referrals.
-   **Tipping**: Allow users to tip creators for their content.
-   **Purchases**: Use as a payment method for virtual goods, services, or to unlock features.
-   **Sponsorships**: A core component for the `sponsorship` package. 
