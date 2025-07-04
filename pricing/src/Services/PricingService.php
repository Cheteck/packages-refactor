<?php

namespace IJIDeals\Pricing\Services;

use IJIDeals\Pricing\Models\Currency;
use IJIDeals\Pricing\Models\Price;
use IJIDeals\Pricing\Models\Discount;
use IJIDeals\Pricing\Models\DiscountRule;
use IJIDeals\Pricing\Models\ExchangeRate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use IJIDeals\UserManagement\Models\User;
use IJIDeals\Pricing\Services\ShippingCostService; // Added

class PricingService
{
    protected $shippingCostService; // Added

    public function __construct(ShippingCostService $shippingCostService) // Added
    {
        $this->shippingCostService = $shippingCostService; // Added
    }

    /**
     * Get the applicable price for a given priceable item, quantity, user, and currency.
     *
     * @param Model $priceable
     * @param int $quantity
     * @param Currency|string|null $currency
     * @param User|null $user
     * @param string|null $priceType
     * @return Price|null
     */
    public function getItemPrice(
        Model $priceable,
        int $quantity = 1,
        Currency|string $currency = null,
        User $user = null,
        string $priceType = null
    ): ?Price {
        $currencyCode = $currency instanceof Currency ? $currency->code : ($currency ?? config('pricing.currency.default', 'USD'));

        $query = $priceable->prices()
            ->where('currency_code', $currencyCode)
            ->where('min_quantity', '<=', $quantity)
            ->active()
            ->orderBy('min_quantity', 'desc');

        if ($priceType) {
            $query->ofType($priceType);
        }

        $price = $query->orderBy('amount', 'asc')->first();

        if ($user && method_exists($user, 'pricingTier') && $user->pricingTier) {
            $tierPrice = $priceable->prices()
                ->where('currency_code', $currencyCode)
                ->where('min_quantity', '<=', $quantity)
                ->where('pricing_tier_id', $user->pricingTier->id)
                ->active()
                ->orderBy('min_quantity', 'desc')
                ->orderBy('amount', 'asc')
                ->first();

            if ($tierPrice && (!$price || $tierPrice->amount < $price->amount)) {
                $price = $tierPrice;
            }
        }

        return $price;
    }

    /**
     * Calculate the total price for a collection of items (e.g., a shopping cart).
     *
     * @param Collection $items
     * @param Currency|string|null $currency
     * @param User|null $user
     * @param string|null $couponCode
     * @return array
     */
    public function calculateCartTotal(
        Collection $items,
        Currency|string $currency = null,
        User $user = null,
        string $couponCode = null
    ): array {
        $currencyCode = $currency instanceof Currency ? $currency->code : ($currency ?? config('pricing.currency.default', 'USD'));
        $subtotal = 0;
        $lineItemsDetails = [];

        foreach ($items as $item) {
            $priceable = $item['priceable'];
            $quantity = $item['quantity'];

            $priceModel = $this->getItemPrice($priceable, $quantity, $currencyCode, $user);
            $lineTotal = 0;
            if ($priceModel) {
                $lineTotal = $priceModel->amount * $quantity;
            }
            $subtotal += $lineTotal;
            $lineItemsDetails[] = [
                'item' => $priceable,
                'quantity' => $quantity,
                'unit_price' => $priceModel ? $priceModel->amount : 0,
                'line_total' => $lineTotal
            ];
        }

        $applicableDiscounts = $this->getApplicableDiscounts($items, $user, $subtotal, $couponCode);
        $discountAmount = $this->applyDiscountsToSubtotal($subtotal, $applicableDiscounts, $items, $user);

        $totalAfterDiscounts = $subtotal - $discountAmount;

        $taxDetails = $this->calculateTaxes($totalAfterDiscounts, $currencyCode, $user);
        $totalTaxAmount = array_sum(array_column($taxDetails, 'amount'));

        // Calculate shipping cost
        $shippingCost = $this->shippingCostService->calculateShippingCost($items, $user, $currencyCode); // Added

        $finalTotal = $totalAfterDiscounts + $totalTaxAmount + $shippingCost; // Modified

        return [
            'subtotal' => round($subtotal, 2),
            'discount_amount' => round($discountAmount, 2),
            'applied_discounts' => $applicableDiscounts->pluck('name', 'code')->all(),
            'taxes' => $taxDetails,
            'total_tax_amount' => round($totalTaxAmount, 2),
            'shipping_cost' => round($shippingCost, 2), // Added
            'total' => round($finalTotal, 2),
            'currency_code' => $currencyCode,
            'line_items' => $lineItemsDetails,
        ];
    }

