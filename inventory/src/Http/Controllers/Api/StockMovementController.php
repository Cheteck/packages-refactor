<?php

namespace IJIDeals\Inventory\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use IJIDeals\Inventory\Models\StockMovement;
use IJIDeals\Inventory\Http\Resources\StockMovementResource; // Import resource
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;

class StockMovementController extends Controller
{
    /**
     * Display a listing of the stock movements.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Gate::authorize('viewAny', StockMovement::class); // Optional policy

        $validator = Validator::make($request->all(), [
            'product_type_alias' => 'nullable|string',
            'product_id' => 'nullable|integer|min:1',
            'location_id' => 'nullable|integer|exists:inventory_locations,id',
            'movement_type' => 'nullable|string', // Consider Rule::in with known movement types from config
            'user_id' => 'nullable|integer|exists:'.(config('auth.providers.users.model') ? (new (config('auth.providers.users.model')))->getTable() : 'users').',id',
            'reference_type_alias' => 'nullable|string',
            'reference_id' => 'nullable|integer|min:1',
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d|after_or_equal:start_date',
            'period' => ['nullable', 'string', Rule::in(['last_7_days', 'last_30_days', 'this_month', 'last_month', 'all_time'])],
            'per_page' => 'nullable|integer|min:1|max:100',
            'sort_by' => ['nullable', 'string', Rule::in(['created_at', 'quantity_change', 'type'])],
            'sort_direction' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $query = StockMovement::query()->with(['stockable', 'location', 'user', 'reference']);

        // Product filtering
        if ($request->filled('product_type_alias') && $request->filled('product_id')) {
            $stockableModelClass = $this->mapStockableType($request->product_type_alias);
            if ($stockableModelClass) {
                $query->where('stockable_type', $stockableModelClass)
                      ->where('stockable_id', $request->product_id);
            } else {
                 // Optionally return error if alias is invalid, or just ignore filter
                return response()->json(['message' => 'Invalid product_type_alias provided for filtering.'], 400);
            }
        }

        // Location filtering
        if ($request->filled('location_id')) {
            $query->where('location_id', $request->location_id);
        }

        // Movement type filtering
        if ($request->filled('movement_type')) {
            $query->where('type', $request->movement_type);
        }

        // User filtering
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Reference filtering
        if ($request->filled('reference_type_alias') && $request->filled('reference_id')) {
            $referenceModelClass = $this->mapReferenceType($request->reference_type_alias);
            if ($referenceModelClass) {
                $query->where('reference_type', $referenceModelClass)
                      ->where('reference_id', $request->reference_id);
            } else {
                return response()->json(['message' => 'Invalid reference_type_alias provided for filtering.'], 400);
            }
        }

        // Date range filtering
        list($startDate, $endDate) = $this->parseDateRange($request->input('period'), $request->input('start_date'), $request->input('end_date'));
        if ($startDate && $endDate) {
           $query->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()]);
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        $perPage = $request->input('per_page', config('inventory.pagination_limit', 15));
        $movements = $query->paginate($perPage);

        return StockMovementResource::collection($movements);
    }

    /**
     * Maps a string alias to a fully qualified model class name for stockable items.
     * This should be configurable, e.g., via config('inventory.stockable_types').
     */
    protected function mapStockableType(string $alias): ?string
    {
        $map = Config::get('inventory.stockable_types', [
            // Example:
            // 'masterproduct' => \IJIDeals\IJIProductCatalog\Models\MasterProduct::class,
            // 'shopproduct' => \IJIDeals\IJIShopListings\Models\ShopProduct::class,
        ]);
        $className = $map[strtolower($alias)] ?? null;
        return ($className && class_exists($className)) ? $className : null;
    }

    /**
     * Maps a string alias to a fully qualified model class name for reference items.
     * This should be configurable, e.g., via config('inventory.reference_types').
     */
    protected function mapReferenceType(string $alias): ?string
    {
        $map = Config::get('inventory.reference_types', [
            // Example:
            // 'order' => \IJIDeals\IJIOrderManagement\Models\Order::class,
            // 'return_request' => \IJIDeals\ReturnsManagement\Models\ReturnRequest::class,
        ]);
        $className = $map[strtolower($alias)] ?? null;
        return ($className && class_exists($className)) ? $className : null;
    }

    /**
     * Helper to parse date range from request parameters or period alias.
     * Same as in AnalyticsController, consider moving to a Trait or Helper class.
     * @return array [Carbon|null, Carbon|null]
     */
    protected function parseDateRange(?string $period, ?string $startDateInput, ?string $endDateInput): array
    {
        $startDate = $startDateInput ? Carbon::parse($startDateInput)->startOfDay() : null;
        $endDate = $endDateInput ? Carbon::parse($endDateInput)->endOfDay() : null;

        if ($period && $period !== 'all_time') {
            switch ($period) {
                case 'last_7_days':
                    $startDate = Carbon::now()->subDays(6)->startOfDay();
                    $endDate = Carbon::now()->endOfDay();
                    break;
                case 'last_30_days':
                    $startDate = Carbon::now()->subDays(29)->startOfDay();
                    $endDate = Carbon::now()->endOfDay();
                    break;
                case 'this_month':
                    $startDate = Carbon::now()->startOfMonth();
                    $endDate = Carbon::now()->endOfMonth();
                    break;
                case 'last_month':
                    $startDate = Carbon::now()->subMonthNoOverflow()->startOfMonth();
                    $endDate = Carbon::now()->subMonthNoOverflow()->endOfMonth();
                    break;
            }
        }

        if ($period === 'all_time') {
            return [null, null];
        }

        return [$startDate, $endDate];
    }
}
