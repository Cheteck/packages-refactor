<?php

namespace IJIDeals\Inventory\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
// Assuming UserResource might be needed
// use App\Http\Resources\UserResource;

class StockMovementResource extends JsonResource
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
            'stockable_type' => $this->stockable_type,
            'stockable_id' => $this->stockable_id,
            'stockable_details' => $this->whenLoaded('stockable', function () {
                return [
                    'id' => $this->stockable->id,
                    'name' => $this->stockable->name ?? null, // Assuming a 'name' attribute
                ];
            }),
            'location_id' => $this->location_id,
            'location' => new InventoryLocationResource($this->whenLoaded('location')),
            'inventory_id' => $this->inventory_id,
            'user_id' => $this->user_id,
            // 'user' => new UserResource($this->whenLoaded('user')),
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'reference_details' => $this->whenLoaded('reference', function () {
                // Details of the reference (e.g., Order number, ReturnRequest reason)
                return [
                    'id' => $this->reference->id,
                    // Add other relevant details based on reference_type
                ];
            }),
            'type' => $this->type,
            'quantity_change' => (int) $this->quantity_change,
            'quantity_before' => (int) $this->quantity_before,
            'quantity_after' => (int) $this->quantity_after,
            'description' => $this->description,
            'created_at' => $this->created_at->toISOString(),
            // StockMovement typically doesn't have updated_at, but if it does:
            // 'updated_at' => $this->updated_at ? $this->updated_at->toISOString() : null,
        ];
    }
}
