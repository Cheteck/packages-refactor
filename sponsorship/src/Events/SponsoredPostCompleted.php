<?php

namespace IJIDeals\Sponsorship\Events;

use IJIDeals\Sponsorship\Models\SponsoredPost;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SponsoredPostCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public SponsoredPost $sponsoredPost;

    public function __construct(SponsoredPost $sponsoredPost)
    {
        $this->sponsoredPost = $sponsoredPost;
    }
}
