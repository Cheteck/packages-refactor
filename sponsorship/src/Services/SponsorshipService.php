<?php

namespace IJIDeals\Sponsorship\Services;

use Exception;
use IJIDeals\Social\Models\Post;
use IJIDeals\Sponsorship\Models\SponsoredPost;
use IJIDeals\UserManagement\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SponsorshipService
{
    /**
     * Create a new sponsored post campaign.
     *
     * @param  User  $user  The user sponsoring the post.
     * @param  Post  $post  The post to be sponsored.
     * @param  array  $data  Campaign data (budget, cpc, cpi, targeting, dates, etc.).
     *
     * @throws Exception
     */
    public function createSponsoredPost(User $user, Post $post, array $data): SponsoredPost
    {
        // Validate required data
        if (empty($data['budget']) || (empty($data['cost_per_impression']) && empty($data['cost_per_click']))) {
            throw new Exception('Budget and at least one cost model (CPI or CPC) are required.');
        }

        // Ensure user has enough virtual coins for the budget
        if (! $user->virtualCoin || $user->virtualCoin->balance < $data['budget']) {
            throw new Exception('Insufficient virtual coin balance to fund the sponsorship budget.');
        }

        return DB::transaction(function () use ($user, $post, $data) {
            $sponsoredPost = SponsoredPost::create([
                'user_id' => $user->id,
                'post_id' => $post->id,
                'title' => $data['title'] ?? 'Sponsored: '.$post->id, // Basic title
                'description' => $data['description'] ?? null,
                'budget' => $data['budget'],
                'cost_per_impression' => $data['cost_per_impression'] ?? 0,
                'cost_per_click' => $data['cost_per_click'] ?? 0,
                'targeting' => $data['targeting'] ?? null,
                'start_date' => $data['start_date'] ?? now(),
                'end_date' => $data['end_date'] ?? now()->addDays(config('sponsorship.default_duration_days', 7)),
                'status' => $data['status'] ?? 'pending', // Or 'active' if start_date is now/past
                'spent_amount' => 0,
                'impressions' => 0,
                'clicks' => 0,
            ]);

            // Deduct budget from user's wallet and log initial funding transaction
            // This assumes the VirtualCoin model has a method like `createTransaction`
            // or this logic is handled by the VirtualCoinService.
            $user->virtualCoin->createTransaction(
                -(float) $data['budget'],
                'sponsorship_funding',
                ['sponsored_post_id' => $sponsoredPost->id, 'post_id' => $post->id],
                "Funding for sponsored post campaign #{$sponsoredPost->id}",
                'sp_fund_'.uniqid()
            );

            event(new \IJIDeals\Sponsorship\Events\SponsoredPostCreated($sponsoredPost));

            return $sponsoredPost;
        });
    }

    /**
     * Pause an active sponsored post campaign.
     */
    public function pauseCampaign(SponsoredPost $sponsoredPost): SponsoredPost
    {
        if ($sponsoredPost->status === 'active') {
            $sponsoredPost->update(['status' => 'paused']);
            event(new \IJIDeals\Sponsorship\Events\SponsoredPostPaused($sponsoredPost));
        }

        return $sponsoredPost;
    }

    /**
     * Resume a paused sponsored post campaign.
     */
    public function resumeCampaign(SponsoredPost $sponsoredPost): SponsoredPost
    {
        if ($sponsoredPost->status === 'paused' && $sponsoredPost->isActive()) { // isActive checks dates and budget
            $sponsoredPost->update(['status' => 'active']);
            event(new \IJIDeals\Sponsorship\Events\SponsoredPostResumed($sponsoredPost));
        } elseif ($sponsoredPost->status === 'paused' && ! $sponsoredPost->isActive()) {
            Log::warning("Cannot resume campaign {$sponsoredPost->id}: it's no longer valid (date or budget).");
            // Optionally change status to completed or exhausted if applicable
        }

        return $sponsoredPost;
    }

    /**
     * Cancel a sponsored post campaign.
     * (Consider refunding remaining budget)
     *
     * @param  User|null  $cancellingUser  The user initiating the cancellation (for permission checks)
     *
     * @throws Exception
     */
    public function cancelCampaign(SponsoredPost $sponsoredPost, ?User $cancellingUser = null): SponsoredPost
    {
        if ($cancellingUser && Gate::denies('cancel', [$sponsoredPost, $cancellingUser])) {
            throw new Exception('User is not authorized to cancel this sponsored post.');
        }

        $remainingBudget = $sponsoredPost->calculateRemainingBudget();

        DB::transaction(function () use ($sponsoredPost, $remainingBudget) {
            $sponsoredPost->update(['status' => 'cancelled']);

            if ($remainingBudget > 0 && $sponsoredPost->user && $sponsoredPost->user->virtualCoin) {
                $sponsoredPost->user->virtualCoin->createTransaction(
                    (float) $remainingBudget,
                    'sponsorship_refund',
                    ['sponsored_post_id' => $sponsoredPost->id, 'post_id' => $sponsoredPost->post_id],
                    "Refund for cancelled sponsored post campaign #{$sponsoredPost->id}",
                    'sp_refund_'.uniqid()
                );
                // Update spent amount if it was pre-deducted, or adjust based on actual spend.
                // For this model, spent_amount reflects actual spend, so refunding is correct.
            }
            event(new \IJIDeals\Sponsorship\Events\SponsoredPostCancelled($sponsoredPost));
        });

        return $sponsoredPost;
    }

    /**
     * Process daily or periodic checks for campaigns (e.g., end date, budget exhaustion).
     * This would typically be called by a scheduled job.
     */
    public function processCampaigns(): void
    {
        // Find active campaigns that have passed their end date
        $endedCampaigns = SponsoredPost::where('status', 'active')
            ->where('end_date', '<', now())
            ->get();

        foreach ($endedCampaigns as $campaign) {
            $campaign->update(['status' => 'completed']);
            Log::info("Sponsored post campaign #{$campaign->id} completed due to end date.");
            event(new \IJIDeals\Sponsorship\Events\SponsoredPostCompleted($campaign));
        }

        // Find active campaigns where budget might be exhausted (though recordImpression/Click should handle this)
        // This is a safeguard or if budget check is complex.
        $exhaustedCampaigns = SponsoredPost::where('status', 'active')
            // ->whereRaw('spent_amount >= budget') // This condition should ideally be caught by isActive()
            ->get();

        foreach ($exhaustedCampaigns as $campaign) {
            if ($campaign->calculateRemainingBudget() <= 0 && $campaign->status === 'active') {
                $campaign->update(['status' => 'exhausted_budget']);
                Log::info("Sponsored post campaign #{$campaign->id} marked as exhausted_budget.");
                event(new \IJIDeals\Sponsorship\Events\SponsoredPostBudgetExhausted($campaign));
            }
        }
    }

    /**
     * Get active sponsored posts for display logic.
     *
     * @param  array  $targetingCriteria  (e.g., ['gender' => 'male', 'age_range' => '18-24'])
     */
    public function getActiveSponsoredPostsForDisplay(array $targetingCriteria = [], int $limit = 5): \Illuminate\Database\Eloquent\Collection
    {
        $query = SponsoredPost::active()
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->whereRaw('budget > spent_amount')
            ->inRandomOrder()
            ->limit($limit);

        // Basic targeting implementation (can be expanded)
        if (! empty($targetingCriteria)) {
            // Example: if targetingCriteria has 'gender'
            if (isset($targetingCriteria['gender'])) {
                $query->whereJsonContains('targeting->gender', $targetingCriteria['gender']);
            }
            // Add more targeting criteria as needed
        }

        return $query->get();
    }
}
