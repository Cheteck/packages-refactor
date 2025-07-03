<?php

namespace IJIDeals\IJIProductCatalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Image\Manipulations;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Support\Facades\Log;

class MasterProductVariation extends Model implements HasMedia
{
    use InteractsWithMedia, HasFactory;

    protected $fillable = [
        'master_product_id',
        'sku',
        'price_adjustment',
        'stock_override',
    ];

    protected $casts = [
        'price_adjustment' => 'decimal:2',
        'stock_override' => 'integer',
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($variation) {
            Log::info('Creating new master product variation.', ['sku' => $variation->sku, 'master_product_id' => $variation->master_product_id]);
        });

        static::updating(function ($variation) {
            Log::info('Updating master product variation.', ['id' => $variation->id, 'changes' => $variation->getDirty()]);
        });

        static::deleting(function ($variation) {
            Log::info('Deleting master product variation.', ['id' => $variation->id]);
        });
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(config('ijiproductcatalog.media_collections.master_product_variant_image', 'master_product_variant_images'))
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
              ->width(150)
              ->height(150)
              ->performOnCollections(config('ijiproductcatalog.media_collections.master_product_variant_image', 'master_product_variant_images'));
    }

    public function getTable()
    {
        return config('ijiproductcatalog.tables.master_product_variations', 'master_product_variations');
    }

    public function masterProduct(): BelongsTo
    {
        return $this->belongsTo(MasterProduct::class);
    }

    public function attributeOptions(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductAttributeValue::class,
            config('ijiproductcatalog.tables.master_product_variation_options', 'master_product_variation_options'),
            'master_product_variation_id',
            'product_attribute_value_id'
        )->withTimestamps();
    }

    public function shopProductVariations(): HasMany
    {
        // This will eventually point to a model in IJIShopListings
        return $this->hasMany(\IJIDeals\IJIShopListings\Models\ShopProductVariation::class);
    }
}
