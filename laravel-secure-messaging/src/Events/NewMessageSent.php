<?php

namespace Acme\SecureMessaging\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue; // Add this
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Acme\SecureMessaging\Models\Message;
use Acme\SecureMessaging\Models\Conversation;
use Acme\SecureMessaging\Http\Resources\MessageResource; // Pour formater les données diffusées

class NewMessageSent implements ShouldBroadcast, ShouldQueue // Implement ShouldQueue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Message $message;
    public Conversation $conversation;

    /**
     * Create a new event instance.
     *
     * @param Message $message
     * @param Conversation $conversation
     */
    public function __construct(Message $message, Conversation $conversation)
    {
        $this->message = $message;
        $this->conversation = $conversation;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * We broadcast on the conversation channel.
     * All participants of the conversation should be subscribed to this channel.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        // Le client devra s'abonner à "private-conversation.{uuid}"
        return new PrivateChannel('conversation.'.$this->conversation->uuid);
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'new.message';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        // Nous devons charger le contenu chiffré spécifique à l'utilisateur ici.
        // Cependant, l'événement est diffusé à tous les membres du canal de conversation.
        // Le client qui reçoit l'événement devra ensuite prendre le message.id et
        // s'assurer qu'il a le contenu chiffré pour lui-même (via l'API s'il ne l'a pas déjà).
        // Ou, nous pourrions envoyer un message "générique" et le client fait une requête pour obtenir sa version.

        // Pour l'instant, envoyons les données du message de base.
        // Le client, en recevant cet événement, saura qu'un nouveau message est arrivé
        // et pourra le récupérer via l'API s'il est concerné.
        // Une meilleure approche serait que le client récupère `user_specific_content` via l'API.
        // L'événement de diffusion sert de notification "hey, new message available".

        // Créons une ressource API pour le message pour standardiser le format.
        // Pour l'instant, nous allons simplifier et envoyer les données du message.
        // La ressource MessageResource sera créée plus tard si nécessaire.
        return [
            'message_id' => $this->message->id,
            'uuid' => $this->message->uuid,
            'conversation_id' => $this->conversation->id,
            'conversation_uuid' => $this->conversation->uuid,
            'sender_id' => $this->message->sender_id,
            'sender_name' => $this->message->sender->name, // Assumant que la relation sender est chargée
            'type' => $this->message->type,
            'created_at' => $this->message->created_at->toIso8601String(),
            // NE PAS envoyer 'content' ici car il est chiffré pour l'expéditeur.
            // Le client utilisera l'API pour obtenir le contenu chiffré pour lui.
        ];
    }
}
