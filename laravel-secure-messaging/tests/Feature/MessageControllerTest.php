<?php

namespace Acme\SecureMessaging\Tests\Feature;

use Acme\SecureMessaging\Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Acme\SecureMessaging\Models\Conversation;
use Acme\SecureMessaging\Models\Message;
use Acme\SecureMessaging\Models\MessageRecipient;
use Illuminate\Support\Facades\Event;
use Acme\SecureMessaging\Events\NewMessageSent;
use Acme\SecureMessaging\Events\MessageRead;

class MessageControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user1;
    protected User $user2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user1 = User::factory()->create(['name' => 'User One', 'public_key' => 'user1_pk']);
        $this->user2 = User::factory()->create(['name' => 'User Two', 'public_key' => 'user2_pk']);
        Event::fake([NewMessageSent::class, MessageRead::class]); // Fake events for these tests
    }

    private function createIndividualConversation(User $userA, User $userB): Conversation
    {
        $conversation = Conversation::create(['type' => 'individual', 'last_message_at' => now()]);
        $conversation->participants()->attach([$userA->id, $userB->id]);
        return $conversation;
    }

    public function test_user_can_send_message_to_existing_individual_conversation()
    {
        Sanctum::actingAs($this->user1);
        $conversation = $this->createIndividualConversation($this->user1, $this->user2);

        $encryptedContents = [
            $this->user1->id => "encrypted_for_user1_by_user1",
            $this->user2->id => "encrypted_for_user2_by_user1",
        ];

        $response = $this->postJson(route('messaging.messages.store'), [
            'conversation_id' => $conversation->uuid,
            'encrypted_contents' => $encryptedContents,
            'type' => 'text',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.sender_id', $this->user1->id);

        $messageId = $response->json('data.id');
        $this->assertDatabaseHas('messaging_messages', ['id' => $messageId, 'sender_id' => $this->user1->id]);
        $this->assertDatabaseHas('messaging_message_recipients', [
            'message_id' => $messageId,
            'user_id' => $this->user1->id,
            'content' => $encryptedContents[$this->user1->id]
        ]);
        $this->assertDatabaseHas('messaging_message_recipients', [
            'message_id' => $messageId,
            'user_id' => $this->user2->id,
            'content' => $encryptedContents[$this->user2->id]
        ]);

        Event::assertDispatched(NewMessageSent::class, function ($event) use ($messageId) {
            return $event->message->id === $messageId;
        });
    }

    public function test_user_can_send_message_to_create_new_individual_conversation()
    {
        Sanctum::actingAs($this->user1);

        $encryptedContents = [
            $this->user1->id => "encrypted_for_user1_by_user1_new_conv",
            $this->user2->id => "encrypted_for_user2_by_user1_new_conv",
        ];

        $response = $this->postJson(route('messaging.messages.store'), [
            'recipient_id' => $this->user2->id,
            'encrypted_contents' => $encryptedContents,
        ]);

        $response->assertStatus(201);
        $messageId = $response->json('data.id');
        $conversationId = $response->json('data.conversation_id');

        $this->assertDatabaseHas('messaging_conversations', ['id' => $conversationId, 'type' => 'individual']);
        $this->assertDatabaseHas('messaging_messages', ['id' => $messageId, 'conversation_id' => $conversationId]);
        $this->assertDatabaseCount('messaging_conversation_user', 2); // 2 participants

        Event::assertDispatched(NewMessageSent::class);
    }


    public function test_user_can_retrieve_messages_for_a_conversation()
    {
        Sanctum::actingAs($this->user1);
        $conversation = $this->createIndividualConversation($this->user1, $this->user2);

        // User1 sends a message
        MessageRecipient::factory()->for($this->user1, 'user')->for($conversation)->for(
            Message::factory()->for($this->user1, 'sender')->for($conversation)->create(['content' => 'msg1_u1_content_encrypted_for_sender'])
        )->create(['content' => 'msg1_u1_content_encrypted_for_user1', 'read_at' => now()]);

        // User2 sends a message
         MessageRecipient::factory()->for($this->user1, 'user')->for($conversation)->for(
            Message::factory()->for($this->user2, 'sender')->for($conversation)->create(['content' => 'msg2_u2_content_encrypted_for_sender'])
        )->create(['content' => 'msg2_u2_content_encrypted_for_user1']);


        $response = $this->getJson(route('messaging.conversations.messages.index', ['conversationUuid' => $conversation->uuid]));

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data') // Expecting 2 messages
            ->assertJsonPath('data.0.user_specific_content', 'msg2_u2_content_encrypted_for_user1') // Latest first
            ->assertJsonPath('data.1.user_specific_content', 'msg1_u1_content_encrypted_for_user1');
    }

    public function test_user_can_mark_message_as_read()
    {
        Sanctum::actingAs($this->user1);
        $conversation = $this->createIndividualConversation($this->user1, $this->user2);

        // Message sent by user2 to user1, user1 has not read it yet
        $messageFromUser2 = Message::factory()->for($this->user2, 'sender')->for($conversation)->create();
        $recipientEntryForUser1 = MessageRecipient::factory()
            ->for($messageFromUser2, 'message')
            ->for($this->user1, 'user')
            ->for($conversation)
            ->create(['read_at' => null, 'content' => 'encrypted_for_user1']);

        $this->assertNull($recipientEntryForUser1->read_at);

        $response = $this->putJson(route('messaging.messages.read', ['messageId' => $messageFromUser2->id]));
        $response->assertStatus(200);

        $recipientEntryForUser1->refresh();
        $this->assertNotNull($recipientEntryForUser1->read_at);
        Event::assertDispatched(MessageRead::class);
    }

    public function test_user_can_delete_their_copy_of_a_message()
    {
        Sanctum::actingAs($this->user1);
        $conversation = $this->createIndividualConversation($this->user1, $this->user2);
        $message = Message::factory()->for($this->user1, 'sender')->for($conversation)->create();
        // User1's copy
        $recipientEntryUser1 = MessageRecipient::factory()->for($message)->for($this->user1, 'user')->for($conversation)->create();
        // User2's copy
        MessageRecipient::factory()->for($message)->for($this->user2, 'user')->for($conversation)->create();

        $this->assertDatabaseHas('messaging_message_recipients', ['id' => $recipientEntryUser1->id]);

        $response = $this->deleteJson(route('messaging.messages.destroy', ['messageId' => $message->id]));
        $response->assertStatus(200);

        $this->assertDatabaseMissing('messaging_message_recipients', ['id' => $recipientEntryUser1->id]);
        // Main message and User2's copy should still exist
        $this->assertDatabaseHas('messaging_messages', ['id' => $message->id]);
        $this->assertDatabaseHas('messaging_message_recipients', ['message_id' => $message->id, 'user_id' => $this->user2->id]);
    }

    public function test_sending_message_requires_encrypted_content_for_all_participants()
    {
        Sanctum::actingAs($this->user1);
        $conversation = $this->createIndividualConversation($this->user1, $this->user2);

        // Missing content for user2
        $encryptedContents = [
            $this->user1->id => "encrypted_for_user1_by_user1",
        ];

        $response = $this->postJson(route('messaging.messages.store'), [
            'conversation_id' => $conversation->uuid,
            'encrypted_contents' => $encryptedContents,
        ]);

        $response->assertStatus(422)
                 ->assertJsonFragment(['message' => "Encrypted content for participant ID {$this->user2->id} is missing."]);
    }
}


// Factories are now loaded from packages/laravel-secure-messaging/database/factories
// via TestCase.php. No need for inline definitions here.
