<?php

namespace IJIDeals\FileManagement\Policies;

use IJIDeals\FileManagement\Models\Attachment;
use IJIDeals\UserManagement\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization; // Assuming default User model

class AttachmentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the given attachment.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(?User $user, Attachment $attachment)
    {
        // Allow if the user owns the attachment
        return $user && $user->id === $attachment->user_id;
    }

    /**
     * Determine whether the user can create attachments.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        // Any authenticated user can create attachments.
        // You might want to add more specific role/permission checks here.
        return $user !== null;
    }

    /**
     * Determine whether the user can delete the attachment.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Attachment $attachment)
    {
        // Allow if the user owns the attachment
        return $user->id === $attachment->user_id;
    }

    /**
     * Determine whether the user can update the attachment.
     * (Placeholder - not explicitly requested but good to have)
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Attachment $attachment)
    {
        // Allow if the user owns the attachment
        return $user->id === $attachment->user_id;
    }

    // Add other policy methods like restore, forceDelete if using soft deletes
}
