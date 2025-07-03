<?php

namespace Acme\SecureMessaging\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Acme\SecureMessaging\Models\Conversation;
use Acme\SecureMessaging\Models\Message;
use Acme\SecureMessaging\Models\MessageRecipient;
use Acme\SecureMessaging\Services\EncryptionService;
use App\Models\User; // Will be replaced by config('messaging.user_model')
use Acme\SecureMessaging\Events\NewMessageSent;
use Acme\SecureMessaging\Events\MessageRead;

class MessageController extends Controller
{
    protected $userModel;
    protected $encryptionService;

    public function __construct(EncryptionService $encryptionService)
    {
        $this->userModel = config('messaging.user_model');
        $this->encryptionService = $encryptionService; // Gardé pour d'éventuelles fonctions utilitaires ou futures
    }

    /**
     * Store a newly created message in storage.
     *
     * The request should contain 'encrypted_contents' which is an object/map
     * where keys are user IDs and values are the message content encrypted for that specific user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'conversation_id' => 'sometimes|required_without:recipient_id|exists:messaging_conversations,uuid',
            'recipient_id' => 'sometimes|required_without:conversation_id|exists:'.(new $this->userModel)->getTable().',id',
            'encrypted_contents' => 'required|array',
            'encrypted_contents.*' => 'required|string', // Each value is a base64 encoded ciphertext
            'type' => 'sometimes|string|in:text,image,file,ephemeral_text',
            'attachment_path' => 'nullable|string|max:1024', // Path or ID returned by attachment upload endpoint
            'attachment_original_name' => 'nullable|string|max:255',
            'attachment_mime_type' => 'nullable|string|max:100',
            'ttl_seconds' => 'nullable|integer|min:60', // Time To Live in seconds for ephemeral messages
        ]);

        $sender = $request->user();
        $conversation = null;
        $participants = collect();

        DB::beginTransaction();
        try {
            if ($request->has('conversation_id')) {
                $conversation = Conversation::where('uuid', $request->conversation_id)->firstOrFail();
                // Ensure sender is part of the conversation
                if (!$conversation->participants()->where('user_id', $sender->id)->exists()) {
                    return response()->json(['message' => 'Sender is not part of this conversation.'], 403);
                }
                $participants = $conversation->participants()->get();
            } else {
                // New individual conversation
                $recipient = call_user_func([$this->userModel, 'findOrFail'], $request->recipient_id);
                if ($recipient->id === $sender->id) {
                    return response()->json(['message' => 'Cannot create a conversation with yourself.'], 422);
                }

                // Check if a conversation already exists between these two users
                $conversation = Conversation::where('type', 'individual')
                    ->whereHas('participants', function ($q) use ($sender) {
                        $q->where('user_id', $sender->id);
                    })
                    ->whereHas('participants', function ($q) use ($recipient) {
                        $q->where('user_id', $recipient->id);
                    })
                    ->first();

                if (!$conversation) {
                    $conversation = Conversation::create(['type' => 'individual', 'last_message_at' => now()]);
                    $conversation->participants()->attach([$sender->id, $recipient->id]);
                }
                $participants = collect([$sender, $recipient]);
            }

            // The 'encrypted_contents' array should contain a key for the sender as well.
            // The main message content could be the one encrypted for the sender.
            $mainEncryptedContent = $request->encrypted_contents[$sender->id] ?? null;
            if (!$mainEncryptedContent) {
                DB::rollBack();
                return response()->json(['message' => 'Encrypted content for the sender is missing.'], 422);
            }

            $message = $conversation->messages()->create([
                'sender_id' => $sender->id,
                'content' => $mainEncryptedContent, // Content encrypted for the sender
                'type' => $request->input('type', 'text'),
                'attachment_path' => $request->input('attachment_path'),
                'attachment_original_name' => $request->input('attachment_original_name'),
                'attachment_mime_type' => $request->input('attachment_mime_type'),
                'expires_at' => null, // Will be set below if applicable
            ]);

            if ($message->type === 'ephemeral_text' && $request->has('ttl_seconds') && config('messaging.features.ephemeral_messages.enabled')) {
                $message->expires_at = now()->addSeconds($request->input('ttl_seconds'));
                $message->save();
            } elseif ($message->type === 'ephemeral_text' && !config('messaging.features.ephemeral_messages.enabled')) {
                 // If feature is disabled but client tries to send, revert type or reject?
                 // For now, let's silently convert to 'text' or let validation handle it if 'ephemeral_text' is not allowed.
                 // The current validation allows 'ephemeral_text' type.
                 // Perhaps it's better to reject if feature disabled and type is ephemeral.
                 // Or, more gracefully, convert to standard text.
                 // Let's assume validation/client handles this. If it reaches here and feature is off, it's a misconfiguration.
            }


            // Create MessageRecipient entries for each participant
            foreach ($participants as $participant) {
                $participantEncryptedContent = $request->encrypted_contents[$participant->id] ?? null;
                if (!$participantEncryptedContent) {
                    // This means the client didn't provide an encrypted version for this participant.
                    // This is a critical failure for E2EE.
                    DB::rollBack();
                    return response()->json(['message' => "Encrypted content for participant ID {$participant->id} is missing."], 422);
                }

                MessageRecipient::create([
                    'message_id' => $message->id,
                    'user_id' => $participant->id,
                    'conversation_id' => $conversation->id,
                    'content' => $participantEncryptedContent, // Store the specific encrypted content
                    'delivered_at' => ($participant->id === $sender->id) ? now() : null, // Sender's copy is delivered
                    'read_at' => ($participant->id === $sender->id) ? now() : null, // Sender's copy is read by sender
                ]);
            }

            $conversation->touch('last_message_at'); // Eloquent model event should also do this

            DB::commit();

            // Broadcast new message event
            // Ensure message sender relationship is loaded if accessed in the event
            $message->load('sender');
            event(new NewMessageSent($message, $conversation));

            // Invalidate conversation list cache for all participants
            foreach ($participants as $participant) {
                Cache::tags(["user_{$participant->id}_conversations"])->flush();
            }
            // Invalidate specific conversation details cache if any
            Cache::tags(["conversation_{$conversation->uuid}_details"])->flush();


            return response()->json([
                'message' => 'Message sent successfully.',
                'data' => $message->load('sender', 'recipients') // Load relations for response
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Failed to send message: " . $e->getMessage(), ['exception' => $e]);
            return response()->json(['message' => 'Failed to send message. ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string $conversationUuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request, string $conversationUuid)
    {
        $user = $request->user();
        $conversation = Conversation::where('uuid', $conversationUuid)
            ->whereHas('participants', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->firstOrFail();

        // Fetch messages for this user in this conversation
        // We need to get the content from MessageRecipient table for the current user
        $messages = Message::where('conversation_id', $conversation->id)
            ->with(['sender:id,name', 'recipients' => function ($query) use ($user) {
                // Only load recipient info relevant to the current user (e.g., their own read status)
                // Or, more simply, the client will use the content from the main query below.
                $query->where('user_id', $user->id);
            }])
            ->join('messaging_message_recipients', function ($join) use ($user) {
                $join->on('messaging_messages.id', '=', 'messaging_message_recipients.message_id')
                     ->where('messaging_message_recipients.user_id', '=', $user->id);
            })
            ->select('messaging_messages.*', 'messaging_message_recipients.content as user_specific_content', 'messaging_message_recipients.read_at')
            ->orderBy('messaging_messages.created_at', 'desc')
            ->paginate(config('messaging.pagination_limit', 25));


        // The 'user_specific_content' is what the client needs to decrypt.
        // The original 'content' on the message itself is likely encrypted for the sender.
        return response()->json($messages);
    }


    /**
     * Mark a message as read.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $messageId
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsRead(Request $request, $messageId)
    {
        $user = $request->user();
        $messageRecipient = MessageRecipient::where('message_id', $messageId)
            ->where('user_id', $user->id)
            ->first();

        if (!$messageRecipient) {
            return response()->json(['message' => 'Message or recipient entry not found.'], 404);
        }

        if ($messageRecipient->read_at) {
            return response()->json(['message' => 'Message already marked as read.'], 200);
        }

        $messageRecipient->markAsRead();

        // Broadcast message read event
        // Ensure necessary relations are loaded for the event
        $messageRecipient->load('message.sender', 'conversation', 'user');
        event(new MessageRead($messageRecipient));

        return response()->json(['message' => 'Message marked as read.']);
    }

    /**
     * Remove the specified message from storage (soft delete).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $messageId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $messageId)
    {
        $user = $request->user();
        $message = Message::findOrFail($messageId);

        // Policy: Only sender can delete a message, or perhaps group admins.
        // For now, only sender.
        if ($message->sender_id !== $user->id) {
            return response()->json(['message' => 'You are not authorized to delete this message.'], 403);
        }

        // Instead of soft deleting the main message (which affects all recipients),
        // we might want to "delete" it only for the sender.
        // Or, if it's a true E2EE system, the sender tells other clients to delete their copies.
        // For simplicity here, we will soft delete the main message.
        // This means other recipients might still see it unless client-side logic hides it based on a flag.
        // A better approach for "delete for me" is to delete the MessageRecipient entry.
        // A "delete for everyone" (if sender) would soft delete Message and all MessageRecipient entries.

        // Let's implement "delete for me" by default by deleting the MessageRecipient entry.
        $recipientEntry = MessageRecipient::where('message_id', $message->id)
                                          ->where('user_id', $user->id)
                                          ->first();
        if ($recipientEntry) {
            $recipientEntry->delete(); // This is a hard delete of the recipient's view
                                       // Or add softdeletes to MessageRecipient too if needed.
        } else {
            // This case should not happen if the user is part of the conversation.
            return response()->json(['message' => 'Message copy not found for this user.'], 404);
        }

        // If you want "delete for everyone" (only if sender):
        // if ($message->sender_id === $user->id) {
        //     $message->delete(); // Soft deletes the main message
        //     MessageRecipient::where('message_id', $message->id)->delete(); // Or soft delete these too
        // }

        // TODO: Broadcast message deleted event

        return response()->json(['message' => 'Message deleted successfully for you.']);
    }
}
