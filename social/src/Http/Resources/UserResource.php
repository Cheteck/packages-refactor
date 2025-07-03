<?php

namespace IJIDeals\Social\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'name' => $this->name, // Default 'name' from Laravel User model
            'nom' => $this->nom,     // Custom field
            'prénom' => $this->prénom, // Custom field
            'email' => $this->email,
            'avatar' => $this->avatar, // Custom field
            'bio' => $this->bio,       // Custom field
            'rôle' => $this->rôle,     // Custom field
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
