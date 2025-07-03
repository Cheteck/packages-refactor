<?php

namespace IJIDeals\IJIProductCatalog\Http\Controllers\Shop;

use Illuminate\Http\Request; // Keep for index, show
use Illuminate\Routing\Controller;
use IJIDeals\IJIProductCatalog\Models\ProductProposal;
use IJIDeals\IJICommerce\Models\Shop; // Shop model remains in IJICommerce
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule; // No longer needed here
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use IJIDeals\IJIProductCatalog\Http\Requests\Shop\StoreShopProductProposalRequest;

/**
 * Shop-facing controller for managing Product Proposals.
 * Allows authenticated users (shop owners/managers) to submit new product proposals
 * to the platform catalog and view their existing proposals.
 */
class ProductProposalController extends Controller
{
    public function __construct()
    {
        // $this->middleware('auth:sanctum'); // Applied at route level
        // Log::debug('Shop ProductProposalController constructed.');
    }

    /**
     * Display a listing of product proposals submitted by shops managed by the authenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $userId = $user ? $user->id : null;
        Log::debug('Shop ProductProposalController@index: Fetching product proposals.', ['user_id' => $userId, 'query_params' => $request->query()]);

        if (!$user) {
            Log::warning('Shop ProductProposalController@index: Unauthenticated access attempt.');
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $teamForeignKey = config('permission.column_names.team_foreign_key', 'team_id');
        $modelHasRolesTable = config('permission.table_names.model_has_roles', 'model_has_roles');
        $rolesToManageProposals = config('ijiproductcatalog.shop_proposal_management_roles', ['Owner', 'Administrator']);

        $manageableShopIds = DB::table($modelHasRolesTable)
            ->join('roles', "{$modelHasRolesTable}.role_id", '=', 'roles.id')
            ->where('model_type', $user->getMorphClass())
            ->where('model_id', $user->getKey())
            ->whereIn('roles.name', $rolesToManageProposals)
            ->whereNotNull($teamForeignKey)
            ->distinct()
            ->pluck($teamForeignKey);

        if ($manageableShopIds->isEmpty()) {
            Log::info('Shop ProductProposalController@index: User has no manageable shops for proposals or lacks required role.', ['user_id' => $userId, 'roles_checked' => $rolesToManageProposals]);
            return response()->json(['data' => [], 'message' => 'You do not manage any shops or have permission to view proposals.'], 200);
        }
        Log::debug('Shop ProductProposalController@index: User manages shops.', ['user_id' => $userId, 'shop_ids' => $manageableShopIds->toArray()]);

        $proposals = ProductProposal::whereIn('shop_id', $manageableShopIds)
            ->with('shop:id,name')
            ->orderByDesc('created_at')
            ->paginate(config('ijiproductcatalog.pagination.shop_proposals', 15));

        Log::info('Shop ProductProposalController@index: Product proposals fetched successfully.', ['user_id' => $userId, 'count' => $proposals->count(), 'total' => $proposals->total()]);
        return response()->json($proposals);
    }

    /**
     * Store a newly created Product Proposal from a shop.
     *
     * @param  \IJIDeals\IJIProductCatalog\Http\Requests\Shop\StoreShopProductProposalRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreShopProductProposalRequest $request)
    {
        $user = $request->user();
        $userId = $user->id; // User is guaranteed by FormRequest authorize method
        Log::debug('Shop ProductProposalController@store: Attempting to store new product proposal.', ['user_id' => $userId, 'request_data' => $request->all()]);

        // Authorization handled by StoreShopProductProposalRequest->authorize()
        // Validation handled by StoreShopProductProposalRequest->rules()
        $validatedData = $request->validated();
        Log::debug('Shop ProductProposalController@store: Validation passed via FormRequest.', ['user_id' => $userId, 'validated_data' => $validatedData]);

        // Shop existence is validated by FormRequest Rule::exists
        // $shop = Shop::find($validatedData['shop_id']);
        // No need to re-find, shop_id is validated.

        try {
            $proposal = ProductProposal::create([
                'shop_id' => $validatedData['shop_id'],
                'name' => $validatedData['name'],
                'description' => $validatedData['description'] ?? null,
                'proposed_brand_name' => $validatedData['proposed_brand_name'] ?? null,
                'proposed_category_name' => $validatedData['proposed_category_name'] ?? null,
                'proposed_specifications' => $validatedData['proposed_specifications'] ?? [],
                'proposed_images_payload' => $validatedData['proposed_images_payload'] ?? [],
                'status' => 'pending',
            ]);

            Log::info('Shop ProductProposalController@store: Product proposal stored successfully.', ['user_id' => $userId, 'proposal_id' => $proposal->id, 'shop_id' => $validatedData['shop_id']]);
            // TODO: Notify platform admins of new proposal
            return response()->json($proposal->fresh()->load('shop:id,name'), 201);
        } catch (\Exception $e) {
            Log::error('Shop ProductProposalController@store: Error storing product proposal.', [
                'user_id' => $userId,
                'shop_id' => $validatedData['shop_id'] ?? null, // shop_id might not be set if validation failed before this point, though unlikely with FormRequest
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Error storing product proposal.'], 500);
        }
    }

    /**
     * Display the specified Product Proposal if the authenticated user is authorized to view it.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \IJIDeals\IJIProductCatalog\Models\ProductProposal  $productProposal The ProductProposal instance via route model binding.
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, ProductProposal $productProposal)
    {
        $user = $request->user();
        $userId = $user ? $user->id : null;
        Log::debug('Shop ProductProposalController@show: Showing product proposal details.', ['user_id' => $userId, 'proposal_id' => $productProposal->id]);

        if (!$user) {
            Log::warning('Shop ProductProposalController@show: Unauthenticated access attempt.');
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ($user->cannot('view', $productProposal)) {
             Log::warning('Shop ProductProposalController@show: Authorization failed for user to view proposal.', ['user_id' => $userId, 'proposal_id' => $productProposal->id]);
             return response()->json(['message' => 'You are not authorized to view this proposal.'], 403);
        }

        $productProposal->load('shop:id,name'); // Load only necessary shop fields
        Log::info('Shop ProductProposalController@show: Product proposal details fetched.', ['user_id' => $userId, 'proposal_id' => $productProposal->id]);
        return response()->json($productProposal);
    }
}