    /**
     * Get applicable discounts for the given context (cart items, user, subtotal, coupon).
     *
     * @param Collection $items
     * @param User|null $user
     * @param float $subtotal
     * @param string|null $couponCode
     * @return Collection
     */
    public function getApplicableDiscounts(Collection $items, ?User $user, float $subtotal, ?string $couponCode): Collection
    {
        $query = Discount::active();

        if ($couponCode) {
            $query->where('code', $couponCode);
        } else {
            $query->whereNull('code');
        }

        $potentialDiscounts = $query->orderBy('priority', 'desc')->get();
        $applicableDiscounts = new Collection();
        $appliedNonCombinable = false;

        foreach ($potentialDiscounts as $discount) {
            if (!$discount->isValid()) continue;
            if ($user && !$discount->canBeUsedBy($user)) continue;

            if ($this->checkDiscountRules($discount, $items, $user, $subtotal)) {
                if (!$discount->is_combinable) {
                    if ($appliedNonCombinable) continue;
                    $applicableDiscounts = new Collection([$discount]);
                    $appliedNonCombinable = true;
                    break;
                } else {
                    if (!$appliedNonCombinable || ($appliedNonCombinable && $discount->is_combinable)) {
                        $applicableDiscounts->push($discount);
                    }
                }
            }
        }

        if ($applicableDiscounts->first() && !$applicableDiscounts->first()->is_combinable && $applicableDiscounts->count() > 1) {
            return new Collection([$applicableDiscounts->first()]);
        }

        return $applicableDiscounts;
    }

    /**
     * Check if all rules for a discount are met.
     *
     * @param Discount $discount
     * @param Collection $items
     * @param User|null $user
     * @param float $subtotal
     * @return bool
     */
    protected function checkDiscountRules(Discount $discount, Collection $items, ?User $user, float $subtotal): bool
    {
        if ($discount->rules->isEmpty()) {
            return true;
        }

        $passedRules = 0;
        foreach ($discount->rules as $rule) {
            if ($this->evaluateRule($rule, $items, $user, $subtotal)) {
                $passedRules++;
            }
        }

        if ($discount->conditions_match_type === 'all') {
            return $passedRules === $discount->rules->count();
        } elseif ($discount->conditions_match_type === 'any') {
            return $passedRules > 0;
        }
        return false;
    }

    /**
     * Evaluate a single discount rule.
     *
     * @param DiscountRule $rule
     * @param Collection $items
     * @param User|null $user
     * @param float $subtotal
     * @return bool
     */
    protected function evaluateRule(DiscountRule $rule, Collection $items, ?User $user, float $subtotal): bool
    {
        switch ($rule->rule_type) {
            case 'cart_subtotal_min':
                return $subtotal >= ($rule->parameters['min_amount'] ?? 0);
            case 'cart_item_count_min':
                return $items->sum('quantity') >= ($rule->parameters['min_count'] ?? 0);
            case 'customer_group_is':
                if (!$user || !isset($rule->parameters['group_id'])) return false;
                // Implement group check logic here if available
                return false;
            case 'product_in_cart':
                if (!isset($rule->parameters['product_ids']) || !is_array($rule->parameters['product_ids'])) return false;
                foreach ($items as $item) {
                    if (in_array($item['priceable']->getKey(), $rule->parameters['product_ids'])) {
                        return true;
                    }
                }
                return false;
            case 'category_in_cart':
                if (!isset($rule->parameters['category_ids']) || !is_array($rule->parameters['category_ids'])) return false;
                foreach ($items as $item) {
                    if (method_exists($item['priceable'], 'categories') && $item['priceable']->categories->pluck('id')->intersect($rule->parameters['category_ids'])->isNotEmpty()) {
                        return true;
                    }
                }
                return false;
            default:
                return false;
        }
    }

