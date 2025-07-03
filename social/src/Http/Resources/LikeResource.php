<?php

namespace IJIDeals\Social\Http\Resources;

use IJIDeals\Social\Models\Reaction;
// Assuming UserResource is correctly namespaced, e.g., use App\Http\Resources\UserResource;
use Illuminate\Http\Resources\Json\JsonResource; // Added use statement for the Reaction model

// Assuming UserResource is correctly namespaced, e.g., use App\Http\Resources\UserResource;

class LikeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user' => UserResource::make($this->whenLoaded('user')), // Assuming IJIDeals\Social\Models\Reaction has a 'user' relationship
            'interactable_id' => $this->interactable_id, // Changed 'post_id' to 'interactable_id'
            'interactable_type' => $this->interactable_type, // Added 'interactable_type'
            'reaction_type' => $this->type, // Added reaction type (e.g., 'like')
            'created_at' => $this->created_at,
        ];
    }
}
