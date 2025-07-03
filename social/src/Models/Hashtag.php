<?php

namespace IJIDeals\Social\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="Hashtag",
 *     title="Hashtag",
 *     description="Modèle représentant un hashtag utilisé dans les publications, groupes et événements",
 *
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64",
 *         description="ID unique du hashtag"
 *     ),
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         description="Nom du hashtag (sans le '#')"
 *     ),
 *     @OA\Property(
 *         property="slug",
 *         type="string",
 *         description="Slug unique du hashtag pour les URL"
 *     ),
 *     @OA\Property(
 *         property="post_count",
 *         type="integer",
 *         description="Nombre de posts utilisant ce hashtag"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="Date de création du hashtag"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         description="Date de dernière mise à jour du hashtag"
 *     )
 * )
 * Represents a hashtag used in posts, groups and events
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property int $post_count
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static static findOrCreateByName(string $name)
 * @method static static firstOrCreate(array $attributes, array $values = [])
 * @method static \Illuminate\Database\Eloquent\Builder|static query()
 *
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Hashtag extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = ['name', 'slug', 'post_count'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'post_count' => 'integer',
    ];

    /**
     * Define the relationship with the posts that use this hashtag.
     */
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'post_hashtags')
            ->with(['analytics', 'reachEstimate'])
            ->where('status', 'published')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Define the relationship with the groups that use this hashtag.
     */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class)
            ->withTimestamps();
    }

    /**
     * Define the relationship with the events that use this hashtag.
     */
    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class)
            ->withTimestamps();
    }

    /**
     * Scope a query to only include popular hashtags based on engagement.
     *
     * @param  Builder  $query  The query builder instance
     * @param  int  $minEngagementScore  The minimum engagement score
     */
    public function scopePopular(Builder $query, int $minEngagementScore = 50): Builder
    {
        return $query->whereHas('posts', function ($q) use ($minEngagementScore) {
            $q->where('engagement_score', '>=', $minEngagementScore);
        });
    }

    /**
     * Scope a query to only include trending hashtags.
     *
     * @param  Builder  $query  The query builder instance
     * @param  int  $minPosts  The minimum number of posts in last 24 hours
     */
    public function scopeTrending(Builder $query, int $minPosts = 10): Builder
    {
        return $query->whereHas('posts', function ($q) {
            $q->where('created_at', '>', now()->subDay());
        })
            ->having('post_count', '>=', $minPosts);
    }

    /**
     * Increment the post count for this hashtag
     */
    public function incrementPostCount(): void
    {
        $this->increment('post_count');
    }

    /**
     * Decrement the post count for this hashtag
     */
    public function decrementPostCount(): void
    {
        $this->decrement('post_count');
    }

    /**
     * Find or create a hashtag by name.
     *
     * @param  string  $name  The hashtag name
     * @return static The created or found hashtag model.
     */
    public static function findOrCreateByName(string $name): self
    {
        $name = Str::lower(trim($name, '#'));
        $slug = Str::slug($name);

        return static::firstOrCreate(
            ['name' => $name],
            ['slug' => $slug, 'post_count' => 0]
        );
    }

    /**
     * Get the URL for this hashtag
     */
    public function getUrlAttribute(): string
    {
        return route('hashtags.show', $this->slug);
    }
}
