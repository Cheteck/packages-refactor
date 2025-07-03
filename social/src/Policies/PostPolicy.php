<?php

namespace IJIDeals\Social\Policies;

use IJIDeals\Social\Models\Post; // Changed to new Post model namespace
use IJIDeals\UserManagement\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PostPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     * For now, basic true. Implement logic based on visibility (public, amis, prive) later.
     * e.g., if $post->visibilite === 'prive' return $user->id === $post->user_id;
     * e.g., if $post->visibilite === 'amis' check if $user is a friend of $post->user.
     */
    public function view(User $user, Post $post): bool
    {
        if ($post->visibilite === 'public') {
            return true;
        }
        if ($post->visibilite === 'prive') {
            return $user->id === $post->user_id;
        }

        // Add 'amis' logic when friend system is implemented
        // For now, default to true if not public or prive (could be an issue if 'amis' posts are common)
        // Or, more restrictively:
        // return $user->id === $post->user_id; // Only owner can see non-public/non-private posts by default
        return true; // Simplified for now
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true; // Any authenticated user can create a post
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Post $post): bool
    {
        return $user->id === $post->user_id; // Only owner can restore
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Post $post): bool
    {
        return $user->id === $post->user_id; // Only owner can force delete
    }
}
