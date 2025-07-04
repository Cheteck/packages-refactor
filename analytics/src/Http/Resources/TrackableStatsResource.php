<?php

namespace IJIDeals\Analytics\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TrackableStatsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        // This resource is intended for TrackableStatsDaily model instances
        return [
            'date' => $this->date->toDateString(), // Assuming 'date' is a Carbon instance
            'views_count' => (int) $this->views_count,
            'likes_count' => (int) $this->likes_count,
            'shares_count' => (int) $this->shares_count,
            'comments_count' => (int) $this->comments_count,
            'engagement_score' => (float) $this->engagement_score,
            'interaction_details' => $this->interaction_details, // This is an array/JSON
            // We don't typically include trackable_id and trackable_type here
            // because the context is already the specific trackable model.
        ];
    }
}
