<?php

namespace IJIDeals\IJIProductCatalog\Policies\Admin;

use App\Models\User;
use IJIDeals\IJIProductCatalog\Models\MasterProduct;
use IJIDeals\IJIProductCatalog\Models\MasterProductVariation;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Log;

class MasterProductVariationPolicy
{
    use HandlesAuthorization;

    private const PLATFORM_ADMIN_ROLE = 'Platform Admin';

    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user, $ability): ?bool
    {
        $can = $user->hasRole(self::PLATFORM_ADMIN_ROLE);
        Log::info('MasterProductVariationPolicy: before check.', ['user_id' => $user->id, 'ability' => $ability, 'can' => $can]);
        if ($can) {
            return true;
        }
        return null;
    }

    /**
     * Determine whether the user can view any models.
     * (Listing variations for a specific master product)
     */
    public function viewAny(User $user, MasterProduct $masterProduct): bool
    {
        Log::info('MasterProductVariationPolicy: viewAny check (should be handled by before). ', ['user_id' => $user->id, 'master_product_id' => $masterProduct->id]);
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, MasterProductVariation $variation): bool
    {
        Log::info('MasterProductVariationPolicy: view check (should be handled by before). ', ['user_id' => $user->id, 'variation_id' => $variation->id]);
        return false;
    }

    /**
     * Determine whether the user can create models.
     * (Creating a variation for a specific master product)
     */
    public function create(User $user, MasterProduct $masterProduct): bool
    {
        Log::info('MasterProductVariationPolicy: create check (should be handled by before). ', ['user_id' => $user->id, 'master_product_id' => $masterProduct->id]);
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, MasterProductVariation $variation): bool
    {
        Log::info('MasterProductVariationPolicy: update check (should be handled by before). ', ['user_id' => $user->id, 'variation_id' => $variation->id]);
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, MasterProductVariation $variation): bool
    {
        Log::info('MasterProductVariationPolicy: delete check (should be handled by before). ', ['user_id' => $user->id, 'variation_id' => $variation->id]);
        return false;
    }
}
