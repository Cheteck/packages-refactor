<?php

namespace IJIDeals\IJIShopListings\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Image\Manipulations;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Support\Facades\Log;

class ShopProduct extends Model implements HasMedia
{
    use InteractsWithMedia, HasFactory;

    protected $fillable = [
        'shop_id',
        'master_product_id',
        'price',
        'stock_quantity',
        'is_visible_in_shop',
        'shop_specific_notes',
        'master_version_hash',
        'needs_review_by_shop',
        'sale_price',
        'sale_start_date',
        'sale_end_date',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'is_visible_in_shop' => 'boolean',
        'needs_review_by_shop' => 'boolean',
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
        static::creating(function ($shopProduct) {
            Log::info('Creating new shop product.', ['shop_id' => $shopProduct->shop_id, 'master_product_id' => $shopProduct->master_product_id]);
        });

        static::updating(function ($shopProduct) {
            Log::info('Updating shop product.', ['id' => $shopProduct->id, 'changes' => $shopProduct->getDirty()]);
        });

        static::deleting(function ($shopProduct) {
            Log::info('Deleting shop product.', ['id' => $shopProduct->id]);
        });
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(config('ijishoplistings.media_collections.shop_product_additional_images', 'shop_product_additional_images'))
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
              ->width(200)
              ->height(200)
              ->performOnCollections(config('ijishoplistings.media_collections.shop_product_additional_images', 'shop_product_additional_images'));
    }

    public function getTable()
    {
        return config('ijishoplistings.tables.shop_products', 'shop_products');
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(\IJIDeals\IJICommerce\Models\Shop::class);
    }

    public function masterProduct(): BelongsTo
    {
        return $this->belongsTo(\IJIDeals\IJIProductCatalog\Models\MasterProduct::class);
    }

    public function variations(): HasMany
    {
        return $this->hasMany(ShopProductVariation::class);
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
