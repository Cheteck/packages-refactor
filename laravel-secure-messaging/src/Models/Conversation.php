<?php

namespace Acme\SecureMessaging\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory; // Pour les tests futurs

class Conversation extends Model
{
    // use HasFactory; // Décommenter si vous créez des factories

    protected $table = 'messaging_conversations';

    protected $fillable = [
        'uuid', // Pour une identification unique et plus sécurisée que l'ID incrémental
        'type', // 'individual' ou 'group'
        'group_id', // Si type == 'group'
        'last_message_at', // Pour trier les conversations
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    /**
     * Get the messages for the conversation.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'conversation_id');
    }

    /**
     * The users that belong to the conversation.
     * Pour les conversations individuelles, il y aura deux utilisateurs.
     * Pour les conversations de groupe, cela peut être géré via le modèle Group et GroupMember.
     */
    public function participants(): BelongsToMany
    {
        // Nous aurons besoin d'une table pivot `messaging_conversation_user`
        return $this->belongsToMany(config('messaging.user_model'), 'messaging_conversation_user', 'conversation_id', 'user_id')
            ->withTimestamps();
    }

    /**
     * Get the group associated with the conversation, if any.
     */
    public function group()
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    /**
     * Scope a query to only include conversations of a specific type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
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
    }
}
