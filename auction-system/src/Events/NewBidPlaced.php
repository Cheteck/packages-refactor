<?php

namespace IJIDeals\AuctionSystem\Events;

use IJIDeals\AuctionSystem\Models\Auction;
use IJIDeals\AuctionSystem\Models\Bid;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewBidPlaced implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Auction $auction;

    public Bid $bid;

    /**
     * Create a new event instance.
     */
    public function __construct(Auction $auction, Bid $bid)
    {
        $this->auction = $auction;
        $this->bid = $bid;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // Example: private-auction.{auction_id}
        // Ensure channel name matches config and frontend listener
        $channelName = config('auction-system.echo_channels.auction_updates_prefix', 'private-auction.').$this->auction->id;

        return [new PrivateChannel($channelName)];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'new.bid';
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
            'current_price' => $this->auction->current_price,
            'bids_count' => $this->auction->bids_count,
            'end_date' => $this->auction->end_date->toIso8601String(), // Ensure consistent date format
            'bid' => [
                'id' => $this->bid->id,
                'user_id' => $this->bid->user_id,
                'amount' => $this->bid->amount,
                'created_at' => $this->bid->created_at->toIso8601String(),
                // Include bidder's name/avatar if needed, but be mindful of privacy
                // 'bidder_name' => $this->bid->user->name,
            ],
        ];
    }
}
