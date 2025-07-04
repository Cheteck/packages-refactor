<?php

namespace IJIDeals\Analytics\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PlatformSummaryStatsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        // The $this->resource here is expected to be an associative array
        // or an object containing the summary data prepared by the controller.
        return [
            'period' => [
                'start_date' => $this->resource['period_start_date'] ?? null,
                'end_date' => $this->resource['period_end_date'] ?? null,
            ],
            'total_views' => $this->resource['total_views'] ?? 0,
            'total_unique_visitors' => $this->resource['total_unique_visitors'] ?? 0, // Example field
            'top_events' => $this->resource['top_events'] ?? [], // Expects an array of event summaries
            'active_users' => $this->resource['active_users'] ?? 0, // Example: Daily/Monthly Active Users
            // Add other platform-wide summary statistics as needed
        ];
    }
}
