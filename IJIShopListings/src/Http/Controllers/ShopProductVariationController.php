<?php

namespace IJIDeals\IJIShopListings\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use IJIDeals\IJICommerce\Models\Shop;
use IJIDeals\IJIShopListings\Models\ShopProduct;
use IJIDeals\IJIShopListings\Models\ShopProductVariation;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class ShopProductVariationController extends Controller
{
    /**
     * Update the specified ShopProductVariation for a ShopProduct.
     * (Manages price, stock for a specific variant listed by a shop)
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \IJIDeals\IJICommerce\Models\Shop  $shop
     * @param  \IJIDeals\IJIShopListings\Models\ShopProduct  $shopProduct
     * @param  \IJIDeals\IJIShopListings\Models\ShopProductVariation  $variation The ShopProductVariation instance
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Shop $shop, ShopProduct $shopProduct, ShopProductVariation $variation)
    {
        Log::info('ShopProductVariationController: Attempting to update shop product variation.', ['variation_id' => $variation->id, 'shop_product_id' => $shopProduct->id, 'shop_id' => $shop->id, 'user_id' => Auth::id(), 'request_data' => $request->all()]);
        // Authorization: Ensure user can manage products for this shop
        if ($request->user()->cannot('manageShopProducts', $shop)) {
            Log::warning('ShopProductVariationController: Unauthorized attempt to update shop product variation.', ['variation_id' => $variation->id, 'shop_id' => $shop->id, 'user_id' => Auth::id()]);
            return response()->json(['message' => "Unauthorized to manage products for shop '{$shop->name}'."], 403);
        }

        // Validate that the variation belongs to the shopProduct, which belongs to the shop
        if ($shopProduct->shop_id !== $shop->id || $variation->shop_product_id !== $shopProduct->id) {
            Log::warning('ShopProductVariationController: Variation not found for this shop product.', ['variation_id' => $variation->id, 'shop_product_id' => $shopProduct->id, 'shop_id' => $shop->id]);
            return response()->json(['message' => 'Variation not found for this shop product.'], 404);
        }

        $validated = $request->validate([
            'price' => 'sometimes|required|numeric|min:0',
            'stock_quantity' => 'sometimes|required|integer|min:0',
            'shop_sku_variant' => 'nullable|string|max:255',
            'sale_price' => 'nullable|numeric|min:0',
            'sale_start_date' => 'nullable|date|required_with:sale_price',
            'sale_end_date' => 'nullable|date|after_or_equal:sale_start_date',
        ]);

        if (isset($validated['sale_price']) && $validated['sale_price'] !== null && isset($validated['price']) && $validated['sale_price'] >= $validated['price']) {
             Log::warning('ShopProductVariationController: Sale price not less than regular price.', ['variation_id' => $variation->id, 'sale_price' => $validated['sale_price'], 'price' => $validated['price']]);
             return response()->json(['errors' => ['sale_price' => ['Sale price must be less than the regular price.']]], 422);
        }
        if (isset($validated['sale_price']) && $validated['sale_price'] !== null && !isset($validated['price']) && $variation->price !== null && $validated['sale_price'] >= $variation->price){
             Log::warning('ShopProductVariationController: Sale price not less than existing regular price.', ['variation_id' => $variation->id, 'sale_price' => $validated['sale_price'], 'price' => $variation->price]);
             return response()->json(['errors' => ['sale_price' => ['Sale price must be less than the regular price.']]], 422);
        }

        $variation->update($validated);

        $variation->refresh()->load('masterProductVariation.attributeOptions.attribute');
        $variation->effective_price = $variation->getEffectivePriceAttribute();
        $variation->is_on_sale = $variation->getIsOnSaleAttribute();

        Log::info('ShopProductVariationController: Shop product variation updated successfully.', ['variation_id' => $variation->id]);
        return response()->json($variation);
    }
}
