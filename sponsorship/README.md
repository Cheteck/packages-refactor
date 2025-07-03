# Sponsorship Package

The Sponsorship package provides a dedicated system for managing **Sponsored Posts** and **Post Boosting** campaigns on the IJIDeals platform. It allows users to promote their content to a wider audience based on defined budgets and targeting criteria.

## Core Features

-   **Campaign Creation**: Users can create sponsored post campaigns with defined budgets, cost models (CPI/CPC), targeting, and durations.
-   **Budget Management**: Integration with the `virtualcoin` package for funding campaigns and tracking spent amounts.
-   **Campaign Status Management**: Pause, resume, and cancel campaigns.
-   **Performance Tracking**: Records impressions and clicks for sponsored posts.
-   **Automated Processing**: Scheduled jobs to manage campaign status based on budget exhaustion or end dates.

## Key Components

### Models

-   `SponsoredPost`: Represents a single sponsored post campaign, including budget, targeting, and performance metrics.

### Services

-   `SponsorshipService`: The central service for creating, managing, and processing sponsored post campaigns.

## How It Works

1.  A user funds a sponsored post campaign with virtual coins.
2.  The `SponsorshipService` creates a `SponsoredPost` record.
3.  The system tracks impressions and clicks for the sponsored post, deducting from the budget.
4.  Scheduled jobs monitor campaigns, marking them as `completed` or `exhausted_budget` when conditions are met.

## Dependencies

-   **`ijideals/user-management`**: To identify the user sponsoring the post.
-   **`ijideals/social`**: To link sponsored posts to actual user posts.
-   **`ijideals/virtualcoin`**: For managing campaign budgets and transactions.

## Installation

You can install the package via composer:

```bash
composer require ijideals/sponsorship
```

## Configuration

The package will automatically register its service provider.

## Usage

Detailed documentation on how to use the package's features will be added here.

## Testing

```bash
composer test
```

## Security

If you discover any security-related issues, please email security@ijideals.com instead of using the issue tracker.