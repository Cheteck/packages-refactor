<?php

namespace IJIDeals\IJIShopListings\Http\Controllers;

use Illuminate\Http\Request; // Keep for indexMasterProducts, indexShopProducts, show, destroy, acknowledgeMasterProductUpdate
use Illuminate\Routing\Controller;
use IJIDeals\IJICommerce\Models\Shop;
use IJIDeals\IJIProductCatalog\Models\MasterProduct;
use IJIDeals\IJIShopListings\Models\ShopProduct;
// use IJIDeals\IJIShopListings\Models\ShopProductVariation; // Not directly used as a type-hint, remove
// use Illuminate\Validation\Rule; // No longer needed here
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use IJIDeals\IJIShopListings\Http\Requests\StoreShopProductRequest;
use IJIDeals\IJIShopListings\Http\Requests\UpdateShopProductRequest;

/**
 * Controller for managing shop-specific product listings (ShopProducts).
 * Handles how a Shop lists, prices, and manages stock for products from the MasterProduct catalog.
 */
class ShopProductController extends Controller
{
    /**
     * Display a listing of MasterProducts available for a specific Shop to list.
     * These are active MasterProducts not yet listed by the Shop.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \IJIDeals\IJICommerce\Models\Shop  $shop The Shop for which to list available MasterProducts.
     * @return \Illuminate\Http\JsonResponse
     */
    public function indexMasterProducts(Request $request, Shop $shop)
    {
        $userId = $request->user() ? $request->user()->id : null;
        Log::info('ShopProductController@indexMasterProducts: Fetching available master products for shop.', ['shop_id' => $shop->id, 'user_id' => $userId]);

        if ($request->user()->cannot('manageShopProducts', $shop)) {
            Log::warning('ShopProductController@indexMasterProducts: Unauthorized.', ['shop_id' => $shop->id, 'user_id' => $userId]);
            return response()->json(['message' => "Unauthorized to list available products for shop '{$shop->name}'."], 403);
        }

        $listedMasterProductIds = $shop->shopProducts()->pluck('master_product_id')->toArray();

        $query = MasterProduct::where('status', 'active')
            ->whereNotIn('id', $listedMasterProductIds)
            ->with(['brand:id,name', 'category:id,name', 'media']);

        if ($request->filled('name')) {
            $query->where('name', 'LIKE', '%' . $request->input('name') . '%');
        }
        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->input('brand_id'));
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        $availableMasterProducts = $query->orderBy('name')
                                     ->paginate($request->input('per_page', config('ijishoplistings.pagination.master_products', 20)));

