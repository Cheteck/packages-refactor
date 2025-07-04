<?php

namespace IJIDeals\Inventory\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use IJIDeals\Inventory\Models\Inventory;
use IJIDeals\Inventory\Models\InventoryLocation;
use IJIDeals\Inventory\Http\Resources\InventoryResource; // Import the resource
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;


class InventoryController extends Controller
{
    /**
     * Display a listing of inventory items for a specific product.
     * The $productId could be an ID of MasterProduct, ShopProduct, or a Variation.
     * The $productTypeAlias helps resolve which type it is.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $productTypeAlias
     * @param  mixed  $productId
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Http\JsonResponse
     */
    public function getForProduct(Request $request, string $productTypeAlias, $productId)
    {
        // Gate::authorize('viewInventoryForProduct', [$productTypeAlias, $productId]); // Example policy

        $productModelClass = $this->mapStockableType($productTypeAlias);
        if (!$productModelClass) {
            return response()->json(['message' => 'Invalid product type alias provided.'], 400);
        }

        // Optional: Validate if the product instance actually exists
        // if (!app($productModelClass)->where('id', $productId)->exists()) {
        //     return response()->json(['message' => "Product with ID {$productId} of type {$productTypeAlias} not found."], 404);
        // }

        $query = Inventory::where('stockable_type', $productModelClass)
                            ->where('stockable_id', $productId)
                            ->with('location'); // Eager load location

        if ($request->filled('location_id')) {
            $query->where('location_id', $request->location_id);
        }

        // Non-paginated list of inventory for a specific product across its locations
        // Or paginate if a product can truly be in an excessive number of locations (less common)
        $inventories = $query->get();

        return InventoryResource::collection($inventories);
    }

    /**
     * Display a listing of inventory items for a specific location.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \IJIDeals\Inventory\Models\InventoryLocation  $location
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Http\JsonResponse
     */
    public function getForLocation(Request $request, InventoryLocation $location)
    {
        // Gate::authorize('viewInventoryForLocation', $location); // Example policy

        $query = $location->inventoryItems()->with('stockable'); // Eager load the stockable item

        if ($request->filled('stockable_type_alias')) {
            $stockableModelClass = $this->mapStockableType($request->stockable_type_alias);
            if ($stockableModelClass) {
                $query->where('stockable_type', $stockableModelClass);
            } else {
                // Optionally return error if alias is invalid, or just ignore filter
                return response()->json(['message' => 'Invalid stockable_type_alias provided for filtering.'], 400);
            }
        }

        $query->orderBy($request->input('sort_by', 'created_at'), $request->input('sort_direction', 'desc'));

        $perPage = $request->input('per_page', config('inventory.pagination_limit', 15));
        $inventories = $query->paginate($perPage);

        return InventoryResource::collection($inventories);
    }

    /**
     * Maps a string alias to a fully qualified model class name for stockable items.
     * This should be configurable, e.g., via config('inventory.stockable_types').
     *
     * @param string $alias
     * @return string|null
     */
    protected function mapStockableType(string $alias): ?string
    {
        $map = Config::get('inventory.stockable_types', [
            // Example:
            // 'masterproduct' => \IJIDeals\IJIProductCatalog\Models\MasterProduct::class,
            // 'shopproduct' => \IJIDeals\IJIShopListings\Models\ShopProduct::class,
            // 'masterproductvariation' => \IJIDeals\IJIProductCatalog\Models\MasterProductVariation::class,
            // 'shopproductvariation' => \IJIDeals\IJIShopListings\Models\ShopProductVariation::class,
        ]);

        $className = $map[strtolower($alias)] ?? null;

        if ($className && class_exists($className)) {
            return $className;
        }

        return null;
    }
}
