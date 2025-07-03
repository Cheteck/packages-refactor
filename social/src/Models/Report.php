<?php

namespace IJIDeals\Social\Models; // Changed namespace

use IJIDeals\UserManagement\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // Added use for User model
use Illuminate\Database\Eloquent\Relations\MorphTo;
use OpenApi\Annotations as OA; // Import OpenApi namespace

/**
 * @OA\Schema(
 *     schema="Report",
 *     title="Report",
 *     description="Modèle représentant un signalement d'abus ou de contenu inapproprié",
 *
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64",
 *         description="ID unique du signalement"
 *     ),
 *     @OA\Property(
 *         property="reporter_id",
 *         type="integer",
 *         format="int64",
 *         description="ID de l'utilisateur qui a fait le signalement"
 *     ),
 *     @OA\Property(
 *         property="reportable_id",
 *         type="integer",
 *         format="int64",
 *         description="ID de l'entité (post, commentaire, utilisateur, etc.) qui est signalée"
 *     ),
 *     @OA\Property(
 *         property="reportable_type",
 *         type="string",
 *         description="Type du modèle de l'entité qui est signalée (polymorphique)"
 *     ),
 *     @OA\Property(
 *         property="reason",
 *         type="string",
 *         description="Raison du signalement (e.g., 'spam', 'harcèlement', 'nudité')",
 *         example="spam"
 *     ),
 *     @OA\Property(
 *         property="details",
 *         type="string",
 *         nullable=true,
 *         description="Détails supplémentaires fournis par le signaleur"
 *     ),
 *     @OA\Property(
 *         property="status",
 *         type="string",
 *         description="Statut du signalement",
 *         enum={"pending", "reviewed", "dismissed", "actioned"},
 *         default="pending"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="Date de création du signalement"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         description="Date de dernière mise à jour du signalement"
 *     )
 * )
 */
class Report extends Model
{
    use HasFactory;

    /**
     * Les attributs qui sont mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'reporter_id',
        'reportable_id',
        'reportable_type',
        'reason',
        'details',
        'status',
    ];

    /**
     * Les statuts possibles pour un signalement.
     */
    const STATUS_PENDING = 'pending';

    const STATUS_REVIEWED = 'reviewed';

    const STATUS_DISMISSED = 'dismissed';

    const STATUS_ACTIONED = 'actioned';

    /**
     * Relation vers l'élément signalé (polymorphique).
     */
    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Relation vers l'utilisateur qui a fait le signalement.
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    /**
     * Scope pour filtrer les signalements par statut.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope pour filtrer les signalements par type d'élément signalé.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('reportable_type', $type);
    }

    /**
     * Marquer le signalement comme examiné.
     */
    public function markAsReviewed(): void
    {
        $this->update(['status' => self::STATUS_REVIEWED]);
    }

    /**
     * Marquer le signalement comme rejeté.
     */
    public function dismiss(): void
    {
        $this->update(['status' => self::STATUS_DISMISSED]);
    }

    /**
     * Marquer le signalement comme traité (action prise).
     */
    public function markAsActioned(): void
    {
        $this->update(['status' => self::STATUS_ACTIONED]);
    }

    /**
     * Crée un nouveau rapport.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public static function create(array $attributes = [])
    {
        // Définir le statut par défaut si non spécifié
        if (! isset($attributes['status'])) {
            $attributes['status'] = self::STATUS_PENDING;
        }

        return static::query()->create($attributes);
    }

    // TODO: Move this model to packages/social/src/Models/Report.php
    // TODO: Use Social\Traits\CanReport for report logic
}
