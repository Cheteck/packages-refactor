<?php

namespace IJIDeals\Social\Models; // Changed namespace

use Illuminate\Database\Eloquent\Model;
use OpenApi\Annotations as OA; // Import OpenApi namespace

// Added use for Post model

/**
 * @OA\Schema(
 *     schema="PostAnalytics",
 *     title="PostAnalytics",
 *     description="Modèle pour le suivi des métriques de performance des posts sociaux",
 *
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64",
 *         description="ID unique des analytiques du post"
 *     ),
 *     @OA\Property(
 *         property="post_id",
 *         type="integer",
 *         format="int64",
 *         description="ID du post associé"
 *     ),
 *     @OA\Property(
 *         property="impressions",
 *         type="integer",
 *         description="Nombre de fois que le post a été affiché"
 *     ),
 *     @OA\Property(
 *         property="engagements",
 *         type="integer",
 *         description="Nombre total d'interactions (likes, commentaires, clics)"
 *     ),
 *     @OA\Property(
 *         property="shares_count",
 *         type="integer",
 *         description="Nombre de fois que le post a été partagé"
 *     ),
 *     @OA\Property(
 *         property="engagement_rate",
 *         type="number",
 *         format="float",
 *         description="Taux d'engagement calculé (engagements/impressions)"
 *     ),
 *     @OA\Property(
 *         property="estimated_reach",
 *         type="number",
 *         format="float",
 *         description="Estimation de la portée calculée basée sur les partages et les impressions"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="Date de création de l'enregistrement analytique"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         description="Date de dernière mise à jour de l'enregistrement analytique"
 *     )
 * )
 * PostAnalytics model tracks performance metrics for social media posts
 *
 * @property int $post_id
 * @property int $impressions Number of times post was displayed
 * @property int $engagements Total interactions (likes, comments, clicks)
 * @property int $shares_count Number of times post was shared
 * @property float $engagement_rate Calculated engagement rate (engagements/impressions)
 * @property float $estimated_reach Calculated reach estimate based on shares and impressions
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class PostAnalytics extends Model
{
    protected $fillable = [
        'post_id',
        'impressions',
        'engagements',
        'shares_count',
        'engagement_rate',
        'estimated_reach',
    ];

    protected $casts = [
        'engagement_rate' => 'float',
        'estimated_reach' => 'float',
    ];

    /**
     * Relationship to the associated Post
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Calculate engagement rate based on impressions and engagements
     */
    public function calculateEngagementRate(): float
    {
        if ($this->impressions > 0) {
            return ($this->engagements / $this->impressions) * 100;
        }

        return 0.0;
    }

    /**
     * Update analytics metrics
     */
    public function updateMetrics(array $metrics): void
    {
        $this->fill($metrics);
        $this->engagement_rate = $this->calculateEngagementRate();
        $this->save();
    }
}
