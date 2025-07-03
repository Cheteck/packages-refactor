<?php

namespace IJIDeals\IJICommerce\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use IJIDeals\IJICommerce\Models\Shop;
use App\Models\User; // Assuming default User model path
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;
use Illuminate\Validation\Rule;

class ShopTeamController extends Controller
{
    protected $userModel;

    public function __construct()
    {
        $this->userModel = config('ijicommerce.user_model', \App\Models\User::class);
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
        if ($request->user()->cannot('manageTeam', $shop) && $request->user()->cannot('view', $shop)) {
             // Even viewing team members might be restricted, or have a separate permission 'view shop team'
            return response()->json(['message' => 'Unauthorized to view shop team.'], 403);
        }

        // To get users with roles specifically for this shop (team):
        // This requires querying the model_has_roles table.
        $teamForeignKey = config('permission.column_names.team_foreign_key', 'team_id');
        $modelHasRolesTable = config('permission.table_names.model_has_roles', 'model_has_roles');
        $rolesTable = config('permission.table_names.roles', 'roles');

        $membersWithRoles = $this->userModel::query()
            ->select('users.*', "{$rolesTable}.name as role_name") // Alias role name
            ->join($modelHasRolesTable, function ($join) use ($user, $teamForeignKey, $modelHasRolesTable) {
                $join->on('users.id', '=', "{$modelHasRolesTable}.model_id")
                    ->where("{$modelHasRolesTable}.model_type", '=', (new $this->userModel)->getMorphClass());
            })
            ->join($rolesTable, "{$modelHasRolesTable}.role_id", '=', "{$rolesTable}.id")
            ->where($teamForeignKey, $shop->id)
            ->distinct() // A user might have multiple roles, list them or pick one primary one.
            ->orderBy('users.name')
            ->paginate(15);

        // The above query gives one row per role assignment. If a user has multiple roles in the shop, they appear multiple times.
        // A more complex query or collection processing would be needed to group roles per user.
        // For now, this lists role assignments.

        return response()->json($membersWithRoles);
    }

    /**
     * Add a user to the shop's team and assign a role.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \IJIDeals\IJICommerce\Models\Shop  $shop
     * @return \Illuminate\Http\JsonResponse
     */
    public function addUser(Request $request, Shop $shop)
    {
        if ($request->user()->cannot('manageTeam', $shop)) {
            return response()->json(['message' => 'Unauthorized to manage this shop team.'], 403);
        }

        $validated = $request->validate([
            'email' => ['required', 'email', Rule::exists((new $this->userModel)->getTable(), 'email')],
            'role' => ['required', 'string', Rule::exists(config('permission.table_names.roles'), 'name')], // Ensure role exists
        ]);

        $userToAdd = $this->userModel::where('email', $validated['email'])->first();
        if (!$userToAdd) {
            // This case should be caught by Rule::exists, but as a fallback.
            return response()->json(['message' => 'User not found.'], 404);
        }

        // Ensure the role is valid for the guard and optionally for the team context (if using strict team roles)
        $role = Role::findByName($validated['role'], $userToAdd->guard_name);
        if (!$role) {
             return response()->json(['message' => "Role '{$validated['role']}' not found for the user's guard."], 400);
        }

        // Check if user is already a member (Spatie might handle this gracefully or throw error)
        // $userToAdd->assignRole($role->name, $shop); // Assign role scoped to the $shop (team)
        // To avoid issues if user already has a role, sync or set explicitly:
        $userToAdd->syncRoles([$role->name], $shop);


        return response()->json([
            'message' => "User {$userToAdd->name} added to shop {$shop->name} as {$role->name}.",
            'user' => $userToAdd->fresh()->load(['roles' => function ($query) use ($shop) { // Load roles for this specific shop
                $query->where(config('permission.column_names.team_foreign_key', 'team_id'), $shop->id);
            }])
        ], 200);
    }

    /**
     * Update a user's role within the shop's team.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \IJIDeals\IJICommerce\Models\Shop  $shop
     * @param  int $userId The ID of the user whose role is to be updated.
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateUserRole(Request $request, Shop $shop, $userId)
    {
        if ($request->user()->cannot('manageTeam', $shop)) {
            return response()->json(['message' => 'Unauthorized to manage this shop team.'], 403);
        }

        $userToUpdate = $this->userModel::findOrFail($userId);

        $validated = $request->validate([
            'role' => ['required', 'string', Rule::exists(config('permission.table_names.roles'), 'name')],
        ]);

        // Prevent self-demotion or changing Owner role if current user is not also an Owner or higher admin
        if ($userToUpdate->id === $request->user()->id && $userToUpdate->hasRole('Owner', $shop) && $validated['role'] !== 'Owner') {
            if (!$request->user()->hasRole('Owner', $shop)) { // Only another owner can demote an owner
                 return response()->json(['message' => 'Owners cannot demote themselves unless another Owner performs the action.'], 403);
            }
        }
        // Prevent non-Owners from changing an Owner's role
        if ($userToUpdate->hasRole('Owner', $shop) && $userToUpdate->id !== $request->user()->id && !$request->user()->hasRole('Owner', $shop)) {
            return response()->json(['message' => "Only an Owner can change another Owner's role."], 403);
        }


        $role = Role::findByName($validated['role'], $userToUpdate->guard_name);
         if (!$role) {
             return response()->json(['message' => "Role '{$validated['role']}' not found for the user's guard."], 400);
        }

        // SyncRoles will remove all other team-scoped roles for this shop and set the new one.
        $userToUpdate->syncRoles([$role->name], $shop);

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
        if ($request->user()->cannot('manageTeam', $shop)) {
            return response()->json(['message' => 'Unauthorized to manage this shop team.'], 403);
        }

        $userToRemove = $this->userModel::findOrFail($userId);

        // Prevent removing the last Owner if they are the one being removed
        if ($userToRemove->hasRole('Owner', $shop)) {
            // Count other owners for this shop
            $ownerRole = Role::findByName('Owner', $userToRemove->guard_name); // Assuming 'Owner' role exists
            if ($ownerRole) {
                $otherOwnersCount = $this->userModel::query()
                    ->whereHas('roles', function ($q) use ($ownerRole, $shop) {
                        $q->where('id', $ownerRole->id)
                          ->where(config('permission.table_names.model_has_roles').'.'.config('permission.column_names.team_foreign_key'), $shop->id);
                    })
                    ->where('id', '!=', $userToRemove->id)
                    ->count();

                if ($otherOwnersCount === 0) {
                    return response()->json(['message' => 'Cannot remove the last Owner of the shop.'], 403);
                }
            }
        }

        // Prevent non-Owners from removing an Owner
        if ($userToRemove->hasRole('Owner', $shop) && !$request->user()->hasRole('Owner', $shop)) {
             return response()->json(['message' => "Only an Owner can remove another Owner from the team."], 403);
        }


        // Remove all roles for this user within this specific shop (team)
        // $userToRemove->removeRole('any role', $shop); // This doesn't exist directly
        // We need to get all roles the user has for this shop and remove them one by one, or sync to empty.
        $userToRemove->syncRoles([], $shop);


        return response()->json(['message' => "User {$userToRemove->name} removed from shop {$shop->name}'s team."]);
    }
}
