<?php

namespace IJIDeals\Social\Models; // Changed namespace

use IJIDeals\Messaging\Models\Conversation; // Added
use IJIDeals\UserManagement\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use OpenApi\Annotations as OA; // Import OpenApi namespace

// User and Hashtag are already imported or correctly referenced.

/**
 * @OA\Schema(
 *     schema="Group",
 *     title="Group",
 *     description="Modèle représentant un groupe social dans l'application",
 *
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64",
 *         description="ID unique du groupe"
 *     ),
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         description="Nom du groupe"
 *     ),
 *     @OA\Property(
 *         property="description",
 *         type="string",
 *         nullable=true,
 *         description="Description du groupe"
 *     ),
 *     @OA\Property(
 *         property="creator_id",
 *         type="integer",
 *         format="int64",
 *         description="ID de l'utilisateur qui a créé le groupe"
 *     ),
 *     @OA\Property(
 *         property="privacy",
 *         type="string",
 *         description="Niveau de confidentialité du groupe",
 *         enum={"public", "private", "secret"}
 *     ),
 *     @OA\Property(
 *         property="avatar",
 *         type="string",
 *         nullable=true,
 *         description="Chemin du fichier de l'avatar du groupe"
 *     ),
 *     @OA\Property(
 *         property="cover_photo",
 *         type="string",
 *         nullable=true,
 *         description="Chemin du fichier de la photo de couverture du groupe"
 *     ),
 *     @OA\Property(
 *         property="slug",
 *         type="string",
 *         nullable=true,
 *         description="Slug unique du groupe pour les URL"
 *     ),
 *     @OA\Property(
 *         property="rules",
 *         type="string",
 *         nullable=true,
 *         description="Règles du groupe"
 *     ),
 *     @OA\Property(
 *         property="location",
 *         type="object",
 *         nullable=true,
 *         description="Informations de localisation du groupe (JSON)"
 *     ),
 *     @OA\Property(
 *         property="group_category_id",
 *         type="integer",
 *         format="int64",
 *         nullable=true,
 *         description="ID de la catégorie de groupe"
 *     ),
 *     @OA\Property(
 *         property="settings",
 *         type="object",
 *         nullable=true,
 *         description="Paramètres du groupe (JSON)"
 *     ),
 *     @OA\Property(
 *         property="is_verified",
 *         type="boolean",
 *         description="Indique si le groupe est vérifié"
 *     ),
 *     @OA\Property(
 *         property="allow_events",
 *         type="boolean",
 *         description="Indique si le groupe peut créer des événements"
 *     ),
 *     @OA\Property(
 *         property="auto_approve_members",
 *         type="boolean",
 *         description="Indique si les nouveaux membres sont automatiquement approuvés"
 *     ),
 *     @OA\Property(
 *         property="auto_approve_posts",
 *         type="boolean",
 *         description="Indique si les nouveaux posts sont automatiquement approuvés"
 *     ),
 *     @OA\Property(
 *         property="metadata",
 *         type="object",
 *         nullable=true,
 *         description="Métadonnées supplémentaires du groupe (JSON)"
 *     ),
 *     @OA\Property(
 *         property="last_activity_at",
 *         type="string",
 *         format="date-time",
 *         nullable=true,
 *         description="Date et heure de la dernière activité dans le groupe"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="Date de création du groupe"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         description="Date de dernière mise à jour du groupe"
 *     ),
 *     @OA\Property(
 *         property="avatar_url",
 *         type="string",
 *         format="url",
 *         readOnly=true,
 *         description="URL publique de l'avatar du groupe"
 *     ),
 *     @OA\Property(
 *         property="cover_photo_url",
 *         type="string",
 *         format="url",
 *         readOnly=true,
 *         description="URL publique de la photo de couverture du groupe"
 *     ),
 *     @OA\Property(
 *         property="member_count",
 *         type="integer",
 *         readOnly=true,
 *         description="Nombre de membres dans le groupe"
 *     ),
 *     @OA\Property(
 *         property="is_member",
 *         type="boolean",
 *         readOnly=true,
 *         description="Indique si l'utilisateur authentifié est membre du groupe"
 *     ),
 *     @OA\Property(
 *         property="member_role",
 *         type="string",
 *         nullable=true,
 *         readOnly=true,
 *         description="Rôle de l'utilisateur authentifié dans le groupe (e.g., 'member', 'moderator', 'admin')"
 *     ),
 *     @OA\Property(
 *         property="activity_level",
 *         type="string",
 *         readOnly=true,
 *         description="Niveau d'activité du groupe (e.g., 'very_active', 'active', 'moderate', 'quiet')"
 *     ),
 *     @OA\Property(
 *         property="creator",
 *         ref="#/components/schemas/User",
 *         description="L'utilisateur qui a créé le groupe"
 *     ),
 *     @OA\Property(
 *         property="members",
 *         type="array",
 *
 *         @OA\Items(ref="#/components/schemas/User"),
 *         description="Liste des membres du groupe"
 *     ),
 *
 *     @OA\Property(
 *         property="admins",
 *         type="array",
 *
 *         @OA\Items(ref="#/components/schemas/User"),
 *         description="Liste des administrateurs du groupe"
 *     ),
 *
 *     @OA\Property(
 *         property="moderators",
 *         type="array",
 *
 *         @OA\Items(ref="#/components/schemas/User"),
 *         description="Liste des modérateurs du groupe"
 *     ),
 *
 *     @OA\Property(
 *         property="posts",
 *         type="array",
 *
 *         @OA\Items(ref="#/components/schemas/Post"),
 *         description="Liste des posts du groupe"
 *     ),
 *
 *     @OA\Property(
 *         property="events",
 *         type="array",
 *
 *         @OA\Items(ref="#/components/schemas/Event"),
 *         description="Liste des événements du groupe"
 *     ),
 *
 *     @OA\Property(
 *         property="conversation",
 *         ref="#/components/schemas/Conversation",
 *         description="La conversation de messagerie associée au groupe"
 *     ),
 *     @OA\Property(
 *         property="category",
 *         ref="#/components/schemas/GroupCategory",
 *         description="La catégorie du groupe"
 *     ),
 *     @OA\Property(
 *         property="hashtags",
 *         type="array",
 *
 *         @OA\Items(ref="#/components/schemas/Hashtag"),
 *         description="Liste des hashtags associés au groupe"
 *     ),
 *
 *     @OA\Property(
 *         property="pinned_posts",
 *         type="array",
 *
 *         @OA\Items(ref="#/components/schemas/Post"),
 *         description="Liste des posts épinglés dans le groupe"
 *     )
 * )
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property int $creator_id
 * @property string $privacy (e.g., 'public', 'private')
 * @property string|null $avatar
 * @property string|null $cover_photo
 * @property bool $auto_approve_members
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \IJIDeals\UserManagement\Models\User $creator
 * @property-read \Illuminate\Database\Eloquent\Collection|\IJIDeals\UserManagement\Models\User[] $members
 * @property-read \Illuminate\Database\Eloquent\Collection|\IJIDeals\Social\Models\Post[] $posts
 * @property-read \Illuminate\Database\Eloquent\Collection|\IJIDeals\Social\Models\Hashtag[] $hashtags
 * @property-read \IJIDeals\Social\Models\GroupCategory|null $category  // Assuming GroupCategory model exists and is related
 *
 * @method static \Illuminate\Database\Eloquent\Builder|static query()
 * @method static static|null find(mixed $id, array $columns = ['*'])
 * @method static static findOrFail(mixed $id, array $columns = ['*'])
 * @method static static create(array $attributes = [])
 *
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Group extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'description',
        'privacy',
        'cover_photo',
        'avatar',
        'slug',
        'rules',
        'location',
        'group_category_id',
        'creator_id',
        'settings',
        'is_verified',
        'allow_events',
        'auto_approve_members',
        'auto_approve_posts',
        'metadata',
        'last_activity_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'settings' => 'array',
        'metadata' => 'array',
        'last_activity_at' => 'datetime',
        'is_verified' => 'boolean',
        'allow_events' => 'boolean',
        'auto_approve_members' => 'boolean',
        'auto_approve_posts' => 'boolean',
    ];

    /**
     * The attributes that should be appended to the array.
     *
     * @var array<string>
     */
    protected $appends = ['avatar_url', 'cover_photo_url', 'member_count', 'is_member', 'member_role', 'activity_level'];

    /**
     * Get the avatar URL.
     */
    protected function avatarUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->avatar ? \Illuminate\Support\Facades\Storage::url($this->avatar) : asset('images/default-group-avatar.jpg'),
        );
    }

    /**
     * Get the cover photo URL.
     */
    protected function coverPhotoUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->cover_photo ? \Illuminate\Support\Facades\Storage::url($this->cover_photo) : asset('images/default-group-cover.jpg'),
        );
    }

    /**
     * Get the member count.
     */
    protected function memberCount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->members()->getQuery()->count(),
        );
    }

    /**
     * Check if the authenticated user is a member of the group.
     */
    protected function isMember(): Attribute
    {
        return Attribute::make(
            get: fn () => auth()->check() ? $this->members()->getQuery()->where('user_id', auth()->id())->exists() : false,
        );
    }

    /**
     * Get the role of the authenticated user in the group.
     */
    protected function memberRole(): Attribute
    {
        return Attribute::make(
            get: fn () => auth()->check() ? optional($this->members()->getQuery()->where('user_id', auth()->id())->first())->pivot->role : null,
        );
    }

    /**
     * Calculate the activity level of the group.
     */
    protected function activityLevel(): Attribute
    {
        return Attribute::make(
            get: function () {
                $postCount = $this->posts()->getQuery()->where('created_at', '>=', now()->subDays(30))->count();
                $memberCount = $this->members()->getQuery()->count();

                if ($postCount > 100 || $memberCount > 1000) {
                    return __('group.activity_level.very_active');
                } elseif ($postCount > 50 || $memberCount > 500) {
                    return __('group.activity_level.active');
                } elseif ($postCount > 10 || $memberCount > 100) {
                    return __('group.activity_level.moderate');
                } else {
                    return __('group.activity_level.quiet');
                }
            }
        );
    }

    /**
     * Relation with the creator of the group.
     */
    public function creator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Relation with the members of the group.
     */
    public function members(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_user')
            ->withPivot('role', 'status', 'joined_at', 'notification_settings', 'is_favorite')
            ->withTimestamps();
    }

    /**
     * Relation with the admins of the group.
     */
    public function admins(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->members()->wherePivot('role', 'admin');
    }

    /**
     * Relation with the moderators of the group.
     */
    public function moderators(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->members()->wherePivot('role', 'moderator');
    }

    /**
     * Relation with the posts of the group.
     */
    public function posts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Relation with the events of the group.
     */
    public function events(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Relation with the conversation associated with the group.
     */
    public function conversation(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Relation with the category of the group.
     */
    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(GroupCategory::class, 'group_category_id');
    }

    /**
     * Relation with the hashtags associated with the group.
     */
    public function hashtags(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Hashtag::class)->withTimestamps();
    }

    /**
     * Add a hashtag to the group.
     *
     * @param  string  $name  The name of the hashtag
     */
    public function addHashtag(string $name): void
    {
        $hashtag = Hashtag::firstOrCreate(['name' => $name]);
        $this->hashtags()->syncWithoutDetaching($hashtag);
    }

    /**
     * Get the pinned posts.
     */
    public function pinnedPosts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->posts()->where('is_pinned', true)->orderBy('pinned_at', 'desc');
    }

    /**
     * Check if a user is an admin.
     *
     * @param  User  $user  The user to check
     * @return bool
     */
    public function isAdmin(User $user)
    {
        return $this->admins()->getQuery()->where('user_id', $user->id)->exists();
    }

    /**
     * Check if a user is a moderator.
     *
     * @param  User  $user  The user to check
     * @return bool
     */
    public function isModerator(User $user)
    {
        return $this->moderators()->getQuery()->where('user_id', $user->id)->exists() || $this->isAdmin($user);
    }

    /**
     * Add a member to the group.
     *
     * @param  User  $user  The user to add
     * @param  string  $role  The role to assign (member, moderator, admin)
     * @param  bool  $sendNotification  Send a notification
     */
    public function addMember(User $user, $role = 'member', $sendNotification = true): bool
    {
        if ($this->members()->getQuery()->where('user_id', $user->id)->exists()) {
            return false;
        }

        $status = 'approved';
        if ($this->privacy === 'private' && $role === 'member') {
            $status = $this->auto_approve_members ? 'approved' : 'pending';
        }

        $this->members()->attach($user->id, [
            'role' => $role,
            'status' => $status,
            'joined_at' => $status === 'approved' ? now() : null,
            'notification_settings' => json_encode([
                'posts' => true,
                'events' => true,
                'comments' => false,
            ]),
        ]);

        if ($sendNotification && $status === 'approved') {
            // $user->notify(new \App\Notifications\GroupMembershipApproved($this));
        }

        return true;
    }

    /**
     * Invite a user to join the group.
     *
     * @param  User  $user  The user to invite
     * @param  User|null  $invitedBy  The user who invites
     */
    public function inviteUser(User $user, ?User $invitedBy = null): bool
    {
        $membership = $this->members()->getQuery()->where('user_id', $user->id)->first();

        if ($membership) {
            if ($membership->pivot->status === 'invited') {
                return false;
            }

            return false;
        }

        $this->members()->attach($user->id, [
            'role' => 'member',
            'status' => 'invited',
            'invited_by' => $invitedBy ? $invitedBy->id : null,
            'notification_settings' => json_encode([
                'posts' => true,
                'events' => true,
                'comments' => false,
            ]),
        ]);

        // Envoi d'une notification
        // $user->notify(new \App\Notifications\GroupInvitation($this, $invitedBy));

        return true;
    }

    /**
     * Approve a membership request.
     *
     * @param  User  $user  The user to approve
     */
    public function approveMembership(User $user): bool
    {
        $membership = $this->members()->getQuery()->where('user_id', $user->id)->first();

        if (! $membership || $membership->pivot->status !== 'pending') {
            return false;
        }

        $this->members()->updateExistingPivot($user->id, [
            'status' => 'approved',
            'joined_at' => now(),
        ]);

        // Envoi d'une notification
        // $user->notify(new \App\Notifications\GroupMembershipApproved($this));

        return true;
    }

    /**
     * Reject a membership request.
     *
     * @param  User  $user  The user to reject
     */
    public function rejectMembership(User $user): bool
    {
        $membership = $this->members()->getQuery()->where('user_id', $user->id)->first();

        if (! $membership || $membership->pivot->status !== 'pending') {
            return false;
        }

        $this->members()->detach($user->id);

        // Envoi d'une notification
        // $user->notify(new \App\Notifications\GroupMembershipRejected($this));

        return true;
    }

    /**
     * Change the role of a member.
     *
     * @param  User  $user  The user concerned
     * @param  string  $role  The new role
     */
    public function changeMemberRole(User $user, string $role): bool
    {
        if (! in_array($role, ['member', 'moderator', 'admin'])) {
            return false;
        }

        $membership = $this->members()->getQuery()->where('user_id', $user->id)->first();

        if (! $membership || $membership->pivot->status !== 'approved') {
            return false;
        }

        $this->members()->updateExistingPivot($user->id, [
            'role' => $role,
        ]);

        return true;
    }

    /**
     * Ban a member from the group.
     *
     * @param  User  $user  The user to ban
     * @param  string|null  $reason  The reason for the ban
     */
    public function banMember(User $user, ?string $reason = null): bool
    {
        $membership = $this->members()->getQuery()->where('user_id', $user->id)->first();

        if (! $membership) {
            return false;
        }

        if ($user->id === $this->creator_id) {
            return false;
        }

        $this->members()->updateExistingPivot($user->id, [
            'status' => 'banned',
            'ban_reason' => $reason,
            'banned_at' => now(),
        ]);

        // Envoi d'une notification
        // $user->notify(new \App\Notifications\GroupBan($this, $reason));

        return true;
    }

    /**
     * Remove a member from the group.
     *
     * @param  User  $user  The user to remove
     */
    public function removeMember(User $user): bool
    {
        $membership = $this->members()->getQuery()->where('user_id', $user->id)->first();

        if (! $membership || $membership->pivot->status !== 'approved') {
            return false;
        }

        if ($user->id === $this->creator_id) {
            return false;
        }

        $this->members()->detach($user->id);

        return true;
    }

    /**
     * Create a unique slug for the group.
     *
     * @param  string  $name  Group name
     */
    public static function createUniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $count = static::whereRaw("slug RLIKE '^{$slug}(-[0-9]+)?$'")->count();

        return $count ? "{$slug}-{$count}" : $slug;
    }

    /**
     * Get suggested groups for a user.
     *
     * @param  User  $user  The user
     * @param  int|null  $limit  Result limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function suggestedForUser(User $user, ?int $limit = null)
    {
        $userGroupIds = $user->groups()->pluck('groups.id')->toArray();

        $query = self::whereNotIn('id', $userGroupIds)
            ->where(function ($q) {
                $q->where('privacy', 'public')
                    ->orWhere('privacy', 'restricted');
            })
            ->orderBy('members_count', 'desc')
            ->withCount('members');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Define a local scope for public groups.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePublic($query)
    {
        return $query->where('privacy', 'public');
    }

    /**
     * Define a local scope for active groups.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('last_activity_at', '>=', now()->subDays(30));
    }

    /**
     * Define a local scope for trending groups.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $days  Number of days to consider recent activity
     */
    public function scopeTrending($query, int $days = 7): Builder
    {
        return $query->withCount(['posts' => function ($q) use ($days) {
            $q->where('created_at', '>=', now()->subDays($days));
        }])
            ->withCount('members')
            ->orderByRaw('posts_count * 3 + members_count DESC')
            ->where('last_activity_at', '>=', now()->subDays($days));
    }
}
