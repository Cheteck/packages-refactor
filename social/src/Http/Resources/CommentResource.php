<?php

namespace IJIDeals\Social\Http\Resources;

use IJIDeals\Social\Models\Comment;
use Illuminate\Http\Resources\Json\JsonResource; // Added use statement for the moved Comment model

// Assuming UserResource is correctly namespaced, e.g., use App\Http\Resources\UserResource;

class CommentResource extends JsonResource
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
            'content' => $this->content, // Changed 'contenu' to 'content'
            'author' => UserResource::make($this->whenLoaded('author')), // Changed 'user' to 'author'
            'commentable_id' => $this->commentable_id, // Changed 'post_id' to 'commentable_id'
            'commentable_type' => $this->commentable_type, // Added 'commentable_type'
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
