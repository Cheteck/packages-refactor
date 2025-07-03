<?php

namespace IJIDeals\IJIProductCatalog\Policies\Admin;

use App\Models\User;
use IJIDeals\IJIProductCatalog\Models\Category;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Log;

class CategoryPolicy
{
    use HandlesAuthorization;

    private const PLATFORM_ADMIN_ROLE = 'Platform Admin';

    public function viewAny(User $user): bool
    {
        $can = $user->hasRole(self::PLATFORM_ADMIN_ROLE);
        Log::info('CategoryPolicy: viewAny check.', ['user_id' => $user->id, 'can' => $can]);
        return $can;
    }

    public function view(User $user, Category $category): bool
    {
        $can = $user->hasRole(self::PLATFORM_ADMIN_ROLE);
        Log::info('CategoryPolicy: view check.', ['user_id' => $user->id, 'category_id' => $category->id, 'can' => $can]);
        return $can;
    }

    public function create(User $user): bool
    {
        $can = $user->hasRole(self::PLATFORM_ADMIN_ROLE);
        Log::info('CategoryPolicy: create check.', ['user_id' => $user->id, 'can' => $can]);
        return $can;
    }

    public function update(User $user, Category $category): bool
    {
        $can = $user->hasRole(self::PLATFORM_ADMIN_ROLE);
        Log::info('CategoryPolicy: update check.', ['user_id' => $user->id, 'category_id' => $category->id, 'can' => $can]);
        return $can;
    }

    public function delete(User $user, Category $category): bool
    {
        $can = $user->hasRole(self::PLATFORM_ADMIN_ROLE);
        Log::info('CategoryPolicy: delete check.', ['user_id' => $user->id, 'category_id' => $category->id, 'can' => $can]);
        return $can;
    }

    public function restore(User $user, Category $category): bool
    {
        $can = $user->hasRole(self::PLATFORM_ADMIN_ROLE);
        Log::info('CategoryPolicy: restore check.', ['user_id' => $user->id, 'category_id' => $category->id, 'can' => $can]);
        return $can;
    }

    public function forceDelete(User $user, Category $category): bool
    {
        $can = $user->hasRole(self::PLATFORM_ADMIN_ROLE);
        Log::info('CategoryPolicy: forceDelete check.', ['user_id' => $user->id, 'category_id' => $category->id, 'can' => $can]);
        return $can;
    }
}
