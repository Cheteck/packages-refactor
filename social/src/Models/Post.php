<?php

namespace IJIDeals\Social\Models; // Changed namespace

use IJIDeals\IJICommerce\Models\Product;
use IJIDeals\Social\Enums\PostTypeEnum;
use IJIDeals\Social\Enums\VisibilityType;
use IJIDeals\FileManagement\Models\Attachment;
use IJIDeals\Social\Models\Hashtag;
use IJIDeals\Social\Models\Group;
use IJIDeals\Social\Jobs\CalculatePostReach;
use IJIDeals\Social\Jobs\IndexPostContent;
use IJIDeals\Social\Jobs\NotifyMentionedUsers;
use IJIDeals\Social\Traits\HasComments;
use IJIDeals\Social\Traits\HasReaction;
use IJIDeals\Social\Traits\HasReports;
use IJIDeals\Social\Traits\HasShares;
// use IJIDeals\UserManagement\Models\User; // Will use configured user model
use Illuminate\Database\Eloquent\Builder;
// Assuming PostAnalytics, Hashtag are in the same namespace or imported correctly.
// If not, their FQCN or imports would be needed for the property type hints.
// use IJIDeals\Social\Models\PostAnalytics;
// use IJIDeals\Social\Models\Hashtag;
// use App\Enums\PostTypeEnum; // Already imported
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany; // Added this import
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use OpenApi\Annotations as OA;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia; // Import OpenApi namespace

// It's good practice to import the models you'll be relating to,
// even if they are in different packages. This helps with static analysis and clarity.
// Assuming these are the correct namespaces based on the project structure.
// Not importing them here directly in Post.php as they are not directly used in THIS file,
// but the relation definition implies their existence elsewhere.
// use IJIDeals\IJIProductCatalog\Models\MasterProduct;
// use IJIDeals\IJIShopListings\Models\ShopProduct;


/**
 * @OA\Schema(
 *     schema="Post",
 *     title="Post",
 *     description="Modèle représentant un post social dans l'application",
 *
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64"
 *     ),
 *     @OA\Property(
 *         property="author_id",
 *         type="integer",
 *         format="int64"
 *     ),
 *     @OA\Property(
 *         property="author_type",
 *         type="string"
 *     ),
 *     @OA\Property(
 *         property="content",
 *         type="string",
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="type",
 *         type="string",
 *         enum={"text", "image", "video", "product", "poll", "event", "article", "link", "story"}
 *     ),
 *     @OA\Property(
 *         property="visibility",
 *         type="string",
 *         enum={"public", "restricted", "private"}
 *     ),
 *     @OA\Property(
 *         property="metadata",
 *         type="object",
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="status",
 *         type="string",
 *         enum={"draft", "published", "archived", "pending", "rejected"}
 *     ),
 *     @OA\Property(
 *         property="location",
 *         type="object",
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="scheduled_at",
 *         type="string",
 *         format="date-time",
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="expires_at",
 *         type="string",
 *         format="date-time",
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="comment_settings",
 *         type="string",
 *         enum={"everyone", "followers", "disabled"}
 *     ),
 *     @OA\Property(
 *         property="reaction_settings",
 *         type="string",
 *         enum={"enabled", "disabled"}
 *     ),
 *     @OA\Property(
 *         property="engagement_score",
 *         type="number",
 *         format="float"
 *     ),
 *     @OA\Property(
 *         property="product_id",
 *         type="integer",
 *         format="int64",
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="is_published",
 *         type="boolean"
 *     ),
 *     @OA\Property(
 *         property="poll_id",
 *         type="integer",
 *         format="int64",
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="reach_estimate_id",
 *         type="integer",
 *         format="int64",
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="title",
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
 *     ),
 *     @OA\Property(
 *         property="deleted_at",
 *         type="string",
 *         format="date-time",
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="attachments",
 *         type="array",
 *
 *         @OA\Items(ref="#/components/schemas/Attachment")
 *     ),
 *
 *     @OA\Property(
 *         property="analytics",
 *         ref="#/components/schemas/PostAnalytics"
 *     ),
 *     @OA\Property(
 *         property="hashtags",
 *         type="array",
 *
 *         @OA\Items(ref="#/components/schemas/Hashtag")
 *     ),
 *
 *     @OA\Property(
 *         property="mentions",
 *         type="array",
 *
 *         @OA\Items(ref="#/components/schemas/User")
 *     ),
 *
 *     @OA\Property(
 *         property="reach_estimate",
 *         ref="#/components/schemas/PostReachEstimate"
 *     ),
 *     @OA\Property(
 *         property="versions",
 *         type="array",
 *
 *         @OA\Items(ref="#/components/schemas/PostVersion")
 *     ),
 *
 *     @OA\Property(
 *         property="poll",
 *         ref="#/components/schemas/PostPoll"
 *     ),
 *     @OA\Property(
 *         property="reactions",
 *         type="array",
 *
 *         @OA\Items(ref="#/components/schemas/Reaction")
 *     ),
 *
 *     @OA\Property(
 *         property="comments",
 *         type="array",
 *
 *         @OA\Items(ref="#/components/schemas/Comment")
 *     )
 * )
 * Modèle représentant un post social
 *
 * @category Social
 *
 * @property int $id
 * @property string $content
 */
