<?php

namespace IJIDeals\UserManagement\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;
    // Assuming IJICommerce, SocialLinkManager, VirtualCoin traits are desired here
    // If not, these should be conditionally added or managed by the application consuming UserManagement
    // For this exercise, let's assume they are desired as per the previous context.
    use Spatie\Permission\Traits\HasRoles;
    use IJIDeals\SocialLinkManager\Traits\HasSocialLinks;
    use IJIDeals\VirtualCoin\Traits\HasVirtualWallet;
    // If IJIUserSettings is created and has a trait:
    // use IJIDeals\IJIUserSettings\Traits\HasUserSettings;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'username', // Retaining username as it's common for social features
        'profile_photo_path',
        'cover_photo_path',
        'bio',
        'birthdate',
        'gender',
        'phone',
        'preferred_language',
        'location',
        'website',
        // followers_count and following_count are typically not mass assignable directly
        // but updated via specific methods or events.
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'birthdate' => 'date', // Casting birthdate to a Carbon date object
        'followers_count' => 'integer',
        'following_count' => 'integer',
    ];

    // Add relationships here if needed, for example:
    // public function posts()
    // {
    //     return $this->hasMany(Post::class);
    // }

    /**
     * Get the shops owned by the user.
     * Assumes Shop model is available in the global namespace or correctly imported.
     * If Shop model is part of IJICommerce, ensure it's properly namespaced.
     * Example: use IJIDeals\IJICommerce\Models\Shop;
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function shops(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        // Assuming Shop model will be available. If it's in a package, adjust namespace.
        // For example: return $this->hasMany(\IJIDeals\IJICommerce\Models\Shop::class, 'owner_id');
        if (class_exists(\App\Models\Shop::class)) {
            return $this->hasMany(\App\Models\Shop::class, 'owner_id');
        }
        // Fallback or throw exception if Shop class is not found
        // This indicates a dependency that needs to be resolved by the consuming application
        // or by adding a direct dependency to IJICommerce if UserManagement should always know about Shops.
        // For now, we'll return an empty relation to avoid breaking if Shop doesn't exist.
        return $this->hasMany(Model::class, 'owner_id')->whereRaw('1 = 0'); // Empty relation
    }

    /**
     * Get the shops managed by the user through a pivot table.
     * Assumes Shop model and shop_user pivot table.
     * Example: use IJIDeals\IJICommerce\Models\Shop;
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function managedShops(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        // Assuming Shop model will be available and a 'shop_user' pivot table.
        // For example: return $this->belongsToMany(\IJIDeals\IJICommerce\Models\Shop::class, 'shop_user', 'user_id', 'shop_id')
        if (class_exists(\App\Models\Shop::class)) {
            return $this->belongsToMany(\App\Models\Shop::class, 'shop_user', 'user_id', 'shop_id')
                        ->withPivot('role')
                        ->withTimestamps();
        }
        // Fallback for when Shop class isn't available
        return $this->belongsToMany(Model::class, 'shop_user', 'user_id', 'shop_id')->whereRaw('1 = 0'); // Empty relation
    }

    /**
     * Check if the user has the 'admin' role.
     * Relies on Spatie\Permission\Traits\HasRoles being used.
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Check if the user is the owner of the given shop.
     *
     * @param  mixed $shop The shop instance or shop ID.
     * @return bool
     */
    public function isShopOwner($shop): bool
    {
        $shopId = $shop instanceof \Illuminate\Database\Eloquent\Model ? $shop->id : $shop;
        // This assumes shops relationship returns shops owned by this user.
        return $this->shops()->where('id', $shopId)->exists();
    }


    /**
     * Check if the user has a specific role in a given shop.
     *
     * @param mixed $shop The shop instance or shop ID.
     * @param string $role The role name to check.
     * @return bool
     */
    public function hasShopRole($shop, string $role): bool
    {
        $shopId = $shop instanceof \Illuminate\Database\Eloquent\Model ? $shop->id : $shop;
        return $this->managedShops()->where('shop_id', $shopId)->wherePivot('role', $role)->exists();
    }


    /**
     * Check if the user can manage the given shop (either as admin, owner, or a specific manager role).
     *
     * @param  mixed $shop The shop instance or shop ID.
     * @return bool
     */
    public function canManageShop($shop): bool
    {
        if ($this->isAdmin()) {
            return true;
        }
        return $this->isShopOwner($shop) || $this->hasShopRole($shop, 'manager'); // Assuming 'manager' is a role
    }
}
