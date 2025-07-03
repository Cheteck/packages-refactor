<?php

use Illuminate\Support\Facades\Broadcast;
use Acme\SecureMessaging\Models\Conversation;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// Canal pour une conversation spécifique
// Seuls les participants à la conversation peuvent écouter.
Broadcast::channel('conversation.{conversationUuid}', function ($user, $conversationUuid) {
    $conversation = Conversation::where('uuid', $conversationUuid)->first();
    if ($conversation && $conversation->participants()->where('user_id', $user->id)->exists()) {
        return ['id' => $user->id, 'name' => $user->name]; // Data returned to presence channel callbacks
    }
    return false;
});

// Canal privé pour un utilisateur spécifique (si nécessaire pour des notifications directes non liées à une conversation)
// Par exemple, notification "Vous avez été ajouté à un groupe"
Broadcast::channel('users.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// Vous pourriez également vouloir un canal de présence pour les conversations
// pour savoir qui est actuellement "en ligne" dans une conversation.
// Broadcast::presenceChannel('presence-conversation.{conversationUuid}', function ($user, $conversationUuid) {
//     $conversation = Conversation::where('uuid', $conversationUuid)->first();
//     if ($conversation && $conversation->participants()->where('user_id', $user->id)->exists()) {
//         return ['id' => $user->id, 'name' => $user->name];
//     }
//     return null;
// });
