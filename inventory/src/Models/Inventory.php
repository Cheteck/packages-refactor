<?php

namespace IJIDeals\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Inventory extends Model
{
    use HasFactory;

    protected $table = 'inventories'; // Or 'stock'

    protected $fillable = [
        'stockable_id',
        'stockable_type',
        'location_id',
        'quantity',
        'reserved_quantity',
        'last_stock_update',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'reserved_quantity' => 'integer',
        'last_stock_update' => 'datetime',
    ];

    /**
     * Get the parent stockable model (Product, Variant, etc.).
     */
    public function stockable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the location of this inventory item.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'location_id');
    }

    /**
     * Get the available quantity (quantity - reserved_quantity).
     */
    public function getAvailableQuantityAttribute(): int
    {
        return $this->quantity - $this->reserved_quantity;
    }

    // TODO: Add a factory if not already created by ls check
    // protected static function newFactory()
    // {
    //     return \IJIDeals\Inventory\Database\factories\InventoryFactory::new();
    // }
}
