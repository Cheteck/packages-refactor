<?php

namespace IJIDeals\IJICommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // Optional: if you want soft deletes
use Illuminate\Support\Str;

// If you intend to use Spatie's HasRoles trait directly on Shop for shop-level (non-team) roles,
// you would add: use Spatie\Permission\Traits\HasRoles;
// However, for team-specific roles, the Shop model acts as the "team" instance,
// and users associated with this shop (team) will have roles scoped to it.
// The Shop model itself usually doesn't need HasRoles for this pattern.

class Shop extends Model
{
    // use SoftDeletes; // Uncomment if you want to use soft deletes for shops

    protected $fillable = [
        'name',
        'slug',
        'description',
        'logo_path',
        'cover_photo_path',
        'contact_email',
        'contact_phone',
        'website_url',
        'status',
        'settings',
        'approved_at',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'display_address',
        // 'owner_id' was removed as ownership is handled by role
    ];

    protected $casts = [
        'settings' => 'array', // Using 'array' cast for JSON, more flexible than 'json' for older Laravel
        'approved_at' => 'datetime',
    ];

    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public function getTable()
    {
        return config('ijicommerce.tables.shops', 'shops'); // Example if you make table names configurable
        // return 'shops'; // Or simply hardcode
    }

    /**
     * Boot function from Laravel.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($shop) {
            if (empty($shop->slug)) {
                $shop->slug = Str::slug($shop->name);
                // Ensure slug is unique
                $originalSlug = $shop->slug;
                $count = 1;
                while (static::where('slug', $shop->slug)->exists()) {
                    $shop->slug = "{$originalSlug}-{$count}";
                    $count++;
                }
            }
        });

        static::updating(function ($shop) {
            if ($shop->isDirty('name') && empty($shop->slug)) { // Or if you allow slug regeneration
                $shop->slug = Str::slug($shop->name);
                $originalSlug = $shop->slug;
                $count = 1;
                // Check for uniqueness, excluding the current model
                while (static::where('slug', $shop->slug)->where('id', '!=', $shop->id)->exists()) {
                    $shop->slug = "{$originalSlug}-{$count}";
                    $count++;
                }
            }
        });
    }

    // Relationships
    // Example: If users are directly related to shops in a way not covered by Spatie teams
    // public function owner()
    // {
    //    // If owner_id was kept, this would be:
    //    // return $this->belongsTo(config('ijicommerce.user_model', \App\Models\User::class), 'owner_id');
    // }

    // If you have products related to a shop:
    // public function products()
    // {
    //     return $this->hasMany(Product::class); // Assuming Product model exists
    // }

    /**
     * Get the shop products (listings) for this shop.
     */
    public function shopProducts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ShopProduct::class);
    }

    /**
     * Get the orders associated with this shop.
     */
    public function orders(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Order::class);
    }


    // Spatie team permissions:
    // The Shop model acts as the "team". When you do:
    // $user->assignRole('owner', $shop);
    // Spatie stores $shop->getKey() (the shop's ID) in the team_foreign_key column (e.g., 'shop_id')
    // in the model_has_roles pivot table.
}
