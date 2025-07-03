<?php

namespace IJIDeals\AuctionSystem\Jobs;

use Carbon\Carbon;
use IJIDeals\AuctionSystem\Events\AuctionEnded;
use IJIDeals\AuctionSystem\Models\Auction;
use IJIDeals\AuctionSystem\Models\Bid;
use IJIDeals\AuctionSystem\Services\AuctionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log; // Optional, if complex logic is reused

class DetermineAuctionWinnerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(AuctionService $auctionService): void // Inject AuctionService if needed
    {
        Log::info('DetermineAuctionWinnerJob started running.');

        $now = Carbon::now();

        // Find auctions that have ended and are still active (not yet processed)
        $endedAuctions = Auction::where('status', Auction::STATUS_ACTIVE)
            ->where('end_date', '<=', $now)
            ->get();

        if ($endedAuctions->isEmpty()) {
            Log::info('No ended auctions to process.');

            return;
        }

        foreach ($endedAuctions as $auction) {
            Log::info("Processing auction #{$auction->id} for winner determination.");

            // Find the highest valid bid
            $winningBid = $auction->bids()
                ->where('status', Bid::STATUS_ACTIVE) // Consider only active bids
                ->orderBy('amount', 'desc')
                ->orderBy('created_at', 'asc') // Oldest bid wins in case of a tie in amount
                ->first();

            $winner = null;
            $winningAmount = null;
            $newStatus = Auction::STATUS_ENDED_NO_WINNER; // Default if no bids or reserve not met

            if ($winningBid) {
                // Check if reserve price is met (if set)
                if ($auction->reserve_price && $winningBid->amount < $auction->reserve_price) {
                    $newStatus = Auction::STATUS_ENDED_RESERVE_NOT_MET;
                    Log::info("Auction #{$auction->id}: Winning bid {$winningBid->amount} did not meet reserve price {$auction->reserve_price}.");
                } else {
                    $winner = $winningBid->user;
                    $winningAmount = $winningBid->amount;
                    $newStatus = Auction::STATUS_ENDED_SOLD;

                    // Update the winning bid's status
                    $winningBid->status = Bid::STATUS_WINNER;
                    $winningBid->is_winning = true;
                    $winningBid->save();

                    // Mark other active bids on this auction as lost/outbid (optional, could be done by AuctionService)
                    $auction->bids()
                        ->where('status', Bid::STATUS_ACTIVE)
                        ->where('id', '!=', $winningBid->id)
                        ->update(['status' => Bid::STATUS_OUTBID, 'is_winning' => false]);

                    Log::info("Auction #{$auction->id}: Winner determined - User #{$winner->id} with bid {$winningAmount}.");
                }
            } else {
                Log::info("Auction #{$auction->id}: No active bids found.");
            }

            // Update auction details
            $auction->status = $newStatus;
            if ($winner) {
                $auction->winner_id = $winner->id;
                $auction->winning_bid_amount = $winningAmount;
            }
            $auction->save();

            // Dispatch AuctionEnded event
            event(new AuctionEnded($auction, $winner, $winningAmount));
            Log::info("AuctionEnded event dispatched for auction #{$auction->id}. Status: {$newStatus}");
        }

        Log::info('DetermineAuctionWinnerJob finished processing '.$endedAuctions->count().' auctions.');
    }
}
