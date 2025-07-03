<?php

namespace IJIDeals\IJIProductCatalog\Policies\Admin;

use App\Models\User;
use IJIDeals\IJIProductCatalog\Models\ProductAttribute;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Log;

class ProductAttributePolicy
{
    use HandlesAuthorization;

    private const PLATFORM_ADMIN_ROLE = 'Platform Admin';

    public function before(User $user, $ability): ?bool
    {
        $can = $user->hasRole(self::PLATFORM_ADMIN_ROLE);
        Log::info('ProductAttributePolicy: before check.', ['user_id' => $user->id, 'ability' => $ability, 'can' => $can]);
        if ($can) {
            return true;
        }
        return null;
    }

    public function viewAny(User $user): bool
    {
        Log::info('ProductAttributePolicy: viewAny check (should be handled by before). ', ['user_id' => $user->id]);
        return false;
    }

    public function view(User $user, ProductAttribute $productAttribute): bool
    {
        Log::info('ProductAttributePolicy: view check (should be handled by before). ', ['user_id' => $user->id, 'attribute_id' => $productAttribute->id]);
        return false;
    }

    public function create(User $user): bool
    {
        Log::info('ProductAttributePolicy: create check (should be handled by before). ', ['user_id' => $user->id]);
        return false;
    }

    public function update(User $user, ProductAttribute $productAttribute): bool
    {
        Log::info('ProductAttributePolicy: update check (should be handled by before). ', ['user_id' => $user->id, 'attribute_id' => $productAttribute->id]);
        return false;
    }

    public function delete(User $user, ProductAttribute $productAttribute): bool
    {
        Log::info('ProductAttributePolicy: delete check (should be handled by before). ', ['user_id' => $user->id, 'attribute_id' => $productAttribute->id]);
        return false;
    }

    public function manageValues(User $user, ProductAttribute $productAttribute): bool
    {
        Log::info('ProductAttributePolicy: manageValues check (should be handled by before). ', ['user_id' => $user->id, 'attribute_id' => $productAttribute->id]);
        return false;
    }
}
