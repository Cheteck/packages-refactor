<?php

namespace IJIDeals\Analytics\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="TrackableStatsDaily",
 *     title="TrackableStatsDaily",
 *     description="Modèle pour les statistiques quotidiennes traçables",
 *
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64"
 *     ),
 *     @OA\Property(
 *         property="trackable_id",
 *         type="integer",
 *         format="int64"
 *     ),
 *     @OA\Property(
 *         property="trackable_type",
 *         type="string"
 *     ),
 *     @OA\Property(
 *         property="date",
 *         type="string",
 *         format="date"
 *     ),
 *     @OA\Property(
 *         property="views_count",
 *         type="integer"
 *     ),
 *     @OA\Property(
 *         property="likes_count",
 *         type="integer"
 *     ),
 *     @OA\Property(
 *         property="shares_count",
 *         type="integer"
 *     ),
 *     @OA\Property(
 *         property="comments_count",
 *         type="integer"
 *     ),
 *     @OA\Property(
 *         property="engagement_score",
 *         type="number",
 *         format="float"
 *     ),
 *     @OA\Property(
 *         property="interaction_details",
 *         type="object"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time"
 *     )
 * )
 *
 * @property int $trackable_id
 * @property string $trackable_type
 * @property \Illuminate\Support\Carbon $date
 * @property int $views_count
 * @property int $likes_count
 * @property int $shares_count
 * @property int $comments_count
 * @property float $engagement_score
 * @property array $interaction_details
 * @property-read \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Relations\MorphTo $trackable
 *
 * @mixin \Illuminate\Database\Eloquent\Builder
 *
 * @method static static where(string|\Closure $column, mixed $operator = null, mixed $value = null, string $boolean = 'and')
 * @method static static create(array $attributes = [])
 * @method static static|null find(mixed $id, array $columns = ['*'])
 * @method static static findOrFail(mixed $id, array $columns = ['*'])
 * @method static \Illuminate\Database\Eloquent\Collection|static[] get(array $columns = ['*'])
 * @method static static first(array $columns = ['*'])
 * @method static static firstOrFail(array $columns = ['*'])
 * @method static \Illuminate\Database\Eloquent\Builder|static query()
 */
class TrackableStatsDaily extends Model
{
    protected $fillable = [
        'trackable_id',
        'trackable_type',
        'date',
        'views_count',
        'likes_count',
        'shares_count',
        'comments_count',
        'engagement_score',
        'interaction_details',
    ];

    protected $casts = [
        'date' => 'date',
        'views_count' => 'integer',
        'likes_count' => 'integer',
        'shares_count' => 'integer',
        'comments_count' => 'integer',
        'engagement_score' => 'float',
        'interaction_details' => 'array',
    ];

    /**
     * Relation avec l'entité trackable
     */
    public function trackable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Calcule le score d'engagement pour la journée
     */
    public function calculateEngagementScore(): void
    {
        $this->engagement_score = ($this->views_count * 0.5) +
            ($this->likes_count * 2) +
            ($this->shares_count * 3) +
            ($this->comments_count * 5);
    }

    /**
     * Met à jour les statistiques avec de nouvelles données
     */
    public function updateStats(array $stats): void
    {
        $this->views_count += $stats['views'] ?? 0;
        $this->likes_count += $stats['likes'] ?? 0;
        $this->shares_count += $stats['shares'] ?? 0;
        $this->comments_count += $stats['comments'] ?? 0;

        // Mise à jour des détails d'interaction
        $currentDetails = $this->interaction_details ?? [];
        $newDetails = $stats['details'] ?? [];
        $this->interaction_details = array_merge_recursive($currentDetails, $newDetails);

        $this->calculateEngagementScore();
        $this->save();
    }
}
