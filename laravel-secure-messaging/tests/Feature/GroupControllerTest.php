<?php

namespace Acme\SecureMessaging\Tests\Feature;

use Acme\SecureMessaging\Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Acme\SecureMessaging\Models\Group;
use Acme\SecureMessaging\Models\GroupMember;
use Illuminate\Support\Facades\Event;
use Acme\SecureMessaging\Events\UserJoinedGroup;
use Acme\SecureMessaging\Events\UserLeftGroup;


class GroupControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user1;
    protected User $user2;
    protected User $user3;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user1 = User::factory()->create(['name' => 'Group Admin User']);
        $this->user2 = User::factory()->create(['name' => 'Group Member User']);
        $this->user3 = User::factory()->create(['name' => 'Non Member User']);
        Event::fake([UserJoinedGroup::class, UserLeftGroup::class]);
    }

    public function test_user_can_create_a_group()
    {
        Sanctum::actingAs($this->user1);

        $groupName = 'Test Group Alpha';
        $response = $this->postJson(route('messaging.groups.store'), [
            'name' => $groupName,
            'description' => 'A test group description.',
            'members' => [$this->user2->id]
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', $groupName)
            ->assertJsonPath('data.created_by_user_id', $this->user1->id);

        $groupUuid = $response->json('data.uuid');
        $this->assertDatabaseHas('messaging_groups', ['uuid' => $groupUuid, 'name' => $groupName]);
        $this->assertDatabaseHas('messaging_group_members', [
            // 'group_id' => $response->json('data.id'), // Need group_id from DB, not response for this check
            'user_id' => $this->user1->id,
            'role' => GroupMember::ROLE_ADMIN
        ]);
        $this->assertDatabaseHas('messaging_group_members', [
            'user_id' => $this->user2->id,
            'role' => GroupMember::ROLE_MEMBER
        ]);

        // Check if conversation was created for the group
        $group = Group::where('uuid', $groupUuid)->first();
        $this->assertNotNull($group->conversation);
        $this->assertEquals('group', $group->conversation->type);
        $this->assertTrue($group->conversation->participants->contains($this->user1));
        $this->assertTrue($group->conversation->participants->contains($this->user2));

        Event::assertDispatched(UserJoinedGroup::class, function($event) {
            return $event->joinedUserData['id'] === $this->user2->id;
        });
         // Creator joining is implicit, UserJoinedGroup might not be fired for creator in current setup
         // Event::assertDispatched(UserJoinedGroup::class, function($event) {
         // return $event->joinedUserData['id'] === $this->user1->id;
         // });
    }

    public function test_admin_can_add_member_to_group()
    {
        Sanctum::actingAs($this->user1);
        $group = Group::factory()->create(['created_by_user_id' => $this->user1->id]);
        $group->groupMembers()->create(['user_id' => $this->user1->id, 'role' => GroupMember::ROLE_ADMIN]);

        $response = $this->postJson(route('messaging.groups.members.add', [
            'groupUuid' => $group->uuid,
            'userIdToAdd' => $this->user2->id
        ]));

        $response->assertStatus(200);
        $this->assertDatabaseHas('messaging_group_members', [
            'group_id' => $group->id,
            'user_id' => $this->user2->id,
            'role' => GroupMember::ROLE_MEMBER
        ]);
        Event::assertDispatched(UserJoinedGroup::class);
    }

    public function test_admin_can_remove_member_from_group()
    {
        Sanctum::actingAs($this->user1);
        $group = Group::factory()->create(['created_by_user_id' => $this->user1->id]);
        $group->groupMembers()->create(['user_id' => $this->user1->id, 'role' => GroupMember::ROLE_ADMIN]);
        $group->groupMembers()->create(['user_id' => $this->user2->id, 'role' => GroupMember::ROLE_MEMBER]);

        $this->assertDatabaseHas('messaging_group_members', ['group_id' => $group->id, 'user_id' => $this->user2->id]);

        $response = $this->deleteJson(route('messaging.groups.members.remove', [
            'groupUuid' => $group->uuid,
            'userIdToRemove' => $this->user2->id
        ]));

        $response->assertStatus(200);
        $this->assertDatabaseMissing('messaging_group_members', ['group_id' => $group->id, 'user_id' => $this->user2->id]);
        Event::assertDispatched(UserLeftGroup::class);
    }

    public function test_member_can_view_group_details()
    {
        Sanctum::actingAs($this->user2);
        $group = Group::factory()->create(['created_by_user_id' => $this->user1->id]);
        $group->groupMembers()->create(['user_id' => $this->user1->id, 'role' => GroupMember::ROLE_ADMIN]);
        $group->groupMembers()->create(['user_id' => $this->user2->id, 'role' => GroupMember::ROLE_MEMBER]);

        $response = $this->getJson(route('messaging.groups.show', ['groupUuid' => $group->uuid]));
        $response->assertStatus(200)
                 ->assertJsonPath('data.uuid', $group->uuid);
    }

    public function test_non_member_cannot_view_group_details()
    {
        Sanctum::actingAs($this->user3); // user3 is not a member
        $group = Group::factory()->create(['created_by_user_id' => $this->user1->id]);
        $group->groupMembers()->create(['user_id' => $this->user1->id, 'role' => GroupMember::ROLE_ADMIN]);
        // user2 might be a member, but user3 is not

        $response = $this->getJson(route('messaging.groups.show', ['groupUuid' => $group->uuid]));
        $response->assertStatus(403);
    }

     public function test_admin_can_update_group_role()
    {
        Sanctum::actingAs($this->user1);
        $group = Group::factory()->create(['created_by_user_id' => $this->user1->id]);
        $group->groupMembers()->create(['user_id' => $this->user1->id, 'role' => GroupMember::ROLE_ADMIN]);
        $group->groupMembers()->create(['user_id' => $this->user2->id, 'role' => GroupMember::ROLE_MEMBER]);

        $response = $this->putJson(route('messaging.groups.members.updateRole', [
            'groupUuid' => $group->uuid,
            'memberIdToUpdate' => $this->user2->id
        ]), ['role' => GroupMember::ROLE_ADMIN]);

        $response->assertStatus(200)
                 ->assertJsonPath('data.role', GroupMember::ROLE_ADMIN);
        $this->assertDatabaseHas('messaging_group_members', [
            'group_id' => $group->id,
            'user_id' => $this->user2->id,
            'role' => GroupMember::ROLE_ADMIN
        ]);
    }

}

// GroupFactory is now loaded from packages/laravel-secure-messaging/database/factories
// via TestCase.php. No need for inline definitions here.
