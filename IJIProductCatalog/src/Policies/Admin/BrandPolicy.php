<?php

namespace IJIDeals\IJIProductCatalog\Policies\Admin;

use App\Models\User;
use IJIDeals\IJIProductCatalog\Models\Brand;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Log;

class BrandPolicy
{
    use HandlesAuthorization;

    private const PLATFORM_ADMIN_ROLE = 'Platform Admin';

    public function viewAny(User $user): bool
    {
        $can = $user->hasRole(self::PLATFORM_ADMIN_ROLE);
        Log::info('BrandPolicy: viewAny check.', ['user_id' => $user->id, 'can' => $can]);
        return $can;
    }

    public function view(User $user, Brand $brand): bool
    {
        $can = $user->hasRole(self::PLATFORM_ADMIN_ROLE);
        Log::info('BrandPolicy: view check.', ['user_id' => $user->id, 'brand_id' => $brand->id, 'can' => $can]);
        return $can;
    }

    public function create(User $user): bool
    {
        $can = $user->hasRole(self::PLATFORM_ADMIN_ROLE);
        Log::info('BrandPolicy: create check.', ['user_id' => $user->id, 'can' => $can]);
        return $can;
    }

    public function update(User $user, Brand $brand): bool
    {
        $can = $user->hasRole(self::PLATFORM_ADMIN_ROLE);
        Log::info('BrandPolicy: update check.', ['user_id' => $user->id, 'brand_id' => $brand->id, 'can' => $can]);
        return $can;
    }

    public function delete(User $user, Brand $brand): bool
    {
        $can = $user->hasRole(self::PLATFORM_ADMIN_ROLE);
        Log::info('BrandPolicy: delete check.', ['user_id' => $user->id, 'brand_id' => $brand->id, 'can' => $can]);
        return $can;
    }

    public function restore(User $user, Brand $brand): bool
    {
        $can = $user->hasRole(self::PLATFORM_ADMIN_ROLE);
        Log::info('BrandPolicy: restore check.', ['user_id' => $user->id, 'brand_id' => $brand->id, 'can' => $can]);
        return $can;
    }

    public function forceDelete(User $user, Brand $brand): bool
    {
        $can = $user->hasRole(self::PLATFORM_ADMIN_ROLE);
        Log::info('BrandPolicy: forceDelete check.', ['user_id' => $user->id, 'brand_id' => $brand->id, 'can' => $can]);
        return $can;
    }
}