    /**
     * Apply a collection of discounts to a subtotal.
     *
     * @param float $subtotal
     * @param Collection $discounts
     * @param Collection $cartItems
     * @param User|null $user
     * @return float
     */
    protected function applyDiscountsToSubtotal(float $subtotal, Collection $discounts, Collection $cartItems, ?User $user): float
    {
        $totalDiscountAmount = 0;

        $sortedDiscounts = $discounts->sortBy('priority');

        foreach ($sortedDiscounts as $discount) {
            $amountToDiscountFrom = $subtotal;
            $discountValue = 0;
            switch ($discount->type) {
                case 'percentage':
                    $discountValue = ($amountToDiscountFrom * ($discount->value['percentage'] / 100));
                    break;
                case 'fixed_amount':
                    $discountValue = $discount->value['amount'];
                    break;
            }
            $discountValue = min($discountValue, $amountToDiscountFrom);
            $totalDiscountAmount += $discountValue;
        }

        return min($totalDiscountAmount, $subtotal);
    }

    /**
     * Calculate taxes.
     *
     * @param float $amount
     * @param string $currencyCode
     * @param User|null $user
     * @return array
     */
    protected function calculateTaxes(float $amount, string $currencyCode, ?User $user): array
    {
        $taxRate = config('pricing.tax.default_rate', 0.10);
        $taxAmount = $amount * $taxRate;
        return [['name' => 'VAT', 'rate' => $taxRate, 'amount' => round($taxAmount, 2)]];
    }

    /**
     * Create a new Currency.
     *
     * @param array $data
     * @return Currency
     */
    public function createCurrency(array $data): Currency
    {
        return Currency::create($data);
    }

    /**
     * Update an existing Currency.
     *
     * @param Currency $currency
     * @param array $data
     * @return Currency
     */
    public function updateCurrency(Currency $currency, array $data): Currency
    {
        $currency->update($data);
        return $currency;
    }

    /**
     * Delete a Currency.
     *
     * @param Currency $currency
     * @return bool|null
     */
    public function deleteCurrency(Currency $currency): ?bool
    {
        return $currency->delete();
    }

    /**
     * Create a new Price.
     *
     * @param array $data
     * @return Price
     */
    public function createPrice(array $data): Price
    {
        return Price::create($data);
    }

    /**
     * Update an existing Price.
     *
     * @param Price $price
     * @param array $data
     * @return Price
     */
    public function updatePrice(Price $price, array $data): Price
    {
        $price->update($data);
        return $price;
    }

    /**
     * Delete a Price.
     *
     * @param Price $price
     * @return bool|null
     */
    public function deletePrice(Price $price): ?bool
    {
        return $price->delete();
    }

    /**
     * Create a new Discount.
     *
     * @param array $data
     * @return Discount
     */
    public function createDiscount(array $data): Discount
    {
        return Discount::create($data);
    }

    /**
     * Update an existing Discount.
     *
     * @param Discount $discount
     * @param array $data
     * @return Discount
     */
    public function updateDiscount(Discount $discount, array $data): Discount
    {
        $discount->update($data);
        return $discount;
    }

    /**
     * Delete a Discount.
     *
     * @param Discount $discount
     * @return bool|null
     */
    public function deleteDiscount(Discount $discount): ?bool
    {
        return $discount->delete();
    }

    /**
     * Converts an amount from a source currency to a target currency.
     *
     * @param float $amount
     * @param string $fromCurrencyCode
     * @param string $toCurrencyCode
     * @return float
     * @throws \Exception
     */
    public function convertCurrency(float $amount, string $fromCurrencyCode, string $toCurrencyCode): float
    {
        if ($fromCurrencyCode === $toCurrencyCode) {
            return $amount;
        }

        $fromRate = ExchangeRate::where('currency_code', $fromCurrencyCode)->first();
        $toRate = ExchangeRate::where('currency_code', $toCurrencyCode)->first();

        if (!$fromRate || !$toRate) {
            throw new \Exception("Exchange rate not found for one of the currencies.");
        }

        $amountInBase = $amount / $fromRate->rate;
        return $amountInBase * $toRate->rate;
    }
}
