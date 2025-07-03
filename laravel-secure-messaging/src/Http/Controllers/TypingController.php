<?php

namespace Acme\SecureMessaging\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Acme\SecureMessaging\Models\Conversation;
use Acme\SecureMessaging\Events\TypingIndicator;
use Illuminate\Support\Facades\Auth;

class TypingController extends Controller
{
    /**
     * Store a typing indicator event.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string $conversationUuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, string $conversationUuid)
    {
        $request->validate([
            'is_typing' => 'required|boolean',
        ]);

        $user = $request->user();
        $conversation = Conversation::where('uuid', $conversationUuid)->firstOrFail();

        // Ensure user is part of the conversation
        if (!$conversation->participants()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'Forbidden: You are not a participant of this conversation.'], 403);
        }

        // Broadcast the typing indicator event
        // Use toOthers() to prevent the user who is typing from receiving the event.
        broadcast(new TypingIndicator($user, $conversation, $request->is_typing))->toOthers();

        return response()->json(['message' => 'Typing indicator sent.']);
    }
}
