<?php

namespace IJIDeals\AuctionSystem\Listeners;

use IJIDeals\AuctionSystem\Events\AuctionEnded;
use IJIDeals\AuctionSystem\Notifications\AuctionEndedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class SendAuctionEndedNotification implements ShouldQueue
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
     * @param  \IJIDeals\AuctionSystem\Events\AuctionEnded  $event
     * @return void
     */
    public function handle(AuctionEnded $event)
    {
        $auction = $event->auction;

        // Notify the winner
        if ($auction->winner_id) {
            $winner = $auction->winner;
            if ($winner) {
                Notification::send($winner, new AuctionEndedNotification($auction, $winner, true));
            }
        }

        // Notify other bidders (losers)
        $bidders = $auction->bids()->distinct('user_id')->get()->map(function ($bid) {
            return $bid->user;
        })->filter(function ($user) use ($auction) {
            return $user->id !== $auction->winner_id; // Exclude the winner
        });

        foreach ($bidders as $bidder) {
            Notification::send($bidder, new AuctionEndedNotification($auction, $bidder, false));
        }
    }
}
