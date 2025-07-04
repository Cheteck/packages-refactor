<?php

namespace IJIDeals\AuctionSystem\Listeners;

use IJIDeals\AuctionSystem\Events\NewBidPlaced;
use IJIDeals\AuctionSystem\Notifications\BidOverbidNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class SendOverbidNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \IJIDeals\AuctionSystem\Events\NewBidPlaced  $event
     * @return void
     */
    public function handle(NewBidPlaced $event)
    {
        $auction = $event->auction;
        $newBid = $event->newBid;

        // Get all bidders for this auction, excluding the new bidder
        $previousBidders = $auction->bids()->where('user_id', '!=', $newBid->user_id)->distinct('user_id')->get()->map(function ($bid) {
            return $bid->user;
        })->filter(); // Filter out null users if any

        foreach ($previousBidders as $bidder) {
            // Only notify if the bidder was actually outbid (i.e., their bid is lower than the new bid)
            // This requires knowing the previous highest bid of this specific bidder.
            // For simplicity, we'll notify all previous bidders for now.
            // A more robust solution would involve tracking each user's highest bid.
            Notification::send($bidder, new BidOverbidNotification($auction, $bidder));
        }
    }
}
