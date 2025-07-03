<?php

namespace IJIDeals\Sponsorship\Policies;

use IJIDeals\Sponsorship\Models\SponsoredPost;
use IJIDeals\UserManagement\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SponsoredPostPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can cancel the sponsored post.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function cancel(User $user, SponsoredPost $sponsoredPost)
    {
        return $user->id === $sponsoredPost->user_id;
    }
}
