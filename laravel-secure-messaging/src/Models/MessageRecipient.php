<?php

namespace Acme\SecureMessaging\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory; // Pour les tests futurs

class MessageRecipient extends Model
{
    // use HasFactory; // Décommenter si vous créez des factories

    protected $table = 'messaging_message_recipients';

    protected $fillable = [
        'message_id',
        'user_id', // Le destinataire du message
        'conversation_id', // Pour faciliter les requêtes
        'read_at',   // Date et heure à laquelle le message a été lu par cet utilisateur
        'delivered_at', // Date et heure à laquelle le message a été délivré (facultatif, si on veut suivre ça)
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    /**
     * Get the message.
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'message_id');
    }

    /**
     * Get the recipient user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('messaging.user_model'), 'user_id');
    }

    /**
     * Get the conversation.
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    /**
     * Mark the message as read for this recipient.
     */
    public function markAsRead(): bool
    {
        if (is_null($this->read_at)) {
            $this->read_at = now();
            return $this->save();
        }
        return false; // Already marked as read
    }

    /**
     * Mark the message as delivered for this recipient.
     */
    public function markAsDelivered(): bool
    {
        if (is_null($this->delivered_at)) {
            $this->delivered_at = now();
            return $this->save();
        }
        return false; // Already marked as delivered
    }
}
