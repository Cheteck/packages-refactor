<?php

namespace IJIDeals\IJICommerce\Http\Controllers;

use Illuminate\Http\Request; // Keep for index, show, destroy that don't use FormRequests yet or for $request->user()
use Illuminate\Routing\Controller;
use IJIDeals\IJICommerce\Models\Shop;
use Illuminate\Support\Facades\Auth; // Keep if $request->user() is not preferred everywhere
use Illuminate\Support\Facades\Log;
// use Illuminate\Validation\Rule; // No longer needed here
use Spatie\Permission\Models\Role;
use IJIDeals\IJICommerce\Http\Requests\StoreShopRequest;
use IJIDeals\IJICommerce\Http\Requests\UpdateShopRequest;

/**
 * Controller for managing Shops.
 * Handles CRUD operations for shops and related functionalities.
 */
class ShopController extends Controller
{
    /**
     * Display a listing of shops accessible to the authenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();
        Log::debug("ShopController@index: Fetching shops for user.", ['user_id' => $user ? $user->id : null]);

        if (!$user) {
            Log::warning("ShopController@index: Unauthenticated user attempted to list shops.");
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Authorization is handled by ShopPolicy through $user->can() or middleware
        // For index, typically a 'viewAny' policy is checked.
        if ($user->cannot('viewAny', Shop::class)) {
            Log::warning("ShopController@index: Authorization failed for user to list shops.", ['user_id' => $user->id, 'action' => 'viewAny']);
            return response()->json(['message' => 'You are not authorized to list shops.'], 403);
        }

        // Get the team foreign key column name from Spatie's config
        $teamForeignKey = config('permission.column_names.team_foreign_key', 'team_id');
        $modelHasRolesTable = config('permission.table_names.model_has_roles', 'model_has_roles');

        // Get shop IDs where the user has any role
        $shopIds = \Illuminate\Support\Facades\DB::table($modelHasRolesTable)
            ->where('model_type', $user->getMorphClass())
            ->where('model_id', $user->getKey())
            ->whereNotNull($teamForeignKey)
            ->distinct()
            ->pluck($teamForeignKey);

        Log::debug("ShopController@index: Found shop IDs for user.", ['user_id' => $user->id, 'shop_ids' => $shopIds->toArray()]);

        $shops = Shop::whereIn('id', $shopIds)->paginate(config('ijicommerce.pagination.shops', 15));
        Log::info("ShopController@index: Successfully fetched shops.", ['user_id' => $user->id, 'count' => $shops->count(), 'total' => $shops->total()]);

        return response()->json($shops);
    }

    /**
     * Store a newly created Shop in storage.
     * Assigns the creating user as 'Owner' of the new shop.
     *
     * @param  \IJIDeals\IJICommerce\Http\Requests\StoreShopRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreShopRequest $request)
    {
        $user = $request->user(); // User is already available via FormRequest
        Log::debug("ShopController@store: Attempting to create shop.", ['user_id' => $user->id, 'request_data' => $request->all()]);

        // Authorization is handled by StoreShopRequest->authorize()
        // Validation is handled by StoreShopRequest->rules()

        $validatedData = $request->validated();
        Log::debug("ShopController@store: Validation passed via FormRequest.", ['user_id' => $user->id, 'validated_data' => $validatedData]);

        // prepareForValidation in StoreShopRequest handles default status
        // if (empty($validatedData['status'])) {
        //     $validatedData['status'] = 'pending_approval';
        // }

        $shop = Shop::create($validatedData);
        Log::info("ShopController@store: Shop created successfully.", ['user_id' => $user->id, 'shop_id' => $shop->id]);

        $ownerRoleName = config('ijicommerce.defaults.owner_role_name', 'Owner');
        $role = Role::firstOrCreate(
            ['name' => $ownerRoleName, 'guard_name' => $user->guard_name ?? config('auth.defaults.guard')],
            [config('permission.column_names.team_foreign_key', 'team_id') => null]
        );

        if (method_exists($user, 'assignRole')) {
            $user->assignRole($role->name, $shop);
            Log::info("ShopController@store: Assigned role to user for new shop.", ['user_id' => $user->id, 'shop_id' => $shop->id, 'role_name' => $role->name]);
        } else {
            Log::error("ShopController@store: User model does not have assignRole method. Spatie HasRoles trait might be missing.", ['user_id' => $user->id, 'shop_id' => $shop->id]);
        }

        return response()->json($shop->fresh(), 201); // Return fresh model
    }

    /**
     * Display the specified Shop.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \IJIDeals\IJICommerce\Models\Shop  $shop The Shop instance via route model binding.
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, Shop $shop)
    {
        $user = $request->user();
        Log::debug("ShopController@show: Fetching shop.", ['user_id' => $user ? $user->id : null, 'shop_id' => $shop->id]);

        // Authorization handled by ShopPolicy
        if ($user->cannot('view', $shop)) {
            Log::warning("ShopController@show: Authorization failed for user to view shop.", ['user_id' => $user ? $user->id : null, 'shop_id' => $shop->id, 'action' => 'view']);
            return response()->json(['message' => 'You are not authorized to view this shop.'], 403);
        }
        Log::info("ShopController@show: Successfully fetched shop.", ['user_id' => $user ? $user->id : null, 'shop_id' => $shop->id]);
        return response()->json($shop);
    }

    /**
     * Update the specified Shop in storage.
     *
     * @param  \IJIDeals\IJICommerce\Http\Requests\UpdateShopRequest  $request
     * @param  \IJIDeals\IJICommerce\Models\Shop  $shop The Shop instance via route model binding.
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateShopRequest $request, Shop $shop)
    {
        $user = $request->user();
        Log::debug("ShopController@update: Attempting to update shop.", ['user_id' => $user->id, 'shop_id' => $shop->id, 'request_data' => $request->all()]);

        // Authorization is handled by UpdateShopRequest->authorize()
        // Validation is handled by UpdateShopRequest->rules()

        $validatedData = $request->validated();
        Log::debug("ShopController@update: Validation passed via FormRequest.", ['user_id' => $user->id, 'shop_id' => $shop->id, 'validated_data' => $validatedData]);

        $shop->update($validatedData);
        Log::info("ShopController@update: Shop updated successfully.", ['user_id' => $user->id, 'shop_id' => $shop->id]);

        return response()->json($shop->fresh()); // Return fresh model
    }

    /**
     * Remove the specified Shop from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \IJIDeals\IJICommerce\Models\Shop  $shop The Shop instance via route model binding.
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, Shop $shop)
    {
        $user = $request->user();
        Log::debug("ShopController@destroy: Attempting to delete shop.", ['user_id' => $user ? $user->id : null, 'shop_id' => $shop->id]);

        if ($user->cannot('delete', $shop)) {
            Log::warning("ShopController@destroy: Authorization failed for user to delete shop.", ['user_id' => $user ? $user->id : null, 'shop_id' => $shop->id, 'action' => 'delete']);
            return response()->json(['message' => 'You are not authorized to delete this shop.'], 403);
        }

        $shop->delete();
        Log::info("ShopController@destroy: Shop deleted successfully.", ['user_id' => $user ? $user->id : null, 'shop_id' => $shop->id]);

        return response()->json(['message' => 'Shop deleted successfully.'], 200);
    }

    // Helper for command output during development, can be removed or adapted for logging
    private function commandOutput($message, $type = 'info')
    {
        // This method seems specific to console output during development.
        // For general logging, prefer direct Log::info, Log::error etc.
        // If this needs to remain for console specific feedback and also log, it can be adapted.
        // For now, direct logging is added in the main methods.
        Log::debug("ShopController::commandOutput (dev helper):", ['message' => $message, 'type' => $type]);

        // Retain original functionality if still needed for direct console feedback from web context (unusual)
        if (app()->runningInConsole() && method_exists(app('Illuminate\Contracts\Console\Kernel'), 'getArtisan')) {
            $command = app('Illuminate\Contracts\Console\Kernel')->getArtisan()->runningCommand();
            if ($command && method_exists($command, $type)) {
                $command->{$type}($message);
            } else {
                // echo "[$type] $message\n"; // Avoid echo in controllers if possible
            }
        }
    }
}
