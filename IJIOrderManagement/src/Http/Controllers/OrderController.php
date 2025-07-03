<?php

namespace IJIDeals\IJIOrderManagement\Http\Controllers;

use Illuminate\Http\Request; // Keep for index, show
use Illuminate\Routing\Controller;
use IJIDeals\IJIOrderManagement\Models\Order;
// use IJIDeals\IJIOrderManagement\Models\OrderItem; // Not directly type-hinted
use IJIDeals\IJICommerce\Models\Shop;
use IJIDeals\IJIShopListings\Models\ShopProduct;
use IJIDeals\IJIShopListings\Models\ShopProductVariation;
// use IJIDeals\IJIProductCatalog\Models\MasterProduct; // Not directly type-hinted
// use IJIDeals\IJIProductCatalog\Models\MasterProductVariation; // Not directly type-hinted
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
// use Illuminate\Validation\Rule; // No longer needed here
use IJIDeals\IJIOrderManagement\Http\Requests\StoreOrderRequest;
use IJIDeals\IJIOrderManagement\Exceptions\InsufficientStockException;
use IJIDeals\IJIOrderManagement\Exceptions\ProductAssociationException;
use IJIDeals\IJIOrderManagement\Exceptions\InvalidOrderItemTypeException;
use IJIDeals\IJIOrderManagement\Exceptions\OrderLogicException;

/**
 * Handles customer-facing order operations.
 * Allows authenticated users to place orders and view their order history.
 */
