<?php

namespace IJIDeals\Pricing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Price extends Model
{
    use HasFactory;

    protected $table = 'prices';

    protected $fillable = [
        'priceable_id',
        'priceable_type',
        'currency_code', // FK to currencies table
        'amount',
        'price_type', // e.g., 'default', 'sale', 'vip', 'wholesale'
        'starts_at',  // For scheduled prices like sales
        'ends_at',    // For scheduled prices like sales
        'min_quantity', // For tiered pricing based on quantity
        'is_active',
    ];

    protected $casts = [
        'amount' => 'decimal:4', // Store with high precision, format on display
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'min_quantity' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the parent priceable model (Product, Variant, SubscriptionPlan, etc.).
     */
    public function priceable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the currency for this price.
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }

    /**
     * Scope to get only active prices.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            });
    }

    /**
     * Scope to get prices for a specific type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('price_type', $type);
    }

    // protected static function newFactory()
    // {
    //     return \IJIDeals\Pricing\Database\factories\PriceFactory::new();
    // }
}
