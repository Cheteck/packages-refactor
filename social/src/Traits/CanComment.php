<?php

namespace IJIDeals\Social\Traits;

use IJIDeals\Social\Models\Comment; // Changed to new Comment model namespace
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Log;

trait CanComment
{
    /**
     * Relation polymorphique pour les entités qui peuvent commenter un autre modèle.
     */
    public function commentsGiven(): MorphMany
    {
        Log::info('Récupération des commentaires donnés par l\'auteur.', ['author_id' => $this->id, 'author_type' => get_class($this)]);

        return $this->morphMany(Comment::class, 'author');
    }

    /**
     * Ajouter un commentaire à un modèle spécifié.
     *
     * @param  Model  $model  Le modèle auquel ajouter le commentaire.
     * @param  string  $content  Le contenu du commentaire.
     */
    public function addCommentTo(Model $model, string $content): Comment
    {
        Log::info('Ajout d\'un commentaire à un modèle.', ['model_id' => $model->id, 'model_type' => get_class($model), 'content' => $content, 'author_id' => $this->id, 'author_type' => get_class($this)]);

        $comment = new Comment([
            'content' => $content,
            'author_type' => get_class($this),
            'author_id' => $this->id,
        ]);

        // Associer le commentaire au modèle cible
        $comment = $model->comments()->save($comment);

        Log::info('Commentaire ajouté avec succès.', ['comment_id' => $comment->id]);

        return $comment;
    }

    /**
     * Compter le nombre de commentaires d'une entité sur un modèle spécifique.
     *
     * @param  Model  $model  Le modèle sur lequel compter les commentaires.
     */
    public function countCommentsOn(Model $model): int
    {
        $count = $model->comments()->where('author_type', get_class($this))
            ->where('author_id', $this->id)
            ->count();

        Log::info('Nombre de commentaires de l\'auteur sur un modèle.', ['model_id' => $model->id, 'model_type' => get_class($model), 'author_id' => $this->id, 'author_type' => get_class($this), 'count' => $count]);

        return $count;
    }

    /**
     * Récupérer tous les commentaires laissés par cet auteur.
     */
    public function allCommentsGiven(): Collection
    {
        Log::info('Récupération de tous les commentaires donnés par l\'auteur.', ['author_id' => $this->id, 'author_type' => get_class($this)]);

        return $this->commentsGiven()->get();
    }

    /**
     * Récupérer les derniers commentaires laissés par cet auteur.
     *
     * @param  int  $limit  Le nombre maximum de commentaires à récupérer.
     */
    public function latestCommentsGiven(int $limit = 5): Collection
    {
        Log::info('Récupération des derniers commentaires donnés par l\'auteur.', ['author_id' => $this->id, 'author_type' => get_class($this), 'limit' => $limit]);

        return $this->commentsGiven()->latest()->take($limit)->get();
    }

    /**
     * Supprimer un commentaire laissé par cet auteur sur un modèle donné.
     *
     * @param  Comment  $comment  Le commentaire à supprimer.
     */
    public function deleteCommentGiven(Comment $comment): ?bool
    {
        Log::info('Suppression d\'un commentaire donné par l\'auteur.', ['comment_id' => $comment->id, 'author_id' => $this->id, 'author_type' => get_class($this)]);

        return $comment->delete();
    }

    public function comment(string $content): Comment
    {
        return $this->commentsGiven()->create([
            'content' => $content,
            'user_id' => auth()->id(),
        ]);
    }
}
