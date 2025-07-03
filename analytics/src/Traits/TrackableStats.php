<?php

namespace IJIDeals\Analytics\Traits;

use Carbon\Carbon;
use IJIDeals\Analytics\Jobs\RecordViewJob;
use IJIDeals\Analytics\Models\TrackableInteraction;
use IJIDeals\Analytics\Models\TrackableStatsDaily;
use IJIDeals\Analytics\Models\TrackableView;
use IJIDeals\IJICommerce\Models\Shop;
use IJIDeals\Social\Events\NewInteraction;
use IJIDeals\Social\Notifications\HighEngagementNotification;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

trait TrackableStats
{
    /**
     * @property int $id
     */

    /**
     * Polymorphic relation for views.
     */
    public function views(): MorphMany
    {
        return $this->morphMany(TrackableView::class, 'trackable');
    }

    /**
     * Polymorphic relation for interactions.
     */
    public function interactions(): MorphMany
    {
        return $this->morphMany(TrackableInteraction::class, 'trackable');
    }

    /**
     * Records a view for the entity.
     */
    public function recordView(array $data = []): void
    {
        $data = array_merge([
            'user_id' => auth()->id(),
            'source' => request()->header('referer'),
            'session_id' => session()->getId(),
            'device_type' => request()->header('User-Agent'),
            'ip_address' => request()->ip(),
            'referrer' => request()->header('referer'), // Ajout du referrer
        ], $data);

        // TODO: Ajouter la propriété $id dans le modèle Product si nécessaire
        $cacheKey = "view:{$this->getMorphClass()}:{$this->id}:{$data['session_id']}";
        if (! Cache::has($cacheKey)) {
            RecordViewJob::dispatch($this, $data);
            Cache::put($cacheKey, true, now()->addMinutes(30));
            Log::info('View recorded', ['trackable_type' => $this->getMorphClass(), 'trackable_id' => $this->id, 'data' => $data]);
        } else {
            Log::info('Duplicate view prevented', ['trackable_type' => $this->getMorphClass(), 'trackable_id' => $this->id, 'session_id' => $data['session_id']]);
        }
    }

    /**
     * Records an interaction for the entity.
     */
    public function recordInteraction(string $type, array $data = []): void
    {
        $interaction = $this->interactions()->create([
            'user_id' => auth()->id(),
            'interaction_type' => $type,
            'details' => $data,
        ]);

        // Dispatch event for real-time notifications.
        event(new NewInteraction($interaction));
        Log::info('New interaction recorded', ['trackable_type' => $this->getMorphClass(), 'trackable_id' => $this->id, 'interaction_type' => $type, 'data' => $data]);

        // Update Redis cache for real-time statistics.
        $this->updateInteractionCache($type);
    }

    /**
     * Retrieves the total number of views.
     */
    public function getTotalViews(?string $startDate = null, ?string $endDate = null): int
    {
        $query = $this->views()->getQuery();

        if ($startDate) {
            $query->where('created_at', '>=', Carbon::parse($startDate));
        }
        if ($endDate) {
            $query->where('created_at', '<=', Carbon::parse($endDate));
        }

        $count = $query->count();
        Log::info('Total views retrieved', ['trackable_type' => $this->getMorphClass(), 'trackable_id' => $this->id, 'start_date' => $startDate, 'end_date' => $endDate, 'count' => $count]);

        return $count;
    }

    /**
     * Retrieves interaction statistics.
     */
    public function getInteractionStats(?string $startDate = null, ?string $endDate = null): array
    {
        $query = $this->interactions()->getQuery();

        if ($startDate) {
            $query->where('created_at', '>=', Carbon::parse($startDate));
        }
        if ($endDate) {
            $query->where('created_at', '<=', Carbon::parse($endDate));
        }

        $stats = $query->selectRaw('interaction_type, COUNT(*) as count')
            ->groupBy('interaction_type')
            ->pluck('count', 'interaction_type')
            ->toArray();

        Log::info('Interaction stats retrieved', ['trackable_type' => $this->getMorphClass(), 'trackable_id' => $this->id, 'start_date' => $startDate, 'end_date' => $endDate, 'stats' => $stats]);

        return $stats;
    }

