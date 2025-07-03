<?php

namespace IJIDeals\Social\Traits;

use IJIDeals\Social\Models\Reaction; // Changed
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Trait CanReact
 *
 * Provides methods for a model (e.g., User) to manage reactions on interactable entities.
 *
 * @category Traits
 *
 * @author   Your Name <your.email@example.com>
 * @license  MIT
 *
 * @link     https://example.com/docs/reactions
 */
trait CanReact
{
    /**
     * Get all reactions made by this user.
     */
    public function reactions(): HasMany
    {
        return $this->hasMany(Reaction::class, 'user_id');
    }

    /**
     * Add a reaction to a specific interactable model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $interactable  The model to react to.
     * @param  string  $type  The type of reaction (e.g., 'like', 'heart').
     * @return Reaction The created reaction. // Changed PHPDoc
     *
     * @throws \InvalidArgumentException If the reaction type is invalid or the user is not authenticated.
     * @throws \Illuminate\Database\QueryException If the reaction cannot be saved.
     */
    public function reactTo(Model $interactable, string $type): Reaction
    {
        $this->ensureAuthenticated();
        $this->validateReactionType($type);

        // Check for existing reaction to prevent duplicates
        if ($this->hasReacted($interactable)) {
            Log::warning('Attempted to add duplicate reaction.', [
                'user_id' => auth()->id(),
                'interactable_id' => $interactable->id,
                'interactable_type' => get_class($interactable),
                'type' => $type,
            ]);
            throw new InvalidArgumentException('User has already reacted to this item.');
        }

        try {
            return DB::transaction(function () use ($interactable, $type) {
                $reaction = Reaction::addReaction($interactable, $type);

                Log::info('Reaction added successfully.', [
                    'user_id' => auth()->id(),
                    'interactable_id' => $interactable->id,
                    'interactable_type' => get_class($interactable),
                    'type' => $type,
                    'reaction_id' => $reaction->id,
                ]);

                // Optionally dispatch an event
                event(new \App\Events\ReactionAdded($reaction));

                return $reaction;
            });
        } catch (\Exception $e) {
            Log::error('Failed to add reaction.', [
                'user_id' => auth()->id(),
                'interactable_id' => $interactable->id,
                'interactable_type' => get_class($interactable),
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Remove a reaction from a specific interactable model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $interactable  The model to remove the reaction from.
     * @return bool True if the reaction was removed, false if no reaction existed.
     *
     * @throws \InvalidArgumentException If the user is not authenticated.
     * @throws \Illuminate\Database\QueryException If the reaction cannot be removed.
     */
    public function unreactTo(Model $interactable): bool
    {
        $this->ensureAuthenticated();

        try {
            $result = DB::transaction(function () use ($interactable) {
                $result = Reaction::removeReaction($interactable);

                if ($result) {
                    Log::info('Reaction removed successfully.', [
                        'user_id' => auth()->id(),
                        'interactable_id' => $interactable->id,
                        'interactable_type' => get_class($interactable),
                    ]);

                    // Optionally dispatch an event
                    event(new \App\Events\ReactionRemoved($interactable, auth()->user()));
                }

                return $result;
            });

            return $result;
        } catch (\Exception $e) {
            Log::error('Failed to remove reaction.', [
                'user_id' => auth()->id(),
                'interactable_id' => $interactable->id,
                'interactable_type' => get_class($interactable),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Check if this user has reacted to a specific interactable model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $interactable  The model to check.
     * @return bool True if the user has reacted, false otherwise.
     */
    public function hasReacted(Model $interactable): bool
    {
        return Reaction::hasReacted($interactable);
    }

    /**
     * Get the type of reaction the user has made to a specific interactable model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $interactable  The model to check.
     * @return string|null The reaction type if it exists, null otherwise.
     */
    public function getReactionType(Model $interactable): ?string
    {
        $reaction = Reaction::where('user_id', auth()->id())
            ->where('interactable_id', $interactable->id)
            ->where('interactable_type', get_class($interactable))
            ->select('type') // Optimize query by selecting only the needed column
            ->first();

        return $reaction ? $reaction->type : null;
    }

    /**
     * Update an existing reaction to a new type for a specific interactable model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $interactable  The model to update the reaction for.
     * @param  string  $type  The new reaction type.
     * @return Reaction The updated or newly created reaction. // Changed PHPDoc
     *
     * @throws \InvalidArgumentException If the reaction type is invalid or the user is not authenticated.
     * @throws \Illuminate\Database\QueryException If the reaction cannot be updated or created.
     */
    public function updateReaction(Model $interactable, string $type): Reaction
    {
        $this->ensureAuthenticated();
        $this->validateReactionType($type);

        try {
            return DB::transaction(function () use ($interactable, $type) {
                $reaction = Reaction::where('user_id', auth()->id())
                    ->where('interactable_id', $interactable->id)
                    ->where('interactable_type', get_class($interactable))
                    ->first();

                if ($reaction) {
                    if ($reaction->type !== $type) {
                        $oldType = $reaction->type;
                        $reaction->type = $type;
                        $reaction->save();

                        Log::info('Reaction type updated.', [
                            'user_id' => auth()->id(),
                            'interactable_id' => $interactable->id,
                            'interactable_type' => get_class($interactable),
                            'old_type' => $oldType,
                            'new_type' => $type,
                            'reaction_id' => $reaction->id,
                        ]);

                        // Optionally dispatch an event
                        event(new \App\Events\ReactionUpdated($reaction, $oldType));
                    }

                    return $reaction;
                }

                // If no reaction exists, create a new one
                return $this->reactTo($interactable, $type);
            });
        } catch (\Exception $e) {
            Log::error('Failed to update reaction.', [
                'user_id' => auth()->id(),
                'interactable_id' => $interactable->id,
                'interactable_type' => get_class($interactable),
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get the count of reactions made by this user across all interactable models.
     *
     * @return int The total number of reactions.
     */
    public function reactionsCount(): int
    {
        return $this->reactions()->count();
    }

    /**
     * Get reactions grouped by type for a specific interactable model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $interactable  The model to check.
     * @return \Illuminate\Support\Collection A collection of reaction types and their counts.
     */
    public function getReactionsByType(Model $interactable): \Illuminate\Support\Collection
    {
        return Reaction::where('user_id', auth()->id())
            ->where('interactable_id', $interactable->id)
            ->where('interactable_type', get_class($interactable))
            ->groupBy('type')
            ->select('type', DB::raw('count(*) as count'))
            ->get()
            ->pluck('count', 'type');
    }

    /**
     * Ensure the user is authenticated.
     *
     * @throws \InvalidArgumentException If the user is not authenticated.
     */
    protected function ensureAuthenticated(): void
    {
        if (! auth()->check()) {
            throw new InvalidArgumentException('User must be authenticated to perform this action.');
        }
    }

    /**
     * Validate the reaction type.
     *
     * @param  string  $type  The reaction type to validate.
     *
     * @throws \InvalidArgumentException If the reaction type is invalid.
     */
    protected function validateReactionType(string $type): void
    {
        $validTypes = config('reactions.valid_types', ['like', 'heart', 'smile', 'sad', 'angry']);
        if (! in_array($type, $validTypes)) {
            throw new InvalidArgumentException("Invalid reaction type: {$type}. Valid types are: ".implode(', ', $validTypes));
        }
    }
}
