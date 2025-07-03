<?php

namespace IJIDeals\IJICommerce\Policies;

use IJIDeals\IJICommerce\Models\Shop;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Models\User; // Assuming the User model is in App\Models

class ShopPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        // Anyone authenticated can attempt to list shops;
        // The controller's index method should filter results based on specific roles/permissions.
        return true;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \IJIDeals\IJICommerce\Models\Shop  $shop
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Shop $shop)
    {
        // User can view a shop if they are a member of its team (any role)
        // or if the shop is public (future feature).
        // For now, let's assume if they have any role on the shop's team.
        // Spatie's $user->hasAnyRole($roleNames, $teamInstance) or checking roles directly.
        // This requires knowing all possible roles or having a 'member' meta-role/permission.

        // A simple check: does the user have *any* role assigned for this specific shop?
        // Note: Spatie's default HasRoles trait doesn't provide a simple `isMemberOfTeam($team)` method.
        // This often requires a more direct query or a custom method on the User model.
        // For simplicity here, we'll assume 'Owner' or 'Administrator' can view.
        // A more granular 'view shop' permission assigned to roles would be better.
        if ($user->hasRole(['Owner', 'Administrator', 'Editor', 'Support', 'Viewer'], $shop)) {
            return true;
        }
        return false;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        // Any authenticated user can attempt to create a shop.
        // Further restrictions (e.g., subscription, max shops) could be added.
        return $user->exists;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \IJIDeals\IJICommerce\Models\Shop  $shop
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Shop $shop)
    {
        // User can update if they are 'Owner' or 'Administrator' of this specific shop.
        return $user->hasRole(['Owner', 'Administrator'], $shop);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \IJIDeals\IJICommerce\Models\Shop  $shop
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Shop $shop)
    {
        // User can delete if they are 'Owner' of this specific shop.
        return $user->hasRole('Owner', $shop);
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \IJIDeals\IJICommerce\Models\Shop  $shop
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Shop $shop)
    {
        // If using soft deletes
        return $user->hasRole('Owner', $shop);
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \IJIDeals\IJICommerce\Models\Shop  $shop
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Shop $shop)
    {
        return $user->hasRole('Owner', $shop);
    }

    // Policies for team management within a shop
    public function manageTeam(User $user, Shop $shop)
    {
        return $user->hasRole(['Owner', 'Administrator'], $shop);
    }
}