class Post extends Model implements HasMedia
{
    use HasComments, HasFactory, HasReaction, HasReports, HasShares, InteractsWithMedia, SoftDeletes;

    protected static function newFactory()
    {
        return \IJIDeals\Social\Database\Factories\PostFactory::new();
    }

    // Define all post types as an array constant
    public const TYPES = [
        'text' => 'text',
        'image' => 'image',
        'video' => 'video',
        'product' => 'product',
        'poll' => 'poll',
        'event' => 'event',
        'article' => 'article',
        'link' => 'link',
        'story' => 'story',
    ];

    // Define post statuses
    public const STATUSES = [
        'draft' => 'draft',
        'published' => 'published',
        'archived' => 'archived',
        'pending' => 'pending',
        'rejected' => 'rejected',
    ];

    // Define comment settings
    public const COMMENT_SETTINGS = [
        'everyone' => 'everyone',
        'followers' => 'followers',
        'disabled' => 'disabled',
    ];

    // Define reaction settings
    public const REACTION_SETTINGS = [
        'enabled' => 'enabled',
        'disabled' => 'disabled',
    ];

    // Individual constants for backward compatibility
    public const TYPE_TEXT = 'text';

    public const TYPE_IMAGE = 'image';

    public const TYPE_VIDEO = 'video';

    public const TYPE_PRODUCT = 'product';

    public const TYPE_POLL = 'poll';

    public const TYPE_EVENT = 'event';

    public const TYPE_ARTICLE = 'article';

    public const TYPE_LINK = 'link';

    public const TYPE_STORY = 'story';

