<?php

namespace IJIDeals\Inventory\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InventoryResource extends JsonResource
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
            'stockable_type' => $this->stockable_type, // e.g., "App\\Models\\Product" or a mapped alias
            'stockable_id' => $this->stockable_id,
            'stockable_details' => $this->whenLoaded('stockable', function () {
                // Depending on the stockable_type, you might want to return different details
                // or use a specific resource for the stockable item.
                // This requires knowing the actual model instance of $this->stockable.
                return [
                    'id' => $this->stockable->id,
                    'name' => $this->stockable->name ?? null, // Assuming a 'name' attribute
                    // Add other relevant details of the stockable item
                ];
            }),
            'location_id' => $this->location_id,
            'location' => new InventoryLocationResource($this->whenLoaded('location')),
            'quantity' => (int) $this->quantity,
            'reserved_quantity' => (int) $this->reserved_quantity,
            'available_quantity' => $this->available_quantity, // Accessor from model
            'last_stock_update' => $this->last_stock_update ? $this->last_stock_update->toISOString() : null,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
