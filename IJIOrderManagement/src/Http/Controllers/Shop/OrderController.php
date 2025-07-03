<?php

namespace IJIDeals\IJIOrderManagement\Http\Controllers\Shop;

use Illuminate\Http\Request; // Keep for index, show
use Illuminate\Routing\Controller;
use IJIDeals\IJIOrderManagement\Models\Order;
use IJIDeals\IJICommerce\Models\Shop;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
// use Illuminate\Validation\Rule; // No longer needed here
use IJIDeals\IJIOrderManagement\Http\Requests\Shop\UpdateShopOrderStatusRequest;

/**
 * Handles shop-specific order management operations.
 * Allows shop managers to view their orders and update order statuses.
 */
class OrderController extends Controller
{
    /**
     * Display a paginated listing of orders belonging to a specific Shop.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \IJIDeals\IJICommerce\Models\Shop  $shop The Shop instance via route model binding.
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request, Shop $shop)
    {
        $userId = $request->user() ? $request->user()->id : (Auth::id() ?? null);
        Log::info('Shop OrderController@index: Fetching orders for shop.', ['shop_id' => $shop->id, 'user_id' => $userId]);

        if ($request->user()->cannot('manageShopOrders', $shop)) {
             Log::warning('Shop OrderController@index: Unauthorized attempt to view orders.', ['shop_id' => $shop->id, 'user_id' => $userId]);
             return response()->json(['message' => "Unauthorized to view orders for shop '{$shop->name}'."], 403);
        }

        $orders = $shop->orders()
                       ->with(['user:id,name,email', 'items']) // Consider items.product for more details if needed
                       ->orderByDesc('created_at')
                       ->paginate(config('ijiordermanagement.pagination.shop_orders', 20));

        Log::info('Shop OrderController@index: Orders fetched successfully.', ['shop_id' => $shop->id, 'user_id' => $userId, 'count' => $orders->count(), 'total' => $orders->total()]);
        return response()->json($orders);
    }

    /**
     * Display the specified Order, ensuring it belongs to the given Shop.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \IJIDeals\IJICommerce\Models\Shop  $shop The parent Shop.
     * @param  \IJIDeals\IJIOrderManagement\Models\Order  $order The Order instance via route model binding.
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, Shop $shop, Order $order)
    {
        $userId = $request->user() ? $request->user()->id : (Auth::id() ?? null);
        Log::info('Shop OrderController@show: Showing order details.', ['order_id' => $order->id, 'shop_id' => $shop->id, 'user_id' => $userId]);

        if ($order->shop_id !== $shop->id) {
            Log::warning('Shop OrderController@show: Order not found in this shop.', ['order_id' => $order->id, 'shop_id' => $shop->id, 'user_id' => $userId]);
            return response()->json(['message' => 'Order not found in this shop.'], 404);
        }

        if ($request->user()->cannot('manageShopOrders', $shop)) { // Or a more specific 'viewShopOrder' policy
             Log::warning('Shop OrderController@show: Unauthorized attempt to view order details.', ['order_id' => $order->id, 'shop_id' => $shop->id, 'user_id' => $userId]);
             return response()->json(['message' => "Unauthorized to view this order for shop '{$shop->name}'."], 403);
        }

        $order->load(['user:id,name,email', 'items.masterProduct:id,name', 'items.masterProductVariation:id,sku']); // Adjust eager loaded relations as needed
        Log::info('Shop OrderController@show: Order details fetched successfully.', ['order_id' => $order->id, 'shop_id' => $shop->id, 'user_id' => $userId]);
        return response()->json($order);
    }

    /**
     * Update the status of a specific Order belonging to a Shop.
     * Handles status transitions and associated actions like setting shipped_at or completed_at.
     *
     * @param  \IJIDeals\IJIOrderManagement\Http\Requests\Shop\UpdateShopOrderStatusRequest  $request
     * @param  \IJIDeals\IJICommerce\Models\Shop  $shop The parent Shop.
     * @param  \IJIDeals\IJIOrderManagement\Models\Order  $order The Order instance via route model binding.
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(UpdateShopOrderStatusRequest $request, Shop $shop, Order $order)
    {
        $userId = $request->user()->id; // User is guaranteed by FormRequest
        Log::info('Shop OrderController@updateStatus: Attempting to update order status.', ['order_id' => $order->id, 'shop_id' => $shop->id, 'user_id' => $userId, 'request_data' => $request->all()]);

        // Authorization and basic shop_id matching is handled by UpdateShopOrderStatusRequest->authorize()
        // Validation handled by UpdateShopOrderStatusRequest->rules()
        // The check $order->shop_id !== $shop->id is also in FormRequest authorize method.

        $validatedData = $request->validated();
        Log::debug('Shop OrderController@updateStatus: Validation passed via FormRequest.', ['validated_data' => $validatedData]);

        try {
            $order->status = $validatedData['status'];
            if ($validatedData['status'] === 'shipped' && !empty($validatedData['tracking_number'])) {
                $order->shipped_at = now();
                // Append tracking number if notes already exist, or set it.
                $existingNotes = trim($order->notes_for_customer ?? '');
                $trackingInfo = "Tracking: " . $validatedData['tracking_number'];
                $order->notes_for_customer = $existingNotes ? ($existingNotes . "\n" . $trackingInfo) : $trackingInfo;

                Log::info('Shop OrderController@updateStatus: Order marked as shipped.', ['order_id' => $order->id, 'tracking_number' => $validatedData['tracking_number']]);
            }
            if ($validatedData['status'] === 'completed' && !$order->completed_at) {
                $order->completed_at = now();
                Log::info('Shop OrderController@updateStatus: Order marked as completed.', ['order_id' => $order->id]);
            }
            if ($validatedData['status'] === 'processing' && !$order->processing_at) {
                $order->processing_at = now();
                Log::info('Shop OrderController@updateStatus: Order marked as processing.', ['order_id' => $order->id]);
            }
            // Update notes if provided, separate from tracking info concatenation
            if (isset($validatedData['notes_for_customer']) && $validatedData['status'] !== 'shipped') { // Avoid overwriting tracking if just notes are sent
                $order->notes_for_customer = $validatedData['notes_for_customer'];
            } elseif (isset($validatedData['notes_for_customer']) && $validatedData['status'] === 'shipped' && empty($validatedData['tracking_number'])) {
                // If status is shipped but no tracking, allow updating notes.
                 $order->notes_for_customer = $validatedData['notes_for_customer'];
            }


            $order->save();
            Log::info('Shop OrderController@updateStatus: Order status updated successfully.', ['order_id' => $order->id, 'new_status' => $order->status, 'user_id' => $userId]);

            // TODO: Dispatch OrderStatusUpdated event, Notify customer

            return response()->json($order->fresh()->load(['user:id,name,email', 'items']));
        } catch (\Exception $e) {
            Log::error('Shop OrderController@updateStatus: Error updating order status.', [
                'order_id' => $order->id,
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Error updating order status.'], 500);
        }
    }
}
