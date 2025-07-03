<?php

namespace IJIDeals\Analytics\Traits;

use IJIDeals\Analytics\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

trait HasHistory
{
    /**
     * Boot method du trait.
     *
     * @return void
     */
    public static function bootHasHistory()
    {
        // TODO: Implémenter la méthode recordActivity ou utiliser un trait compatible Eloquent
        static::created(function (\Illuminate\Database\Eloquent\Model $model) {
            // @phpstan-ignore-next-line
            $model->recordActivity('created');
        });

        static::updated(function (\Illuminate\Database\Eloquent\Model $model) {
            // @phpstan-ignore-next-line
            $model->recordActivity('updated');
        });

        static::deleted(function (\Illuminate\Database\Eloquent\Model $model) {
            // @phpstan-ignore-next-line
            $model->recordActivity('deleted');
        });

        if (method_exists(static::class, 'restored')) {
            static::restored(function (\Illuminate\Database\Eloquent\Model $model) {
                // @phpstan-ignore-next-line
                $model->recordActivity('restored');
            });
        }
    }

    /**
     * Enregistre une activité pour ce modèle.
     *
     * @return void
     */
    public function recordActivity(string $event, array $properties = [])
    {
        $userId = Auth::id() ?: null;

        $changes = [];

        if ($event === 'updated') {
            $changes = $this->getChanges();

            // Exclure les champs à ne pas suivre
            foreach ($this->getHistoryExcludedFields() as $field) {
                unset($changes[$field]);
            }

            // Si aucun changement pertinent, ne rien enregistrer
            if (empty($changes)) {
                return;
            }
        }

        ActivityLog::create([
            'user_id' => $userId,
            'loggable_type' => get_class($this),
            'loggable_id' => $this->getKey(),
            'event' => $event,
            'properties' => array_merge($properties, [
                'changes' => $changes,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]),
        ]);
    }

    /**
     * Obtenir les activités associées à ce modèle.
     */
    public function activities()
    {
        return $this->morphMany(ActivityLog::class, 'loggable');
    }

    /**
     * Liste des champs exclus de l'historique.
     */
    protected function getHistoryExcludedFields(): array
    {
        return property_exists($this, 'historyExcluded')
            ? $this->historyExcluded
            : ['updated_at', 'created_at'];
    }

    /**
     * Historique des changements de ce modèle.
     *
     * @param  int  $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getHistory($limit = 10)
    {
        return $this->activities()
            ->with('user')
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Historique des changements d'un champ spécifique.
     *
     * @param  int  $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getFieldHistory(string $field, $limit = 10)
    {
        return $this->activities()
            ->with('user')
            ->where('event', 'updated')
            ->whereRaw("JSON_EXTRACT(properties, '$.changes.{$field}') IS NOT NULL")
            ->latest()
            ->limit($limit)
            ->get();
    }
}
