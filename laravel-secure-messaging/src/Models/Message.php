<?php

namespace Acme\SecureMessaging\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory; // Pour les tests futurs

class Message extends Model
{
    use SoftDeletes;
    // use HasFactory; // Décommenter si vous créez des factories

    protected $table = 'messaging_messages';

    protected $fillable = [
        'uuid',
        'conversation_id',
        'sender_id',
        'content', // Contenu chiffré du message
        'type', // 'text', 'image', 'file', 'ephemeral_text'
        'attachment_path', // Pour les fichiers/images
        'attachment_mime_type',
        'attachment_original_name',
        'expires_at', // Pour les messages éphémères
        'metadata', // JSON pour des infos supplémentaires (ex: preview_data pour liens)
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the conversation that owns the message.
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    /**
     * Get the sender of the message.
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(config('messaging.user_model'), 'sender_id');
    }

    /**
     * Get the recipients and their read status for this message.
     */
    public function recipients(): HasMany
    {
        return $this->hasMany(MessageRecipient::class, 'message_id');
    }

    /**
     * Scope a query to only include non-expired messages.
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Check if the message is ephemeral.
     */
    public function isEphemeral(): bool
    {
        return !is_null($this->expires_at);
    }

    /**
     * Check if the message has expired.
     */
    public function hasExpired(): bool
    {
        return $this->isEphemeral() && $this->expires_at->isPast();
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

        static::created(function ($message) {
            // Mettre à jour `last_message_at` sur la conversation parente
            if ($message->conversation) {
                $message->conversation->touch('last_message_at');
            }
        });
    }
}
