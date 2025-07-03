<?php

namespace IJIDeals\Pricing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscountRule extends Model
{
    use HasFactory;

    protected $table = 'discount_rules';

    protected $fillable = [
        'discount_id',
        'rule_type', // e.g., 'cart_subtotal_min', 'cart_item_count_min', 'customer_group_is',
        // 'product_in_cart', 'category_in_cart', 'coupon_usage_limit_per_customer'
        'parameters', // JSON for rule specifics (e.g., min_amount, item_ids, category_ids, customer_group_id)
    ];

    protected $casts = [
        'parameters' => 'array',
    ];

    /**
     * Get the discount this rule belongs to.
     */
    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }

    // No timestamps needed if rules are defined once and not changed, or changes are part of Discount's lifecycle.
    // public $timestamps = false;

    // protected static function newFactory()
    // {
    //     return \IJIDeals\Pricing\Database\factories\DiscountRuleFactory::new();
    // }
}
