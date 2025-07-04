<?php

namespace IJIDeals\AuctionSystem\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AuctionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'auctionable_type' => $this->auctionable_type,
            'auctionable_id' => $this->auctionable_id,
            // 'auctionable' => $this->whenLoaded('auctionable', function() {
                // Here you might want to use a generic resource or specific ones based on auctionable_type
                // For example:
                // if ($this->resource->auctionable instanceof \IJIDeals\IJIProductCatalog\Models\MasterProduct) {
                //     return new \IJIDeals\IJIProductCatalog\Http\Resources\MasterProductResource($this->resource->auctionable);
                // }
                // return $this->resource->auctionable; // Or a default transformation
            // }),
            'starting_price' => $this->starting_price,
            'current_price' => $this->current_price, // This might be the highest bid amount or starting_price if no bids
            'bid_increment_type' => $this->bid_increment_type,
            'bid_increment_value' => $this->bid_increment_value,
            'start_time' => $this->start_time->toISOString(),
            'end_time' => $this->end_time->toISOString(),
            'status' => $this->status, // Enum: PENDING, ACTIVE, ENDED, CANCELLED
            'winner_id' => $this->winner_id,
            // 'winner' => $this->whenLoaded('winner', function() {
                // return new UserResource($this->resource->winner); // Assuming a generic UserResource exists
            // }),
            'bids_count' => $this->whenCounted('bids'),
            // 'bids' => BidResource::collection($this->whenLoaded('bids')), // If you want to include bids
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),

            // Conditional fields based on status or user permissions could be added here
            // For example, 'highest_bid' or 'time_remaining' (calculated attribute on model or here)
            'time_remaining_seconds' => $this->when($this->resource->isActive(), function() {
                return $this->resource->end_time->isFuture() ? $this->resource->end_time->diffInSeconds(now()) : 0;
            }),
            'highest_bid' => $this->when($this->relationLoaded('highestBid'), $this->highestBid ? $this->highestBid->amount : null),


        ];
    }
}
