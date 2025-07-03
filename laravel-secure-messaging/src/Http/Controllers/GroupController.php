<?php

namespace Acme\SecureMessaging\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Acme\SecureMessaging\Models\Group;
use Acme\SecureMessaging\Models\GroupMember;
use Acme\SecureMessaging\Models\Conversation;
use Acme\SecureMessaging\Events\UserJoinedGroup;
use Acme\SecureMessaging\Events\UserLeftGroup;

class GroupController extends Controller
{
    protected $userModel;

    public function __construct()
    {
        $this->userModel = config('messaging.user_model');
    }

    /**
     * Store a newly created group in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'avatar_url' => 'nullable|string|url|max:2048',
            'members' => 'sometimes|array', // Array of user IDs to add initially
            'members.*' => 'integer|exists:'.(new $this->userModel)->getTable().',id',
        ]);

        $user = $request->user();

        DB::beginTransaction();
        try {
            $group = Group::create([
                'name' => $validatedData['name'],
                'description' => $validatedData['description'],
                'avatar_url' => $validatedData['avatar_url'],
                'created_by_user_id' => $user->id,
            ]);

            // The creator is automatically an admin
            $group->groupMembers()->create([
                'user_id' => $user->id,
                'role' => GroupMember::ROLE_ADMIN,
                'joined_at' => now(),
            ]);
            // The Group model's boot method should automatically create a conversation
            // and add the creator to its participants list.

            if (!empty($validatedData['members'])) {
                foreach ($validatedData['members'] as $memberId) {
                    if ($memberId == $user->id) continue; // Skip if creator is in members list

                    $group->groupMembers()->firstOrCreate(
                        ['user_id' => $memberId],
                        ['role' => GroupMember::ROLE_MEMBER, 'joined_at' => now()]
                    );
                    // The GroupMember model's boot method should add them to the conversation participants.
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Group created successfully.',
                'data' => $group->load('members', 'conversation')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error("Group creation failed: " . $e->getMessage());
            return response()->json(['message' => 'Group creation failed. ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified group.
     *
     * @param  string  $groupUuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, string $groupUuid)
    {
        $user = $request->user();
        $cacheKey = "group_details_{$groupUuid}";
        $cacheTags = ["group_{$groupUuid}_details"];
        $cacheTtl = config('messaging.caching.ttl_seconds.group_details', config('messaging.caching.ttl_seconds.conversations', 3600));

        $group = Cache::tags($cacheTags)->remember($cacheKey, $cacheTtl, function () use ($groupUuid) {
            return Group::where('uuid', $groupUuid)
                ->with(['members:'.implode(',', config('messaging.user_model_public_columns', ['id', 'name'])), 'conversation'])
                ->firstOrFail();
        });

        // Ensure the requesting user is a member of the group to view details
        if (!$group->groupMembers()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'You are not a member of this group.'], 403);
        }

        return response()->json($group);
    }

    /**
     * Update the specified group in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $groupUuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, string $groupUuid)
    {
        $validatedData = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'avatar_url' => 'nullable|string|url|max:2048',
        ]);

        $user = $request->user();
        $group = Group::where('uuid', $groupUuid)->firstOrFail(); // Fetch fresh for update logic

        // Authorization: Only admin can update
        $member = $group->groupMembers()->where('user_id', $user->id)->first();
        if (!$member || $member->role !== GroupMember::ROLE_ADMIN) {
            return response()->json(['message' => 'You are not authorized to update this group.'], 403);
        }

        $group->update($validatedData);

        Cache::tags(["group_{$groupUuid}_details"])->flush();
        Cache::tags(["group_{$groupUuid}_members_pages"])->flush();

        return response()->json([
            'message' => 'Group updated successfully.',
            'data' => $group
        ]);
    }

    /**
     * Remove the specified group from storage.
     *
     * @param  string  $groupUuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, string $groupUuid)
    {
        $user = $request->user();
        $group = Group::where('uuid', $groupUuid)->firstOrFail(); // Fetch fresh for delete logic

        // Authorization: Only admin can delete
        $member = $group->groupMembers()->where('user_id', $user->id)->first();
        if (!$member || $member->role !== GroupMember::ROLE_ADMIN) {
            // Or perhaps only the original creator `created_by_user_id`
            return response()->json(['message' => 'You are not authorized to delete this group.'], 403);
        }

        DB::beginTransaction();
        try {
            // The conversation associated with the group will be deleted by cascade (if foreign key is set up correctly)
            // or should be manually deleted if not. The `Conversation` model does not have soft deletes by default.
            // If Conversation has related messages, those might also need cleanup or will be orphaned if not cascaded.
            // For now, assume cascade or manual cleanup of conversation and its messages if needed.
            if ($group->conversation) {
                 // Manually delete related message recipients first if not handled by cascade on messages
                MessageRecipient::whereIn('message_id', function($query) use ($group) {
                    $query->select('id')->from('messaging_messages')->where('conversation_id', $group->conversation->id);
                })->delete();
                // Manually delete messages if not handled by cascade on conversation
                $group->conversation->messages()->delete();
                $group->conversation->participants()->detach();
                $group->conversation->delete();
            }

            $group->groupMembers()->delete(); // Delete all member associations
            $group->delete(); // Soft deletes the group

            $group->delete(); // Soft deletes the group

            DB::commit();

            Cache::tags(["group_{$groupUuid}_details"])->flush();
            Cache::tags(["group_{$groupUuid}_members_pages"])->flush();

            \Illuminate\Support\Facades\Log::info("User {$user->id} deleted group {$group->uuid} (Name: {$group->name}).");


            return response()->json(['message' => 'Group deleted successfully.']);

        } catch (\Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error("Group deletion failed: " . $e->getMessage());
            return response()->json(['message' => 'Group deletion failed.'], 500);
        }
    }

    /**
     * Add a member to the group.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $groupUuid
     * @param int $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function addMember(Request $request, string $groupUuid, int $userIdToAdd)
    {
        $user = $request->user();
        $group = Group::where('uuid', $groupUuid)->firstOrFail();

        // Authorization: Only admin can add members
        $adminMember = $group->groupMembers()->where('user_id', $user->id)->first();
        if (!$adminMember || $adminMember->role !== GroupMember::ROLE_ADMIN) {
            return response()->json(['message' => 'You are not authorized to add members to this group.'], 403);
        }

        $userModelInstance = new $this->userModel();
        $userExists = call_user_func([$this->userModel, 'where'], 'id', $userIdToAdd)->exists();
        if (!$userExists) {
             return response()->json(['message' => 'User to add not found.'], 404);
        }

        if ($group->groupMembers()->where('user_id', $userIdToAdd)->exists()) {
            return response()->json(['message' => 'User is already a member of this group.'], 409); // 409 Conflict
        }

        $group->groupMembers()->create([
            'user_id' => $userIdToAdd,
            'role' => GroupMember::ROLE_MEMBER, // Default role
        ]);
        // The GroupMember model's boot method handles adding to conversation participants

        Cache::tags(["group_{$groupUuid}_details"])->flush();
        Cache::tags(["group_{$groupUuid}_members_pages"])->flush();

        \Illuminate\Support\Facades\Log::info("User {$user->id} added user {$userIdToAdd} to group {$group->uuid}.");

        // Dispatch event
        $addedUserInstance = call_user_func([$this->userModel, 'find'], $userIdToAdd);
        if ($addedUserInstance) {
            event(new UserJoinedGroup($addedUserInstance, $group));
            // Invalidate conversation list cache for the user who joined
            Cache::tags(["user_{$userIdToAdd}_conversations"])->flush();
        }

        return response()->json([
            'message' => 'Member added successfully.',
            'data' => $group->load('members:'.implode(',', config('messaging.user_model_public_columns', ['id', 'name'])))
        ]);
    }

    /**
     * Remove a member from the group.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $groupUuid
     * @param int $userIdToRemove
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeMember(Request $request, string $groupUuid, int $userIdToRemove)
    {
        $user = $request->user(); // The user performing the action
        $group = Group::where('uuid', $groupUuid)->firstOrFail();

        $memberToRemove = $group->groupMembers()->where('user_id', $userIdToRemove)->first();
        if (!$memberToRemove) {
            return response()->json(['message' => 'User is not a member of this group.'], 404);
        }

        // Authorization:
        // 1. Admin can remove any member (except another admin if rules are stricter, or the creator).
        // 2. Member can remove themselves.
        $currentUserMember = $group->groupMembers()->where('user_id', $user->id)->first();

        if ($user->id == $userIdToRemove) {
            // User is removing themselves
            if ($memberToRemove->role === GroupMember::ROLE_ADMIN && $group->groupMembers()->where('role', GroupMember::ROLE_ADMIN)->count() === 1) {
                return response()->json(['message' => 'Cannot remove the last admin. Promote another member first or delete the group.'], 403);
            }
        } elseif (!$currentUserMember || $currentUserMember->role !== GroupMember::ROLE_ADMIN) {
            // Current user is not an admin and trying to remove someone else
            return response()->json(['message' => 'You are not authorized to remove this member.'], 403);
        } elseif ($memberToRemove->role === GroupMember::ROLE_ADMIN && $group->created_by_user_id === $userIdToRemove) {
             // Prevent admin from removing the original creator if creator is also an admin (optional rule)
            // For simplicity, current rule: admin can remove another admin, except if it's the creator by a non-creator admin.
            // More simply: an admin cannot remove the group creator if the creator is an admin.
            // Or even simpler: an admin cannot remove another admin of the same level (needs hierarchy or creator check)
            // Let's stick to: Admin can remove anyone except if they are the last admin.
             if ($currentUserMember->role === GroupMember::ROLE_ADMIN && $memberToRemove->role === GroupMember::ROLE_ADMIN && $group->groupMembers()->where('role', GroupMember::ROLE_ADMIN)->count() === 1) {
                 return response()->json(['message' => 'Cannot remove the last admin.'], 403);
             }
        }


        $memberToRemove->delete();
        // The GroupMember model's boot method handles removing from conversation participants

        Cache::tags(["group_{$groupUuid}_details"])->flush();
        Cache::tags(["group_{$groupUuid}_members_pages"])->flush();

        \Illuminate\Support\Facades\Log::info("User {$user->id} (performing action) removed user {$userIdToRemove} from group {$group->uuid}.");

        // Dispatch event
        event(new UserLeftGroup($userIdToRemove, $group));
        // Invalidate conversation list cache for the user who left
        Cache::tags(["user_{$userIdToRemove}_conversations"])->flush();

        return response()->json(['message' => 'Member removed successfully.']);
    }

    /**
     * Update a member's role in the group.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $groupUuid
     * @param int $memberId
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateMemberRole(Request $request, string $groupUuid, int $memberIdToUpdate)
    {
        $validatedData = $request->validate([
            'role' => ['required', Rule::in([GroupMember::ROLE_ADMIN, GroupMember::ROLE_MEMBER])],
        ]);

        $user = $request->user(); // User performing the action
        $group = Group::where('uuid', $groupUuid)->firstOrFail();

        // Authorization: Only admin can change roles
        $adminMember = $group->groupMembers()->where('user_id', $user->id)->first();
        if (!$adminMember || $adminMember->role !== GroupMember::ROLE_ADMIN) {
            return response()->json(['message' => 'You are not authorized to change member roles in this group.'], 403);
        }

        $memberToUpdate = $group->groupMembers()->where('user_id', $memberIdToUpdate)->first();
        if (!$memberToUpdate) {
            return response()->json(['message' => 'User is not a member of this group.'], 404);
        }

        // Prevent removing the last admin if demoting an admin to member
        if ($memberToUpdate->role === GroupMember::ROLE_ADMIN &&
            $validatedData['role'] === GroupMember::ROLE_MEMBER &&
            $group->groupMembers()->where('role', GroupMember::ROLE_ADMIN)->count() === 1) {
            return response()->json(['message' => 'Cannot remove the last admin. Promote another member to admin first.'], 403);
        }

        // Cannot change role of the group creator by another admin (optional rule)
        // if ($group->created_by_user_id === $memberIdToUpdate && $user->id !== $group->created_by_user_id) {
        //    return response()->json(['message' => 'The role of the group creator cannot be changed by another admin.'], 403);
        // }


        $memberToUpdate->update(['role' => $validatedData['role']]);

        Cache::tags(["group_{$groupUuid}_details"])->flush();
        Cache::tags(["group_{$groupUuid}_members_pages"])->flush();

        \Illuminate\Support\Facades\Log::info("User {$user->id} updated role of member {$memberIdToUpdate} to {$validatedData['role']} in group {$group->uuid}.");

        return response()->json([
            'message' => 'Member role updated successfully.',
            'data' => $memberToUpdate
        ]);
    }

    /**
     * List members of a group.
     *
     * @param Request $request
     * @param string $groupUuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function listMembers(Request $request, string $groupUuid)
    {
        $user = $request->user();
        $group = Group::where('uuid', $groupUuid)->firstOrFail();

        // Ensure the requesting user is a member of the group to view members
        if (!$group->groupMembers()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'You are not a member of this group.'], 403);
        }

        $page = $request->input('page', 1);
        $cacheKey = "group_members_list_{$groupUuid}_page_{$page}";
        $cacheTags = ["group_{$groupUuid}_members_pages"];
        $cacheTtl = config('messaging.caching.ttl_seconds.group_members', 3600);

        $members = Cache::tags($cacheTags)->remember($cacheKey, $cacheTtl, function () use ($group, $request) {
            return $group->groupMembers()
                ->with(['user:'.implode(',', config('messaging.user_model_public_columns', ['id', 'name']))])
                ->paginate(config('messaging.pagination_limit', config('messaging.pagination_limit_conversations', 15) ));
        });

        return response()->json($members);
    }
}