    /**
     * Calculates the engagement score.
     */
    public function getEngagementScore(?string $startDate = null, ?string $endDate = null): float
    {
        $stats = $this->getInteractionStats($startDate, $endDate);
        $views = $this->getTotalViews($startDate, $endDate);

        $score = ($views * 0.5) +
            ($stats['like'] ?? 0) * 2 +
            ($stats['share'] ?? 0) * 3 +
            ($stats['comment'] ?? 0) * 5;

        Log::info('Engagement score calculated', ['trackable_type' => $this->getMorphClass(), 'trackable_id' => $this->id, 'start_date' => $startDate, 'end_date' => $endDate, 'score' => $score]);

        return $score;
    }

    /**
     * Retrieves similar entities based on interactions.
     */
    public function recommended(int $limit = 5): Collection
    {
        $interactionTypes = ['like', 'share'];
        $userIds = $this->interactions()->getQuery()
            ->whereIn('interaction_type', $interactionTypes)
            ->pluck('user_id')
            ->unique();

        if (! method_exists(static::class, 'whereHas')) {
            throw new \LogicException('The class using TrackableStats must extend Eloquent Model.');
        }

        $recommendations = static::whereHas('interactions', function ($query) use ($userIds, $interactionTypes) {
            $query->whereIn('user_id', $userIds)
                ->whereIn('interaction_type', $interactionTypes)
                ->where('trackable_id', '!=', $this->id);
        })
            ->withCount(['views', 'interactions'])
            ->orderBy('interactions_count', 'desc')
            ->limit($limit)
            ->get();

        Log::info('Recommendations retrieved', ['trackable_type' => $this->getMorphClass(), 'trackable_id' => $this->id, 'limit' => $limit, 'recommendations' => $recommendations->pluck('id')->toArray()]);

        return $recommendations;
    }

    /**
     * Updates the cache for interactions.
     * This method is designed to be flexible with different cache drivers.
     */
    protected function updateInteractionCache(string $type): void
    {
        $key = "stats:{$this->getMorphClass()}:{$this->id}";

        // Retrieve the current interaction stats from cache.
        // If the key doesn't exist, initialize with an empty array.
        $cachedStats = Cache::get($key, []);

        // Increment the count for the specific interaction type.
        // Ensure the value is an integer, defaulting to 0 if not set.
        $cachedStats[$type] = (int) ($cachedStats[$type] ?? 0) + 1;

        // Store the updated stats back into the cache with an expiration time.
        // The expiration time is set for the entire key, similar to Redis's EXPIRE command.
        Cache::put($key, $cachedStats, now()->addHours(1)); // 1 hour expiration

        Log::info('Interaction cache updated', ['trackable_type' => $this->getMorphClass(), 'trackable_id' => $this->id, 'type' => $type, 'key' => $key]);
    }

    /**
     * Checks and notifies in case of high engagement.
     */
    public function checkHighEngagement(): void
    {
        $score = $this->getEngagementScore(now()->subDays(7), now());

        if ($score > 100 && $this instanceof Shop) {
            $this->notify(new HighEngagementNotification($score));
            Log::warning('High engagement notification sent', ['trackable_type' => $this->getMorphClass(), 'trackable_id' => $this->id, 'score' => $score]);
        }
    }

    /**
     * Retrieves daily statistics.
     */
    public function getDailyStats(?string $startDate = null, ?string $endDate = null): Collection
    {
        $query = TrackableStatsDaily::where('trackable_id', $this->id)
            ->where('trackable_type', $this->getMorphClass());

        if ($startDate) {
            $query->where('date', '>=', Carbon::parse($startDate));
        }
        if ($endDate) {
            $query->where('date', '<=', Carbon::parse($endDate));
        }

        $dailyStats = $query->orderBy('date')->get();
        Log::info('Daily stats retrieved', ['trackable_type' => $this->getMorphClass(), 'trackable_id' => $this->id, 'start_date' => $startDate, 'end_date' => $endDate, 'count' => $dailyStats->count()]);

        return $dailyStats;
    }
}
