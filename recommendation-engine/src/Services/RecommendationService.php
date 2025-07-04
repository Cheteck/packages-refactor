<?php

namespace IJIDeals\RecommendationEngine\Services;

use IJIDeals\UserManagement\Models\User;
use IJIDeals\IJIProductCatalog\Models\MasterProduct;
use IJIDeals\Analytics\Models\ActivityLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RecommendationService
{
    /**
     * Get popular products based on analytics data (e.g., most viewed, most purchased).
     *
     * @param int $limit
     * @return Collection
     */
    public function getPopularProducts(int $limit = 10): Collection
    {
        // This is a simplified example. A real implementation would aggregate data
        // from ActivityLog (e.g., 'product_viewed', 'order_placed') over a period.
        // For now, let's just return some random products as a placeholder.

        // In a real scenario, you'd query ActivityLog and join with MasterProduct
        // to get the most interacted-with products.
        // Example: Most viewed products
        $popularProductIds = ActivityLog::where('event_name', 'product_viewed')
            ->select('properties->product_id', DB::raw('count(*) as views_count'))
            ->groupBy('properties->product_id')
            ->orderByDesc('views_count')
            ->limit($limit)
            ->pluck('properties->product_id');

        return MasterProduct::whereIn('id', $popularProductIds)->get();
    }

    /**
     * Get recommendations for a specific user based on their past interactions.
     *
     * @param User $user
     * @param int $limit
     * @return Collection
     */
    public function getRecommendationsForUser(User $user, int $limit = 10): Collection
    {
        // This is a simplified example. A real implementation would use more sophisticated algorithms
        // like collaborative filtering or content-based filtering.

        // For now, let's get products the user has viewed and recommend similar ones or other popular ones.
        $userViewedProductIds = ActivityLog::where('user_id', $user->id)
            ->where('event_name', 'product_viewed')
            ->pluck('properties->product_id')
            ->unique();

        if ($userViewedProductIds->isEmpty()) {
            return $this->getPopularProducts($limit); // Fallback to popular products if no history
        }

        // Get products from the same categories as viewed products
        $viewedProducts = MasterProduct::whereIn('id', $userViewedProductIds)->get();
        $categoryIds = $viewedProducts->pluck('category_id')->unique();

        $recommendedProducts = MasterProduct::whereIn('category_id', $categoryIds)
            ->whereNotIn('id', $userViewedProductIds) // Don't recommend products already viewed
            ->inRandomOrder()
            ->limit($limit)
            ->get();

        // If not enough recommendations, fill with popular products
        if ($recommendedProducts->count() < $limit) {
            $popular = $this->getPopularProducts($limit);
            $recommendedProducts = $recommendedProducts->merge($popular)->unique('id')->take($limit);
        }

        return $recommendedProducts;
    }

    /**
     * Get products related to a specific product (e.g., based on category, brand, or tags).
     *
     * @param MasterProduct $product
     * @param int $limit
     * @return Collection
     */
    public function getRelatedProducts(MasterProduct $product, int $limit = 5): Collection
    {
        return MasterProduct::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }
}
