<?php

namespace IJIDeals\Social\Traits;

use IJIDeals\UserManagement\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasFollowers
{
    /**
     * Obtenir tous les followers de ce modèle.
     */
    public function followers(): MorphToMany
    {
        // Unified polymorphic relationship for all followable models
        return $this->morphToMany(User::class, 'followable', 'follows', 'followable_id', 'user_id')
            ->withTimestamps(); // Assuming 'follows' table has timestamps
    }

    /**
     * Obtenir le nombre de followers.
     */
    public function getFollowersCountAttribute(): int
    {
        return $this->followers()->count();
    }

    /**
     * Vérifier si l'utilisateur donné suit ce modèle.
     *
     * @param  User|int|null  $user
     */
    public function isFollowedBy($user = null): bool
    {
        if (is_null($user) && auth()->guest()) {
            return false;
        }

        $userId = $user instanceof User ? $user->id : ($user ?: auth()->id());

        return $this->followers()
            ->where('users.id', $userId) // Ensure 'users.id' if there's ambiguity, or just 'id' if context is clear
            ->exists();
    }

    /**
     * Suivre ce modèle par un utilisateur.
     *
     * @param  User|int|null  $user
     */
    public function follow($user = null): bool
    {
        if ($this->isFollowedBy($user)) {
            return false; // Already followed
        }

        $userId = $user instanceof User ? $user->id : ($user ?: auth()->id());

        if (! $userId) {
            return false;
        } // Cannot follow if user is not identified

        $this->followers()->attach($userId);

        // Déclencher l'événement de nouveau follower
        $this->triggerFollowEvent('followed', $userId);

        return true;
    }

    /**
     * Ne plus suivre ce modèle.
     *
     * @param  User|int|null  $user
     */
    public function unfollow($user = null): bool
    {
        if (! $this->isFollowedBy($user)) {
            return false; // Not following, nothing to unfollow
        }

        $userId = $user instanceof User ? $user->id : ($user ?: auth()->id());

        if (! $userId) {
            return false;
        }

        $this->followers()->detach($userId);

        // Déclencher l'événement de suppression de follower
        $this->triggerFollowEvent('unfollowed', $userId);

        return true;
    }

    /**
     * Toggle le statut de suivi de ce modèle.
     *
     * @param  User|int|null  $user
     */
    public function toggleFollow($user = null): bool
    {
        if (is_null($user) && auth()->guest()) {
            return false; // Or handle based on guest policy
        }
        $userId = $user instanceof User ? $user->id : ($user ?: auth()->id());
        if (! $userId) {
            return false;
        }

        if ($this->isFollowedBy($userId)) {
            return $this->unfollow($userId);
        } else {
            return $this->follow($userId);
        }
    }

    /**
     * Obtenir tous les utilisateurs qui suivent ce modèle.
     * This is essentially the same as accessing the `followers` relationship directly.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function followerUsers()
    {
        return $this->followers; // The relationship already returns a collection of User models
    }

    /**
     * Déclencher l'événement de suivi.
     */
    protected function triggerFollowEvent(string $action, int $userId): void
    {
        $eventName = "eloquent.{$action}: ".static::class;

        event($eventName, [$this, $userId]);

        if (method_exists($this, 'recordActivity')) {
            $this->recordActivity($action, [
                'related_user_id' => $userId,
            ]);
        }
    }
}
