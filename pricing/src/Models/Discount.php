<?php

namespace IJIDeals\Pricing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Discount extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'discounts';

    protected $fillable = [
        'name',
        'code', // Unique, nullable for automatic discounts
        'description',
        'type', // 'percentage', 'fixed_amount', 'buy_x_get_y_item', 'free_shipping'
        'value', // Stores percentage, fixed amount, or JSON for complex rules like BOGO
        'max_uses',
        'max_uses_per_user',
        'total_uses', // Counter for total uses
        'starts_at',
        'ends_at',
        'status', // 'active', 'inactive', 'expired', 'scheduled'
        'is_combinable', // Can this discount be combined with others?
        'priority', // In case of multiple non-combinable discounts, which one applies
        'conditions_match_type', // 'all' (AND) or 'any' (OR) for discount rules
    ];

    protected $casts = [
        'value' => 'json', // Flexible for different discount types
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'max_uses' => 'integer',
        'max_uses_per_user' => 'integer',
        'total_uses' => 'integer',
        'is_combinable' => 'boolean',
        'priority' => 'integer',
    ];

    /**
     * Get the rules associated with this discount.
     */
    public function rules(): HasMany
    {
        return $this->hasMany(DiscountRule::class);
    }

    /**
     * Get the usage records for this discount.
     */
    public function usage(): HasMany
    {
        return $this->hasMany(DiscountUsage::class); // New model to track usage per user/order
    }

    /**
     * Scope to get only active and valid discounts.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->where(function ($q) { // Check usage limits
                $q->whereNull('max_uses')->orWhereColumn('total_uses', '<', 'max_uses');
            });
    }

    /**
     * Check if the discount is currently valid (dates, status, uses).
     */
    public function isValid(): bool
    {
        $now = now();
        if ($this->status !== 'active') {
            return false;
        }
        if ($this->starts_at && $this->starts_at->gt($now)) {
            return false;
        }
        if ($this->ends_at && $this->ends_at->lt($now)) {
            return false;
        }
        if (! is_null($this->max_uses) && $this->total_uses >= $this->max_uses) {
            return false;
        }

        return true;
    }

    /**
     * Check if a specific user can use this discount.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $user  The user model
     */
    public function canBeUsedBy(Model $user): bool
    {
        if (! $this->isValid()) {
            return false;
        }

        if (! is_null($this->max_uses_per_user)) {
            $userUsageCount = $this->usage()->where('user_id', $user->getKey())->count();
            if ($userUsageCount >= $this->max_uses_per_user) {
                return false;
            }
        }

        return true;
    }

    /**
     * Increment the total uses counter.
     */
    public function incrementTotalUses(): void
    {
        $this->increment('total_uses');
    }

    // protected static function newFactory()
    // {
    //     return \IJIDeals\Pricing\Database\factories\DiscountFactory::new();
    // }
}
