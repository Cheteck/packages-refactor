# Analytics Package

The Analytics package is the central nervous system for data collection and reporting on the IJIDeals platform. It provides powerful tools to track user behavior, measure e-commerce performance, and generate insights to drive business decisions.

## Core Features

-   **Event Tracking**: A simple API to track any action or event on the platform (e.g., `page_view`, `product_viewed`, `added_to_cart`).
-   **User Journey Tracking**: Follow the path of users through the application to understand conversion funnels and drop-off points.
-   **E-commerce Analytics**: Deep integration with the `commerce` package to provide key metrics like conversion rate, average order value (AOV), and sales by product.
-   **Data Aggregation**: Asynchronous jobs process raw event data into daily aggregated reports for fast dashboard loading.
-   **Trackable Models**: A trait to easily log all interactions (creates, updates, deletes) with any Eloquent model.

## Key Components

### Models

-   `ActivityLog`: Stores raw event data tracked on the platform.
-   `TrackableInteraction`: A log of every interaction with a `Trackable` model.
-a   `TrackableStatsDaily`: Stores daily aggregated metrics for a `Trackable` model (e.g., number of views per day for a `Product`).

### Traits

-   `TrackableStats`: Add this trait to any model (like `Product`, `Post`, `Shop`) to automatically track and aggregate its views and interactions.

### Jobs

-   `RecordViewJob`: An asynchronous job to record model views without slowing down the user's request.
-   `AggregateAnalyticsJob`: A scheduled job that processes the `ActivityLog` to populate aggregated data tables.

## How It Works

The package provides a simple facade or helper for event tracking: `Analytics::track('event_name', ['property' => 'value']);`. This data is stored in the `ActivityLog`. For high-volume events like product views, the `IsTrackable` trait dispatches a job to handle the recording in the background. A nightly cron job then processes this raw data into daily summaries, ensuring that analytics dashboards remain fast and responsive.

## Dependencies

-   **`ijideals/user-management`**: To associate events with users.
-   This package is a "dependency" for many others, in that they fire events that are captured and processed here. For example, `Commerce` fires `order_placed`, and `Social` fires `post_created`.

## Structure

```
src/
├── Models/           # Analytics models
├── Database/
│   ├── factories/    # Model factories for testing
│   └── migrations/   # Database migrations
├── Providers/        # Service providers
└── Config/          # Package configuration
```

## Models

- PageView
- UserSession
- EventTracker
- Conversion
- TrafficSource
- UserBehavior
- PerformanceMetric
- CustomEvent
- Campaign
- Goal
- Funnel

## Features

- Page view tracking
- User session tracking
- Event tracking
- Conversion tracking
- Traffic source analysis
- User behavior analysis
- Performance metrics
- Custom event tracking
- Campaign tracking
- Goal tracking
- Funnel analysis
- Real-time analytics
- Custom reports
- Data export

## Dependencies

- Laravel Framework ^10.0

## Installation

```bash
composer require ijideals/analytics
```

## Configuration

Publish the configuration:

```bash
php artisan vendor:publish --tag=analytics-config
``` 
