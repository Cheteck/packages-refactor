<?php

namespace IJIDeals\IJIProductCatalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Image\Manipulations;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Brand extends Model implements HasMedia
{
    use InteractsWithMedia, HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'website_url',
        'social_links', // JSON
        'story',        // TEXT
        'is_featured',  // Boolean
        'meta_title',
        'meta_description',
        'meta_keywords',
        'status',       // String e.g., 'active', 'pending_approval', 'inactive'
    ];

    protected $casts = [
        'social_links' => 'array',
        'is_featured' => 'boolean',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(config('ijiproductcatalog.media_collections.brand_logo', 'brand_logo'))
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

        $this->addMediaCollection(config('ijiproductcatalog.media_collections.brand_cover', 'brand_covers'))
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
              ->width(150)
              ->height(150)
              ->sharpen(10)
              ->performOnCollections(config('ijiproductcatalog.media_collections.brand_logo', 'brand_logo'));

        $this->addMediaConversion('cover_preview')
              ->width(800)
              ->performOnCollections(config('ijiproductcatalog.media_collections.brand_cover', 'brand_covers'));
    }

    public function getTable()
    {
        return config('ijiproductcatalog.tables.brands', 'brands');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($brand) {
            if (empty($brand->slug)) {
                $brand->slug = Str::slug($brand->name);
                $originalSlug = $brand->slug;
                $count = 1;
                while (static::where('slug', $brand->slug)->exists()) {
                    $brand->slug = "{$originalSlug}-{$count}";
                    $count++;
                }
            }
        });

        static::updating(function ($brand) {
            if ($brand->isDirty('name') && !$brand->isDirty('slug')) {
                $brand->slug = Str::slug($brand->name);
                $originalSlug = $brand->slug;
                $count = 1;
                while (static::where('slug', $brand->slug)->where('id', '!=', $brand->id)->exists()) {
                    $brand->slug = "{$originalSlug}-{$count}";
                    $count++;
                }
            }
        });
    }

    // Relationships
    public function masterProducts()
    {
        return $this->hasMany(MasterProduct::class);
    }
}
