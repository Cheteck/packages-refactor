<?php

namespace IJIDeals\Pricing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceHistory extends Model
{
    use HasFactory;

    protected $table = 'price_histories';

    protected $fillable = [
        'price_id',
        'old_amount',
        'new_amount',
        'changed_at',
        'user_id', // Optional: ID of the user who made the change
    ];

    protected $casts = [
        'old_amount' => 'decimal:4',
        'new_amount' => 'decimal:4',
        'changed_at' => 'datetime',
    ];

    /**
     * Get the price that this history entry belongs to.
     */
    public function price(): BelongsTo
    {
        return $this->belongsTo(Price::class);
    }

    /**
     * Get the user who made the change (if applicable).
     * This assumes you have a User model in your application.
     * You might need to adjust the namespace or make this polymorphic.
     */
    public function user(): BelongsTo
    {
        // Assuming default Laravel User model. Adjust if using a different user model or a polymorphic relationship.
        return $this->belongsTo(config('auth.providers.users.model', \App\Models\User::class));
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        // Attempt to resolve the factory from the conventional path.
        // You'll need to create this factory: IJIDeals\Pricing\Database\factories\PriceHistoryFactory
        return \IJIDeals\Pricing\Database\factories\PriceHistoryFactory::new();
    }
}
