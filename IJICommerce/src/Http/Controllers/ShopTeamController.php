<?php

namespace IJIDeals\IJICommerce\Http\Controllers;

use Illuminate\Http\Request; // Keep for index, removeUser
use Illuminate\Routing\Controller;
use IJIDeals\IJICommerce\Models\Shop;
// User model is dynamically resolved, so direct 'use App\Models\User;' might not always be correct.
// The constructor handles setting $this->userModel.
use Illuminate\Support\Facades\Gate; // Keep if used directly, though policies are preferred
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;
// use Illuminate\Validation\Rule; // No longer needed here
use IJIDeals\IJICommerce\Http\Requests\AddShopTeamMemberRequest;
use IJIDeals\IJICommerce\Http\Requests\UpdateShopTeamMemberRequest;

/**
 * Controller for managing Shop team members and their roles.
 */
class ShopTeamController extends Controller
{
    /**
     * The User model class name.
     * @var string
     */
    protected $userModel;

    public function __construct()
    {
        // Directly use the UserManagement package's configured model, with a fallback to App\Models\User
        $this->userModel = config('user-management.model', \App\Models\User::class);
        Log::debug("ShopTeamController: User model initialized.", ['user_model_class' => $this->userModel]);
    }

    /**
     * Display a listing of the shop's team members.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \IJIDeals\IJICommerce\Models\Shop  $shop
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request, Shop $shop)
    {
        $requestingUser = $request->user();
        Log::debug("ShopTeamController@index: Fetching team members.", ['requesting_user_id' => $requestingUser ? $requestingUser->id : null, 'shop_id' => $shop->id]);

        // Policy check: viewTeam or manageTeam
        if ($requestingUser->cannot('manageTeam', $shop) && $requestingUser->cannot('view', $shop)) {
            Log::warning("ShopTeamController@index: Authorization failed.", ['requesting_user_id' => $requestingUser ? $requestingUser->id : null, 'shop_id' => $shop->id, 'action' => 'viewTeam']);
            return response()->json(['message' => 'Unauthorized to view shop team.'], 403);
        }

        $teamForeignKey = config('permission.column_names.team_foreign_key', 'team_id');
        $modelHasRolesTable = config('permission.table_names.model_has_roles', 'model_has_roles');
        $rolesTable = config('permission.table_names.roles', 'roles');
        $userInstance = new $this->userModel;

        $membersWithRoles = $this->userModel::query()
            ->select("{$userInstance->getTable()}.*", "{$rolesTable}.name as role_name")
            ->join($modelHasRolesTable, function ($join) use ($userInstance, $teamForeignKey, $modelHasRolesTable, $shop) {
                $join->on("{$userInstance->getTable()}.id", '=', "{$modelHasRolesTable}.model_id")
                    ->where("{$modelHasRolesTable}.model_type", '=', $userInstance->getMorphClass())
                    ->where($teamForeignKey, $shop->id);
            })
            ->join($rolesTable, "{$modelHasRolesTable}.role_id", '=', "{$rolesTable}.id")
            ->distinct()
            ->orderBy("{$userInstance->getTable()}.name")
            ->paginate(config('ijicommerce.pagination.team_members', 15));

        Log::info("ShopTeamController@index: Successfully fetched team members.", ['requesting_user_id' => $requestingUser ? $requestingUser->id : null, 'shop_id' => $shop->id, 'count' => $membersWithRoles->count(), 'total' => $membersWithRoles->total()]);
        return response()->json($membersWithRoles);
    }

    /**
     * Add a user to the shop's team and assign a role.
     *
     * @param  \IJIDeals\IJICommerce\Http\Requests\AddShopTeamMemberRequest  $request
     * @param  \IJIDeals\IJICommerce\Models\Shop  $shop
     * @return \Illuminate\Http\JsonResponse
     */
    public function addUser(AddShopTeamMemberRequest $request, Shop $shop)
    {
        $requestingUser = $request->user();
        Log::debug("ShopTeamController@addUser: Attempting to add user to team.", ['requesting_user_id' => $requestingUser->id, 'shop_id' => $shop->id, 'data' => $request->all()]);

        // Authorization handled by AddShopTeamMemberRequest->authorize()
        // Validation handled by AddShopTeamMemberRequest->rules()
        $validated = $request->validated();
        Log::debug("ShopTeamController@addUser: Validation passed via FormRequest.", ['validated_data' => $validated]);

        $userToAdd = $this->userModel::where('email', $validated['email'])->firstOrFail();
        // No need for if (!$userToAdd) check as firstOrFail() handles it or Rule::exists in FormRequest.

        $role = Role::findByName($validated['role'], $userToAdd->guard_name);
        if (!$role) { // This check is still useful as role might exist but not for the user's guard
            Log::error("ShopTeamController@addUser: Role not found for user's guard.", ['role_name' => $validated['role'], 'guard_name' => $userToAdd->guard_name, 'shop_id' => $shop->id]);
            return response()->json(['message' => "Role '{$validated['role']}' not found for the user's guard."], 400);
        }

        try {
            $userToAdd->syncRoles([$role->name], $shop);
            Log::info("ShopTeamController@addUser: User added to shop team successfully.", ['requesting_user_id' => $requestingUser->id, 'shop_id' => $shop->id, 'added_user_id' => $userToAdd->id, 'role' => $role->name]);
        } catch (\Exception $e) {
            Log::error("ShopTeamController@addUser: Failed to assign role.", [
                'requesting_user_id' => $requestingUser->id, 'shop_id' => $shop->id, 'user_to_add_id' => $userToAdd->id, 'role_name' => $role->name, 'error' => $e->getMessage()
            ]);
            return response()->json(['message' => 'Failed to add user to team. Please try again.'], 500);
        }

        return response()->json([
            'message' => "User {$userToAdd->name} added to shop {$shop->name} as {$role->name}.",
            'user' => $userToAdd->fresh()->load(['roles' => function ($query) use ($shop) {
                $query->where(config('permission.column_names.team_foreign_key', 'team_id'), $shop->id);
            }])
        ], 200);
    }

