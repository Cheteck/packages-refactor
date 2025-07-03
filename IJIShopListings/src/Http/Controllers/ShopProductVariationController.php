<?php

namespace IJIDeals\IJIShopListings\Http\Controllers;

// use Illuminate\Http\Request; // No longer needed if only update method uses FormRequest
use Illuminate\Routing\Controller;
use IJIDeals\IJICommerce\Models\Shop;
use IJIDeals\IJIShopListings\Models\ShopProduct;
use IJIDeals\IJIShopListings\Models\ShopProductVariation;
use Illuminate\Validation\Rule; // No longer needed here
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth; // Keep for user ID or use $request->user()
use IJIDeals\IJIShopListings\Http\Requests\UpdateShopProductVariationRequest;

/**
 * Controller for managing shop-specific details of product variations (ShopProductVariations).
 * This typically involves updating price, stock, and SKU for a variation listed by a shop.
 */
class ShopProductVariationController extends Controller
{
    /**
     * Update the specified ShopProductVariation for a given ShopProduct within a Shop.
     * Manages shop-specific price, stock, SKU, and sale details for a particular variant.
     *
     * @param  \IJIDeals\IJIShopListings\Http\Requests\UpdateShopProductVariationRequest  $request
     * @param  \IJIDeals\IJICommerce\Models\Shop  $shop The parent Shop.
     * @param  \IJIDeals\IJIShopListings\Models\ShopProduct  $shopProduct The parent ShopProduct.
     * @param  \IJIDeals\IJIShopListings\Models\ShopProductVariation  $variation The ShopProductVariation instance via route model binding.
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateShopProductVariationRequest $request, Shop $shop, ShopProduct $shopProduct, ShopProductVariation $variation)
    {
        $userId = $request->user() ? $request->user()->id : (Auth::id() ?? null);
        Log::info('ShopProductVariationController@update: Attempting to update shop product variation.', [
            'variation_id' => $variation->id,
            'shop_product_id' => $shopProduct->id,
            'shop_id' => $shop->id,
            'user_id' => $userId,
            'request_data' => $request->all()
        ]);

        // Authorization handled by UpdateShopProductVariationRequest->authorize()

        // Validate that the variation belongs to the shopProduct, which belongs to the shop
        if ($shopProduct->shop_id !== $shop->id || $variation->shop_product_id !== $shopProduct->id) {
            Log::warning('ShopProductVariationController@update: Variation not found for this shop product.', ['variation_id' => $variation->id, 'shop_product_id' => $shopProduct->id, 'shop_id' => $shop->id, 'user_id' => $userId]);
            return response()->json(['message' => 'Variation not found for this shop product.'], 404);
        }

        // Validation (including custom sale_price check) handled by UpdateShopProductVariationRequest
        $validatedData = $request->validated();
        Log::debug('ShopProductVariationController@update: Validation passed via FormRequest.', ['validated_data' => $validatedData]);

        try {
            $variation->update($validatedData);

            $variation->refresh()->load('masterProductVariation.attributeOptions.attribute');
            // Recalculate these after update for the response
            $variation->effective_price = $variation->getEffectivePriceAttribute();
            $variation->is_on_sale = $variation->getIsOnSaleAttribute();

            Log::info('ShopProductVariationController@update: Shop product variation updated successfully.', ['variation_id' => $variation->id, 'user_id' => $userId]);
            return response()->json($variation);
        } catch (\Exception $e) {
            Log::error('ShopProductVariationController@update: Error updating shop product variation.', [
                'variation_id' => $variation->id,
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Error updating shop product variation.'], 500);
        }
    }
}
