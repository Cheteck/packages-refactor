<?php

namespace IJIDeals\IJIShopListings\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use IJIDeals\IJICommerce\Models\Shop;
use IJIDeals\IJIProductCatalog\Models\MasterProduct;
use IJIDeals\IJIShopListings\Models\ShopProduct;
use IJIDeals\IJIShopListings\Models\ShopProductVariation;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ShopProductController extends Controller
{
    /**
     * Display a listing of master products available for a shop to sell.
     * (i.e., active MasterProducts not yet listed by this specific shop)
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \IJIDeals\IJICommerce\Models\Shop  $shop
     * @return \Illuminate\Http\JsonResponse
     */
    public function indexMasterProducts(Request $request, Shop $shop)
    {
        Log::info('ShopProductController: Fetching available master products for shop.', ['shop_id' => $shop->id, 'user_id' => Auth::id()]);
        // Policy: Can user 'manage products' for this shop?
        if ($request->user()->cannot('manageShopProducts', $shop)) {
            Log::warning('ShopProductController: Unauthorized attempt to list available master products.', ['shop_id' => $shop->id, 'user_id' => Auth::id()]);
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

        $availableMasterProducts = $query->orderBy('name')->paginate($request->input('per_page', 20));

        $availableMasterProducts->getCollection()->transform(function($product){
            $product->base_image_urls = $product->getMedia(config('ijiproductcatalog.media_collections.master_product_base_images', 'master_product_base_images'))->map(function ($media) {
                return ['id' => $media->id, 'original' => $media->getUrl(), 'thumb' => $media->getUrl('thumb')];
            });
            return $product;
        });
        Log::info('ShopProductController: Available master products fetched successfully.', ['shop_id' => $shop->id, 'count' => $availableMasterProducts->count()]);
        return response()->json($availableMasterProducts);
    }

    /**
     * Display a listing of the shop's own product listings (ShopProducts).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \IJIDeals\IJICommerce\Models\Shop  $shop
     * @return \Illuminate\Http\JsonResponse
     */
    public function indexShopProducts(Request $request, Shop $shop)
    {
        Log::info('ShopProductController: Fetching shop products for shop.', ['shop_id' => $shop->id, 'user_id' => Auth::id()]);
        if ($request->user()->cannot('manageShopProducts', $shop)) {
             Log::warning('ShopProductController: Unauthorized attempt to list shop products.', ['shop_id' => $shop->id, 'user_id' => Auth::id()]);
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

        $shopProducts = $query->orderBy('created_at', 'desc')->paginate($request->input('per_page', 20));

        $shopProducts->getCollection()->transform(function ($shopProduct) {
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
            return $shopProduct;
        });
        Log::info('ShopProductController: Shop products fetched successfully.', ['shop_id' => $shop->id, 'count' => $shopProducts->count()]);
        return response()->json($shopProducts);
    }


    /**
     * Store a new ShopProduct ("Sell This" action).
     * Links a MasterProduct to a Shop with shop-specific details.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \IJIDeals\IJICommerce\Models\Shop  $shop
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, Shop $shop)
    {
        Log::info('ShopProductController: Attempting to store new shop product.', ['shop_id' => $shop->id, 'user_id' => Auth::id(), 'request_data' => $request->all()]);
        if ($request->user()->cannot('manageShopProducts', $shop)) {
             Log::warning('ShopProductController: Unauthorized attempt to add product to shop.', ['shop_id' => $shop->id, 'user_id' => Auth::id()]);
             return response()->json(['message' => "Unauthorized to add products to shop '{$shop->name}'."], 403);
        }

        $validated = $request->validate([
            'master_product_id' => [
                'required',
                Rule::exists((new MasterProduct())->getTable(), 'id')->where('status', 'active'),
                Rule::unique((new ShopProduct())->getTable())->where(function ($query) use ($shop) {
                    return $query->where('shop_id', $shop->id);
                })->setMessage('This product is already listed in your shop.')
            ],
            'price' => 'required_without:variations|numeric|min:0',
            'stock_quantity' => 'required_without:variations|integer|min:0',
            'is_visible_in_shop' => 'sometimes|boolean',
            'shop_specific_notes' => 'nullable|string|max:5000',
            'shop_images' => 'nullable|array',
            'shop_images.*' => 'image|mimes:jpg,jpeg,png,gif,webp|max:2048',
            'sale_price' => 'nullable|numeric|min:0|lt:price',
            'sale_start_date' => 'nullable|date|required_with:sale_price',
            'sale_end_date' => 'nullable|date|after_or_equal:sale_start_date',
            'variations' => 'nullable|array',
            'variations.*.master_product_variation_id' => ['required_with:variations', 'integer', Rule::exists(config('ijiproductcatalog.tables.master_product_variations','master_product_variations'), 'id')],
            'variations.*.price' => 'required_with:variations|numeric|min:0',
            'variations.*.stock_quantity' => 'required_with:variations|integer|min:0',
            'variations.*.shop_sku_variant' => 'nullable|string|max:255',
            'variations.*.sale_price' => 'nullable|numeric|min:0',
            'variations.*.sale_start_date' => 'nullable|date|required_with:variations.*.sale_price',
            'variations.*.sale_end_date' => 'nullable|date|after_or_equal:variations.*.sale_start_date',
        ]);

        $masterProduct = MasterProduct::with('variations')->find($validated['master_product_id']);
        $masterVersionHash = md5($masterProduct->name . $masterProduct->description . json_encode($masterProduct->specifications));

        $shopProductData = [
            'master_product_id' => $masterProduct->id,
            'is_visible_in_shop' => $validated['is_visible_in_shop'] ?? true,
            'shop_specific_notes' => $validated['shop_specific_notes'] ?? null,
            'master_version_hash' => $masterVersionHash,
            'needs_review_by_shop' => false,
            'sale_price' => $validated['sale_price'] ?? null,
            'sale_start_date' => $validated['sale_start_date'] ?? null,
            'sale_end_date' => $validated['sale_end_date'] ?? null,
        ];

        if ($masterProduct->variations->isEmpty() && empty($validated['variations'])) {
            $shopProductData['price'] = $validated['price'];
            $shopProductData['stock_quantity'] = $validated['stock_quantity'];
        } else if (!empty($validated['variations'])) {
            $shopProductData['price'] = $validated['price'] ?? null;
            $shopProductData['stock_quantity'] = $validated['stock_quantity'] ?? 0;
        } else {
             $shopProductData['price'] = $validated['price'] ?? null;
             $shopProductData['stock_quantity'] = $validated['stock_quantity'] ?? 0;
        }

        $shopProduct = null;
        DB::beginTransaction();
        try {
            $shopProductDataForCreate = collect($shopProductData)->except(['shop_images'])->toArray();
            $shopProduct = $shop->shopProducts()->create($shopProductDataForCreate);

            if ($request->hasFile('shop_images')) {
                foreach ($request->file('shop_images') as $file) {
                    if ($file->isValid()) {
                        $shopProduct->addMedia($file)->toMediaCollection(config('ijishoplistings.media_collections.shop_product_additional_images', 'shop_product_additional_images'));
                    }
                }
                Log::info('ShopProductController: Shop images uploaded for shop product.', ['shop_product_id' => $shopProduct->id]);
            }

            if (!empty($validated['variations']) && $masterProduct->variations->isNotEmpty()) {
                foreach ($validated['variations'] as $variationInput) {
                    $masterVariation = $masterProduct->variations()->find($variationInput['master_product_variation_id']);
                    if (!$masterVariation) {
                        DB::rollBack();
                        Log::error('ShopProductController: Invalid master_product_variation_id provided during store.', ['shop_id' => $shop->id, 'master_product_variation_id' => $variationInput['master_product_variation_id']]);
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
                Log::info('ShopProductController: Shop product variations created.', ['shop_product_id' => $shopProduct->id, 'count' => count($validated['variations'])]);
            }
            DB::commit();
            $shopProduct->load(['masterProduct:id,name', 'variations.masterProductVariation.attributeOptions.attribute', 'media']);
            $shopProduct->shop_image_urls = $shopProduct->getMedia(config('ijishoplistings.media_collections.shop_product_additional_images', 'shop_product_additional_images'))->map(fn($media) => ['original' => $media->getUrl(), 'thumb' => $media->getUrl('thumb')]);

            if ($shopProduct->variations->isEmpty()) {
                $shopProduct->effective_price = $shopProduct->getEffectivePriceAttribute();
                $shopProduct->is_on_sale = $shopProduct->getIsOnSaleAttribute();
            }
            Log::info('ShopProductController: Shop product stored successfully.', ['shop_product_id' => $shopProduct->id]);
            return response()->json($shopProduct, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ShopProductController: Failed to store shop product.', ['shop_id' => $shop->id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to list product: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified ShopProduct.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \IJIDeals\IJICommerce\Models\Shop  $shop
     * @param  \IJIDeals\IJIShopListings\Models\ShopProduct  $shopProduct
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
     * Update the specified ShopProduct.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \IJIDeals\IJICommerce\Models\Shop  $shop
     * @param  \IJIDeals\IJIShopListings\Models\ShopProduct  $shopProduct
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Shop $shop, ShopProduct $shopProduct)
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
     * Remove the specified ShopProduct (de-list product from shop).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \IJIDeals\IJICommerce\Models\Shop  $shop
     * @param  \IJIDeals\IJIShopListings\Models\ShopProduct  $shopProduct
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
     * Endpoint for shop admin to acknowledge/review master product changes.
     *
     * @param \Illuminate\Http\Request $request
     * @param Shop $shop
     * @param ShopProduct $shopProduct
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