    /**
     * Update a user's role within the shop's team.
     *
     * @param  \IJIDeals\IJICommerce\Http\Requests\UpdateShopTeamMemberRequest  $request
     * @param  \IJIDeals\IJICommerce\Models\Shop  $shop
     * @param  int $userId The ID of the user whose role is to be updated.
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateUserRole(UpdateShopTeamMemberRequest $request, Shop $shop, $userId)
    {
        $requestingUser = $request->user();
        Log::debug("ShopTeamController@updateUserRole: Attempting to update user role.", ['requesting_user_id' => $requestingUser->id, 'shop_id' => $shop->id, 'target_user_id' => $userId, 'data' => $request->all()]);

        // Main 'manageTeam' authorization handled by UpdateShopTeamMemberRequest->authorize()
        // Validation handled by UpdateShopTeamMemberRequest->rules()
        $validated = $request->validated();
        Log::debug("ShopTeamController@updateUserRole: Validation passed via FormRequest.", ['validated_data' => $validated]);

        $userToUpdate = $this->userModel::find($userId);
        if (!$userToUpdate) {
            Log::error("ShopTeamController@updateUserRole: Target user not found.", ['target_user_id' => $userId, 'shop_id' => $shop->id]);
            return response()->json(['message' => 'User to update not found.'], 404);
        }


        $ownerRoleName = config('ijicommerce.defaults.owner_role_name', 'Owner');

        // Specific logic for owner changes remains in controller
        if ($userToUpdate->id === $requestingUser->id && $userToUpdate->hasRole($ownerRoleName, $shop) && $validated['role'] !== $ownerRoleName) {
            if (!$requestingUser->hasRole($ownerRoleName, $shop)) {
                Log::warning("ShopTeamController@updateUserRole: Owner attempted to demote self without another Owner action.", ['requesting_user_id' => $requestingUser->id, 'shop_id' => $shop->id, 'target_user_id' => $userId]);
                return response()->json(['message' => 'Owners cannot demote themselves unless another Owner performs the action.'], 403);
            }
        }
        if ($userToUpdate->hasRole($ownerRoleName, $shop) && $userToUpdate->id !== $requestingUser->id && !$requestingUser->hasRole($ownerRoleName, $shop)) {
            Log::warning("ShopTeamController@updateUserRole: Non-owner attempted to change Owner's role.", ['requesting_user_id' => $requestingUser->id, 'shop_id' => $shop->id, 'target_user_id' => $userId]);
            return response()->json(['message' => "Only an Owner can change another Owner's role."], 403);
        }

        $role = Role::findByName($validated['role'], $userToUpdate->guard_name);
        if (!$role) {
            Log::error("ShopTeamController@updateUserRole: Role not found for user's guard.", ['role_name' => $validated['role'], 'guard_name' => $userToUpdate->guard_name, 'shop_id' => $shop->id]);
            return response()->json(['message' => "Role '{$validated['role']}' not found for the user's guard."], 400);
        }

        try {
            $userToUpdate->syncRoles([$role->name], $shop);
            Log::info("ShopTeamController@updateUserRole: User role updated successfully.", ['requesting_user_id' => $requestingUser->id, 'shop_id' => $shop->id, 'target_user_id' => $userToUpdate->id, 'new_role' => $role->name]);
        } catch (\Exception $e) {
            Log::error("ShopTeamController@updateUserRole: Failed to update role.", [
                'requesting_user_id' => $requestingUser->id, 'shop_id' => $shop->id, 'target_user_id' => $userToUpdate->id, 'role_name' => $role->name, 'error' => $e->getMessage()
            ]);
            return response()->json(['message' => 'Failed to update user role. Please try again.'], 500);
        }

        return response()->json([
            'message' => "User {$userToUpdate->name}'s role in shop {$shop->name} updated to {$role->name}.",
            'user' => $userToUpdate->fresh()->load(['roles' => function ($query) use ($shop) {
                $query->where(config('permission.column_names.team_foreign_key', 'team_id'), $shop->id);
            }])
        ]);
    }

    /**
     * Remove a user from the shop's team.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \IJIDeals\IJICommerce\Models\Shop  $shop
     * @param  int $userId The ID of the user to remove.
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeUser(Request $request, Shop $shop, $userId)
    {
        $requestingUser = $request->user();
        Log::debug("ShopTeamController@removeUser: Attempting to remove user from team.", ['requesting_user_id' => $requestingUser->id, 'shop_id' => $shop->id, 'target_user_id' => $userId]);

        if ($requestingUser->cannot('manageTeam', $shop)) {
            Log::warning("ShopTeamController@removeUser: Authorization failed.", ['requesting_user_id' => $requestingUser->id, 'shop_id' => $shop->id, 'action' => 'manageTeam']);
            return response()->json(['message' => 'Unauthorized to manage this shop team.'], 403);
        }

        $userToRemove = $this->userModel::find($userId);
        if (!$userToRemove) {
            Log::error("ShopTeamController@removeUser: Target user not found.", ['target_user_id' => $userId, 'shop_id' => $shop->id]);
            return response()->json(['message' => 'User to remove not found.'], 404);
        }

        $ownerRoleName = config('ijicommerce.defaults.owner_role_name', 'Owner');

        if ($userToRemove->hasRole($ownerRoleName, $shop)) {
            $ownerRole = Role::where('name', $ownerRoleName)->where('guard_name', $userToRemove->guard_name)->first();
            if ($ownerRole) {
                $teamForeignKey = config('permission.column_names.team_foreign_key', 'team_id');
                $modelHasRolesTable = config('permission.table_names.model_has_roles', 'model_has_roles');
                $userInstance = new $this->userModel;

                $otherOwnersCount = $this->userModel::query()
                    ->whereHas('roles', function ($q) use ($ownerRole, $shop, $teamForeignKey, $modelHasRolesTable, $userInstance) {
                        $q->where("{$modelHasRolesTable}.role_id", $ownerRole->id)
                          ->where("{$modelHasRolesTable}.{$teamForeignKey}", $shop->id)
                          ->where("{$modelHasRolesTable}.model_type", $userInstance->getMorphClass());
                    })
                    ->where("{$userInstance->getTable()}.id", '!=', $userToRemove->id)
                    ->count();

                if ($otherOwnersCount === 0) {
                    Log::warning("ShopTeamController@removeUser: Attempt to remove the last Owner.", ['requesting_user_id' => $requestingUser->id, 'shop_id' => $shop->id, 'target_user_id' => $userId]);
                    return response()->json(['message' => 'Cannot remove the last Owner of the shop.'], 403);
                }
            }
        }

        if ($userToRemove->hasRole($ownerRoleName, $shop) && !$requestingUser->hasRole($ownerRoleName, $shop)) {
            Log::warning("ShopTeamController@removeUser: Non-owner attempted to remove an Owner.", ['requesting_user_id' => $requestingUser->id, 'shop_id' => $shop->id, 'target_user_id' => $userId]);
            return response()->json(['message' => "Only an Owner can remove another Owner from the team."], 403);
        }

        try {
            $userToRemove->syncRoles([], $shop);
            Log::info("ShopTeamController@removeUser: User removed from shop team successfully.", ['requesting_user_id' => $requestingUser->id, 'shop_id' => $shop->id, 'removed_user_id' => $userToRemove->id]);
        } catch (\Exception $e) {
            Log::error("ShopTeamController@removeUser: Failed to remove user roles.", [
                'requesting_user_id' => $requestingUser->id, 'shop_id' => $shop->id, 'target_user_id' => $userToRemove->id, 'error' => $e->getMessage()
            ]);
            return response()->json(['message' => 'Failed to remove user from team. Please try again.'], 500);
        }

        return response()->json(['message' => "User {$userToRemove->name} removed from shop {$shop->name}'s team."]);
    }
}
