<?php

namespace IJIDeals\IJIShopListings\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Log;

class ShopProductVariation extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_product_id',
        'master_product_variation_id',
        'price',
        'stock_quantity',
        'shop_sku_variant',
        'sale_price',
        'sale_start_date',
        'sale_end_date',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'sale_price' => 'decimal:2',
        'sale_start_date' => 'datetime',
        'sale_end_date' => 'datetime',
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($variation) {
            Log::info('Creating new shop product variation.', ['shop_product_id' => $variation->shop_product_id, 'master_product_variation_id' => $variation->master_product_variation_id]);
        });

        static::updating(function ($variation) {
            Log::info('Updating shop product variation.', ['id' => $variation->id, 'changes' => $variation->getDirty()]);
        });

        static::deleting(function ($variation) {
            Log::info('Deleting shop product variation.', ['id' => $variation->id]);
        });
    }

    public function getTable()
    {
        return config('ijishoplistings.tables.shop_product_variations', 'shop_product_variations');
    }

    public function shopProduct(): BelongsTo
    {
        return $this->belongsTo(ShopProduct::class);
    }

    public function masterProductVariation(): BelongsTo
    {
        return $this->belongsTo(\IJIDeals\IJIProductCatalog\Models\MasterProductVariation::class);
    }

    public function getEffectivePriceAttribute()
    {
        if (
            $this->sale_price !== null &&
            $this->sale_price < $this->price &&
            (!$this->sale_start_date || $this->sale_start_date->isPast()) &&
            (!$this->sale_end_date || $this->sale_end_date->isFuture())
        ) {
            return $this->sale_price;
        }
        return $this->price;
    }

    public function getIsOnSaleAttribute(): bool
    {
        return $this->sale_price !== null &&
               $this->sale_price < $this->price &&
               (!$this->sale_start_date || $this->sale_start_date->isPast()) &&
               (!$this->sale_end_date || $this->sale_end_date->isFuture());
    }
}
