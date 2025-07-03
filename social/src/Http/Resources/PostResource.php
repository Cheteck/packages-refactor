<?php

namespace IJIDeals\Social\Http\Resources;

use IJIDeals\Social\Models\Post;
use Illuminate\Http\Resources\Json\JsonResource; // Added use statement for the moved Post model

// Assuming UserResource is correctly namespaced, e.g., use App\Http\Resources\UserResource;

class PostResource extends JsonResource
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
            'content' => $this->content,
            'type' => $this->type->value, // Assuming type is an enum
            'visibility' => $this->visibility->value, // Assuming visibility is an enum
            'status' => $this->status,
            'author' => UserResource::make($this->whenLoaded('author')), // Changed 'user' to 'author'
            'comments_count' => $this->whenCounted('comments'),
            'reactions_count' => $this->whenCounted('reactions'), // Changed likes_count to reactions_count
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
