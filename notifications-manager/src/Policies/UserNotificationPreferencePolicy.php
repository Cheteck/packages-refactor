<?php

namespace IJIDeals\NotificationsManager\Policies;

use IJIDeals\NotificationsManager\Models\UserNotificationPreference;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserNotificationPreferencePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  mixed  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny($user)
    {
        // Typically, users can only view their own preferences.
        // Admin might view all, handled by a different policy or Gate::before.
        return true; // Controller will scope to authenticated user.
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  mixed  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view($user, UserNotificationPreference $userNotificationPreference)
    {
        return $user->id === $userNotificationPreference->user_id;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  mixed  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create($user)
    {
        // Users create/update their preferences through the service, not direct model creation usually.
        return true; // Controller actions will be for the authenticated user.
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  mixed  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update($user, UserNotificationPreference $userNotificationPreference)
    {
        return $user->id === $userNotificationPreference->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  mixed  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete($user, UserNotificationPreference $userNotificationPreference)
    {
        // Preferences are typically not deleted, but enabled/disabled.
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  mixed  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore($user, UserNotificationPreference $userNotificationPreference)
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  mixed  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete($user, UserNotificationPreference $userNotificationPreference)
    {
        return false;
    }
}
