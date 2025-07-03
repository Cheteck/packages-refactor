<?php

namespace IJIDeals\Analytics\Models;

// use IJIDeals\UserManagement\Models\User; // Will use configured model
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="TrackableView",
 *     title="TrackableView",
 *     description="Modèle pour le suivi des vues d'entités traçables",
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
 *         property="user_id",
 *         type="integer",
 *         format="int64",
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="source",
 *         type="string",
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="session_id",
 *         type="string",
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="device_type",
 *         type="string",
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="ip_address",
 *         type="string",
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="referrer",
 *         type="string",
 *         nullable=true
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
 */
class TrackableView extends Model
{
    protected $fillable = [
        'trackable_id',
        'trackable_type',
        'user_id',
        'source',
        'session_id',
        'device_type',
        'ip_address',
        'referrer',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Relation avec l'entité trackable
     */
    public function trackable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Relation avec l'utilisateur
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('user-management.model', \App\Models\User::class));
    }

    /**
     * Anonymise l'adresse IP pour la conformité RGPD
     */
    protected static function booted(): void
    {
        static::creating(function ($view) {
            if ($view->ip_address) {
                $view->ip_address = self::anonymizeIp($view->ip_address);
            }
        });
    }

    /**
     * Anonymise une adresse IP
     */
    protected static function anonymizeIp(string $ip): string
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return preg_replace('/\.\d+$/', '.0', $ip);
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return preg_replace('/:[^:]+$/', ':0000', $ip);
        }

        return $ip;
    }
}
