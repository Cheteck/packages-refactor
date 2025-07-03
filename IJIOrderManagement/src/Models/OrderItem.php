<?php

namespace IJIDeals\IJIOrderManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Log;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'shop_product_id',
        'master_product_variation_id',
        'master_product_id',
        'product_name_at_purchase',
        'variant_details_at_purchase',
        'sku_at_purchase',
        'quantity',
        'price_at_purchase',
        'total_line_amount',
    ];

    protected $casts = [
        'variant_details_at_purchase' => 'array',
        'price_at_purchase' => 'decimal:2',
        'total_line_amount' => 'decimal:2',
        'quantity' => 'integer',
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($orderItem) {
            Log::info('Creating new order item.', ['order_id' => $orderItem->order_id, 'product_name' => $orderItem->product_name_at_purchase, 'quantity' => $orderItem->quantity]);
        });

        static::updating(function ($orderItem) {
            Log::info('Updating order item.', ['id' => $orderItem->id, 'order_id' => $orderItem->order_id, 'changes' => $orderItem->getDirty()]);
        });

        static::deleting(function ($orderItem) {
            Log::info('Deleting order item.', ['id' => $orderItem->id, 'order_id' => $orderItem->order_id]);
        });
    }

    public function getTable()
    {
        return config('ijiordermanagement.tables.order_items', 'order_items');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function shopProduct(): BelongsTo
    {
        return $this->belongsTo(\IJIDeals\IJIShopListings\Models\ShopProduct::class);
    }

    public function masterProduct(): BelongsTo
    {
        return $this->belongsTo(\IJIDeals\IJIProductCatalog\Models\MasterProduct::class);
    }

    public function masterProductVariation(): BelongsTo
    {
        return $this->belongsTo(\IJIDeals\IJIProductCatalog\Models\MasterProductVariation::class);
    }
}
