<?php

namespace IJIDeals\Social\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewInteraction
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $interaction;

    /**
     * Create a new event instance.
     *
     * @param  mixed  $interaction
     * @return void
     */
    public function __construct($interaction)
    {
        $this->interaction = $interaction;
    }
}
