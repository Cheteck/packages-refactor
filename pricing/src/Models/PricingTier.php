<?php

namespace IJIDeals\Pricing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PricingTier extends Model
{
    use HasFactory;

    protected $table = 'pricing_tiers';

    protected $fillable = [
        'name', // e.g., "Basic", "Premium", "Wholesale"
        'key',  // e.g., "basic_tier", unique identifier
        'description',
        'is_active',
        'order', // For sorting tiers
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    // If a PricingTier can have specific prices for Priceable items,
    // you might have a pivot table like 'priceable_pricing_tier'
    // or a direct relationship if a Price belongs to a Tier.
    // For example, a Price model could have a nullable `pricing_tier_id`.

    // If tiers are associated with user roles or groups from user-management:
    // public function roles()
    // {
    //     // This depends on how roles are managed in ijideals/user-management
    //     // return $this->belongsToMany(config('user-management.role_model'), 'role_pricing_tier');
    // }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return \IJIDeals\Pricing\Database\factories\PricingTierFactory::new();
    }
}
