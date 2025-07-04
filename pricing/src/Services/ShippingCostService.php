<?php

namespace IJIDeals\Pricing\Services;

use Illuminate\Support\Collection;
use IJIDeals\UserManagement\Models\User;
use IJIDeals\Location\Models\Address;
use IJIDeals\Pricing\Models\ShippingRule;

class ShippingCostService
{
    /**
     * Calculate the shipping cost for a given set of items and user.
     *
     * @param Collection $items Collection of items, each with 'priceable' (product) and 'quantity'.
     * @param User|null $user The user for whom to calculate shipping.
     * @param string $currencyCode The currency code for the calculation.
     * @return float The calculated shipping cost.
     */
    public function calculateShippingCost(Collection $items, ?User $user, string $currencyCode): float
    {
        if (!$user || $items->isEmpty()) {
            return 0.0;
        }

        // Get user's shipping address
        $userShippingAddress = $user->addresses()->where('type', 'shipping')->first();

        if (!$userShippingAddress) {
            // Fallback to primary address if no specific shipping address
            $userShippingAddress = $user->primaryAddress();
        }

        if (!$userShippingAddress) {
            return 0.0; // Cannot calculate shipping without user address
        }

        // Get seller's/product's origin address (assuming first item's seller's primary address)
        $firstItemPriceable = $items->first()['priceable'];
        $sellerOriginAddress = null;

        // This part is highly dependent on your actual product/seller model structure.
        // Assuming 'priceable' (e.g., Product) has a 'seller' relationship, and seller has addresses.
        if (method_exists($firstItemPriceable, 'seller') && $firstItemPriceable->seller) {
            $sellerOriginAddress = $firstItemPriceable->seller->primaryAddress();
        }

        if (!$sellerOriginAddress) {
            return 0.0; // Cannot calculate shipping without seller origin address
        }

        $shippingCost = 0.0;

        // Fetch active shipping rules, ordered by priority
        $shippingRules = ShippingRule::where('is_active', true)->orderBy('priority', 'asc')->get();

        foreach ($shippingRules as $rule) {
            switch ($rule->rule_type) {
                case 'by_country':
                    if ($userShippingAddress->country_id === $sellerOriginAddress->country_id) {
                        $shippingCost = $this->applyRuleCost($rule, $items->sum('quantity'));
                        return $shippingCost; // Apply first matching rule and exit
                    }
                    break;
                case 'by_distance':
                    $distance = Address::calculateDistance($userShippingAddress, $sellerOriginAddress);
                    if ($distance > 0 && 
                        ($rule->parameters['min_distance'] ?? 0) <= $distance && 
                        ($rule->parameters['max_distance'] ?? PHP_INT_MAX) >= $distance) {
                        
                        $shippingCost = $this->applyRuleCost($rule, $distance);
                        return $shippingCost; // Apply first matching rule and exit
                    }
                    break;
                // Add more rule types as needed (e.g., 'by_zone', 'by_weight')
            }
        }

        return $shippingCost;
    }

    /**
     * Apply the cost based on the rule type.
     *
     * @param ShippingRule $rule
     * @param float $value The value to apply the cost against (e.g., quantity, distance).
     * @return float
     */
    protected function applyRuleCost(ShippingRule $rule, float $value): float
    {
        switch ($rule->cost_type) {
            case 'fixed':
                return $rule->cost_value;
            case 'per_item':
                return $rule->cost_value * $value; // $value is quantity here
            case 'per_km':
                return $rule->cost_value * $value; // $value is distance here
            // Add more cost types as needed (e.g., 'per_kg')
            default:
                return 0.0;
        }
    }
}

