<?php

namespace IJIDeals\Social\Models;

use Carbon\Carbon;
use IJIDeals\Messaging\Models\Message;
use IJIDeals\Social\Notifications\EventCancelled;
use IJIDeals\Social\Notifications\EventInvitation;
use IJIDeals\Social\Notifications\EventReminder;
use IJIDeals\UserManagement\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Notification;
use Laravel\Jetstream\HasProfilePhoto; // Assuming this is still needed
use OpenApi\Annotations as OA; // Import OpenApi namespace

// Notification use statements are already present

/**
 * @OA\Schema(
 *     schema="Event",
 *     title="Event",
 *     description="Modèle représentant un événement social",
 *
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64",
 *         description="ID unique de l'événement"
 *     ),
 *     @OA\Property(
 *         property="title",
 *         type="string",
 *         nullable=true,
 *         description="Titre de l'événement"
 *     ),
 *     @OA\Property(
 *         property="description",
 *         type="string",
 *         nullable=true,
 *         description="Description de l'événement"
 *     ),
 *     @OA\Property(
 *         property="location_name",
 *         type="string",
 *         nullable=true,
 *         description="Nom du lieu de l'événement"
 *     ),
 *     @OA\Property(
 *         property="location_address",
 *         type="string",
 *         nullable=true,
 *         description="Adresse complète du lieu de l'événement"
 *     ),
 *     @OA\Property(
 *         property="location_latitude",
 *         type="number",
 *         format="float",
 *         nullable=true,
 *         description="Latitude du lieu de l'événement"
 *     ),
 *     @OA\Property(
 *         property="location_longitude",
 *         type="number",
 *         format="float",
 *         nullable=true,
 *         description="Longitude du lieu de l'événement"
 *     ),
 *     @OA\Property(
 *         property="cover_image",
 *         type="string",
 *         nullable=true,
 *         description="Chemin de l'image de couverture de l'événement"
 *     ),
 *     @OA\Property(
 *         property="cover_image_disk",
 *         type="string",
 *         nullable=true,
 *         description="Disque de stockage de l'image de couverture"
 *     ),
 *     @OA\Property(
 *         property="start_date",
 *         type="string",
 *         format="date",
 *         nullable=true,
 *         description="Date de début de l'événement (YYYY-MM-DD)"
 *     ),
 *     @OA\Property(
 *         property="end_date",
 *         type="string",
 *         format="date",
 *         nullable=true,
 *         description="Date de fin de l'événement (YYYY-MM-DD)"
 *     ),
 *     @OA\Property(
 *         property="start_time",
 *         type="string",
 *         format="time",
 *         nullable=true,
 *         description="Heure de début de l'événement (HH:MM)"
 *     ),
 *     @OA\Property(
 *         property="end_time",
 *         type="string",
 *         format="time",
 *         nullable=true,
 *         description="Heure de fin de l'événement (HH:MM)"
 *     ),
 *     @OA\Property(
 *         property="all_day",
 *         type="boolean",
 *         description="Indique si l'événement dure toute la journée"
 *     ),
 *     @OA\Property(
 *         property="created_by",
 *         type="integer",
 *         format="int64",
 *         description="ID de l'utilisateur qui a créé l'événement"
 *     ),
 *     @OA\Property(
 *         property="group_id",
 *         type="integer",
 *         format="int64",
 *         nullable=true,
 *         description="ID du groupe auquel l'événement est associé (si applicable)"
 *     ),
 *     @OA\Property(
 *         property="privacy_setting",
 *         type="string",
 *         description="Paramètre de confidentialité de l'événement",
 *         enum={"public", "private", "friends_only"}
 *     ),
 *     @OA\Property(
 *         property="status",
 *         type="string",
 *         nullable=true,
 *         description="Statut de l'événement (e.g., 'active', 'cancelled', 'draft')",
 *         enum={"active", "cancelled", "draft", "pending", "past"}
 *     ),
 *     @OA\Property(
 *         property="settings",
 *         type="object",
 *         nullable=true,
 *         description="Paramètres supplémentaires de l'événement (JSON)"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="Date de création de l'événement"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         description="Date de dernière mise à jour de l'événement"
 *     ),
 *     @OA\Property(
 *         property="deleted_at",
 *         type="string",
 *         format="date-time",
 *         nullable=true,
 *         description="Date de suppression douce de l'événement"
 *     ),
 *     @OA\Property(
 *         property="profile_photo_path",
 *         type="string",
 *         nullable=true,
 *         description="Chemin de la photo de profil de l'événement"
 *     ),
 *     @OA\Property(
 *         property="cover_image_url",
 *         type="string",
 *         format="url",
 *         readOnly=true,
 *         description="URL publique de l'image de couverture de l'événement"
 *     ),
 *     @OA\Property(
 *         property="formatted_date",
 *         type="string",
 *         readOnly=true,
 *         description="Date de l'événement formatée pour l'affichage"
 *     ),
 *     @OA\Property(
 *         property="is_past",
 *         type="boolean",
 *         readOnly=true,
 *         description="Indique si l'événement est passé"
 *     ),
 *     @OA\Property(
 *         property="participant_count",
 *         type="integer",
 *         readOnly=true,
 *         description="Nombre de participants confirmés ou 'peut-être' à l'événement"
 *     ),
 *     @OA\Property(
 *         property="attendees",
 *         type="array",
 *
 *         @OA\Items(ref="#/components/schemas/User"),
 *         readOnly=true,
 *         description="Liste des participants confirmés à l'événement"
 *     ),
 *
 *     @OA\Property(
 *         property="profile_photo_url",
 *         type="string",
 *         format="url",
 *         readOnly=true,
 *         description="URL de la photo de profil de l'événement"
 *     ),
 *     @OA\Property(
 *         property="creator",
 *         ref="#/components/schemas/User",
 *         description="L'utilisateur qui a créé l'événement"
 *     ),
 *     @OA\Property(
 *         property="group",
 *         ref="#/components/schemas/Group",
 *         description="Le groupe associé à l'événement (si applicable)"
 *     ),
 *     @OA\Property(
 *         property="participants",
 *         type="array",
 *
 *         @OA\Items(ref="#/components/schemas/User"),
 *         description="Liste de tous les participants (invités, confirmés, peut-être, refusés)"
 *     ),
 *
 *     @OA\Property(
 *         property="confirmed_participants",
 *         type="array",
 *
 *         @OA\Items(ref="#/components/schemas/User"),
 *         description="Liste des participants qui ont confirmé leur présence"
 *     ),
 *
 *     @OA\Property(
 *         property="maybe_participants",
 *         type="array",
 *
 *         @OA\Items(ref="#/components/schemas/User"),
 *         description="Liste des participants qui ont indiqué 'peut-être'"
 *     ),
 *
 *     @OA\Property(
 *         property="declined_participants",
 *         type="array",
 *
 *         @OA\Items(ref="#/components/schemas/User"),
 *         description="Liste des participants qui ont refusé"
 *     ),
 *
 *     @OA\Property(
 *         property="pending_participants",
 *         type="array",
 *
 *         @OA\Items(ref="#/components/schemas/User"),
 *         description="Liste des participants invités en attente de réponse"
 *     ),
 *
 *     @OA\Property(
 *         property="messages",
 *         type="array",
 *
 *         @OA\Items(ref="#/components/schemas/Message"),
 *         description="Messages associés à l'événement"
 *     ),
 *
 *     @OA\Property(
 *         property="hashtags",
 *         type="array",
 *
 *         @OA\Items(ref="#/components/schemas/Hashtag"),
 *         description="Hashtags associés à l'événement"
 *     ),
 *
 *     @OA\Property(
 *         property="favorited_by",
 *         type="array",
 *
 *         @OA\Items(ref="#/components/schemas/User"),
 *         description="Utilisateurs ayant mis l'événement en favoris"
 *     )
 * )
 *
 * @property int $id
 * @property string|null $title
 * @property string|null $description
 * @property string|null $location_name
 * @property string|null $location_address
 * @property float|null $location_latitude
 * @property float|null $location_longitude
 * @property string|null $cover_image
 * @property string|null $cover_image_disk
 * @property \Illuminate\Support\Carbon|null $start_date
 * @property \Illuminate\Support\Carbon|null $end_date
 * @property string|null $start_time
 * @property string|null $end_time
 * @property bool $all_day
 * @property int $created_by
 * @property int|null $group_id
 * @property string $privacy_setting // Example, adjust if actual field name differs for settings.visibility
 * @property string|null $status
 * @property array|null $settings
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property string|null $profile_photo_path
 * @property-read string $cover_image_url
 * @property-read string $formatted_date
 * @property-read bool $is_past
 * @property-read int $participant_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\IJIDeals\UserManagement\Models\User[] $attendees
 * @property-read string $profile_photo_url
 * @property-read \IJIDeals\UserManagement\Models\User|null $creator
 * @property-read \IJIDeals\Social\Models\Group|null $group
 * @property-read \Illuminate\Database\Eloquent\Collection|\IJIDeals\UserManagement\Models\User[] $participants
 * @property-read \Illuminate\Database\Eloquent\Collection|\IJIDeals\UserManagement\Models\User[] $confirmedParticipants
 * @property-read \Illuminate\Database\Eloquent\Collection|\IJIDeals\UserManagement\Models\User[] $maybeParticipants
 * @property-read \Illuminate\Database\Eloquent\Collection|\IJIDeals\UserManagement\Models\User[] $declinedParticipants
 * @property-read \Illuminate\Database\Eloquent\Collection|\IJIDeals\UserManagement\Models\User[] $pendingParticipants
 * @property-read \Illuminate\Database\Eloquent\Collection|\IJIDeals\UserManagement\Models\User[] $favoritedBy
 * @property-read \Illuminate\Database\Eloquent\Collection|\IJIDeals\Social\Models\Hashtag[] $hashtags
 * @property-read \Illuminate\Database\Eloquent\Collection|\IJIDeals\Messaging\Models\Message[] $messages
 *
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Event extends Model
{
    use HasFactory, HasProfilePhoto, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'location',
        'user_id', // Assumed to be the creator's ID based on typical migrations and existing 'creator' relationship
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'all_day',
        'cover_image',
        'settings',
        'group_id',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
        'start_time' => 'string', // Stored as H:i string
        'end_time' => 'string',   // Stored as H:i string
        'all_day' => 'boolean',
        'settings' => 'array',
        'deleted_at' => 'datetime',
    ];

    protected $appends = [
        'cover_image_url',
        'formatted_date',
        'is_past',
        'participant_count',
        'attendees',
    ];

    // Accessors
    public function getCoverImageUrlAttribute(): string
    {
        return $this->cover_image ? \Illuminate\Support\Facades\Storage::url($this->cover_image) : asset('images/default-event-cover.jpg');
    }

    public function getFormattedDateAttribute(): string
    {
        // Ensure start_date is a Carbon instance before calling methods on it.
        // The 'date' cast should convert it to Carbon, but if it's unexpectedly null,
        // this check prevents a "Call to a member function translatedFormat() on null" error.
        $datePart = '';
        if ($this->start_date) {
            $datePart = $this->start_date->translatedFormat('l j F Y');
        }

        // Parse start_time string to Carbon for consistent formatting.
        $timePart = $this->start_time ? Carbon::parse($this->start_time)->format('H:i') : '';

        // Combine date and time parts based on availability.
        if ($datePart && $timePart) {
            return "$datePart à $timePart";
        } elseif ($datePart) {
            return $datePart;
        } elseif ($timePart) {
            // If only time is available (e.g., date was null or not set), return just the time.
            return $timePart;
        }

        return ''; // Return an empty string if neither date nor time is available.
    }

    public function getIsPastAttribute(): bool
    {
        $endDate = $this->end_date ?? $this->start_date;
        $endDateTime = $this->end_time ? Carbon::parse($this->end_time)->setTimeFromTimeString($this->end_time) : Carbon::parse($endDate)->endOfDay();

        return now()->gt($endDateTime);
    }

    public function getParticipantCountAttribute(): int
    {
        return $this->participants()->getQuery()->whereIn('event_user.status', ['going', 'maybe'])->count();
    }

    public function getAttendeesAttribute()
    {
        return $this->confirmedParticipants()->get();
    }

    // Relationships

    /**
     * Get the user who created the event.
     * This relationship uses the 'user_id' column, which is assumed to be the primary creator link
     * based on typical Laravel conventions and usage in scopes like `upcomingForUser`.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'event_user')
            ->withPivot('status', 'invited_by', 'reminder_enabled', 'responded_at')
            ->withTimestamps();
    }

    public function confirmedParticipants(): BelongsToMany
    {
        return $this->participants()->wherePivot('status', 'going');
    }

    public function maybeParticipants(): BelongsToMany
    {
        return $this->participants()->wherePivot('status', 'maybe');
    }

    public function declinedParticipants(): BelongsToMany
    {
        return $this->participants()->wherePivot('status', 'declined');
    }

    /** @return \Illuminate\Database\Query\Builder */
    public function pendingParticipants(): Builder
    {
        return $this->participants()->wherePivot('status', 'invited')->getQuery()->whereNull('event_user.responded_at');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function hashtags(): BelongsToMany
    {
        return $this->belongsToMany(Hashtag::class, 'event_hashtag')->withTimestamps();
    }

    /**
     * Relationship for users who favorited this event.
     * Assumes a pivot table named 'event_favorites' exists.
     */
    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'event_favorites', 'event_id', 'user_id')->withTimestamps();
    }

    // Helper Methods

    /**
     * Check if the given user is the creator of this event based on the 'user_id' column.
     */
    public function isCreator(User $user): bool
    {
        return $this->created_by === $user->id;
    }

    /**
     * Check if the event is private based on its settings.
     */
    public function isPrivate(): bool
    {
        return Arr::get($this->settings, 'visibility', 'public') === 'private';
    }

    /**
     * Check if the event is only visible to friends based on its settings.
     */
    public function isFriendsOnly(): bool
    {
        return Arr::get($this->settings, 'visibility', 'public') === 'friends';
    }

    /**
     * Determine if a user can view this event based on its visibility settings.
     * Assumes 'friends' visibility allows the creator and participants to view.
     */
    public function canView(User $user): bool
    {
        if (Arr::get($this->settings, 'visibility', 'public') === 'public') {
            return true;
        }

        if ($this->isCreator($user)) {
            return true;
        }

        if ($this->isPrivate()) {
            return false; // Only creator can view private events
        }

        if ($this->isFriendsOnly()) {
            return $this->isParticipating($user); // Allow participants to view friends-only events
        }

        return false;
    }

    /**
     * Get the current participation status of a user for this event.
     */
    public function getUserStatus(User $user): ?string
    {
        return $this->participants()->getQuery()->where('user_id', $user->id)->first()?->pivot->status;
    }

    /**
     * Check if the given user is participating in this event (any status).
     */
    public function isParticipating(User $user): bool
    {
        return $this->participants()->getQuery()->where('user_id', $user->id)->exists();
    }

    /**
     * Check if the given user has favorited this event.
     */
    public function isFavorite(User $user): bool
    {
        return $this->favoritedBy()->getQuery()->where('user_id', $user->id)->exists();
    }

    /**
     * Toggle the favorite status for a given user.
     */
    public function toggleFavorite(User $user): void
    {
        $this->favoritedBy()->toggle($user->id);
    }

    /**
     * Determine if a user can invite others to this event.
     * Allows creator and confirmed participants to invite.
     */
    public function canInvite(User $user): bool
    {
        return $this->isCreator($user) || $this->confirmedParticipants()->getQuery()->where('user_id', $user->id)->exists();
    }

    public function inviteUser(User $user, bool $sendNotification = true): bool
    {
        if ($this->participants()->getQuery()->where('user_id', $user->id)->exists()) {
            return false;
        }

        $this->participants()->attach($user->id, [
            'status' => 'invited',
            'reminder_enabled' => true,
            'invited_by' => auth()->id(),
        ]);

        if ($sendNotification) {
            $user->notify(new EventInvitation);
        }

        return true;
    }

    public function inviteUsers(array $userIds, bool $sendNotification = true): int
    {
        $existingParticipants = $this->participants()->getQuery()->whereIn('user_id', $userIds)->pluck('user_id')->toArray();
        $newParticipants = array_diff($userIds, $existingParticipants);

        $attachData = [];
        foreach ($newParticipants as $userId) {
            $attachData[$userId] = [
                'status' => 'invited',
                'reminder_enabled' => true,
                'invited_by' => auth()->id(),
            ];
        }

        $this->participants()->attach($attachData);

        if ($sendNotification && ! empty($newParticipants)) {
            $users = \IJIDeals\UserManagement\Models\User::whereIn('id', $newParticipants)->get();
            \Illuminate\Support\Facades\Notification::send($users, new EventInvitation);
        }

        return count($newParticipants);
    }

    public function updateParticipantStatus(int $userId, string $status): self
    {
        $validStatuses = ['invited', 'going', 'maybe', 'declined'];
        if (! in_array($status, $validStatuses)) {
            throw new \InvalidArgumentException("Invalid status: $status");
        }

        $this->participants()->syncWithoutDetaching([
            $userId => [
                'status' => $status,
                'responded_at' => $status !== 'invited' ? now() : null,
            ],
        ]);

        return $this;
    }

    public function getParticipantStatus(int $userId): ?string
    {
        return $this->participants()->getQuery()->where('user_id', $userId)->first()?->pivot->status;
    }

    public function countParticipantsByStatus(): array
    {
        $counts = [
            'invited' => 0,
            'going' => 0,
            'maybe' => 0,
            'declined' => 0,
        ];

        $statuses = $this->participants()->getQuery()->pluck('event_user.status')->toArray();
        foreach ($statuses as $status) {
            $counts[$status] = ($counts[$status] ?? 0) + 1;
        }

        return $counts;
    }

    // The isPast() helper method was redundant with the getIsPastAttribute() accessor and has been removed.

    public function isOngoing(): bool
    {
        $startDateTime = $this->start_time
            ? Carbon::parse($this->start_time)->setTimeFromTimeString($this->start_time)
            : Carbon::parse($this->start_date)->startOfDay();

        $endDate = $this->end_date ?? $this->start_date;
        $endDateTime = $this->end_time
            ? Carbon::parse($this->end_time)->setTimeFromTimeString($this->end_time)
            : Carbon::parse($endDate)->endOfDay();

        return now()->between($startDateTime, $endDateTime);
    }

    public function cancel(bool $sendNotification = true): bool
    {
        $this->update(['deleted_at' => now()]);

        if ($sendNotification) {
            $participants = $this->participants()->getQuery()->whereIn('event_user.status', ['going', 'maybe', 'invited'])->get();
            \Illuminate\Support\Facades\Notification::send($participants, new EventCancelled);
        }

        return true;
    }

    public function isCancelled(): bool
    {
        return ! is_null($this->deleted_at);
    }

    public function addHashtag(string $name): void
    {
        $hashtag = \IJIDeals\Social\Models\Hashtag::firstOrCreate(['name' => trim($name)]);
        $this->hashtags()->syncWithoutDetaching([$hashtag->id]);
    }

    // Methods encapsulating typical EventController logic (store, update, destroy, index)

    /**
     * Create a new event.
     * Corresponds to the 'store' method in a controller.
     *
     * @param  array  $data  Event data (title, description, dates, times, group_id, cover_image, settings, etc.)
     * @param  array  $hashtagNames  Array of hashtag names to attach.
     * @param  array  $invitedUserIds  Array of user IDs to invite.
     * @return self The created Event instance.
     */
    public static function storeEvent(array $data, array $hashtagNames = [], array $invitedUserIds = []): self
    {
        // Automatically set the creator's ID from the authenticated user
        $data['createdby'] = auth()->id();

        // Extract and process settings
        $settings = \Illuminate\Support\Arr::get($data, 'settings', []); // Start with any existing settings in data
        if (isset($data['visibility'])) {
            $settings['visibility'] = $data['visibility'];
            unset($data['visibility']);
        }
        if (isset($data['allow_guests'])) {
            $settings['allow_guests'] = (bool) $data['allow_guests'];
            unset($data['allow_guests']);
        }
        if (isset($data['max_participants'])) {
            $settings['max_participants'] = (int) $data['max_participants'];
            unset($data['max_participants']);
        }
        $data['settings'] = $settings; // Assign merged settings back to data

        $event = self::create($data);
        /** @var \IJIDeals\Social\Models\Event $event */

        // Attach hashtags
        if (! empty($hashtagNames)) {
            $hashtagIds = [];
            foreach ($hashtagNames as $name) {
                $hashtag = \IJIDeals\Social\Models\Hashtag::firstOrCreate(['name' => trim($name)]); // Changed to use imported Hashtag
                $hashtagIds[] = $hashtag->id;
            }
            $event->hashtags()->sync($hashtagIds);
        }

        // Invite users
        if (! empty($invitedUserIds)) {
            $event->inviteUsers($invitedUserIds);
        }

        return $event;
    }

    /**
     * Update an existing event.
     * Corresponds to the 'update' method in a controller.
     *
     * @param  array  $data  Event data to update (title, description, dates, times, cover_image, settings, etc.)
     * @param  array  $hashtagNames  Array of hashtag names to sync. If empty, all existing hashtags will be detached.
     * @return bool True on success, false otherwise.
     */
    public function updateEvent(array $data, array $hashtagNames = []): bool
    {
        // Extract and process settings for update
        $settings = Arr::get($this->attributes, 'settings', []); // Get existing settings from model attributes
        if (isset($data['visibility'])) {
            $settings['visibility'] = $data['visibility'];
            unset($data['visibility']);
        }
        if (isset($data['allow_guests'])) {
            $settings['allow_guests'] = (bool) $data['allow_guests'];
            unset($data['allow_guests']);
        }
        if (isset($data['max_participants'])) {
            $settings['max_participants'] = (int) $data['max_participants'];
            unset($data['max_participants']);
        }
        $data['settings'] = $settings; // Assign updated settings back

        $updated = $this->update($data);

        // Sync hashtags
        if (! empty($hashtagNames)) {
            $hashtagIds = [];
            foreach ($hashtagNames as $name) {
                $hashtag = \IJIDeals\Social\Models\Hashtag::firstOrCreate(['name' => trim($name)]);
                $hashtagIds[] = $hashtag->id;
            }
            $this->hashtags()->sync($hashtagIds);
        } else {
            // If no hashtags are provided, detach all existing ones
            $this->hashtags()->detach();
        }

        return $updated;
    }

    /**
     * Delete (soft delete) an event.
     * Corresponds to the 'destroy' method in a controller.
     * This method leverages the existing `cancel()` method for consistency,
     * which also handles sending notifications to participants.
     *
     * @param  bool  $sendNotification  Whether to send cancellation notifications.
     * @return bool True if the event was successfully cancelled/deleted.
     */
    public function destroyEvent(bool $sendNotification = true): bool
    {
        return $this->cancel($sendNotification);
    }

    // Scopes

    /**
     * Scope a query to only include events created by a specific user based on the 'user_id' column.
     */
    public function scopeCreator(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    /**
     * Scope a query to filter events based on various criteria, typically for an 'index' view.
     * Corresponds to filtering logic often found in an 'index' method of a controller.
     *
     * @param  array  $filters  Associative array of filters (e.g., 'user_id', 'group_id', 'participant_user_id', 'participant_status', 'search', 'date_range', 'hashtag').
     */
    public function scopeIndex(Builder $query, array $filters): Builder
    {
        $query->whereNull('deleted_at'); // Always exclude soft-deleted events by default for general queries

        // Filter by creator
        if ($userId = Arr::get($filters, 'user_id')) {
            $query->where('user_id', $userId);
        }

        // Filter by group
        if ($groupId = Arr::get($filters, 'group_id')) {
            $query->where('group_id', $groupId);
        }

        // Filter by participant status for a specific user
        if ($participantUserId = Arr::get($filters, 'participant_user_id')) {
            $status = Arr::get($filters, 'participant_status', ['going', 'maybe', 'invited']); // Default statuses
            $query->whereHas('participants', fn ($q) => $q->where('user_id', $participantUserId)
                ->whereIn('event_user.status', (array) $status)
            );
        }

        // Filter by date range (e.g., 'upcoming', 'past', 'today', 'custom')
        if ($dateRange = Arr::get($filters, 'date_range')) {
            switch ($dateRange) {
                case 'upcoming':
                    $query->where(function ($q) {
                        $q->where('end_date', '>=', now()->startOfDay()->format('Y-m-d'))
                            ->orWhere(function ($subQ) {
                                $subQ->whereNull('end_date') // For events without an end date, consider start date
                                    ->where('start_date', '>=', now()->startOfDay()->format('Y-m-d'));
                            });
                    })
                        ->orderBy('start_date', 'asc')
                        ->orderBy('start_time', 'asc');
                    break;
                case 'past':
                    $query->where(function ($q) {
                        $q->where('end_date', '<', now()->startOfDay()->format('Y-m-d'))
                            ->orWhere(function ($subQ) {
                                $subQ->whereNull('end_date') // For events without an end date, consider start date
                                    ->where('start_date', '<', now()->startOfDay()->format('Y-m-d'));
                            });
                    })
                        ->orderBy('start_date', 'desc')
                        ->orderBy('start_time', 'desc');
                    break;
                case 'today':
                    $query->where(function ($q) {
                        $q->whereDate('start_date', now()->toDateString())
                            ->orWhere(function ($subQ) {
                                $subQ->whereDate('start_date', '<=', now()->toDateString())
                                    ->whereDate('end_date', '>=', now()->toDateString());
                            });
                    });
                    break;
                    // Add more custom date range logic if needed (e.g., 'start_date_after', 'end_date_before')
            }
        }

        // Search by title or description
        if ($search = Arr::get($filters, 'search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%');
            });
        }

        // Filter by hashtag
        if ($hashtagName = Arr::get($filters, 'hashtag')) {
            $query->whereHas('hashtags', fn ($q) => $q->where('name', $hashtagName));
        }

        return $query;
    }

    public static function upcomingForUser(User $user, ?int $limit = null)
    {
        // Corrected: Uses 'user_id' for creator consistency
        $query = self::where(function (Builder $query) use ($user) {
            $query->where('created_by', $user->id)
                ->orWhereHas('participants', fn ($q) => $q->where('user_id', $user->id)
                    ->whereIn('event_user.status', ['going', 'maybe']));
        })
            ->where('start_date', '>=', now()->startOfDay()->format('Y-m-d'))
            ->whereNull('deleted_at')
            ->orderBy('start_date')
            ->orderBy('start_time', 'asc');

        return $limit ? $query->take($limit)->get() : $query->get();
    }

    public static function pastForUser(User $user, ?int $limit = null)
    {
        $query = self::where(function (Builder $query) use ($user) {
            $query->where('user_id', $user->id) // Uses user_id for creator
                ->orWhereHas('participants', fn ($q) => $q->where('user_id', $user->id)
                    ->where('event_user.status', 'going'));
        })
            ->where('start_date', '<', now()->startOfDay()->format('Y-m-d'))
            ->whereNull('deleted_at')
            ->orderBy('start_date', 'desc')
            ->orderBy('start_time', 'desc');

        return $limit ? $query->take($limit)->get() : $query->get();
    }

    // Static Methods

    public static function sendReminders(): int
    {
        $tomorrow = now()->addDay()->startOfDay();
        $events = self::where('start_date', $tomorrow->format('Y-m-d'))
            ->whereNull('deleted_at')
            ->get();

        foreach ($events as $event) {
            $participants = $event->participants()->getQuery()
                ->whereIn('event_user.status', ['going', 'maybe'])
                ->where('event_user.reminder_enabled', true)
                ->get();

            \Illuminate\Support\Facades\Notification::send($participants, new EventReminder);
        }

        return $events->count();
    }
}
