<?php

namespace Acme\SecureMessaging\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory; // Pour les tests futurs

class Group extends Model
{
    use SoftDeletes;
    // use HasFactory; // Décommenter si vous créez des factories

    protected $table = 'messaging_groups';

    protected $fillable = [
        'uuid',
        'name',
        'description',
        'avatar_url', // Optionnel
        'created_by_user_id', // Qui a créé le groupe
    ];

    /**
     * The members that belong to the group.
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(config('messaging.user_model'), 'messaging_group_members', 'group_id', 'user_id')
            ->using(GroupMember::class) // Utiliser le modèle pivot personnalisé
            ->withPivot(['role', 'joined_at'])
            ->withTimestamps();
    }

    /**
     * Get the group member entries.
     */
    public function groupMembers(): HasMany
    {
        return $this->hasMany(GroupMember::class, 'group_id');
    }

    /**
     * Get the conversation associated with this group.
     * Un groupe aura une conversation dédiée.
     */
    public function conversation(): HasOne
    {
        return $this->hasOne(Conversation::class, 'group_id');
    }

    /**
     * Get the user who created the group.
     */
    public function creator()
    {
        return $this->belongsTo(config('messaging.user_model'), 'created_by_user_id');
    }

    /**
     * Boot function from Laravel.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = \Illuminate\Support\Str::uuid()->toString();
            }
        });

        static::created(function ($group) {
            // Créer automatiquement une conversation pour ce groupe
            $conversation = Conversation::create([
                'type' => 'group',
                'group_id' => $group->id,
                'last_message_at' => now(), // Initialiser avec l'heure actuelle
            ]);
            // Associer le créateur du groupe comme premier participant de la conversation
            // Les autres membres seront ajoutés à la conversation lorsqu'ils rejoignent le groupe
            $creatorId = $group->created_by_user_id;
            if ($creatorId) {
                 $conversation->participants()->attach($creatorId);
            }
        });
    }
}
