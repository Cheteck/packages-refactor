<?php

namespace IJIDeals\IJICommerce\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller; // Use Illuminate\Routing\Controller for base controller
use IJIDeals\IJICommerce\Models\Shop;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule; // For unique slug validation

// Assuming Spatie's PermissionModels are available
use Spatie\Permission\Models\Role;


class ShopController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();
        // This is a simplified index. A more complex one might involve checking
        // all shops a user has any role in, or shops they own/administer.
        // For now, let's assume we list shops where the user is 'Owner'.
        // This requires Spatie's team permissions to be correctly set up.

        // To get shops where user has a specific role (e.g., 'Owner')
        // This is a bit more complex with Spatie teams as there isn't a direct
        // $user->shopsWhereRoleIs('Owner') out of the box.
        // You'd typically iterate over user's roles scoped to teams or query the model_has_roles table.

        // A simpler approach for now: list all shops, or shops created by the user if we had owner_id.
        // Since owner_id is removed, a more robust solution is needed.
        // For this initial step, let's return all shops, and note that permissions need to be layered.
        // Or, if we want to be strict, only allow access via specific user-shop links.

        // Let's assume for now, index shows shops the user has *any* role in.
        // This still needs a custom query if not using a specific Spatie trait for it.
        // For true simplicity in this step:
        // $shops = Shop::all();
        // return response()->json($shops);
        // However, this is not secure.

        // TODO: Implement proper retrieval of shops based on user's team roles.
        // For now, as a placeholder that requires the user to be authenticated:
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Authorize using ShopPolicy@viewAny (checks if user can list any shops at all)
        if ($user->cannot('viewAny', Shop::class)) {
            return response()->json(['message' => 'You are not authorized to list shops.'], 403);
        }

        // Get the team foreign key column name from Spatie's config
        $teamForeignKey = config('permission.column_names.team_foreign_key', 'team_id');
        $modelHasRolesTable = config('permission.table_names.model_has_roles', 'model_has_roles');
        $userModelKeyName = $user->getKeyName(); // e.g., 'id'

        // Get shop IDs where the user has any role
        $shopIds = \Illuminate\Support\Facades\DB::table($modelHasRolesTable)
            ->where('model_type', $user->getMorphClass()) // Use getMorphClass() for correctness with morph maps
            ->where('model_id', $user->getKey())
            ->whereNotNull($teamForeignKey) // Ensure it's a team-scoped role
            ->distinct()
            ->pluck($teamForeignKey);

        $shops = Shop::whereIn('id', $shopIds)->paginate(15);

        return response()->json($shops);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated for shop creation.'], 401);
        }

        // Authorize using ShopPolicy@create
        // Note: $this->authorize typically requires the controller to use AuthorizesRequests trait.
        // If not using that trait, call Gate directly: Gate::authorize('create', Shop::class);
        // Assuming AuthorizesRequests trait will be added or Gate is used.
        // For now, let's assume direct Gate call or that the trait is present.
        if ($user->cannot('create', Shop::class)) {
             return response()->json(['message' => 'You are not authorized to create shops.'], 403);
        }

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique(config('ijicommerce.tables.shops', 'shops'), 'slug') // Using Shop model's table
            ],
            'description' => 'nullable|string',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'website_url' => 'nullable|url|max:255',
            'status' => ['nullable', 'string', Rule::in(['active', 'inactive', 'pending_approval', 'suspended'])],
            'display_address' => 'nullable|string|max:1000',
            'logo_path' => 'nullable|string|max:2048', // Consider validation for actual file uploads later
            'cover_photo_path' => 'nullable|string|max:2048',
            'settings' => 'nullable|array',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:500',
        ]);

        if (empty($validatedData['status'])) {
            $validatedData['status'] = 'pending_approval'; // Default status
        }

        $shop = Shop::create($validatedData);

        // Assign 'Owner' role to the creating user for this shop
        // This assumes Spatie teams are configured with Shop as the team model
        // and 'shop_id' as the team_foreign_key.
        $ownerRoleName = 'Owner'; // Make this configurable if needed

        // Ensure the role exists globally (or create it if your seeder hasn't run)
        $role = Role::firstOrCreate(
            ['name' => $ownerRoleName, 'guard_name' => $user->guard_name ?? config('auth.defaults.guard')],
            [$teamKeyField = config('permission.column_names.team_foreign_key', 'team_id') => null] // Global role
        );

        if ($user && method_exists($user, 'assignRole')) {
            $user->assignRole($role->name, $shop); // Assign role scoped to the $shop (team)
            $this->commandOutput("Assigned role '{$role->name}' to user ID {$user->id} for shop ID {$shop->id}");
        } else {
             $this->commandOutput("User model does not have assignRole method or user is null. Spatie HasRoles trait might be missing or user not authenticated.", 'error');
            // Potentially roll back shop creation or log an error
            // For now, we'll proceed, but this is a critical point.
        }

        return response()->json($shop, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \IJIDeals\IJICommerce\Models\Shop  $shop
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, Shop $shop) // Route model binding, added Request
    {
        if ($request->user()->cannot('view', $shop)) {
            return response()->json(['message' => 'You are not authorized to view this shop.'], 403);
        }
        return response()->json($shop);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \IJIDeals\IJICommerce\Models\Shop  $shop
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Shop $shop)
    {
        if ($request->user()->cannot('update', $shop)) {
            return response()->json(['message' => 'You are not authorized to update this shop.'], 403);
        }

        $validatedData = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'slug' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                Rule::unique(config('ijicommerce.tables.shops', 'shops'), 'slug')->ignore($shop->id)
            ],
            'description' => 'nullable|string',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'website_url' => 'nullable|url|max:255',
            'status' => ['sometimes', 'required', 'string', Rule::in(['active', 'inactive', 'pending_approval', 'suspended'])],
            'display_address' => 'nullable|string|max:1000',
            'logo_path' => 'nullable|string|max:2048',
            'cover_photo_path' => 'nullable|string|max:2048',
            'settings' => 'nullable|array',
            'approved_at' => 'nullable|date',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:500',
        ]);

        $shop->update($validatedData);

        return response()->json($shop);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \IJIDeals\IJICommerce\Models\Shop  $shop
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, Shop $shop) // Added Request
    {
        if ($request->user()->cannot('delete', $shop)) {
            return response()->json(['message' => 'You are not authorized to delete this shop.'], 403);
        }

        $shop->delete(); // Or $shop->forceDelete(); if not using soft deletes

        return response()->json(['message' => 'Shop deleted successfully.'], 200);
    }

    // Helper for command output during development, can be removed later
    private function commandOutput($message, $type = 'info')
    {
        if (app()->runningInConsole() && method_exists(app('Illuminate\Contracts\Console\Kernel'), 'getArtisan')) {
            $command = app('Illuminate\Contracts\Console\Kernel')->getArtisan()->runningCommand();
            if ($command && method_exists($command, $type)) {
                $command->{$type}($message);
            } else {
                echo "[$type] $message\n";
            }
        } else {
            // For non-console (e.g. web requests during testing if needed)
            // Log::{$type}($message); // Or echo, or nothing
        }
    }
}