class OrderController extends Controller
{
    /**
     * Store a newly created Order in storage (customer places an order).
     * This involves validating items, checking stock, calculating totals,
     * and saving the order and its items.
     *
     * @param  \IJIDeals\IJIOrderManagement\Http\Requests\StoreOrderRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreOrderRequest $request)
    {
        $user = $request->user(); // User is guaranteed by FormRequest authorize
        $userId = $user->id;
        Log::info('OrderController@store: Attempting to store new order.', ['user_id' => $userId, 'request_data' => $request->all()]);

        // Authorization handled by StoreOrderRequest->authorize()
        // Validation handled by StoreOrderRequest->rules()
        $validatedData = $request->validated();
        Log::debug('OrderController@store: Validation passed via FormRequest.', ['user_id' => $userId, 'validated_data' => $validatedData]);

        $shop = Shop::findOrFail($validatedData['shop_id']);
        $orderTotal = 0;
        $orderItemsData = [];

        DB::beginTransaction();
        Log::debug('OrderController@store: Transaction started.', ['user_id' => $userId]);
        try {
            foreach ($validatedData['items'] as $itemInput) {
                $quantity = $itemInput['quantity'];
                // $productInstance = null; // Not needed as it's scoped
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
                        Log::error('OrderController@store: Variation ID does not belong to shop.', ['variation_id' => $productInstance->id, 'shop_id' => $shop->id, 'user_id' => $userId]);
                        throw new ProductAssociationException("Variation ID {$productInstance->id} does not belong to shop ID {$shop->id}.");
                    }
                    if ($productInstance->stock_quantity < $quantity) {
                        Log::warning('OrderController@store: Insufficient stock for variant.', ['variation_id' => $productInstance->id, 'requested_quantity' => $quantity, 'available_stock' => $productInstance->stock_quantity, 'user_id' => $userId]);
                        throw new InsufficientStockException("Insufficient stock for variant: {$productInstance->masterProductVariation->sku}. Requested: {$quantity}, Available: {$productInstance->stock_quantity}");
                    }
                    $productInstance->decrement('stock_quantity', $quantity);

                    $priceAtPurchase = $productInstance->price; // Consider using effective_price if sales are possible
                    $productName = $productInstance->masterProductVariation->masterProduct->name;
                    $variantDetails = $productInstance->masterProductVariation->attributeOptions->mapWithKeys(fn($opt) => [$opt->attribute->name => $opt->value]);
                    $sku = $productInstance->masterProductVariation->sku ?? $productInstance->shop_sku_variant;
                    $shopProductId = $productInstance->shop_product_id;
                    $masterProductVariationId = $productInstance->master_product_variation_id;
                    $masterProductId = $productInstance->masterProductVariation->master_product_id;

                } elseif ($itemInput['type'] === 'shopproduct') {
                    $productInstance = ShopProduct::with('masterProduct')->findOrFail($itemInput['id']);

                    if ($productInstance->shop_id !== $shop->id) {
                        Log::error('OrderController@store: ShopProduct ID does not belong to shop.', ['shop_product_id' => $productInstance->id, 'shop_id' => $shop->id, 'user_id' => $userId]);
                        throw new ProductAssociationException("ShopProduct ID {$productInstance->id} does not belong to shop ID {$shop->id}.");
                    }
                    if ($productInstance->variations()->exists()) {
                        Log::warning('OrderController@store: Attempted to order simple product with variations.', ['shop_product_id' => $productInstance->id, 'user_id' => $userId]);
                         throw new OrderLogicException("Product ID {$productInstance->id} has variations and must be ordered by specific variant.");
                    }
                    if ($productInstance->stock_quantity < $quantity) {
                        Log::warning('OrderController@store: Insufficient stock for product.', ['shop_product_id' => $productInstance->id, 'requested_quantity' => $quantity, 'available_stock' => $productInstance->stock_quantity, 'user_id' => $userId]);
                        throw new InsufficientStockException("Insufficient stock for product: {$productInstance->masterProduct->name}. Requested: {$quantity}, Available: {$productInstance->stock_quantity}");
                    }
                    $productInstance->decrement('stock_quantity', $quantity);

                    $priceAtPurchase = $productInstance->price; // Consider using effective_price
                    $productName = $productInstance->masterProduct->name;
                    $sku = $productInstance->masterProduct->sku; // Or shop-specific SKU if available
                    $shopProductId = $productInstance->id;
                    $masterProductId = $productInstance->master_product_id;
                } else {
                    Log::error('OrderController@store: Invalid item type provided.', ['item_type' => $itemInput['type'], 'user_id' => $userId]);
                     throw new InvalidOrderItemTypeException("Invalid item type: {$itemInput['type']}");
                }

                $lineTotal = $priceAtPurchase * $quantity;
                $orderTotal += $lineTotal;

                $orderItemsData[] = [
                    'shop_product_id' => $shopProductId,
                    'master_product_variation_id' => $masterProductVariationId,
                    'master_product_id' => $masterProductId,
                    'product_name_at_purchase' => $productName,
                    'variant_details_at_purchase' => $variantDetails, // Stored as JSON
                    'sku_at_purchase' => $sku,
                    'quantity' => $quantity,
                    'price_at_purchase' => $priceAtPurchase,
                    'total_line_amount' => $lineTotal,
                ];
            }
            Log::debug('OrderController@store: Order items processed.', ['user_id' => $userId, 'item_count' => count($orderItemsData), 'calculated_total' => $orderTotal]);

            $order = Order::create([
                'shop_id' => $shop->id,
                'user_id' => $user->id,
                'status' => 'pending_payment', // Initial status
                'total_amount' => $orderTotal,
                'subtotal_amount' => $orderTotal, // Assuming no discounts/taxes applied yet
                'currency' => $shop->settings['currency'] ?? config('ijicommerce.defaults.currency', 'USD'), // Configurable default
                'billing_address' => $validatedData['billing_address'], // Stored as JSON
                'shipping_address' => $validatedData['shipping_address'], // Stored as JSON
                'payment_method' => 'placeholder_gateway', // This would come from payment processing
                'payment_status' => 'pending',
            ]);
            Log::info('OrderController@store: Order header created.', ['user_id' => $userId, 'order_id' => $order->id, 'order_number' => $order->order_number]);

            $order->items()->createMany($orderItemsData);
            Log::info('OrderController@store: Order items saved.', ['user_id' => $userId, 'order_id' => $order->id, 'item_count' => count($orderItemsData)]);

            DB::commit();
            Log::info('OrderController@store: Order created successfully, transaction committed.', ['user_id' => $userId, 'order_id' => $order->id, 'order_number' => $order->order_number]);

            // TODO: Dispatch OrderPlaced event
            // TODO: Trigger payment processing

            return response()->json($order->fresh()->load('items'), 201);

        } catch (InsufficientStockException | ProductAssociationException | InvalidOrderItemTypeException | OrderLogicException $e) {
            DB::rollBack();
            Log::warning('OrderController@store: Order creation failed due to business logic exception.', ['user_id' => $userId, 'error' => $e->getMessage()]);
            return response()->json(['message' => $e->getMessage()], 422); // 422 Unprocessable Entity for these types of errors
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('OrderController@store: Order creation failed due to unexpected error, transaction rolled back.', ['user_id' => $userId, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Order creation failed due to an unexpected error. Please try again.'], 500);
        }
    }

    /**
     * Display the specified Order for the authenticated user.
     * Ensures the user owns the order.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \IJIDeals\IJIOrderManagement\Models\Order  $order The Order instance via route model binding.
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
     * Display a paginated listing of orders for the authenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
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
