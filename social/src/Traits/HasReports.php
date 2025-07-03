<?php

namespace IJIDeals\Social\Traits;

use IJIDeals\Social\Models\Report; // Changed to new Report model namespace
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasReports
{
    /**
     * Relation polymorphique vers les signalements associés à ce modèle.
     */
    public function reports(): MorphMany
    {
        // Définit la relation morphMany vers le modèle Report
        return $this->morphMany(Report::class, 'reportable');
    }

    /**
     * Crée un nouveau signalement pour ce modèle.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $reporter  L'utilisateur ou l'entité qui signale.
     * @param  string  $reason  La raison du signalement.
     * @param  string|null  $details  Détails supplémentaires sur le signalement.
     * @return Report L'instance du signalement créé. // Changed PHPDoc
     */
    public function report(Model $reporter, string $reason, ?string $details = null): Report
    {
        // Crée un nouveau signalement associé à ce modèle et au rapporteur
        return $this->reports()->create([
            'reporter_id' => $reporter->getKey(), // Assure que le rapporteur a une clé primaire
            'reason' => $reason,
            'details' => $details,
            // Le statut par défaut 'pending' est géré dans le modèle Report
        ]);
    }

    /**
     * Vérifie si ce modèle a été signalé par un rapporteur spécifique.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $reporter  L'utilisateur ou l'entité à vérifier.
     * @return bool True si le modèle a été signalé par ce rapporteur, False sinon.
     */
    public function isReportedBy(Model $reporter): bool
    {
        // Vérifie l'existence d'un signalement par ce rapporteur pour ce modèle
        return $this->reports()
            ->where('reporter_id', $reporter->getKey())
            ->exists();
    }

    /**
     * Récupère le nombre total de signalements pour ce modèle.
     *
     * @return int Le nombre total de signalements.
     */
    public function getReportsCountAttribute(): int
    {
        // Compte le nombre de signalements associés à ce modèle
        return $this->reports()->count();
    }
}
