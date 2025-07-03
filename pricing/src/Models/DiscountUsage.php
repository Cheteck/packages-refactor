<?php

namespace IJIDeals\Pricing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DiscountUsage extends Model
{
    use HasFactory;

    protected $table = 'discount_usage';

    protected $fillable = [
        'discount_id',
        'user_id', // User who used the discount
        'order_id', // Order in which the discount was applied (can be morphable if discounts apply to other things)
        // 'order_type', // If order_id is morphable
        'used_at',
    ];

    protected $casts = [
        'used_at' => 'datetime',
    ];

    /**
     * Get the discount that was used.
     */
    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }

    /**
     * Get the user who used the discount.
     * Assuming user_id links to the standard User model.
     * Adjust if using a different User model or polymorphic relation.
     */
    public function user(): BelongsTo
    {
        // Replace with your actual User model path if different
        return $this->belongsTo(config('auth.providers.users.model', \App\Models\User::class));
    }

    /**
     * Get the order associated with this discount usage.
     * This assumes a direct relationship. If discounts can apply to other entities,
     * make 'order' a morphTo relationship.
     */
    public function order(): BelongsTo // Or MorphTo
    {
        // Replace with your actual Order model path from ijideals/commerce
        // Example: return $this->belongsTo(\IJIDeals\IJICommerce\Models\Order::class);
        // For now, keeping it generic. If it's polymorphic, this needs to change.
        // return $this->morphTo('order'); // If polymorphic
        return $this->belongsTo(Model::class, 'order_id'); // Placeholder, to be updated with actual Order model
    }

    // No updated_at timestamp needed for usage logs.
    public const UPDATED_AT = null;

    // protected static function newFactory()
    // {
    //     return \IJIDeals\Pricing\Database\factories\DiscountUsageFactory::new();
    // }
}
