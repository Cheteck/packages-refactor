<?php

namespace IJIDeals\AuctionSystem\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use IJIDeals\AuctionSystem\Models\Auction;
use IJIDeals\AuctionSystem\Http\Resources\AuctionResource;
use IJIDeals\AuctionSystem\Services\AuctionService; // Import the service
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate; // For potential authorization

class AuctionController extends Controller
{
    protected AuctionService $auctionService;

    public function __construct(AuctionService $auctionService)
    {
        $this->auctionService = $auctionService;
    }

    /**
     * Display a listing of the auctions.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        // Gate::authorize('viewAny', Auction::class); // Optional: if you have a policy

        $auctions = Auction::query();

        // Filtering example: by status
        if ($request->has('status')) {
            $auctions->where('status', $request->status);
        }

        // Filtering example: by auctionable_type (e.g., 'MasterProduct')
        if ($request->has('auctionable_type')) {
            // This requires knowing the morph map or using the fully qualified class name
            // For simplicity, assuming a direct string match for now.
            // In a real app, you might resolve this to the actual class name via a map.
            $auctions->where('auctionable_type', $request->auctionable_type);
        }

        // Eager load relationships that are commonly needed
        $auctions->with(['auctionable', 'highestBid']); // 'highestBid' is an example relation you might define on Auction model

        // Sort by end_time by default, or make it configurable
        $auctions->orderBy('end_time', 'asc');

        $perPage = $request->input('per_page', config('auction-system.pagination_limit', 15));
        $paginatedAuctions = $auctions->paginate($perPage);

        return AuctionResource::collection($paginatedAuctions);
    }

    /**
     * Display the specified auction.
     *
     * @param  \IJIDeals\AuctionSystem\Models\Auction  $auction
     * @return \IJIDeals\AuctionSystem\Http\Resources\AuctionResource
     */
    public function show(Auction $auction)
    {
        // Gate::authorize('view', $auction); // Optional: if you have a policy

        // Eager load relationships needed for the detail view
        $auction->loadMissing(['auctionable', 'bids' => function ($query) {
            $query->orderBy('amount', 'desc')->orderBy('created_at', 'desc'); // Load bids, newest and highest first
        }, 'winner', 'highestBid']);

        return new AuctionResource($auction);
    }
}
