<?php

namespace IJIDeals\Social\Models; // Changed namespace

use IJIDeals\UserManagement\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
// Added use for Post model
// Added use for PostPollVote model
use Illuminate\Support\Facades\Auth; // Added use for User model
use OpenApi\Annotations as OA; // Import OpenApi namespace

/**
 * @OA\Schema(
 *     schema="PostPoll",
 *     title="PostPoll",
 *     description="Modèle représentant un sondage associé à un post",
 *
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64"
 *     ),
 *     @OA\Property(
 *         property="post_id",
 *         type="integer",
 *         format="int64"
 *     ),
 *     @OA\Property(
 *         property="question",
 *         type="string"
 *     ),
 *     @OA\Property(
 *         property="options",
 *         type="array",
 *
 *         @OA\Items(
 *             type="object",
 *
 *             @OA\Property(property="id", type="integer"),
 *             @OA\Property(property="text", type="string")
 *         ),
 *         example={{"id": 1, "text": "Option A"}, {"id": 2, "text": "Option B"}}
 *     ),
 *     @OA\Property(
 *         property="ends_at",
 *         type="string",
 *         format="date-time",
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
 *     ),
 *     @OA\Property(
 *         property="total_votes",
 *         type="integer",
 *         readOnly=true
 *     ),
 *     @OA\Property(
 *         property="display_options",
 *         type="array",
 *
 *         @OA\Items(
 *             type="object",
 *
 *             @OA\Property(property="id", type="integer"),
 *             @OA\Property(property="text", type="string"),
 *             @OA\Property(property="votes_count", type="integer", nullable=true),
 *             @OA\Property(property="percentage", type="number", format="float", nullable=true)
 *         ),
 *         readOnly=true
 *     )
 * )
 * Modèle représentant un sondage associé à un post
 */
class PostPoll extends Model
{
    /** @var int */
    public $id;

    /** @var int */
    public $post_id;

    /** @var string */
    public $question;

    /** @var array */
    public $options;

    /** @var \Carbon\Carbon|string|null */
    public $ends_at;

    /** @var int */
    public $total_votes; // Accessed via accessor getTotalVotesAttribute

    // Colonnes qui peuvent être assignées massivement
    protected $fillable = [
        'question', // La question du sondage
        'options', // Les options du sondage (stockées en JSON)
        'ends_at', // Date et heure de fin du sondage
    ];

    // Casts pour convertir les attributs en types natifs
    protected $casts = [
        'options' => 'array', // Convertit la colonne 'options' en tableau PHP
        'ends_at' => 'datetime', // Convertit la colonne 'ends_at' en instance Carbon
    ];

    // Relation : Un sondage appartient à un post
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    // Relation : Un sondage a plusieurs votes
    public function votes(): HasMany
    {
        return $this->hasMany(PostPollVote::class);
    }

    // Retourne les options du sondage
    // Utilise le cast 'array' défini pour la colonne 'options'
    public function options(): array
    {
        return $this->options;
    }

    // Accesseur pour obtenir le nombre total de votes pour ce sondage
    // Calcule le total en comptant les enregistrements dans la relation 'votes'
    public function getTotalVotesAttribute(): int
    {
        // Utilise count() sur la relation pour obtenir le nombre de votes
        return $this->votes()->count();
    }

    // Vérifie si un utilisateur donné a déjà voté pour ce sondage
    // Retourne true si l'utilisateur a un vote associé à ce sondage, false sinon
    public function hasVoted(?User $user): bool
    {
        // Si l'utilisateur n'est pas authentifié, il n'a pas voté
        if (! $user) {
            return false;
        }

        // Vérifie l'existence d'un vote de cet utilisateur pour ce sondage
        return $this->votes()->where('user_id', $user->id)->exists();
    }

    // Calcule le nombre de votes pour une option spécifique du sondage
    // L'option est identifiée par son ID (stocké dans le tableau 'options')
    public function getOptionVotes(int $optionId): int
    {
        // Compte les votes associés à cette option ID
        return $this->votes()->where('option_id', $optionId)->count();
    }

    // Calcule le pourcentage de votes pour une option spécifique
    // Retourne un float représentant le pourcentage (arrondi à 1 décimale)
    public function getOptionPercentage(int $optionId): float
    {
        $totalVotes = $this->total_votes; // Utilise l'accesseur pour le total des votes
        $optionVotes = $this->getOptionVotes($optionId);

        // Évite la division par zéro si aucun vote n'a été enregistré
        if ($totalVotes === 0) {
            return 0.0;
        }

        // Calcule et retourne le pourcentage
        return round(($optionVotes / $totalVotes) * 100, 1);
    }

    // Accesseur pour obtenir les options du sondage enrichies avec les résultats (votes et pourcentage)
    // Les résultats sont inclus si le sondage est terminé ou si l'utilisateur authentifié a voté
    // Cette méthode prépare les données pour l'affichage dans la vue
    // Elle retourne un tableau d'options, où chaque option est un tableau
    public function getDisplayOptionsAttribute(): array
    {
        $optionsArray = $this->options; // Récupère le tableau d'options original (depuis le cast JSON)
        $user = Auth::user(); // Récupère l'utilisateur authentifié

        // Ensure $user is an instance of \IJIDeals\UserManagement\Models\User for hasVoted
        $currentUser = ($user instanceof User) ? $user : null;

        // Détermine si les résultats doivent être affichés : sondage terminé OU utilisateur a voté
        $showResults = ($this->ends_at && $this->ends_at->isPast()) || $this->hasVoted($currentUser);

        $displayOptions = [];
        $totalVotes = $this->total_votes; // Récupère le total des votes une seule fois pour l'efficacité

        // Parcourt chaque option pour l'enrichir si nécessaire
        foreach ($optionsArray as $option) { // Use $optionsArray
            // S'assure que l'option est un tableau et a au moins une clé 'text'
            if (! is_array($option) || ! isset($option['text'])) {
                // Ajoute l'option telle quelle si elle est mal formée
                $displayOptions[] = $option;

                continue;
            }

            // L'ID de l'option est crucial pour lier les votes. On suppose qu'il est présent.
            // Si les options n'ont pas d'ID stable, le système de vote par option ID ne fonctionnera pas correctement.
            $optionId = $option['id'] ?? null;

            $optionData = $option; // Copie l'option originale

            // Si les résultats doivent être affichés ET que l'option a un ID valide
            if ($showResults && $optionId !== null) {
                $optionVotes = $this->getOptionVotes($optionId);
                // Calcule le pourcentage, gère la division par zéro
                $percentage = ($totalVotes > 0) ? round(($optionVotes / $totalVotes) * 100, 1) : 0.0;

                // Ajoute les informations de vote et pourcentage à l'option
                $optionData['votes_count'] = $optionVotes;
                $optionData['percentage'] = $percentage;
            } else {
                // Si les résultats ne sont pas affichés, s'assurer que les clés de résultats ne sont pas présentes
                // pour éviter d'afficher des données incorrectes.
                unset($optionData['votes_count']);
                unset($optionData['percentage']);
            }

            $displayOptions[] = $optionData;
        }

        return $displayOptions;
    }

    // Vérifie si le sondage est terminé (la date de fin est passée)
    public function isEnded(): bool
    {
        return $this->ends_at && $this->ends_at->isPast();
    }
}
