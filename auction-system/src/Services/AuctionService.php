<?php

namespace IJIDeals\AuctionSystem\Services;

use Carbon\Carbon;
use Exception;
use IJIDeals\AuctionSystem\Events\AuctionEnded; // Adjust to your User model
use IJIDeals\AuctionSystem\Events\NewBidPlaced;    // Adjust to your Product model
use IJIDeals\AuctionSystem\Models\Auction;
use IJIDeals\AuctionSystem\Models\Bid; // To be created
use IJIDeals\IJICommerce\Models\Product;
use IJIDeals\UserManagement\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

class AuctionService
{
    /**
     * Create a new auction.
     */
    public function createAuction(array $data, User $creator, Product $product): Auction
    {
        // Basic validation, more robust validation should be in a FormRequest
        if (Carbon::parse($data['start_date']) >= Carbon::parse($data['end_date'])) {
            throw new Exception('End date must be after start date.');
        }
        if ($data['starting_price'] < 0) {
            throw new Exception('Starting price cannot be negative.');
        }
        // Add more validation as needed (reserve price, bid increment)

        return Auction::create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'product_id' => $product->id,
            'creator_id' => $creator->id,
            'starting_price' => $data['starting_price'],
            'current_price' => $data['starting_price'], // Initially current price is starting price
            'reserve_price' => $data['reserve_price'] ?? null,
            'bid_increment_amount' => $data['bid_increment_amount'] ?? config('auction-system.bid_increment.amount', 1.00),
            'start_date' => Carbon::parse($data['start_date']),
            'end_date' => Carbon::parse($data['end_date']),
            'status' => Auction::STATUS_PENDING, // Or determine based on start_date
            'auto_extend_on_bid' => $data['auto_extend_on_bid'] ?? config('auction-system.anti_sniping.enabled', false),
            'extension_time_minutes' => $data['extension_time_minutes'] ?? config('auction-system.anti_sniping.extension_time_minutes', 5),
            'settings' => $data['settings'] ?? [],
        ]);
    }

    /**
     * Place a bid on an auction.
     *
     * @throws Exception
     */
    public function placeBid(Auction $auction, User $bidder, float $amount, bool $isAutoBid = false, ?float $maxAutoBidAmount = null): Bid
    {
        return DB::transaction(function () use ($auction, $bidder, $amount, $isAutoBid, $maxAutoBidAmount) {
            // Reload auction with lock for update to prevent race conditions
            $auction = Auction::lockForUpdate()->findOrFail($auction->id);

            if ($auction->status !== Auction::STATUS_ACTIVE) {
                throw new Exception('Auction is not active.');
            }
            if (now()->lt($auction->start_date) || now()->gt($auction->end_date)) {
                // This check is partly covered by status, but good for explicitness before end_date check during anti-sniping
                throw new Exception('Auction is not within its bidding window.');
            }
            if ($bidder->id === $auction->creator_id) {
                throw new Exception('Creator cannot bid on their own auction.');
            }

            $minNextBid = $auction->current_price + $auction->bid_increment_amount;
            if ($amount < $minNextBid) {
                throw new Exception("Bid amount must be at least {$minNextBid}.");
            }

            // Handle auto-bidding logic if this is a manual bid against an existing auto-bid
            // Or if this is an auto-bid itself. This can get complex.
            // For now, simplified: assumes manual bids or initial auto-bid placement.

            $newBid = $auction->bids()->create([
                'user_id' => $bidder->id,
                'amount' => $amount,
                'is_auto_bid' => $isAutoBid,
                'max_auto_bid_amount' => $isAutoBid ? $maxAutoBidAmount : null,
                'status' => Bid::STATUS_ACTIVE, // Initially active
            ]);

            $auction->current_price = $amount;
            $auction->increment('bids_count');

            // Anti-sniping: Extend auction if configured and bid is near end time
            if ($auction->auto_extend_on_bid &&
                now()->gte(Carbon::parse($auction->end_date)->subMinutes(config('auction-system.anti_sniping.bid_within_minutes', 5)))) {
                $auction->end_date = Carbon::parse($auction->end_date)->addMinutes($auction->extension_time_minutes);
            }

            $auction->save();

            // Mark previous winning bids as outbid (if any, simplistic for now)
            $auction->bids()->where('is_winning', true)->where('id', '!=', $newBid->id)->update(['is_winning' => false, 'status' => Bid::STATUS_OUTBID]);
            $newBid->update(['is_winning' => true]); // Current new bid is the winning one

            Event::dispatch(new NewBidPlaced($auction, $newBid));

            return $newBid;
        });
    }

    /**
     * Process an auction to determine its winner.
     * Typically called by a scheduled job (DetermineAuctionWinnerJob).
     *
     * @return Auction The updated auction instance.
     */
    public function determineWinner(Auction $auction): Auction
    {
        return DB::transaction(function () use ($auction) {
            // Reload auction with lock for update
            $auction = Auction::lockForUpdate()->findOrFail($auction->id);

            if ($auction->status !== Auction::STATUS_ACTIVE || now()->lt(Carbon::parse($auction->end_date))) {
                // Not active or not yet ended (allowing for extensions)
                return $auction;
            }

            $winningBid = $auction->bids()
                ->where('status', Bid::STATUS_ACTIVE) // Or consider other relevant statuses
                ->orderBy('amount', 'desc')
                ->orderBy('created_at', 'asc') // First highest bid wins
                ->first();

            if ($winningBid) {
                if ($auction->reserve_price && $winningBid->amount < $auction->reserve_price) {
                    $auction->status = Auction::STATUS_ENDED_RESERVE_NOT_MET;
                    $auction->winner_id = null;
                    $auction->winning_bid_amount = null;
                    // Mark all bids as non-winning or some other status
                    $auction->bids()->update(['is_winning' => false]);
                } else {
                    $auction->status = Auction::STATUS_ENDED_SOLD;
                    $auction->winner_id = $winningBid->user_id;
                    $auction->winning_bid_amount = $winningBid->amount;
                    // Ensure only the actual winning bid is marked as winner
                    $auction->bids()->where('id', '!=', $winningBid->id)->update(['is_winning' => false]);
                    $winningBid->update(['status' => Bid::STATUS_WINNER, 'is_winning' => true]);
                }
            } else {
                $auction->status = Auction::STATUS_ENDED_NO_WINNER;
                $auction->winner_id = null;
                $auction->winning_bid_amount = null;
            }

            $auction->save();

            Event::dispatch(new AuctionEnded($auction));

            return $auction;
        });
    }

    // TODO: Add methods for auto-bidding logic if a new bid triggers outbidding by an existing auto-bidder.
    // TODO: Add methods for managing auction statuses (e.g., activate, cancel).
}
