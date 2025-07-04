<?php

namespace IJIDeals\Analytics\Services;

use IJIDeals\Analytics\Models\ActivityLog;

class AnalyticsService
{
    /**
     * Track an event.
     *
     * @param string $eventName
     * @param array $properties
     * @param int|null $userId
     * @return void
     */
    public function track(string $eventName, array $properties = [], ?int $userId = null): void
    {
        ActivityLog::create([
            'event_name' => $eventName,
            'properties' => $properties,
            'user_id' => $userId,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    // Add methods for retrieving aggregated data here later
}
