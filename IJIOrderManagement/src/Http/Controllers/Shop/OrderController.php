<?php

namespace IJIDeals\IJIOrderManagement\Http\Controllers\Shop;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use IJIDeals\IJIOrderManagement\Models\Order;
use IJIDeals\IJICommerce\Models\Shop;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    /**
     * Display a listing of orders for a specific shop.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \IJIDeals\IJICommerce\Models\Shop  $shop
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request, Shop $shop)
    {
        Log::info('Shop OrderController: Fetching orders for shop.', ['shop_id' => $shop->id, 'user_id' => Auth::id()]);
        // Authorization: Check if user can manage orders for this shop
        if ($request->user()->cannot('manageShopOrders', $shop)) {
             Log::warning('Shop OrderController: Unauthorized attempt to view orders.', ['shop_id' => $shop->id, 'user_id' => Auth::id()]);
             return response()->json(['message' => "Unauthorized to view orders for shop '{$shop->name}'."], 403);
        }

        $orders = $shop->orders()
                       ->with(['user:id,name,email', 'items'])
                       ->orderBy('created_at', 'desc')
                       ->paginate(20);

        Log::info('Shop OrderController: Orders fetched successfully.', ['shop_id' => $shop->id, 'count' => $orders->count()]);
        return response()->json($orders);
    }

    /**
     * Display the specified order for a shop.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \IJIDeals\IJICommerce\Models\Shop  $shop
     * @param  \IJIDeals\IJIOrderManagement\Models\Order  $order
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, Shop $shop, Order $order)
    {
        Log::info('Shop OrderController: Showing order details.', ['order_id' => $order->id, 'shop_id' => $shop->id, 'user_id' => Auth::id()]);
        // Ensure the order belongs to the specified shop
        if ($order->shop_id !== $shop->id) {
            Log::warning('Shop OrderController: Order not found in this shop.', ['order_id' => $order->id, 'shop_id' => $shop->id]);
            return response()->json(['message' => 'Order not found in this shop.'], 404);
        }

        if ($request->user()->cannot('manageShopOrders', $shop)) {
             Log::warning('Shop OrderController: Unauthorized attempt to view order details.', ['order_id' => $order->id, 'shop_id' => $shop->id, 'user_id' => Auth::id()]);
             return response()->json(['message' => "Unauthorized to view this order for shop '{$shop->name}'."], 403);
        }

        $order->load(['user:id,name,email', 'items.masterProduct:id,name', 'items.masterProductVariation:id,sku']);
        return response()->json($order);
    }

    /**
     * Update the status of an order (e.g., processing, shipped).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \IJIDeals\IJICommerce\Models\Shop  $shop
     * @param  \IJIDeals\IJIOrderManagement\Models\Order  $order
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Request $request, Shop $shop, Order $order)
    {
        Log::info('Shop OrderController: Attempting to update order status.', ['order_id' => $order->id, 'shop_id' => $shop->id, 'user_id' => Auth::id(), 'request_data' => $request->all()]);
        if ($order->shop_id !== $shop->id) {
            Log::warning('Shop OrderController: Order not found in this shop during status update.', ['order_id' => $order->id, 'shop_id' => $shop->id]);
            return response()->json(['message' => 'Order not found in this shop.'], 404);
        }
        if ($request->user()->cannot('updateShopOrderStatus', [$order, $shop])) {
             Log::warning('Shop OrderController: Unauthorized attempt to update order status.', ['order_id' => $order->id, 'shop_id' => $shop->id, 'user_id' => Auth::id()]);
             return response()->json(['message' => "Unauthorized to update status for this order in shop '{$shop->name}'."], 403);
        }

        $allowedStatuses = ['processing', 'shipped', 'completed', 'on_hold', 'cancelled_by_shop'];
        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in($allowedStatuses)],
            'tracking_number' => 'nullable|string|max:255',
            'notes_for_customer' => 'nullable|string|max:1000',
        ]);

        $order->status = $validated['status'];
        if ($validated['status'] === 'shipped' && !empty($validated['tracking_number'])) {
            $order->shipped_at = now();
            $order->notes_for_customer = trim(($order->notes_for_customer ?? '') . "\nTracking: " . $validated['tracking_number']);
            Log::info('Shop OrderController: Order marked as shipped.', ['order_id' => $order->id, 'tracking_number' => $validated['tracking_number']]);
        }
        if ($validated['status'] === 'completed') {
            $order->completed_at = now();
            Log::info('Shop OrderController: Order marked as completed.', ['order_id' => $order->id]);
        }
         if ($validated['status'] === 'processing' && !$order->processing_at) {
            $order->processing_at = now();
            Log::info('Shop OrderController: Order marked as processing.', ['order_id' => $order->id]);
        }
        if (!empty($validated['notes_for_customer'])) {
            $order->notes_for_customer = $validated['notes_for_customer'];
        }

        $order->save();

        Log::info('Shop OrderController: Order status updated successfully.', ['order_id' => $order->id, 'new_status' => $order->status]);
        return response()->json($order->fresh()->load(['user:id,name,email', 'items']));
    }
}
