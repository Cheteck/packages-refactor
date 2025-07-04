<?php

namespace IJIDeals\AuctionSystem\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use IJIDeals\AuctionSystem\Models\Auction;
use IJIDeals\AuctionSystem\Http\Requests\StoreBidRequest; // Use the FormRequest
use IJIDeals\AuctionSystem\Http\Resources\BidResource;   // Use the Resource
use IJIDeals\AuctionSystem\Services\AuctionService;      // Use the Service
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log; // For logging errors
use IJIDeals\AuctionSystem\Exceptions\InvalidBidException; // Custom exception from service


class BidController extends Controller
{
    protected AuctionService $auctionService;

    public function __construct(AuctionService $auctionService)
    {
        $this->auctionService = $auctionService;
        // Middleware for authentication is applied at the route level in api.php
        // $this->middleware(config('auction-system.auth_middleware', 'auth:sanctum'));
    }

    /**
     * Store a newly created bid in storage.
     *
     * @param  \IJIDeals\AuctionSystem\Http\Requests\StoreBidRequest  $request
     * @param  \IJIDeals\AuctionSystem\Models\Auction  $auction
     * @return \IJIDeals\AuctionSystem\Http\Resources\BidResource|\Illuminate\Http\JsonResponse
     */
    public function store(StoreBidRequest $request, Auction $auction)
    {
        // Authorization: Check if the user can place a bid on this auction.
        // This could be a policy method like Gate::authorize('placeBid', $auction);
        // Or specific checks here. For now, StoreBidRequest handles basic auth.

        // Additional check: Ensure auction is active (also checked in StoreBidRequest rules, but good for defense in depth)
        if (!$auction->isActive()) {
            return response()->json(['message' => 'Auction is not active.'], 403);
        }

        // Prevent user from bidding on their own auction (if auctionable is owned by a User model)
        // This logic might be better placed within the AuctionService or a Policy.
        if ($auction->auctionable_type === config('auth.providers.users.model') && $auction->auctionable_id === Auth::id()) {
             // A more generic way to get User model, if UserManagement package provides a config for its user model:
             // $userModelClass = config('user-management.model', \App\Models\User::class);
             // if ($auction->auctionable_type === $userModelClass && $auction->auctionable_id === Auth::id()) {
            return response()->json(['message' => 'You cannot bid on your own auction.'], 403);
        }

        $validatedData = $request->validated();
        $user = Auth::user();

        try {
            $bid = $this->auctionService->placeBid(
                $auction,
                $user,
                $validatedData['amount']
            );

            return new BidResource($bid->loadMissing('user')); // Load user relation for the resource

        } catch (InvalidBidException $e) {
            return response()->json(['message' => $e->getMessage()], 422); // Unprocessable Entity for validation-like errors
        } catch (\Exception $e) {
            Log::error("Bid placement failed for auction {$auction->id} by user {$user->id}: " . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Failed to place bid. Please try again.'], 500);
        }
    }
}
