<?php

namespace IJIDeals\Analytics\Models;

// Use the configured user model
// use IJIDeals\UserManagement\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="ActivityLog",
 *     title="ActivityLog",
 *     description="Modèle pour les journaux d'activité des utilisateurs",
 *     type="object",
 *
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64"
 *     ),
 *     @OA\Property(
 *         property="user_id",
 *         type="integer",
 *         format="int64",
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="loggable_type",
 *         type="string"
 *     ),
 *     @OA\Property(
 *         property="loggable_id",
 *         type="integer",
 *         format="int64"
 *     ),
 *     @OA\Property(
 *         property="event",
 *         type="string"
 *     ),
 *     @OA\Property(
 *         property="properties",
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
 * @mixin \Illuminate\Database\Eloquent\Builder
 *
 * @method static static create(array $attributes = [])
 * @method static \IJIDeals\Analytics\Models\ActivityLog|null find(mixed $id, array $columns = ['*'])
 * @method static \IJIDeals\Analytics\Models\ActivityLog findOrFail(mixed $id, array $columns = ['*'])
 * @method static \Illuminate\Database\Eloquent\Collection|static[] get(array $columns = ['*'])
 * @method static \IJIDeals\Analytics\Models\ActivityLog first(array $columns = ['*'])
 * @method static \IJIDeals\Analytics\Models\ActivityLog firstOrFail(array $columns = ['*'])
 * @method static \Illuminate\Database\Eloquent\Builder|static query()
 */
class ActivityLog extends Model
{
    use HasFactory;

    /**
     * Les attributs qui sont mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'loggable_type',
        'loggable_id',
        'event',
        'properties',
    ];

    /**
     * Les attributs qui doivent être typés.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'properties' => 'array',
    ];

    // The public properties $loggable and $event are redundant as these attributes
    // are dynamically accessible via Eloquent from the database columns.
    // Removing them to avoid potential shadowing or confusion.

    /**
     * Obtenir l'utilisateur qui a effectué cette action.
     */
    public function user()
    {
        return $this->belongsTo(config('user-management.model', \App\Models\User::class));
    }

    /**
     * Obtenir le modèle sur lequel cette activité a été effectuée.
     */
    public function loggable()
    {
        return $this->morphTo();
    }

    /**
     * Obtenir l'objet parent d'un modèle si applicable (ex: un produit qui appartient à une boutique).
     *
     * @return mixed|null
     */
    public function getParentModel()
    {
        if (! $this->loggable) {
            return null;
        }

        $methods = ['shop', 'user', 'product', 'category', 'auction'];

        foreach ($methods as $method) {
            // Ensure the relationship method exists and returns a model instance
            if (method_exists($this->loggable, $method) && $this->loggable->$method()->exists()) {
                return $this->loggable->$method;
            }
        }

        return null;
    }

    /**
     * Obtenir une représentation lisible de l'événement.
     */
    public function getReadableEventAttribute(): string
    {
        $events = [
            'created' => 'a créé',
            'updated' => 'a modifié',
            'deleted' => 'a supprimé',
            'restored' => 'a restauré',
            'login' => 's\'est connecté',
            'logout' => 's\'est déconnecté',
            'bid' => 'a enchéri',
            'won' => 'a gagné',
            'lost' => 'a perdu',
            'cancelled' => 'a annulé',
        ];

        return $events[$this->event] ?? $this->event;
    }

    /**
     * Obtenir le changement pour une propriété spécifique.
     *
     * @return array|null
     */
    public function getChange(string $property)
    {
        if ($this->event !== 'updated' || ! isset($this->properties['changes'][$property])) {
            return null;
        }

        return [
            'from' => $this->properties['changes'][$property][0] ?? null,
            'to' => $this->properties['changes'][$property][1] ?? null,
        ];
    }

    /**
     * Obtenir un résumé lisible des changements.
     */
    public function getChangesSummaryAttribute(): string
    {
        if ($this->event !== 'updated' || empty($this->properties['changes'])) {
            return '';
        }

        $changes = [];
        foreach ($this->properties['changes'] as $field => $values) {
            $from = $values[0] ?? '(vide)';
            $to = $values[1] ?? '(vide)';
            $changes[] = "$field: $from → $to";
        }

        return implode(', ', $changes);
    }

    /**
     * Scope pour les activités d'un utilisateur.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope pour les activités d'un certain type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('loggable_type', $type);
    }

    /**
     * Scope pour les activités sur un modèle spécifique.
     */
    public function scopeForModel($query, $model)
    {
        if (is_object($model)) {
            return $query->where('loggable_type', get_class($model))
                ->where('loggable_id', $model->getKey());
        }

        return $query->where('loggable_type', $model);
    }

    /**
     * Scope pour les activités d'un certain événement.
     */
    public function scopeOfEvent($query, string $event)
    {
        return $query->where('event', $event);
    }
}
