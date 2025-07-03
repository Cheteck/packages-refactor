<?php

namespace IJIDeals\Social\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReactionRemoved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $reaction;

    /**
     * Create a new event instance.
     *
     * @param  mixed  $reaction
     * @return void
     */
    public function __construct($reaction)
    {
        $this->reaction = $reaction;
    }
}
