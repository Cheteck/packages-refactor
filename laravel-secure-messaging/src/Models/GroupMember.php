<?php

namespace Acme\SecureMessaging\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Factories\HasFactory; // Pour les tests futurs

class GroupMember extends Pivot
{
    // use HasFactory; // Décommenter si vous créez des factories

    protected $table = 'messaging_group_members';

    public $incrementing = true; // Pivot tables can have auto-incrementing IDs if needed

    protected $fillable = [
        'group_id',
        'user_id',
        'role', // e.g., 'admin', 'member'
        'joined_at',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
    ];

    public const ROLE_ADMIN = 'admin';
    public const ROLE_MEMBER = 'member';

    /**
     * Get the group.
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    /**
     * Get the user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('messaging.user_model'), 'user_id');
    }

    /**
     * Boot function from Laravel.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->joined_at)) {
                $model->joined_at = now();
            }
        });

        // Lorsqu'un membre est ajouté à un groupe, l'ajouter aussi à la conversation du groupe
        static::created(function ($groupMember) {
            $group = $groupMember->group;
            if ($group && $group->conversation) {
                // S'assurer que l'utilisateur n'est pas déjà un participant
                if (!$group->conversation->participants()->where('user_id', $groupMember->user_id)->exists()) {
                    $group->conversation->participants()->attach($groupMember->user_id);
                }
            }
        });

        // Lorsqu'un membre est retiré d'un groupe, le retirer aussi de la conversation du groupe
        static::deleting(function ($groupMember) {
            $group = $groupMember->group;
            if ($group && $group->conversation) {
                $group->conversation->participants()->detach($groupMember->user_id);
            }
        });
    }
}
