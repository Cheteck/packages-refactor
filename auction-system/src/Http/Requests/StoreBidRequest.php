<?php

namespace IJIDeals\AuctionSystem\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use IJIDeals\AuctionSystem\Models\Auction;

class StoreBidRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Ensure the user is authenticated
        if (!Auth::check()) {
            return false;
        }

        // Further authorization can be done here or in the controller/policy
        // For example, check if the auction is active or if the user is allowed to bid.
        // $auction = $this->route('auction'); // Get the auction from the route model binding
        // if ($auction && !$auction->isActive()) {
        //     return false; // Or throw a specific AuthorizationException
        // }

        return true; // Basic authentication check, more specific checks in controller/policy
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // Get the auction from the route model binding to use its current price for validation
        $auction = $this->route('auction');
        $minBid = 0;

        if ($auction instanceof Auction) {
            $currentHighestBid = $auction->bids()->max('amount') ?? 0;
            $startingPrice = $auction->starting_price;
            $effectiveCurrentPrice = max($currentHighestBid, $startingPrice);

            // Simple increment for now, could be more complex based on bid_increment_type/value
            // This logic might be better suited in the AuctionService or Auction model itself.
            // For now, a basic +1 minimal increment or a configured fixed minimum.
            $minIncrement = config('auction-system.minimum_bid_increment', 1);
            $minBid = $effectiveCurrentPrice + $minIncrement;
        }


        return [
            'amount' => [
                'required',
                'numeric',
                // Ensure the bid amount is greater than the current highest bid + increment, or starting price + increment
                // This rule can become complex. The AuctionService should be the ultimate source of truth for bid validity.
                // 'min:' . $minBid, // This provides a basic client-side hint
                function ($attribute, $value, $fail) use ($auction, $minBid) {
                    if (!$auction instanceof Auction) {
                        // This case should ideally not happen if route model binding works
                        return $fail('The auction is invalid.');
                    }
                    if (!$auction->isActive()) {
                        return $fail('This auction is not currently active.');
                    }
                    // More robust check against the service or model method
                    // if (!$auction->isValidNextBidAmount($value)) {
                    //    return $fail('The bid amount is not valid for the current auction state.');
                    // }
                    // Simplified check for now:
                    if (bccomp((string)$value, (string)$minBid, 2) < 0) {
                         return $fail("The bid must be at least {$minBid}.");
                    }
                },
            ],
            // You might add other fields here, e.g., 'max_auto_bid_amount' if implementing auto-bidding
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'The bid amount is required.',
            'amount.numeric' => 'The bid amount must be a number.',
            // 'amount.min' => 'The bid amount must be at least :min.', // If using the min rule directly
        ];
    }
}