        $availableMasterProducts->getCollection()->transform(function($product){
            $product->base_image_urls = $product->getMedia(config('ijiproductcatalog.media_collections.master_product_base_images', 'master_product_base_images'))->map(function ($media) {
                return ['id' => $media->id, 'original' => $media->getUrl(), 'thumb' => $media->getUrl('thumb')];
            });
            return $product;
        });
        Log::info('ShopProductController@indexMasterProducts: Available master products fetched.', ['shop_id' => $shop->id, 'user_id' => $userId, 'count' => $availableMasterProducts->count()]);
        return response()->json($availableMasterProducts);
    }

    /**
     * Display a paginated listing of the Shop's own product listings (ShopProducts).
     * Includes details of the linked MasterProduct and any ShopProductVariations.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \IJIDeals\IJICommerce\Models\Shop  $shop The Shop whose listings are to be displayed.
     * @return \Illuminate\Http\JsonResponse
     */
    public function indexShopProducts(Request $request, Shop $shop)
    {
        $userId = $request->user() ? $request->user()->id : null;
        Log::info('ShopProductController@indexShopProducts: Fetching shop products.', ['shop_id' => $shop->id, 'user_id' => $userId]);
        if ($request->user()->cannot('manageShopProducts', $shop)) {
             Log::warning('ShopProductController@indexShopProducts: Unauthorized.', ['shop_id' => $shop->id, 'user_id' => $userId]);
             return response()->json(['message' => "Unauthorized to list products for shop '{$shop->name}'."], 403);
        }

        $query = $shop->shopProducts()
            ->with([
                'masterProduct:id,name,slug',
                'masterProduct.brand:id,name',
                'masterProduct.category:id,name',
                'variations.masterProductVariation.attributeOptions.attribute',
                'variations.masterProductVariation.media',
                'media'
            ]);

        if ($request->filled('name')) {
            $query->whereHas('masterProduct', function ($q) use ($request) {
                $q->where('name', 'LIKE', '%' . $request->input('name') . '%');
            });
        }
         if ($request->filled('is_visible')) {
            $query->where('is_visible_in_shop', filter_var($request->input('is_visible'), FILTER_VALIDATE_BOOLEAN));
        }


        $shopProducts = $query->orderByDesc('created_at')
                             ->paginate($request->input('per_page', config('ijishoplistings.pagination.shop_products', 20)));

        $shopProducts->getCollection()->transform(function ($shopProduct) {
            if ($shopProduct->variations->isEmpty()) {
                $shopProduct->effective_price = $shopProduct->getEffectivePriceAttribute();
                $shopProduct->is_on_sale = $shopProduct->getIsOnSaleAttribute();
            } else {
                $shopProduct->variations->transform(function ($variation) {
                    $variation->effective_price = $variation->getEffectivePriceAttribute();
                    $variation->is_on_sale = $variation->getIsOnSaleAttribute();
                    if ($variation->masterProductVariation) { // Ensure relation is loaded
                        $variation->variant_image_url = $variation->masterProductVariation->getFirstMediaUrl(config('ijiproductcatalog.media_collections.master_product_variant_image', 'master_product_variant_images'), 'thumb');
                    }
                    return $variation;
                });
            }
            $shopProduct->shop_image_urls = $shopProduct->getMedia(config('ijishoplistings.media_collections.shop_product_additional_images', 'shop_product_additional_images'))->map(fn($media) => ['id' => $media->id, 'original' => $media->getUrl(), 'thumb' => $media->getUrl('thumb')]);
            return $shopProduct;
        });
        Log::info('ShopProductController@indexShopProducts: Shop products fetched.', ['shop_id' => $shop->id, 'user_id' => $userId, 'count' => $shopProducts->count()]);
        return response()->json($shopProducts);
    }


    /**
     * Store a new ShopProduct, effectively listing a MasterProduct in a specific Shop.
     * Handles shop-specific pricing, stock, images, and variations if applicable.
     *
     * @param  \IJIDeals\IJIShopListings\Http\Requests\StoreShopProductRequest  $request
     * @param  \IJIDeals\IJICommerce\Models\Shop  $shop The Shop to which the product is being listed.
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreShopProductRequest $request, Shop $shop)
    {
        $userId = $request->user()->id; // User is guaranteed by FormRequest
        Log::info('ShopProductController@store: Attempting to store new shop product.', ['shop_id' => $shop->id, 'user_id' => $userId, 'request_data' => $request->all()]);

        // Authorization and basic validation handled by StoreShopProductRequest
        $validatedData = $request->validated();
        Log::debug('ShopProductController@store: Validation passed via FormRequest.', ['validated_data' => $validatedData]);

        $masterProduct = MasterProduct::with('variations')->find($validatedData['master_product_id']);
        // Calculate master_version_hash based on relevant MasterProduct fields
        $masterVersionHash = md5(
            $masterProduct->name .
            $masterProduct->description .
            json_encode($masterProduct->specifications) .
            // Optionally include media signature if relevant for versioning
            $masterProduct->getMedia(config('ijiproductcatalog.media_collections.master_product_base_images', 'master_product_base_images'))->map(fn($m) => $m->uuid)->sort()->implode(',')
        );


        $shopProductInputData = [
            'master_product_id' => $masterProduct->id,
            'is_visible_in_shop' => $validatedData['is_visible_in_shop'] ?? true, // Default handled in FormRequest prepareForValidation
            'shop_specific_notes' => $validatedData['shop_specific_notes'] ?? null,
            'master_version_hash' => $masterVersionHash,
            'needs_review_by_shop' => false, // New listings are initially in sync
            'sale_price' => $validatedData['sale_price'] ?? null,
            'sale_start_date' => $validatedData['sale_start_date'] ?? null,
            'sale_end_date' => $validatedData['sale_end_date'] ?? null,
        ];

        // Handle price/stock for non-variation products or when variations are not provided by shop
        if ($masterProduct->variations->isEmpty() && empty($validatedData['variations'])) {
            if (!isset($validatedData['price']) || !isset($validatedData['stock_quantity'])) {
                 Log::error('ShopProductController@store: Price and stock are required for non-variation product.', ['shop_id' => $shop->id, 'master_product_id' => $masterProduct->id]);
                return response()->json(['message' => 'Price and stock quantity are required for this product.'], 422);
            }
            $shopProductInputData['price'] = $validatedData['price'];
            $shopProductInputData['stock_quantity'] = $validatedData['stock_quantity'];
        } elseif (empty($validatedData['variations']) && $masterProduct->variations->isNotEmpty()) {
            // Master has variations, but shop didn't provide any overrides. This case might need policy (e.g., require variation details).
            // For now, we'll allow it but log. Shop might manage variations later.
            Log::info('ShopProductController@store: Master product has variations, but no shop-specific variation data provided.', ['shop_id' => $shop->id, 'master_product_id' => $masterProduct->id]);
            // Price/stock on main ShopProduct might be null or default if variations are expected.
            $shopProductInputData['price'] = $validatedData['price'] ?? null; // Or set to 0 / some indicator
            $shopProductInputData['stock_quantity'] = $validatedData['stock_quantity'] ?? 0;
        } elseif (!empty($validatedData['variations'])) {
             // If variations are provided, the main price/stock might be optional or derived.
            $shopProductInputData['price'] = $validatedData['price'] ?? null;
            $shopProductInputData['stock_quantity'] = $validatedData['stock_quantity'] ?? 0;
        }


        DB::beginTransaction();
        try {
            $shopProduct = $shop->shopProducts()->create($shopProductInputData);

            if ($request->hasFile('shop_images')) {
                foreach ($request->file('shop_images') as $file) {
                    if ($file->isValid()) {
                        $shopProduct->addMedia($file)->toMediaCollection(config('ijishoplistings.media_collections.shop_product_additional_images', 'shop_product_additional_images'));
                    }
                }
                Log::info('ShopProductController@store: Shop images uploaded.', ['shop_product_id' => $shopProduct->id]);
            }

            if (!empty($validatedData['variations']) && $masterProduct->variations->isNotEmpty()) {
                foreach ($validatedData['variations'] as $variationInput) {
                    $masterVariation = $masterProduct->variations()->find($variationInput['master_product_variation_id']);
                    if (!$masterVariation) {
                        DB::rollBack();
                        Log::error('ShopProductController@store: Invalid master_product_variation_id provided.', ['shop_id' => $shop->id, 'master_product_variation_id' => $variationInput['master_product_variation_id']]);
                        return response()->json(['message' => "Invalid master_product_variation_id provided: {$variationInput['master_product_variation_id']}"], 422);
                    }
                    $shopProduct->variations()->create([
                        'master_product_variation_id' => $masterVariation->id,
                        'price' => $variationInput['price'],
                        'stock_quantity' => $variationInput['stock_quantity'],
                        'shop_sku_variant' => $variationInput['shop_sku_variant'] ?? null,
                        'sale_price' => $variationInput['sale_price'] ?? null,
                        'sale_start_date' => $variationInput['sale_start_date'] ?? null,
                        'sale_end_date' => $variationInput['sale_end_date'] ?? null,
                    ]);
                }
                Log::info('ShopProductController@store: Shop product variations created.', ['shop_product_id' => $shopProduct->id, 'count' => count($validatedData['variations'])]);
            }
            DB::commit();
            $shopProduct->refresh()->load(['masterProduct:id,name', 'variations.masterProductVariation.attributeOptions.attribute', 'media']);
            $shopProduct->shop_image_urls = $shopProduct->getMedia(config('ijishoplistings.media_collections.shop_product_additional_images', 'shop_product_additional_images'))->map(fn($media) => ['id' => $media->id, 'original' => $media->getUrl(), 'thumb' => $media->getUrl('thumb')]);

            if ($shopProduct->variations->isEmpty()) {
                $shopProduct->effective_price = $shopProduct->getEffectivePriceAttribute();
                $shopProduct->is_on_sale = $shopProduct->getIsOnSaleAttribute();
            }
            Log::info('ShopProductController@store: Shop product stored successfully.', ['shop_product_id' => $shopProduct->id, 'user_id' => $userId]);
            return response()->json($shopProduct, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ShopProductController@store: Failed to store shop product.', ['shop_id' => $shop->id, 'user_id' => $userId, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Failed to list product: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified ShopProduct.
     * Ensures the ShopProduct belongs to the given Shop.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \IJIDeals\IJICommerce\Models\Shop  $shop The parent Shop.
     * @param  \IJIDeals\IJIShopListings\Models\ShopProduct  $shopProduct The ShopProduct instance via route model binding.
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, Shop $shop, ShopProduct $shopProduct)
    {
        Log::info('ShopProductController: Showing shop product details.', ['shop_product_id' => $shopProduct->id, 'shop_id' => $shop->id, 'user_id' => Auth::id()]);
        if ($shopProduct->shop_id !== $shop->id) {
            Log::warning('ShopProductController: Shop product not found in this shop.', ['shop_product_id' => $shopProduct->id, 'shop_id' => $shop->id]);
            return response()->json(['message' => 'Product listing not found in this shop.'], 404);
        }
        if ($request->user()->cannot('manageShopProducts', $shop)) {
             Log::warning('ShopProductController: Unauthorized attempt to view shop product details.', ['shop_product_id' => $shopProduct->id, 'shop_id' => $shop->id, 'user_id' => Auth::id()]);
             return response()->json(['message' => "Unauthorized to view this product listing details for shop '{$shop->name}'."], 403);
        }

        $shopProduct->load([
            'masterProduct:id,name,description,slug',
            'masterProduct.brand:id,name',
            'masterProduct.category:id,name',
            'variations.masterProductVariation.attributeOptions.attribute',
            'variations.masterProductVariation.media',
            'media'
        ]);

        if ($shopProduct->variations->isEmpty()) {
            $shopProduct->effective_price = $shopProduct->getEffectivePriceAttribute();
            $shopProduct->is_on_sale = $shopProduct->getIsOnSaleAttribute();
        } else {
            $shopProduct->variations->transform(function ($variation) {
                $variation->effective_price = $variation->getEffectivePriceAttribute();
                $variation->is_on_sale = $variation->getIsOnSaleAttribute();
                $variation->variant_image_url = $variation->masterProductVariation->getFirstMediaUrl(config('ijiproductcatalog.media_collections.master_product_variant_image', 'master_product_variant_images'), 'thumb');
                return $variation;
            });
        }
        $shopProduct->shop_image_urls = $shopProduct->getMedia(config('ijishoplistings.media_collections.shop_product_additional_images', 'shop_product_additional_images'))->map(fn($media) => ['id' => $media->id, 'original' => $media->getUrl(), 'thumb' => $media->getUrl('thumb')]);
        return response()->json($shopProduct);
    }

    /**
     * Update the specified ShopProduct in storage.
     * Ensures the ShopProduct belongs to the given Shop.
     * Handles updates to pricing, stock, visibility, notes, and shop-specific images.
     * Allows acknowledging updates to the linked MasterProduct.
     *
     * @param  \IJIDeals\IJIShopListings\Http\Requests\UpdateShopProductRequest  $request
     * @param  \IJIDeals\IJICommerce\Models\Shop  $shop The parent Shop.
     * @param  \IJIDeals\IJIShopListings\Models\ShopProduct  $shopProduct The ShopProduct instance via route model binding.
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateShopProductRequest $request, Shop $shop, ShopProduct $shopProduct)
    {
        Log::info('ShopProductController: Attempting to update shop product.', ['shop_product_id' => $shopProduct->id, 'shop_id' => $shop->id, 'user_id' => Auth::id(), 'request_data' => $request->all()]);
        if ($shopProduct->shop_id !== $shop->id) {
            Log::warning('ShopProductController: Shop product not found in this shop during update.', ['shop_product_id' => $shopProduct->id, 'shop_id' => $shop->id]);
            return response()->json(['message' => 'Product listing not found in this shop.'], 404);
        }
        if ($request->user()->cannot('manageShopProducts', $shop)) {
             Log::warning('ShopProductController: Unauthorized attempt to update shop product.', ['shop_product_id' => $shopProduct->id, 'shop_id' => $shop->id, 'user_id' => Auth::id()]);
             return response()->json(['message' => "Unauthorized to update product listings for shop '{$shop->name}'."], 403);
        }

        $validated = $request->validate([
            'price' => 'sometimes|required_without:variations|numeric|min:0',
            'stock_quantity' => 'sometimes|required_without:variations|integer|min:0',
            'is_visible_in_shop' => 'sometimes|boolean',
            'shop_specific_notes' => 'nullable|string|max:5000',
            'shop_images' => 'nullable|array',
            'shop_images.*' => 'image|mimes:jpg,jpeg,png,gif,webp|max:2048',
            'removed_shop_image_ids' => 'nullable|array',
            'removed_shop_image_ids.*' => 'integer',
            'sale_price' => 'nullable|numeric|min:0|lt:price',
            'sale_start_date' => 'nullable|date|required_with:sale_price',
            'sale_end_date' => 'nullable|date|after_or_equal:sale_start_date',
        ]);

        if ($shopProduct->needs_review_by_shop && $request->has('acknowledge_master_update')) {
            if ($request->input('acknowledge_master_update')) {
                $validated['needs_review_by_shop'] = false;
                $masterProduct = $shopProduct->masterProduct;
                $validated['master_version_hash'] = md5($masterProduct->name . $masterProduct->description . json_encode($masterProduct->specifications));
                Log::info('ShopProductController: Master product update acknowledged.', ['shop_product_id' => $shopProduct->id]);
            } else {
                Log::info('ShopProductController: Master product update not acknowledged.', ['shop_product_id' => $shopProduct->id]);
            }
        }

        $shopProductDataToUpdate = collect($validated)->except(['shop_images', 'removed_shop_image_ids'])->toArray();
        $shopProduct->update($shopProductDataToUpdate);

        if ($request->hasFile('shop_images')) {
            foreach ($request->file('shop_images') as $file) {
                if ($file->isValid()) {
                    $shopProduct->addMedia($file)->toMediaCollection(config('ijishoplistings.media_collections.shop_product_additional_images', 'shop_product_additional_images'));
                }
            }
            Log::info('ShopProductController: New shop images uploaded.', ['shop_product_id' => $shopProduct->id]);
        }
        if ($request->filled('removed_shop_image_ids')) {
            foreach ($validated['removed_shop_image_ids'] as $mediaId) {
                $mediaItem = $shopProduct->getMedia(config('ijishoplistings.media_collections.shop_product_additional_images', 'shop_product_additional_images'))->find($mediaId);
                if ($mediaItem) {
                    $mediaItem->delete();
                    Log::info('ShopProductController: Removed shop image.', ['shop_product_id' => $shopProduct->id, 'media_id' => $mediaId]);
                }
            }
        }

        $shopProduct->refresh()->load(['masterProduct:id,name', 'variations', 'media']);
        $shopProduct->shop_image_urls = $shopProduct->getMedia(config('ijishoplistings.media_collections.shop_product_additional_images', 'shop_product_additional_images'))->map(fn($media) => ['id' => $media->id, 'original' => $media->getUrl(), 'thumb' => $media->getUrl('thumb')]);
        Log::info('ShopProductController: Shop product updated successfully.', ['shop_product_id' => $shopProduct->id]);
        return response()->json($shopProduct);
    }

    /**
     * Remove the specified ShopProduct (de-list product from the Shop).
     * Ensures the ShopProduct belongs to the given Shop.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \IJIDeals\IJICommerce\Models\Shop  $shop The parent Shop.
     * @param  \IJIDeals\IJIShopListings\Models\ShopProduct  $shopProduct The ShopProduct instance via route model binding.
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, Shop $shop, ShopProduct $shopProduct)
    {
        Log::info('ShopProductController: Attempting to delete shop product.', ['shop_product_id' => $shopProduct->id, 'shop_id' => $shop->id, 'user_id' => Auth::id()]);
        if ($shopProduct->shop_id !== $shop->id) {
            Log::warning('ShopProductController: Shop product not found in this shop during delete.', ['shop_product_id' => $shopProduct->id, 'shop_id' => $shop->id]);
            return response()->json(['message' => 'Product listing not found in this shop.'], 404);
        }
        if ($request->user()->cannot('manageShopProducts', $shop)) {
             Log::warning('ShopProductController: Unauthorized attempt to delete shop product.', ['shop_product_id' => $shopProduct->id, 'shop_id' => $shop->id, 'user_id' => Auth::id()]);
             return response()->json(['message' => "Unauthorized to remove product listings from shop '{$shop->name}'."], 403);
        }

        $shopProduct->delete();
        Log::info('ShopProductController: Shop product deleted successfully.', ['shop_product_id' => $shopProduct->id]);
        return response()->json(['message' => 'Product listing removed from shop successfully.']);
    }

    /**
     * Allows a shop manager to acknowledge updates made to a MasterProduct.
     * This clears the 'needs_review_by_shop' flag and potentially makes the product visible again.
     * Ensures the ShopProduct belongs to the given Shop.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \IJIDeals\IJICommerce\Models\Shop  $shop The parent Shop.
     * @param  \IJIDeals\IJIShopListings\Models\ShopProduct  $shopProduct The ShopProduct instance via route model binding.
     * @return \Illuminate\Http\JsonResponse
     */
    public function acknowledgeMasterProductUpdate(Request $request, Shop $shop, ShopProduct $shopProduct)
    {
        Log::info('ShopProductController: Attempting to acknowledge master product update.', ['shop_product_id' => $shopProduct->id, 'shop_id' => $shop->id, 'user_id' => Auth::id()]);
        if ($shopProduct->shop_id !== $shop->id) {
            Log::warning('ShopProductController: Shop product not found in this shop during acknowledge.', ['shop_product_id' => $shopProduct->id, 'shop_id' => $shop->id]);
            return response()->json(['message' => 'Product listing not found in this shop.'], 404);
        }
        if ($request->user()->cannot('manageShopProducts', $shop)) {
            Log::warning('ShopProductController: Unauthorized attempt to acknowledge master product update.', ['shop_product_id' => $shopProduct->id, 'shop_id' => $shop->id, 'user_id' => Auth::id()]);
            return response()->json(['message' => "Unauthorized action for shop '{$shop->name}'."], 403);
        }

        if (!$shopProduct->needs_review_by_shop) {
            Log::info('ShopProductController: Product listing does not require review.', ['shop_product_id' => $shopProduct->id]);
            return response()->json(['message' => 'This product listing does not currently require review.'], 400);
        }

        $masterProduct = $shopProduct->masterProduct;
        $currentMasterVersionHash = md5($masterProduct->name . $masterProduct->description . json_encode($masterProduct->specifications));

        $shopProduct->update([
            'needs_review_by_shop' => false,
            'is_visible_in_shop' => true,
            'master_version_hash' => $currentMasterVersionHash,
        ]);

        Log::info('ShopProductController: Master product changes acknowledged and listing activated.', ['shop_product_id' => $shopProduct->id]);
        return response()->json([
            'message' => 'Master product changes acknowledged. Product listing is now active.',
            'shop_product' => $shopProduct->fresh()->load('masterProduct:id,name'),
        ]);
    }
}