    // Status constants
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    public const STATUS_PENDING = 'pending';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'author_id',
        'author_type',
        'content',
        'type',
        'visibility',
        'metadata',
        'status',
        'location',
        'scheduled_at',
        'expires_at',
        'comment_settings',
        'reaction_settings',
        'engagement_score',
        'product_id', // This existing product_id might be for a single primary product link.
                       // The new taggedProducts relation allows for multiple, potentially mixed-type product tags.
                       // We need to consider how this existing field interacts with the new relation.
                       // For now, we'll leave it, but it might become redundant or serve a different purpose.
        'is_published',
        'poll_id',
        'reach_estimate_id',
        'title',
        // 'description' => Supprimé car non utilisé, à réactiver si ajouté dans la migration
    ];

    protected $casts = [
        'metadata' => 'array',
        'visibility' => VisibilityType::class,
        'type' => PostTypeEnum::class,
        'scheduled_at' => 'datetime',
        'expires_at' => 'datetime',
        'engagement_score' => 'float',
        'location' => 'array',
        'is_published' => 'boolean',
        'poll_data' => 'array',
    ];

    /**
     * Méthodes exécutées lors du cycle de vie du modèle
     */
    protected static function booted()
    {
        // Après la création d'un post
        static::created(function (Post $post) { // Typed $post
            \Illuminate\Support\Facades\Log::info("Post created: {$post->id}. Dispatching jobs.");
            CalculatePostReach::dispatch($post);
            $mentionedUsers = $post->extractMentionedUsers($post->content);
            if (!empty($mentionedUsers)) {
                \Illuminate\Support\Facades\Log::info("Dispatching NotifyMentionedUsers for post {$post->id}", ['mentioned_users_count' => count($mentionedUsers)]);
                NotifyMentionedUsers::dispatch($post, $mentionedUsers);
            } else {
                \Illuminate\Support\Facades\Log::info("No users mentioned in post {$post->id}. Skipping NotifyMentionedUsers job.");
            }
            IndexPostContent::dispatch($post);

            // Créer les analytics associés
            $post->analytics()->create([
                'impressions' => 0,
                'engagements' => 0,
                'shares_count' => 0,
                'engagement_rate' => 0,
                'estimated_reach' => 0,
            ]);
        });

        // Après la mise à jour d'un post
        static::updated(function (Post $post) { // Typed $post
            // Créer une version du post si le contenu change
            if ($post->isDirty('content')) {
                $post->versions()->create([
                    'post_id' => $post->id,
                    'content' => $post->getOriginal('content'),
                    'version_number' => $post->versions()->count() + 1,
                    'changes' => $post->getChanges(),
                ]);
            }
            cache()->tags(['posts', "post:{$post->id}"])->flush();
        });
    }

    /**
     * Relation avec les pièces jointes
     */
    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable'); // Now uses imported App\Models\Attachment
    }

    /**
     * Relation avec les statistiques analytiques
     */
    public function analytics()
    {
        return $this->hasOne(PostAnalytics::class); // Uses imported IJIDeals\Social\Models\PostAnalytics
    }

    /**
     * Relation avec les hashtags
     */
    public function hashtags()
    {
        return $this->belongsToMany(Hashtag::class, 'post_hashtags'); // Now uses imported App\Models\Hashtag
    }

    /**
     * Relation avec les utilisateurs mentionnés
     */
    public function mentions()
    {
        return $this->belongsToMany(config('user-management.model', \App\Models\User::class), 'post_mentions');
    }

    /**
     * Relation avec l'estimation de portée
     */
    public function reachEstimate()
    {
        return $this->hasOne(PostReachEstimate::class); // Uses imported IJIDeals\Social\Models\PostReachEstimate
    }

    /**
     * Relation avec les versions du post
     */
    public function versions()
    {
        return $this->hasMany(PostVersion::class); // Uses imported IJIDeals\Social\Models\PostVersion
    }

    /**
     * Relation avec le sondage associé
     */
    public function poll()
    {
        return $this->hasOne(PostPoll::class); // Uses imported IJIDeals\Social\Models\PostPoll
    }

    /**
     * Relation avec l'auteur du post
     */
    public function author(): BelongsTo
    {
        return $this->morphTo();
    }

    /**
     * Relation avec le produit associé (ancienne relation, potentiellement pour un lien produit principal)
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class); // Now uses imported App\Models\Product
    }

    /**
     * Get all of the products that are tagged to this post.
     * This defines the relationship structure for syncing and basic querying.
     * Retrieving a mixed collection of actual MasterProduct and ShopProduct instances
     * will require specific handling in API resources or services.
     */
    public function taggedProducts(): MorphToMany
    {
        // Using Model::class as a placeholder for the related model type in the signature
        // because it can be MasterProduct or ShopProduct.
        // The actual model class will be determined by the 'taggable_type' column in the pivot table.
        return $this->morphToMany(Model::class, 'taggable', 'taggable_products', 'post_id', 'taggable_id')
                    ->withTimestamps();
    }


    /**
     * Relation avec les relations polymorphes
     */
    public function relations()
    {
        return $this->hasMany(PostRelation::class); // Will use IJIDeals\Social\Models\PostRelation
    }

    /**
     * Scope pour les posts publiés
     */
    public function scopePublished(Builder $query)
    {
        return $query->where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->where(function ($q) {
                $q->whereNull('scheduled_at')
                    ->orWhere('scheduled_at', '<=', now());
            });
    }

    /**
     * Scope pour les posts populaires
     */
    public function scopePopular($query, $days = 7)
    {
        return $query->published()
            ->orderByDesc('engagement_score')
            ->whereDate('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope pour les posts contenant un hashtag spécifique
     */
    public function scopeWithHashtag($query, string $hashtag)
    {
        return $query->whereHas('hashtags', function ($q) use ($hashtag) {
            $q->where('name', $hashtag);
        });
    }

    /**
     * Scope pour les posts visibles par un utilisateur spécifique
     */
    public function scopeVisibleTo($query, /* User */ $user) // Type hint will use configured model
    {
        // Ensure $user is an instance of the configured user model.
        // This might require resolving the model class from config first if strict type hinting is desired
        // For now, relying on duck typing or ensuring $user is passed correctly.
        $userModelClass = config('user-management.model', \App\Models\User::class);
        if (! $user instanceof $userModelClass) {
            // Optionally log a warning or throw an error if type is strictly enforced
            // For now, proceed assuming $user is compatible.
        }

        return $query->where(function ($q) use ($user) {
            $q->where('visibility', VisibilityType::PUBLIC)
                ->orWhere(function ($q) use ($user) {
                    $q->where('visibility', VisibilityType::RESTRICTED)
                        ->whereHas('author.followers', fn ($q) => $q->where('user_id', $user->id));
                })
                ->orWhere('author_id', $user->id);
        });
    }

    /**
     * Scope pour les posts programmés
     */
    public function scopeScheduled($query)
    {
        return $query->whereNotNull('scheduled_at')->where('scheduled_at', '>', now());
    }

    /**
     * Scope pour les posts expirés
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')->where('expires_at', '<', now());
    }

    /**
     * Calcul du score de portée
     */
    public function getReachScore(): float
    {
        $analytics = $this->analytics;

        return $analytics->impressions * 0.3 +
               $analytics->engagements * 0.5 +
               $analytics->shares_count * 0.2;
    }

    /**
     * Configuration des collections média
     */
    public function registerMediaCollections(): void
    {
        /** @var \Spatie\MediaLibrary\Conversions\Conversion $conversion */
        $conversion = $this->addMediaCollection('media');
        $conversion->acceptsMimeTypes(['image/jpeg', 'image/png', 'video/mp4'])
            ->withResponsiveImages()
            ->singleFile();

        /** @var \Spatie\MediaLibrary\Conversions\Conversion $conversion */
        $conversion = $this->addMediaCollection('carousel');
        $conversion->acceptsMimeTypes(['image/jpeg', 'image/png', 'video/mp4'])
            ->withResponsiveImages()
            ->onlyKeepLatest(10);
    }

    /**
     * Génère un aperçu dynamique en fonction du type de post
     */
    public function getDynamicPreviewAttribute()
    {
        return match ($this->type) {
            PostTypeEnum::PRODUCT => $this->generateProductPreview(),
            PostTypeEnum::ARTICLE => $this->generateArticlePreview(),
            PostTypeEnum::POLL => $this->generatePollPreview(),
            PostTypeEnum::STORY => $this->generateStoryPreview(),
            default => $this->generateDefaultPreview()
        };
    }

    /**
     * Retourne le template d'affichage approprié en fonction du type de post
     */
    public function getDisplayTemplateAttribute()
    {
        return match ($this->type) {
            'video' => 'posts.video-template',
            'poll' => 'posts.poll-template',
            'story' => 'posts.story-template',
            default => 'posts.default-template'
        };
    }

    /**
     * Charge toutes les relations associées au post
     */
    public function scopeWithAllRelations($query)
    {
        return $query->with([
            'author',
            'attachments' => fn ($q) => $q->select('post_id', 'file_path', 'file_type'),
            'reactions',
            'comments' => fn ($q) => $q->with(['author', 'reactions'])->latest()->limit(5),
        ]);
    }

    /**
     * Relation avec les médias
     */
    public function media(): MorphMany
    {
        return $this->morphMany(\Spatie\MediaLibrary\MediaCollections\Models\Media::class, 'model');
    }

    /**
     * Relation avec les commentaires
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable'); // Will use IJIDeals\Social\Models\Comment
    }

    /**
     * Récupère le résumé des réactions
     */
    public function getReactionSummary()
    {
        // TODO: Method reactions() is likely from a missing trait (HasReaction/CanReact).
        // Placeholder to avoid fatal errors if called.
        if (method_exists($this, 'reactions')) {
            return $this->reactions()
                ->selectRaw('type, count(*) as total')
                ->groupBy('type')
                ->pluck('total', 'type');
        }

        return collect(); // Return an empty collection as a fallback
    }

    public function getinteractableType(): string
    {
        return 'post';
    }

    public function getinteractableIdentifier(): string
    {
        return $this->getKey();
    }

    public function generateProductPreview(): string
    {
        return 'Product preview';
    }

    public function generateArticlePreview(): string
    {
        return 'Article preview';
    }

    public function generatePollPreview(): string
    {
        return 'Poll preview';
    }

    public function generateStoryPreview(): string
    {
        return 'Story preview';
    }

    public function generateDefaultPreview(): string
    {
        return 'Default preview';
    }

    public function scopeWithEngagementScore($query, array $weights)
    {
        return $query->addSelect([
            'engagement_score' => $this->engagementScoreQuery($weights),
        ]);
    }

    public function scopeWithFollowBoost($query, Collection $followedIds)
    {
        return $query->addSelect([
            'follow_boost' => $this->followBoostQuery($followedIds),
        ]);
    }

    public function scopeWithCategoryBoost($query, Collection $preferredCategories)
    {
        return $query->addSelect([
            'category_boost' => $this->categoryBoostQuery($preferredCategories),
        ]);
    }

    public function scopeWithFreshnessFactor($query)
    {
        return $query->addSelect([
            'freshness_factor' => $this->freshnessFactorQuery(),
        ]);
    }

    public function scopeWithViralityBoost($query)
    {
        return $query->addSelect([
            'virality_boost' => $this->viralityBoostQuery(),
        ]);
    }

    private function engagementScoreQuery(array $weights)
    {
        return DB::raw("(likes_count * {$weights['likes']} + comments_count * {$weights['comments']} + shares_count * {$weights['shares']})");
    }

    private function followBoostQuery(Collection $followedIds)
    {
        if ($followedIds->isEmpty()) {
            return DB::raw('1'); // Return 1 if no followed IDs, effectively no boost
        }

        return DB::raw('(CASE WHEN author_id IN ('.implode(',', $followedIds->toArray()).') THEN 1.5 ELSE 1 END)');
    }

    private function categoryBoostQuery(Collection $preferredCategories)
    {
        return $preferredCategories->isNotEmpty()
            ? DB::raw('(CASE WHEN EXISTS (SELECT 1 FROM post_categories WHERE post_categories.post_id = posts.id AND post_categories.category_id IN ('.implode(',', $preferredCategories->toArray()).')) THEN 2 ELSE 1 END)')
            : DB::raw('1');
    }

    private function freshnessFactorQuery()
    {
        return DB::raw('(1 / (TIMESTAMPDIFF(HOUR, posts.created_at, NOW()) + 1))');
    }

    private function viralityBoostQuery()
    {
        return DB::raw('(CASE WHEN viral_score > 0.8 THEN 2 ELSE 1 END)');
    }

    /**
     * Scope pour les posts avec un certain type de relation
     */
    public function scopeWithRelationType($query, string $type)
    {
        return $query->whereHas('relations', function ($q) use ($type) {
            $q->where('relation_type', $type);
        });
    }

    /**
     * Ajouter un hashtag au post
     */
    public function addHashtag(string $name): void
    {
        $hashtag = Hashtag::firstOrCreate(['name' => $name]); // Now uses imported App\Models\Hashtag
        $this->hashtags()->syncWithoutDetaching($hashtag);
    }

    /**
     * Calculer le score de portée en temps réel
     */
    public function calculateReachScore(): float
    {
        $analytics = $this->analytics;

        return $analytics->impressions * 0.3 +
               $analytics->engagements * 0.5 +
               $analytics->shares_count * 0.2;
    }

    /**
     * Mettre à jour les métriques analytiques
     */
    public function updateAnalytics(array $metrics): void
    {
        $this->analytics->updateMetrics($metrics);
        $this->refresh();
    }

    /**
     * Créer un sondage associé au post
     */
    public function createPoll(array $data): PostPoll // Will use IJIDeals\Social\Models\PostPoll
    {
        return $this->poll()->create([
            'question' => $data['question'],
            'options' => $data['options'],
            'ends_at' => $data['ends_at'] ?? null,
        ]);
    }

    /**
     * Recherche un post par son ID.
     *
     * @param  mixed  $id
     * @return \Illuminate\Database\Eloquent\Model|Post|null // Changed to self Post
     */
    public static function find($id)
    {
        return static::query()->find($id);
    }

    /**
     * Get the user who shared the post.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function sharer()
    {
        return $this->morphTo();
    }

    public function isActiveStory()
    {
        return $this->type === self::TYPE_STORY && $this->expires_at > now();
    }

    public function bookmarks()
    {
        return $this->belongsToMany(config('user-management.model', \App\Models\User::class), 'bookmarks');
    }

    /**
     * Relation avec le groupe associé.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function group()
    {
        return $this->belongsTo(Group::class); // Now uses imported App\Models\Group
    }

    public function parseHashtags($content)
    {
        // Récupérer les hashtags associés au post
        $hashtags = $this->hashtags->pluck('slug', 'name')->toArray();

        // Remplacer les hashtags par des liens
        return preg_replace_callback(
            '/#(\w+)/',
            function ($matches) use ($hashtags) {
                $hashtag = $matches[1];
                // Vérifier si le hashtag existe dans les hashtags associés
                if (isset($hashtags[$hashtag])) {
                    return '<a href="'.route('hashtags.show', $hashtags[$hashtag]).'" class="text-blue-400 hover:text-blue-300 transition-colors duration-200">#'.$hashtag.'</a>';
                }

                // Si le hashtag n'est pas dans la base, retourner le texte brut
                return '#'.$hashtag;
            },
            e($content) // Échapper le contenu pour éviter les failles XSS
        );
    }

    public function extractMentionedUsers($content): array
    {
        if (empty($content)) {
            return [];
        }

        preg_match_all('/@([\p{L}\w_]+)/u', $content, $matches);
        $usernames = array_unique($matches[1] ?? []);

        if (empty($usernames)) {
            \Illuminate\Support\Facades\Log::info("No usernames found by regex in content for post {$this->id}.");
            return [];
        }

        \Illuminate\Support\Facades\Log::info("Usernames found by regex for post {$this->id}: " . implode(', ', $usernames));

        // Assuming User model has a 'username' field. If not, adjust the query.
        $userModelClass = config('user-management.model', \App\Models\User::class);
        $users = app($userModelClass)->whereIn('username', $usernames)->get()->all(); // Return as a plain array

        \Illuminate\Support\Facades\Log::info("Users fetched from DB for post {$this->id}: " . count($users) . " users.");

        return $users;
    }

    public function extractHashtags($content)
    {
        preg_match_all('/#([\p{L}\w]+)/u', $content, $matches);

        return array_unique($matches[1] ?? []);
    }

    public function syncHashtags($content)
    {
        $hashtags = $this->extractHashtags($content);
        $hashtagIds = [];

        foreach ($hashtags as $tag) {
            if (strlen($tag) <= 50 && preg_match('/^[\p{L}\d_]+$/u', $tag)) {
                $hashtag = Hashtag::findOrCreateByName($tag); // Now uses imported App\Models\Hashtag
                $hashtag->increment('post_count');
                $hashtagIds[] = $hashtag->id;
            }
        }

        $this->hashtags()->sync($hashtagIds);
    }

    /**
     * Ajoute ou supprime une réaction pour un utilisateur donné.
     * Si l'utilisateur a déjà réagi, la réaction est supprimée.
     * Sinon, la réaction spécifiée est ajoutée.
     *
     * @param  string  $reactionType  Le type de réaction à ajouter si aucune n'existe.
     * @param  \Illuminate\Database\Eloquent\Model|null  $user  L'utilisateur (par défaut l'utilisateur authentifié).
     * @return bool True si une réaction a été ajoutée, False si une réaction a été supprimée.
     *
     * @throws \InvalidArgumentException Si l'utilisateur n'est pas authentifié ou si le type de réaction est invalide lors de l'ajout.
     */
    public function toggleReaction(string $reactionType, ?Model $user = null): bool
    {
        $user = $user ?? auth()->user();
        // $this->ensureAuthenticated($user); // ensureAuthenticated method not defined in this model, assuming it's from a trait or parent
        if (! $user) {
            throw new \InvalidArgumentException('User must be authenticated to toggle reactions.');
        }

        // TODO: Method hasUserReaction() is likely from a missing trait (HasReaction/CanReact).
        // Placeholder logic.
        if (method_exists($this, 'hasUserReaction') && $this->hasUserReaction($user)) {
            // L'utilisateur a déjà réagi, on supprime la réaction existante
            // TODO: Method removeReaction() is likely from a missing trait.
            if (method_exists($this, 'removeReaction')) {
                $this->removeReaction($user);
            }

            return false; // Indique qu'une réaction a été supprimée
        } else {
            // L'utilisateur n'a pas réagi, on ajoute la nouvelle réaction
            // TODO: Method addReaction() is likely from a missing trait.
            if (method_exists($this, 'addReaction')) {
                $this->addReaction($reactionType, $user);
            }

            return true; // Indique qu'une réaction a été ajoutée
        }
    }
}
