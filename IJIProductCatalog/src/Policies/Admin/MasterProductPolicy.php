<?php

namespace IJIDeals\IJIProductCatalog\Policies\Admin;

use App\Models\User;
use IJIDeals\IJIProductCatalog\Models\MasterProduct;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Log;

class MasterProductPolicy
{
    use HandlesAuthorization;

    private const PLATFORM_ADMIN_ROLE = 'Platform Admin';

    public function viewAny(User $user): bool
    {
        $can = $user->hasRole(self::PLATFORM_ADMIN_ROLE);
        Log::info('MasterProductPolicy: viewAny check.', ['user_id' => $user->id, 'can' => $can]);
        return $can;
    }

    public function view(User $user, MasterProduct $masterProduct): bool
    {
        $can = $user->hasRole(self::PLATFORM_ADMIN_ROLE);
        Log::info('MasterProductPolicy: view check.', ['user_id' => $user->id, 'master_product_id' => $masterProduct->id, 'can' => $can]);
        return $can;
    }

    public function create(User $user): bool
    {
        $can = $user->hasRole(self::PLATFORM_ADMIN_ROLE);
        Log::info('MasterProductPolicy: create check.', ['user_id' => $user->id, 'can' => $can]);
        return $can;
    }

    public function update(User $user, MasterProduct $masterProduct): bool
    {
        $can = $user->hasRole(self::PLATFORM_ADMIN_ROLE);
        Log::info('MasterProductPolicy: update check.', ['user_id' => $user->id, 'master_product_id' => $masterProduct->id, 'can' => $can]);
        return $can;
    }

    public function delete(User $user, MasterProduct $masterProduct): bool
    {
        $can = $user->hasRole(self::PLATFORM_ADMIN_ROLE);
        Log::info('MasterProductPolicy: delete check.', ['user_id' => $user->id, 'master_product_id' => $masterProduct->id, 'can' => $can]);
        return $can;
    }

    public function restore(User $user, MasterProduct $masterProduct): bool
    {
        $can = $user->hasRole(self::PLATFORM_ADMIN_ROLE);
        Log::info('MasterProductPolicy: restore check.', ['user_id' => $user->id, 'master_product_id' => $masterProduct->id, 'can' => $can]);
        return $can;
    }

    public function forceDelete(User $user, MasterProduct $masterProduct): bool
    {
        $can = $user->hasRole(self::PLATFORM_ADMIN_ROLE);
        Log::info('MasterProductPolicy: forceDelete check.', ['user_id' => $user->id, 'master_product_id' => $masterProduct->id, 'can' => $can]);
        return $can;
    }
}
