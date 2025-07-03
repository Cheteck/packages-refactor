<?php

namespace IJIDeals\Social\Http\Resources;

use IJIDeals\Social\Models\Notification;
use Illuminate\Http\Resources\Json\JsonResource; // Added use statement

class NotificationResource extends JsonResource
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
            'type' => $this->type, // This is the Notification class name
            'notifiable_type' => $this->notifiable_type,
            'notifiable_id' => $this->notifiable_id,
            'data' => $this->data, // Changed 'contenu' to 'data'
            'read_at' => $this->read_at, // Changed 'lu' to 'read_at' (timestamp)
            'is_read' => $this->read_at !== null, // Added 'is_read' boolean
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
