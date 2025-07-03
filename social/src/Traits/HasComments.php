<?php

namespace IJIDeals\social\Traits;

use IJIDeals\Social\Models\Comment; // Changed to new Comment model namespace
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasComments
{
    /**
     * Relation polymorphique pour les commentaires.
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    /**
     * Ajouter un commentaire à ce modèle.
     */
    public function comment(string $content, Model $author): Comment
    {
        return $this->comments()->create([
            'content' => $content,
            'author_type' => get_class($author),
            'author_id' => $author->id,
        ]);
    }

    /**
     * Adds a comment to the model.
     */
    public function addComment(string $content, Model $author): Comment
    {
        return $this->comment($content, $author);
    }

    /**
     * Compter le nombre total de commentaires sur ce modèle.
     */
    public function countComments(): int
    {
        return $this->comments()->count();
    }

    /**
     * Récupérer les commentaires les plus récents.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function latestComments(int $limit = 5)
    {
        return $this->comments()->latest()->take($limit)->get();
    }

    /**
     * Récupérer tous les commentaires d'un auteur spécifique.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function commentsByAuthor(string $authorType, int $authorId)
    {
        return $this->comments()->where('author_type', $authorType)
            ->where('author_id', $authorId)
            ->get();
    }

    /**
     * Supprimer un commentaire donné.
     *
     * @return bool|null
     */
    public function deleteComment(Comment $comment)
    {
        return $comment->delete();
    }
}
