<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Analytics Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for the IJIDeals Analytics package.
    |
    */

    'view_dedupe_minutes' => 5, // Time in minutes to deduplicate views from the same IP/user

    'interaction_cache_hours' => 24, // How long to cache aggregated interaction data

    'queue' => [
        'record_view_job' => 'default', // Queue connection/name for RecordViewJob
        'aggregate_analytics_job' => 'long-running', // Queue connection/name for AggregateAnalyticsJob
    ],

    'aggregation' => [
        'batch_size' => 1000, // Number of records to process per batch in aggregation jobs
        'frequency' => 'daily', // How often aggregation jobs should run (e.g., 'daily', 'hourly')
        // Define specific interaction types to aggregate into dedicated columns if needed
        'specific_interaction_types' => [
            // 'like', 'comment', 'share', 'purchase',
        ],
    ],

    'features' => [
        'track_views' => true,
        'track_interactions' => true,
        'enable_activity_logging' => true,
    ],

    'engagement_score' => [
        'weights' => [
            'view' => 1,
            'click' => 5,
            'comment' => 10,
            'purchase' => 50,
            // Add more interaction types and their weights
        ],
    ],

    // Mapping for polymorphic relationships or custom parent model detection
    'parent_model_map' => [
        // 'post' => \IJIDeals\Social\Models\Post::class,
        // 'product' => \IJIDeals\IJICommerce\Models\ijicommerce\Product::class,
        // 'user' => \IJIDeals\UserManagement\Models\User::class,
    ],

    // Table names (if you need to override defaults)
    'table_names' => [
        'activity_logs' => 'activity_logs',
        'trackable_views' => 'trackable_views',
        'trackable_interactions' => 'trackable_interactions',
        'trackable_stats_daily' => 'trackable_stats_daily',
    ],
];
