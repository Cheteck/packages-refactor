<?php

namespace IJIDeals\AuctionSystem\Events;

use IJIDeals\AuctionSystem\Models\Auction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AuctionEnded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Auction $auction;

    /**
     * Create a new event instance.
     */
    public function __construct(Auction $auction)
    {
        $this->auction = $auction;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channelName = config('auction-system.echo_channels.auction_updates_prefix', 'private-auction.').$this->auction->id;

        return [
            new PrivateChannel($channelName),
            // Optionally, broadcast to the winner and creator on their private user channels
            // new PrivateChannel('App.Models.User.' . $this->auction->creator_id),
            // if ($this->auction->winner_id) {
            //    new PrivateChannel('App.Models.User.' . $this->auction->winner_id),
            // }
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'auction.ended';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'auction_id' => $this->auction->id,
            'status' => $this->auction->status,
            'winner_id' => $this->auction->winner_id,
            'winning_bid_amount' => $this->auction->winning_bid_amount,
            'product_id' => $this->auction->product_id,
            // 'winner_name' => $this->auction->winner ? $this->auction->winner->name : null, // Be mindful of privacy
        ];
    }
}
