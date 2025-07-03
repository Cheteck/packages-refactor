<?php

namespace IJIDeals\IJIOrderManagement\Policies;

use App\Models\User;
use IJIDeals\IJIOrderManagement\Models\Order;
use IJIDeals\IJICommerce\Models\Shop;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Log;

class OrderPolicy
{
    use HandlesAuthorization;

    private const PLATFORM_ADMIN_ROLE = 'Platform Admin';

    /**
     * Determine whether the user can view any orders (their own as customer).
     */
    public function viewAnyCustomer(User $user): bool
    {
        $can = $user->exists();
        Log::info('OrderPolicy: viewAnyCustomer check.', ['user_id' => $user->id, 'can' => $can]);
        return $can;
    }

    /**
     * Determine whether the user can view the order (as customer).
     */
    public function viewCustomer(User $user, Order $order): bool
    {
        $can = $user->id === $order->user_id || $user->hasRole(self::PLATFORM_ADMIN_ROLE);
        Log::info('OrderPolicy: viewCustomer check.', ['user_id' => $user->id, 'order_id' => $order->id, 'can' => $can]);
        return $can;
    }

    /**
     * Determine whether the user can create orders (as customer).
     */
    public function create(User $user): bool
    {
        $can = $user->exists();
        Log::info('OrderPolicy: create check.', ['user_id' => $user->id, 'can' => $can]);
        return $can;
    }

    /**
     * Determine whether a shop user can view any orders for a specific shop.
     * (e.g., listing orders in shop dashboard)
     */
    public function viewAnyShop(User $user, Shop $shop): bool
    {
        $can = $user->hasRole(['Owner', 'Administrator', 'Editor', 'Support'], $shop) || $user->hasRole(self::PLATFORM_ADMIN_ROLE);
        Log::info('OrderPolicy: viewAnyShop check.', ['user_id' => $user->id, 'shop_id' => $shop->id, 'can' => $can]);
        return $can;
    }

    /**
     * Determine whether a shop user can view a specific order belonging to their shop.
     */
    public function viewShop(User $user, Order $order): bool
    {
        if ($user->hasRole(self::PLATFORM_ADMIN_ROLE)) {
            Log::info('OrderPolicy: viewShop check (Platform Admin).', ['user_id' => $user->id, 'order_id' => $order->id]);
            return true;
        }

        $can = $order->shop_id && $user->hasRole(['Owner', 'Administrator', 'Editor', 'Support'], $order->shop);
        Log::info('OrderPolicy: viewShop check.', ['user_id' => $user->id, 'order_id' => $order->id, 'can' => $can]);
        return $can;
    }

    /**
     * Determine whether a shop user can update an order's status for their shop.
     * (The $shop parameter is passed from the route for context, $order is the target)
     */
    public function updateShopOrderStatus(User $user, Order $order, Shop $shop): bool
    {
        if ($user->hasRole(self::PLATFORM_ADMIN_ROLE) && $order->shop_id === $shop->id) {
            Log::info('OrderPolicy: updateShopOrderStatus check (Platform Admin).', ['user_id' => $user->id, 'order_id' => $order->id, 'shop_id' => $shop->id]);
            return true;
        }

        $can = $order->shop_id === $shop->id &&
               $user->hasRole(['Owner', 'Administrator', 'Editor' /*'Order Manager'*/], $shop);
        Log::info('OrderPolicy: updateShopOrderStatus check.', ['user_id' => $user->id, 'order_id' => $order->id, 'shop_id' => $shop->id, 'can' => $can]);
        return $can;
    }
}
