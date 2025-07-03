<?php

namespace IJIDeals\Social\Traits;

use IJIDeals\Social\Models\Reaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

trait HasReaction
{
    public function reactions(): MorphMany
    {
        return $this->morphMany(Reaction::class, 'interactable');
    }

    public function addReaction(string $reactionType, ?Model $user = null): Reaction
    {
        $user = $user ?? auth()->user();
        $this->ensureAuthenticated($user);
        $this->validateReactionType($reactionType);
        if ($this->hasUserReaction($user)) {
            Log::warning('Attempted to add duplicate reaction.', [
                'user_id' => $user->id,
                'interactable_id' => $this->id,
                'interactable_type' => get_class($this),
                'type' => $reactionType,
            ]);
            throw new InvalidArgumentException('User has already reacted to this item.');
        }
        try {
            return DB::transaction(function () use ($reactionType, $user) {
                $reaction = new Reaction([
                    'user_id' => $user->id,
                    'interactable_id' => $this->id,
                    'interactable_type' => get_class($this),
                    'type' => $reactionType,
                ]);
                $this->reactions()->save($reaction);
                $this->invalidateReactionCache();
                Log::info('Reaction added to model.', [
                    'user_id' => $user->id,
                    'interactable_id' => $this->id,
                    'interactable_type' => get_class($this),
                    'type' => $reactionType,
                    'reaction_id' => $reaction->id,
                ]);
                // Adapter l'event si factorisé dans le package social
                if (class_exists('IJIDeals\Social\Events\ReactionAdded')) {
                    event(new \IJIDeals\Social\Events\ReactionAdded($reaction));
                }

                return $reaction;
            });
        } catch (\Exception $e) {
            Log::error('Failed to add reaction to model.', [
                'user_id' => $user->id,
                'interactable_id' => $this->id,
                'interactable_type' => get_class($this),
                'type' => $reactionType,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function removeReaction(?Model $user = null): bool
    {
        $user = $user ?? auth()->user();
        $this->ensureAuthenticated($user);
        try {
            $result = DB::transaction(function () use ($user) {
                $result = $this->reactions()
                    ->where('user_id', $user->id)
                    ->delete();
                if ($result) {
                    $this->invalidateReactionCache();
                    Log::info('Reaction removed from model.', [
                        'user_id' => $user->id,
                        'interactable_id' => $this->id,
                        'interactable_type' => get_class($this),
                    ]);
                    if (class_exists('IJIDeals\Social\Events\ReactionRemoved')) {
                        event(new \IJIDeals\Social\Events\ReactionRemoved($this, $user));
                    }
                }

                return (bool) $result;
            });

            return $result;
        } catch (\Exception $e) {
            Log::error('Failed to remove reaction from model.', [
                'user_id' => $user->id,
                'interactable_id' => $this->id,
                'interactable_type' => get_class($this),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function hasUserReaction(?Model $user = null): bool
    {
        $user = $user ?? auth()->user();
        $this->ensureAuthenticated($user);
        $cacheKey = $this->getReactionCacheKey($user);

        return Cache::remember($cacheKey, now()->addMinutes(60), function () use ($user) {
            return $this->reactions()
                ->where('user_id', $user->id)
                ->exists();
        });
    }

    public function getUserReactionType(?Model $user = null): ?string
    {
        $user = $user ?? auth()->user();
        $this->ensureAuthenticated($user);
        $cacheKey = $this->getReactionTypeCacheKey($user);

        return Cache::remember($cacheKey, now()->addMinutes(60), function () use ($user) {
            $reaction = $this->reactions()
                ->where('user_id', $user->id)
                ->select('type')
                ->first();

            return $reaction ? $reaction->type : null;
        });
    }

    public function updateReaction(string $reactionType, ?Model $user = null): Reaction
    {
        $user = $user ?? auth()->user();
        $this->ensureAuthenticated($user);
        $this->validateReactionType($reactionType);
        $existing = $this->reactions()->where('user_id', $user->id)->first();
        if ($existing) {
            $existing->type = $reactionType;
            $existing->save();
            $this->invalidateReactionCache();
            Log::info('Reaction updated on model.', [
                'user_id' => $user->id,
                'interactable_id' => $this->id,
                'interactable_type' => get_class($this),
                'type' => $reactionType,
            ]);
            if (class_exists('IJIDeals\Social\Events\ReactionUpdated')) {
                event(new \IJIDeals\Social\Events\ReactionUpdated($existing));
            }

            return $existing;
        }

        return $this->addReaction($reactionType, $user);
    }

    public function countReactions(string $reactionType): int
    {
        $cacheKey = $this->getReactionCountCacheKey($reactionType);

        return Cache::remember($cacheKey, now()->addMinutes(60), function () use ($reactionType) {
            return $this->reactions()->where('type', $reactionType)->count();
        });
    }

    public function getReactionCountsByType(): \Illuminate\Support\Collection
    {
        $cacheKey = $this->getReactionCountsByTypeCacheKey();

        return Cache::remember($cacheKey, now()->addMinutes(60), function () {
            return $this->reactions()
                ->select('type', DB::raw('count(*) as total'))
                ->groupBy('type')
                ->pluck('total', 'type');
        });
    }

    public function getReactingUsers(?string $reactionType = null): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = $this->getReactingUsersCacheKey($reactionType);

        return Cache::remember($cacheKey, now()->addMinutes(60), function () use ($reactionType) {
            $query = $this->reactions();
            if ($reactionType) {
                $query->where('type', $reactionType);
            }

            return $query->with('user')->get();
        });
    }

    public function toggleReaction(string $reactionType, ?Model $user = null)
    {
        $user = $user ?? auth()->user();
        $this->ensureAuthenticated($user);
        if ($this->hasUserReaction($user)) {
            $this->removeReaction($user);

            return false;
        } else {
            $this->addReaction($reactionType, $user);

            return true;
        }
    }

    protected function ensureAuthenticated(?Model $user): void
    {
        if (! $user) {
            throw new InvalidArgumentException('User must be authenticated.');
        }
    }

    protected function validateReactionType(string $type): void
    {
        if (! is_string($type) || empty($type)) {
            throw new InvalidArgumentException('Invalid reaction type.');
        }
    }

    protected function getReactionCacheKey(Model $user): string
    {
        return 'reaction:'.get_class($this).':'.$this->id.':user:'.$user->id;
    }

    protected function getReactionTypeCacheKey(Model $user): string
    {
        return 'reaction_type:'.get_class($this).':'.$this->id.':user:'.$user->id;
    }

    protected function getReactionCountCacheKey(string $reactionType): string
    {
        return 'reaction_count:'.get_class($this).':'.$this->id.':type:'.$reactionType;
    }

    protected function getReactionCountsByTypeCacheKey(): string
    {
        return 'reaction_counts:'.get_class($this).':'.$this->id;
    }

    protected function getReactingUsersCacheKey(?string $reactionType): string
    {
        return 'reacting_users:'.get_class($this).':'.$this->id.':type:'.($reactionType ?? 'all');
    }

    protected function invalidateReactionCache(): void
    {
        // Invalider tous les caches liés aux réactions pour ce modèle
        // (À adapter selon la logique de cache de l'application)
    }
}
