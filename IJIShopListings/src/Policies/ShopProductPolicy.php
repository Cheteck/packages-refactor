<?php

namespace IJIDeals\IJIShopListings\Policies;

use App\Models\User;
use IJIDeals\IJICommerce\Models\Shop;
use IJIDeals\IJIShopListings\Models\ShopProduct;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Log;

class ShopProductPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models (list shop's products or available master products).
     * This is a general check; controller actions will further scope to the specific shop.
     */
    public function viewAny(User $user, Shop $shop): bool
    {
        $can = $user->hasRole(['Owner', 'Administrator', 'Editor'], $shop);
        Log::info('ShopProductPolicy: viewAny check.', ['user_id' => $user->id, 'shop_id' => $shop->id, 'can' => $can]);
        return $can;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ShopProduct $shopProduct): bool
    {
        $can = $user->hasRole(['Owner', 'Administrator', 'Editor', 'Support', 'Viewer'], $shopProduct->shop);
        Log::info('ShopProductPolicy: view check.', ['user_id' => $user->id, 'shop_product_id' => $shopProduct->id, 'can' => $can]);
        return $can;
    }

    /**
     * Determine whether the user can create models ("Sell This" action).
     * The $shop context is passed from the controller.
     */
    public function create(User $user, Shop $shop): bool
    {
        $can = $user->hasRole(['Owner', 'Administrator', 'Editor'], $shop);
        Log::info('ShopProductPolicy: create check.', ['user_id' => $user->id, 'shop_id' => $shop->id, 'can' => $can]);
        return $can;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ShopProduct $shopProduct): bool
    {
        $can = $user->hasRole(['Owner', 'Administrator', 'Editor'], $shopProduct->shop);
        Log::info('ShopProductPolicy: update check.', ['user_id' => $user->id, 'shop_product_id' => $shopProduct->id, 'can' => $can]);
        return $can;
    }

    /**
     * Determine whether the user can delete the model (de-list).
     */
    public function delete(User $user, ShopProduct $shopProduct): bool
    {
        $can = $user->hasRole(['Owner', 'Administrator', 'Editor'], $shopProduct->shop);
        Log::info('ShopProductPolicy: delete check.', ['user_id' => $user->id, 'shop_product_id' => $shopProduct->id, 'can' => $can]);
        return $can;
    }

    /**
     * Determine whether the user can acknowledge master product updates for a shop product.
     */
    public function acknowledgeMasterUpdate(User $user, ShopProduct $shopProduct): bool
    {
        $can = $user->hasRole(['Owner', 'Administrator', 'Editor'], $shopProduct->shop);
        Log::info('ShopProductPolicy: acknowledgeMasterUpdate check.', ['user_id' => $user->id, 'shop_product_id' => $shopProduct->id, 'can' => $can]);
        return $can;
    }

    public function manageShopProducts(User $user, Shop $shop): bool
    {
         $can = $user->hasRole(['Owner', 'Administrator', 'Editor'], $shop);
         Log::info('ShopProductPolicy: manageShopProducts check.', ['user_id' => $user->id, 'shop_id' => $shop->id, 'can' => $can]);
         return $can;
    }
}
