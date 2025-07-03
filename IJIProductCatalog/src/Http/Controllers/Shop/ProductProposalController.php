<?php

namespace IJIDeals\IJIProductCatalog\Http\Controllers\Shop;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use IJIDeals\IJIProductCatalog\Models\ProductProposal;
use IJIDeals\IJICommerce\Models\Shop; // Shop model remains in IJICommerce
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProductProposalController extends Controller
{
    public function __construct()
    {
        // Apply middleware that ensures user is authenticated for all proposal actions.
        // Specific shop team membership/role checks will be done via policies or direct checks.
        // $this->middleware('auth:sanctum'); // Assuming Sanctum, applied at route level for more flexibility
    }

    /**
     * Display a listing of the product proposals for the current user's shop(s).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        Log::info('Shop ProductProposalController: Fetching product proposals.', ['user_id' => Auth::id()]);
        $user = $request->user();

        $teamForeignKey = config('permission.column_names.team_foreign_key', 'team_id');
        $modelHasRolesTable = config('permission.table_names.model_has_roles', 'model_has_roles');

        $manageableShopIds = DB::table($modelHasRolesTable)
            ->join('roles', "{$modelHasRolesTable}.role_id", '=', 'roles.id')
            ->where('model_type', $user->getMorphClass())
            ->where('model_id', $user->getKey())
            ->whereIn('roles.name', ['Owner', 'Administrator']) // TODO: Make these roles configurable or check permission
            ->whereNotNull($teamForeignKey)
            ->distinct()
            ->pluck($teamForeignKey);

        if ($manageableShopIds->isEmpty()) {
            Log::info('Shop ProductProposalController: User has no manageable shops for proposals.', ['user_id' => Auth::id()]);
            return response()->json(['data' => [], 'message' => 'You do not manage any shops or have permission to view proposals.'], 200);
        }

        $proposals = ProductProposal::whereIn('shop_id', $manageableShopIds)
            ->with('shop:id,name')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        Log::info('Shop ProductProposalController: Product proposals fetched successfully.', ['user_id' => Auth::id(), 'count' => $proposals->count()]);
        return response()->json($proposals);
    }

    /**
     * Store a newly created product proposal in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        Log::info('Shop ProductProposalController: Attempting to store new product proposal.', ['user_id' => Auth::id(), 'request_data' => $request->all()]);
        $user = $request->user();
        $validated = $request->validate([
            'shop_id' => [
                'required',
                Rule::exists(config('ijicommerce.tables.shops', 'shops'), 'id')
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'proposed_brand_name' => 'nullable|string|max:255',
            'proposed_category_name' => 'nullable|string|max:255',
            'proposed_specifications' => 'nullable|array',
            'proposed_images_payload' => 'nullable|array',
        ]);

        $shop = Shop::find($validated['shop_id']);
        // Authorization: Check if user can create a proposal for this shop
        if ($user->cannot('createProposal', [ProductProposal::class, $shop])) {
            Log::warning('Shop ProductProposalController: Unauthorized attempt to create proposal for shop.', ['user_id' => Auth::id(), 'shop_id' => $shop->id]);
            return response()->json(['message' => "You are not authorized to submit proposals for shop '{$shop->name}'."], 403);
        }

        $proposal = ProductProposal::create([
            'shop_id' => $shop->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'proposed_brand_name' => $validated['proposed_brand_name'] ?? null,
            'proposed_category_name' => $validated['proposed_category_name'] ?? null,
            'proposed_specifications' => $validated['proposed_specifications'] ?? [],
            'proposed_images_payload' => $validated['proposed_images_payload'] ?? [],
            'status' => 'pending',
        ]);

        Log::info('Shop ProductProposalController: Product proposal stored successfully.', ['proposal_id' => $proposal->id, 'shop_id' => $shop->id]);
        return response()->json($proposal, 201);
    }

    /**
     * Display the specified product proposal.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \IJIDeals\IJIProductCatalog\Models\ProductProposal  $productProposal
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, ProductProposal $productProposal)
    {
        Log::info('Shop ProductProposalController: Showing product proposal details.', ['proposal_id' => $productProposal->id, 'user_id' => Auth::id()]);
        // Authorization: Check if user can view this proposal
        if ($request->user()->cannot('view', $productProposal)) {
             Log::warning('Shop ProductProposalController: Unauthorized attempt to view proposal.', ['user_id' => Auth::id(), 'proposal_id' => $productProposal->id]);
             return response()->json(['message' => 'You are not authorized to view this proposal.'], 403);
        }

        $productProposal->load('shop:id,name');
        return response()->json($productProposal);
    }
}
