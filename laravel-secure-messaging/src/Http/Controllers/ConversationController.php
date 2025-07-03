<?php

namespace Acme\SecureMessaging\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Acme\SecureMessaging\Models\Conversation;

class ConversationController extends Controller
{
    /**
     * Display a listing of the user's conversations.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $page = $request->input('page', 1);
        $cacheKey = "user_{$user->id}_conversations_page_{$page}";
        $cacheTags = ["user_{$user->id}_conversations"];
        $cacheTtl = config('messaging.caching.ttl_seconds.conversations', 3600); // Use general conversations TTL

        $conversations = Cache::tags($cacheTags)->remember($cacheKey, $cacheTtl, function () use ($user) {
            return Conversation::whereHas('participants', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with([
                'participants' => function ($query) use ($user) {
                    $query->where('users.id', '!=', $user->id)
                          ->select(config('messaging.user_model_public_columns', ['id', 'name']));
                },
                // TODO: Optimiser le chargement du dernier message et de son contenu chiffré pour l'utilisateur
                // 'latestMessageWithRecipient' => function ($query) use ($user) {
                //    $query->where('user_id', $user->id); // Assuming a custom relationship
                // }
            ])
            ->orderBy('last_message_at', 'desc')
            ->paginate(config('messaging.pagination_limit_conversations', 15));
        });

        // Le chargement du dernier message (surtout sa version chiffrée spécifique à l'utilisateur)
        // pour chaque conversation dans la liste peut être lourd.
        // Il est souvent préférable que le client fasse une requête pour les messages de la conversation
        // une fois la conversation sélectionnée, ou que l'UI affiche des placeholders.
        // Si on veut le dernier message, il faut une relation optimisée ou une subquery.

        return response()->json($conversations);
    }

    /**
     * Display the specified conversation (details and participants).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $conversationUuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, string $conversationUuid)
    {
        $user = $request->user();
        // Cache details of a specific conversation, less critical than the list but can be useful
        $cacheKey = "conversation_details_{$conversationUuid}_user_{$user->id}";
        $cacheTags = ["conversation_{$conversationUuid}_details", "user_{$user->id}_conversations"];
        $cacheTtl = config('messaging.caching.ttl_seconds.conversations', 3600);


        $conversation = Cache::tags($cacheTags)->remember($cacheKey, $cacheTtl, function () use ($conversationUuid, $user) {
            return Conversation::where('uuid', $conversationUuid)
                ->with(['participants:'.implode(',', config('messaging.user_model_public_columns', ['id', 'name']))])
                ->whereHas('participants', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->firstOrFail();
        });

        return response()->json($conversation);
    }
}
