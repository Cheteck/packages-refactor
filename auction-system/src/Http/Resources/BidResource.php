<?php

namespace IJIDeals\AuctionSystem\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
// Assuming UserResource exists in a shared location or app space
// use App\Http\Resources\UserResource;

class BidResource extends JsonResource
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
            'auction_id' => $this->auction_id,
            'user_id' => $this->user_id,
            // 'user' => new UserResource($this->whenLoaded('user')),
            'amount' => $this->amount,
            'is_winning' => $this->when(isset($this->resource->is_winning), (bool) $this->is_winning), // If calculated and added to model
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
