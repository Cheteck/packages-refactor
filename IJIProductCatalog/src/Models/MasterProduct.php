<?php

namespace IJIDeals\IJIProductCatalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Image\Manipulations;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Support\Facades\Log;
use IJIDeals\Analytics\Traits\TrackableStats; // Added

class MasterProduct extends Model implements HasMedia
{
    use InteractsWithMedia, HasFactory, TrackableStats; // Added TrackableStats

    protected $fillable = [
        'name',
        'slug',
        'description',
        'brand_id',
        'category_id',
        'specifications', // JSON
        'status',         // 'active', 'archived', 'draft_by_admin'
        'created_by_proposal_id', // Nullable FK to product_proposals
    ];

    protected $casts = [
        'specifications' => 'array',
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($product) {
            Log::info('Creating new master product.', ['name' => $product->name]);
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name);
                $originalSlug = $product->slug;
                $count = 1;
                while (static::where('slug', $product->slug)->exists()) {
                    $product->slug = "{$originalSlug}-{$count}";
                    $count++;
                }
            }
        });

        static::updating(function ($product) {
            Log::info('Updating master product.', ['id' => $product->id, 'changes' => $product->getDirty()]);
            if ($product->isDirty('name') && !$product->isDirty('slug')) {
                $product->slug = Str::slug($product->name);
                $originalSlug = $product->slug;
                $count = 1;
                while (static::where('slug', $product->slug)->where('id', '!=', $product->id)->exists()) {
                    $product->slug = "{$originalSlug}-{$count}";
                    $count++;
                }
            }
        });

        static::deleting(function ($product) {
            Log::info('Deleting master product.', ['id' => $product->id]);
        });
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(config('ijiproductcatalog.media_collections.master_product_base_images', 'master_product_base_images'))
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
              ->width(300)
              ->height(300)
              ->performOnCollections(config('ijiproductcatalog.media_collections.master_product_base_images', 'master_product_base_images'));

        $this->addMediaConversion('showcase')
              ->width(800)
              ->performOnCollections(config('ijiproductcatalog.media_collections.master_product_base_images', 'master_product_base_images'));
    }

    public function getTable()
    {
        return config('ijiproductcatalog.tables.master_products', 'master_products');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function productProposal(): BelongsTo
    {
        return $this->belongsTo(ProductProposal::class, 'created_by_proposal_id');
    }

    public function shopProducts(): HasMany
    {
        // This will eventually point to a model in IJIShopListings
        // For now, we keep the class name and will adjust the namespace later.
        return $this->hasMany(\IJIDeals\IJIShopListings\Models\ShopProduct::class);
    }

    public function variations(): HasMany
    {
        return $this->hasMany(MasterProductVariation::class);
    }
}
