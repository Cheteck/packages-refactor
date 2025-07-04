<?php

namespace IJIDeals\Pricing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingRule extends Model
{
    use HasFactory;

    protected $table = 'shipping_rules';

    protected $fillable = [
        'name',
        'rule_type', // e.g., 'by_country', 'by_distance', 'by_zone'
        'parameters', // JSON for rule specifics (e.g., origin_country_id, destination_country_id, min_distance, max_distance, cost_per_unit)
        'cost_type', // e.g., 'fixed', 'per_item', 'per_kg', 'per_km'
        'cost_value',
        'is_active',
        'priority',
    ];

    protected $casts = [
        'parameters' => 'array',
        'is_active' => 'boolean',
    ];

    // protected static function newFactory()
    // {
    //     return \IJIDeals\Pricing\Database\factories\ShippingRuleFactory::new();
    // }
}
