<?php

namespace IJIDeals\Inventory\Models;

use IJIDeals\UserManagement\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo; // Assuming User model path

class StockMovement extends Model
{
    use HasFactory;

    protected $table = 'stock_movements';

    protected $fillable = [
        'stockable_id',
        'stockable_type',
        'location_id', // Can be null if movement is not location-specific or for global adjustments before location assignment
        'inventory_id', // Direct link to the inventory record affected
        'user_id', // User who initiated or is responsible for the movement
        'reference_id', // E.g., Order ID, Return ID
        'reference_type', // E.g., Order::class, ReturnRequest::class
        'type', // E.g., 'sale', 'return', 'restock', 'adjustment', 'transfer_in', 'transfer_out', 'damage'
        'quantity_change', // Positive for increase, negative for decrease
        'quantity_before',
        'quantity_after',
        'description', // Optional notes
        'created_at',
    ];

    protected $casts = [
        'quantity_change' => 'integer',
        'quantity_before' => 'integer',
        'quantity_after' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * Get the parent stockable model (Product, Variant, etc.).
     */
    public function stockable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the inventory record this movement is associated with.
     */
    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class, 'inventory_id');
    }

    /**
     * Get the location this movement pertains to (via inventory).
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'location_id');
    }

    /**
     * Get the user who initiated this movement.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the reference model (Order, ReturnRequest, etc.).
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    // We don't want to update 'updated_at' for stock movements, they are logs.
    public const UPDATED_AT = null;

    // TODO: Add a factory if not already created by ls check
    // protected static function newFactory()
    // {
    //     return \IJIDeals\Inventory\Database\factories\StockMovementFactory::new();
    // }
}
