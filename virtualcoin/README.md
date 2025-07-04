# Virtual Coin Package

The Virtual Coin package implements a complete virtual currency system for the IJIDeals platform. This internal economy allows users to earn, spend, and transfer virtual coins, driving engagement and enabling unique monetization strategies.

## Core Features

-   **User Wallets**: Every user automatically gets a wallet to store their virtual coins.
-   **Transaction Management**: A secure, double-entry accounting system for all coin transactions.
-   **Transactional Integrity**: Operations modifying balances are wrapped in database transactions. Pessimistic locking is used on wallet balances during transactions to prevent race conditions.
-   **Multiple Transaction Types**: Uses a `TransactionType` Enum for standardized transaction types (e.g., `DEPOSIT_PURCHASE`, `SPEND_ITEM`, `ADJUSTMENT_CREDIT`), enhancing type safety and maintainability.
-   **Idempotency**: Optional idempotency for transactions via a unique reference field to prevent accidental duplicate operations.
-   **Event-Driven**: Dispatches events like `VirtualCoinTransactionCreated`, `BalanceAdjusted`, and `TransferOccurred` for better system integration.
-   **Precision Handling**: Uses BCMath for critical calculations to maintain numerical precision.
-   **Service Layer**: A `WalletService` provides a clear API for all wallet operations, including deposits, withdrawals, transfers, and balance adjustments.

## Key Components

### Models

-   `VirtualCoin` (formerly CoinWallet): Represents a user's wallet, storing their current coin balance (with `decimal:4` precision).
-   `CoinTransaction`: Records every movement of coins, including the type, amount, status, reference, and balances before/after (amount with `decimal:2` precision, balances with `decimal:4`).

### Enums

-   `TransactionType`: Defines all valid transaction types and their labels. (Located in `IJIDeals\VirtualCoin\Enums\TransactionType`)

### Traits

-   `HasVirtualWallet`: A trait for the User model to easily interact with their wallet via the `WalletService` or direct model methods. Provides methods like `getWallet()`, `getCoinBalance()`, `depositCoins()`, `withdrawCoins()`.

```php
// Add to User model
use HasVirtualWallet;

// Usage via Trait (basic operations)
$user->getWallet()->balance; // Get balance (string for precision)
$user->depositCoins(100, \IJIDeals\VirtualCoin\Enums\TransactionType::DEPOSIT_BONUS, [], 'For daily login');
$user->withdrawCoins(50, \IJIDeals\VirtualCoin\Enums\TransactionType::SPEND_ITEM, [], 'For virtual item');

// Usage via WalletService (recommended for most operations, especially transfers and adjustments)
$walletService = app(\IJIDeals\VirtualCoin\Services\WalletService::class);
$walletService->deposit($user, 100, \IJIDeals\VirtualCoin\Enums\TransactionType::DEPOSIT_PURCHASE, [], 'Purchased coins');
$balance = $walletService->getBalance($user); // Returns string
$walletService->transfer($user1, $user2, 25, 'Gift', 'Received gift');
// $walletService->adjustBalance($user, -10, 'Fine for rule violation', $adminUser->id); // Requires admin permission
```

### Services

-   `WalletService`: (`IJIDeals\VirtualCoin\Services\WalletService`) Orchestrates all wallet operations, including deposits, withdrawals, transfers between users, and administrative balance adjustments. It ensures business rules, authorization (for adjustments), and event dispatching are handled correctly.

### Events Dispatched

-   `VirtualCoinTransactionCreated`: After any new `CoinTransaction` is successfully created. Contains the `CoinTransaction` model.
-   `BalanceAdjusted`: After an administrator successfully adjusts a user's balance using `WalletService::adjustBalance()`. Contains the `VirtualCoin` wallet, `CoinTransaction`, adjusted amount, reason, and admin user ID.
-   `TransferOccurred`: After a successful transfer between users via `WalletService::transfer()`. Contains from/to User models, amount, sender/receiver transactions, and reference.

## Configuration

The main configuration file is `config/virtualcoin.php`. Key options include:
-   `default_currency_code`: Symbolic code for the virtual currency (e.g., "VC").
-   `balance_precision`: Number of decimal places for balance storage (used by BCMath scale).
-   `default_transaction_status`: Default status for new transactions.
-   `user_model`: The User model class, dynamically configured.
-   `idempotency_enabled`: Enable/disable idempotency checks.
-   `idempotency_window_minutes`: Time window for idempotency checks.
-   `bcmath_scale`: Scale used for BCMath calculations (ensure it matches or exceeds `balance_precision`).
-   `statuses`: List of available transaction statuses.

## Dependencies

-   **`ijideals/user-management`**: (Or the application's configured User model) To link wallets to users.

## Use Cases

-   **Rewarding Users**: Give coins for specific actions like daily logins, content creation, or referrals.
-   **Tipping**: Allow users to tip creators for their content.
-   **Purchases**: Use as a payment method for virtual goods, services, or to unlock features.
-   **Sponsorships**: A core component for the `sponsorship` package. 
