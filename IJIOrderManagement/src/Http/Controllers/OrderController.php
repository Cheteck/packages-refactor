<?php

namespace IJIDeals\IJIOrderManagement\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use IJIDeals\IJIOrderManagement\Models\Order;
use IJIDeals\IJIOrderManagement\Models\OrderItem;
use IJIDeals\IJICommerce\Models\Shop;
use IJIDeals\IJIShopListings\Models\ShopProduct;
use IJIDeals\IJIShopListings\Models\ShopProductVariation;
use IJIDeals\IJIProductCatalog\Models\MasterProduct;
use IJIDeals\IJIProductCatalog\Models\MasterProductVariation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    /**
     * Store a newly created order in storage (customer places an order).
     * This is a simplified version. Real checkout is much more complex.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        Log::info('OrderController: Attempting to store new order.', ['user_id' => Auth::id(), 'request_data' => $request->all()]);
        $user = $request->user();
        if (!$user) {
            Log::warning('OrderController: Unauthenticated attempt to store order.');
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate([
            'shop_id' => ['required', Rule::exists(config('ijicommerce.tables.shops', 'shops'), 'id')],
            'items' => 'required|array|min:1',
            'items.*.type' => ['required', Rule::in(['shopproduct', 'shopproductvariation'])],
            'items.*.id' => 'required|integer',
            'items.*.quantity' => 'required|integer|min:1',
            'billing_address' => 'required|array',
            'shipping_address' => 'required|array',
            'payment_method_token' => 'required|string',
        ]);

        $shop = Shop::findOrFail($validated['shop_id']);
        $orderTotal = 0;
        $orderItemsData = [];

        DB::beginTransaction();
        try {
            foreach ($validated['items'] as $itemInput) {
                $quantity = $itemInput['quantity'];
                $productInstance = null;
                $priceAtPurchase = 0;
                $productName = 'Unknown Product';
                $variantDetails = null;
                $sku = null;
                $shopProductId = null;
                $masterProductVariationId = null;
                $masterProductId = null;

                if ($itemInput['type'] === 'shopproductvariation') {
                    $productInstance = ShopProductVariation::with('masterProductVariation.masterProduct', 'shopProduct')
                                        ->findOrFail($itemInput['id']);

                    if ($productInstance->shopProduct->shop_id !== $shop->id) {
                        Log::error('OrderController: Variation ID does not belong to shop.', ['variation_id' => $productInstance->id, 'shop_id' => $shop->id]);
                        throw new \Exception("Variation ID {$productInstance->id} does not belong to shop ID {$shop->id}.");
                    }
                    if ($productInstance->stock_quantity < $quantity) {
                        Log::warning('OrderController: Insufficient stock for variant.', ['variation_id' => $productInstance->id, 'requested_quantity' => $quantity, 'available_stock' => $productInstance->stock_quantity]);
                        throw new \Exception("Insufficient stock for variant: {$productInstance->masterProductVariation->sku}. Requested: {$quantity}, Available: {$productInstance->stock_quantity}");
                    }
                    $productInstance->decrement('stock_quantity', $quantity);

                    $priceAtPurchase = $productInstance->price;
                    $productName = $productInstance->masterProductVariation->masterProduct->name;
                    $variantDetails = $productInstance->masterProductVariation->attributeOptions->mapWithKeys(fn($opt) => [$opt->attribute->name => $opt->value]);
                    $sku = $productInstance->masterProductVariation->sku ?? $productInstance->shop_sku_variant;
                    $shopProductId = $productInstance->shop_product_id;
                    $masterProductVariationId = $productInstance->master_product_variation_id;
                    $masterProductId = $productInstance->masterProductVariation->master_product_id;

                } elseif ($itemInput['type'] === 'shopproduct') {
                    $productInstance = ShopProduct::with('masterProduct')->findOrFail($itemInput['id']);

                    if ($productInstance->shop_id !== $shop->id) {
                        Log::error('OrderController: ShopProduct ID does not belong to shop.', ['shop_product_id' => $productInstance->id, 'shop_id' => $shop->id]);
                        throw new \Exception("ShopProduct ID {$productInstance->id} does not belong to shop ID {$shop->id}.");
                    }
                    if ($productInstance->variations()->exists()) {
                        Log::warning('OrderController: Attempted to order simple product with variations.', ['shop_product_id' => $productInstance->id]);
                         throw new \Exception("Product ID {$productInstance->id} has variations and must be ordered by specific variant.");
                    }
                    if ($productInstance->stock_quantity < $quantity) {
                        Log::warning('OrderController: Insufficient stock for product.', ['shop_product_id' => $productInstance->id, 'requested_quantity' => $quantity, 'available_stock' => $productInstance->stock_quantity]);
                        throw new \Exception("Insufficient stock for product: {$productInstance->masterProduct->name}. Requested: {$quantity}, Available: {$productInstance->stock_quantity}");
                    }
                    $productInstance->decrement('stock_quantity', $quantity);

                    $priceAtPurchase = $productInstance->price;
                    $productName = $productInstance->masterProduct->name;
                    $sku = $productInstance->masterProduct->sku;
                    $shopProductId = $productInstance->id;
                    $masterProductId = $productInstance->master_product_id;
                } else {
                    Log::error('OrderController: Invalid item type provided.', ['item_type' => $itemInput['type']]);
                     throw new \Exception("Invalid item type: {$itemInput['type']}");
                }

                $lineTotal = $priceAtPurchase * $quantity;
                $orderTotal += $lineTotal;

                $orderItemsData[] = [
                    'shop_product_id' => $shopProductId,
                    'master_product_variation_id' => $masterProductVariationId,
                    'master_product_id' => $masterProductId,
                    'product_name_at_purchase' => $productName,
                    'variant_details_at_purchase' => $variantDetails,
                    'sku_at_purchase' => $sku,
                    'quantity' => $quantity,
                    'price_at_purchase' => $priceAtPurchase,
                    'total_line_amount' => $lineTotal,
                ];
            }

            $order = Order::create([
                'shop_id' => $shop->id,
                'user_id' => $user->id,
                'status' => 'pending_payment',
                'total_amount' => $orderTotal,
                'subtotal_amount' => $orderTotal,
                'currency' => $shop->settings['currency'] ?? 'USD',
                'billing_address' => $validated['billing_address'],
                'shipping_address' => $validated['shipping_address'],
                'payment_method' => 'placeholder_gateway',
                'payment_status' => 'pending',
            ]);

            $order->items()->createMany($orderItemsData);

            DB::commit();
            Log::info('OrderController: Order created successfully.', ['order_id' => $order->id, 'order_number' => $order->order_number]);

            return response()->json($order->load('items'), 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('OrderController: Order creation failed.', ['user_id' => Auth::id(), 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Order creation failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified order for the authenticated user.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \IJIDeals\IJIOrderManagement\Models\Order $order
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, Order $order)
    {
        Log::info('OrderController: Showing order details.', ['order_id' => $order->id, 'user_id' => Auth::id()]);
        if ($request->user()->id !== $order->user_id) {
            Log::warning('OrderController: Unauthorized attempt to view order.', ['order_id' => $order->id, 'user_id' => Auth::id()]);
            return response()->json(['message' => 'Unauthorized to view this order.'], 403);
        }
        return response()->json($order->load(['items', 'shop:id,name']));
    }

    /**
     * List orders for the authenticated user.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        Log::info('OrderController: Listing orders for user.', ['user_id' => Auth::id()]);
        $orders = Order::where('user_id', $request->user()->id)
                        ->with('shop:id,name')
                        ->orderBy('created_at', 'desc')
                        ->paginate(15);
        Log::info('OrderController: Orders listed successfully.', ['user_id' => Auth::id(), 'count' => $orders->count()]);
        return response()->json($orders);
    }
}
