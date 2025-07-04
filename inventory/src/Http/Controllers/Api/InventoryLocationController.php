<?php

namespace IJIDeals\Inventory\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use IJIDeals\Inventory\Models\InventoryLocation;
use IJIDeals\Inventory\Http\Resources\InventoryLocationResource; // Import the resource
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate; // For potential authorization

class InventoryLocationController extends Controller
{
    /**
     * Display a listing of the inventory locations.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        // Gate::authorize('viewAny', InventoryLocation::class); // Optional: if you have a policy

        $query = InventoryLocation::query();

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        $query->orderBy($request->input('sort_by', 'name'), $request->input('sort_direction', 'asc'));

        $perPage = $request->input('per_page', config('inventory.pagination_limit', 15));
        $locations = $query->paginate($perPage);

        return InventoryLocationResource::collection($locations);
    }

    /**
     * Display the specified inventory location.
     *
     * @param  \IJIDeals\Inventory\Models\InventoryLocation  $location
     * @return \IJIDeals\Inventory\Http\Resources\InventoryLocationResource
     */
    public function show(InventoryLocation $location)
    {
        // Gate::authorize('view', $location); // Optional: if you have a policy

        // Optionally load related items if needed for the detail view and supported by the resource
        // $location->loadMissing('inventoryItems');

        return new InventoryLocationResource($location);
    }

    // Note: Store, Update, Destroy methods for full CRUD would require:
    // - Form Requests (e.g., StoreInventoryLocationRequest, UpdateInventoryLocationRequest)
    // - Authorization checks (e.g., who can create/update/delete locations)
    // - Logic to handle creation/update/deletion, potentially using InventoryService
    // - Appropriate responses (201 for created, 200 for updated, 204 for deleted)
}
