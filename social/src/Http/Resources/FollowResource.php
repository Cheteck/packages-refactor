<?php

namespace IJIDeals\Social\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FollowResource extends JsonResource
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
            'follower' => UserResource::make($this->whenLoaded('follower')),
            'followed' => UserResource::make($this->whenLoaded('followed')),
            'created_at' => $this->created_at,
        ];
    }
}
