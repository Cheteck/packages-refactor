<?php

namespace IJIDeals\IJIProductCatalog\Http\Controllers\Admin;

use Illuminate\Http\Request; // Keep for index, show, destroy
use Illuminate\Routing\Controller;
use IJIDeals\IJIProductCatalog\Models\MasterProduct;
use IJIDeals\IJIProductCatalog\Models\Brand;
use IJIDeals\IJIProductCatalog\Models\Category;
use IJIDeals\IJIProductCatalog\Models\ProductProposal;
use Illuminate\Validation\Rule; // No longer needed here
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use IJIDeals\IJIProductCatalog\Http\Requests\Admin\StoreMasterProductRequest;
use IJIDeals\IJIProductCatalog\Http\Requests\Admin\UpdateMasterProductRequest;

/**
 * Admin controller for managing canonical Master Products.
 * Handles CRUD operations, media management, and potential impacts on shop listings.
 */
class MasterProductController extends Controller
{
    public function __construct()
    {
        // Log::debug('Admin MasterProductController constructed.');
    }

    /**
     * Display a paginated listing of Master Products with filtering options.
     * Includes base image URLs.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $adminUserId = $request->user() ? $request->user()->id : (Auth::id() ?? null);
        Log::debug('Admin MasterProductController@index: Fetching master products.', ['admin_user_id' => $adminUserId, 'filters' => $request->query()]);

        if ($request->user() && $request->user()->cannot('viewAny', MasterProduct::class)) {
            Log::warning('Admin MasterProductController@index: Authorization failed.', ['admin_user_id' => $adminUserId, 'action' => 'viewAny']);
            return response()->json(['message' => 'Unauthorized to list master products.'], 403);
        }

        $query = MasterProduct::with(['brand:id,name', 'category:id,name']);

        if ($request->filled('name')) {
            $query->where('name', 'LIKE', '%' . $request->input('name') . '%');
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->input('brand_id'));
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        $masterProducts = $query->orderByDesc('updated_at')->paginate($request->input('per_page', config('ijiproductcatalog.pagination.admin_master_products', 20)));

        $masterProducts->getCollection()->transform(function ($product) {
            $product->base_image_urls = $product->getMedia(config('ijiproductcatalog.media_collections.master_product_base_images', 'master_product_base_images'))->map(function ($media) {
                return ['id' => $media->id, 'original' => $media->getUrl(), 'thumb' => $media->getUrl('thumb'), 'showcase' => $media->getUrl('showcase')];
            });
            return $product;
        });
        Log::info('Admin MasterProductController@index: Master products fetched successfully.', ['admin_user_id' => $adminUserId, 'count' => $masterProducts->count(), 'total' => $masterProducts->total()]);
        return response()->json($masterProducts);
    }

    /**
     * Store a newly created Master Product in storage.
     * Handles upload of base images.
     *
     * @param  \IJIDeals\IJIProductCatalog\Http\Requests\Admin\StoreMasterProductRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreMasterProductRequest $request)
    {
        $adminUserId = $request->user() ? $request->user()->id : (Auth::id() ?? null);
        Log::debug('Admin MasterProductController@store: Attempting to store new master product.', ['admin_user_id' => $adminUserId, 'request_data' => $request->all()]);

        // Authorization handled by StoreMasterProductRequest->authorize()
        $validatedData = $request->validated();
        Log::debug('Admin MasterProductController@store: Validation passed via FormRequest.', ['admin_user_id' => $adminUserId, 'validated_data' => $validatedData]);

        $masterProductData = collect($validatedData)->except(['base_images'])->toArray();

        try {
            $masterProduct = MasterProduct::create($masterProductData);
            Log::info('Admin MasterProductController@store: Master product created in database.', ['admin_user_id' => $adminUserId, 'master_product_id' => $masterProduct->id]);

            if ($request->hasFile('base_images')) {
                $imageCount = 0;
                foreach ($request->file('base_images') as $file) {
                    if ($file->isValid()) {
                        $masterProduct->addMedia($file)->toMediaCollection(config('ijiproductcatalog.media_collections.master_product_base_images', 'master_product_base_images'));
                        $imageCount++;
                    }
                }
                Log::info('Admin MasterProductController@store: Base images uploaded.', ['admin_user_id' => $adminUserId, 'master_product_id' => $masterProduct->id, 'image_count' => $imageCount]);
            }

            Log::info('Admin MasterProductController@store: Master product stored successfully.', ['admin_user_id' => $adminUserId, 'master_product_id' => $masterProduct->id]);
            return response()->json($masterProduct->fresh()->load(['brand:id,name', 'category:id,name']), 201);
        } catch (\Exception $e) {
            Log::error('Admin MasterProductController@store: Error storing master product.', [
                'admin_user_id' => $adminUserId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Error storing master product. Please try again.'], 500);
        }
    }

    /**
     * Display the specified Master Product.
     * Includes brand, category, product proposal (if any), and base image URLs.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \IJIDeals\IJIProductCatalog\Models\MasterProduct  $masterProduct The MasterProduct instance via route model binding.
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, MasterProduct $masterProduct)
    {
        $adminUserId = $request->user() ? $request->user()->id : (Auth::id() ?? null);
        Log::debug('Admin MasterProductController@show: Showing master product details.', ['admin_user_id' => $adminUserId, 'master_product_id' => $masterProduct->id]);

        if ($request->user() && $request->user()->cannot('view', $masterProduct)) {
            Log::warning('Admin MasterProductController@show: Authorization failed.', ['admin_user_id' => $adminUserId, 'master_product_id' => $masterProduct->id, 'action' => 'view']);
            return response()->json(['message' => 'Unauthorized to view this master product.'], 403);
        }
        $masterProduct->load(['brand:id,name', 'category:id,name', 'productProposal:id,shop_id']);
        $masterProduct->base_image_urls = $masterProduct->getMedia(config('ijiproductcatalog.media_collections.master_product_base_images', 'master_product_base_images'))->map(function ($media) {
            return ['id' => $media->id, 'original' => $media->getUrl(), 'thumb' => $media->getUrl('thumb'), 'showcase' => $media->getUrl('showcase')];
        });
        Log::info('Admin MasterProductController@show: Successfully fetched master product details.', ['admin_user_id' => $adminUserId, 'master_product_id' => $masterProduct->id]);
        return response()->json($masterProduct);
    }

    /**
     * Update the specified Master Product in storage.
     * Handles updates to data, base images (new uploads and removals).
     * If significant data or media changes occur for an 'active' product,
     * it marks linked ShopProducts for review.
     *
     * @param  \IJIDeals\IJIProductCatalog\Http\Requests\Admin\UpdateMasterProductRequest  $request
     * @param  \IJIDeals\IJIProductCatalog\Models\MasterProduct  $masterProduct The MasterProduct instance via route model binding.
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateMasterProductRequest $request, MasterProduct $masterProduct)
    {
        $adminUserId = $request->user() ? $request->user()->id : (Auth::id() ?? null);
        Log::debug('Admin MasterProductController@update: Attempting to update master product.', ['admin_user_id' => $adminUserId, 'master_product_id' => $masterProduct->id, 'request_data' => $request->all()]);

        // Authorization handled by UpdateMasterProductRequest->authorize()
        $validatedData = $request->validated();
        Log::debug('Admin MasterProductController@update: Validation passed via FormRequest.', ['admin_user_id' => $adminUserId, 'validated_data' => $validatedData]);

        $masterProductData = collect($validatedData)->except(['base_images', 'removed_media_ids'])->toArray();
        $significantDataFields = ['name', 'description', 'specifications'];

        try {
            $masterProduct->update($masterProductData);
            Log::info('Admin MasterProductController@update: Master product data updated in database.', ['admin_user_id' => $adminUserId, 'master_product_id' => $masterProduct->id]);

            $mediaChanged = false;
            if ($request->hasFile('base_images')) {
                $newImageCount = 0;
                foreach ($request->file('base_images') as $file) {
                    if ($file->isValid()) {
                        $masterProduct->addMedia($file)->toMediaCollection(config('ijiproductcatalog.media_collections.master_product_base_images', 'master_product_base_images'));
                        $mediaChanged = true;
                        $newImageCount++;
                    }
                }
                Log::info('Admin MasterProductController@update: New base images uploaded.', ['admin_user_id' => $adminUserId, 'master_product_id' => $masterProduct->id, 'new_image_count' => $newImageCount]);
            }

            if ($request->filled('removed_media_ids') && isset($validatedData['removed_media_ids'])) {
                $removedCount = 0;
                foreach ($validatedData['removed_media_ids'] as $mediaId) {
                    $mediaItem = $masterProduct->getMedia(config('ijiproductcatalog.media_collections.master_product_base_images', 'master_product_base_images'))->find($mediaId);
                    if ($mediaItem) {
                        $mediaItem->delete();
                        $mediaChanged = true;
                        $removedCount++;
                    }
                }
                 if ($removedCount > 0) {
                    Log::info('Admin MasterProductController@update: Removed media items.', ['admin_user_id' => $adminUserId, 'master_product_id' => $masterProduct->id, 'removed_ids' => $validatedData['removed_media_ids'], 'removed_count' => $removedCount]);
                }
            }

            $wasSignificantlyDataChanged = $masterProduct->wasChanged($significantDataFields);

            if ($masterProduct->status === 'active' && ($wasSignificantlyDataChanged || $mediaChanged)) {
                $masterProduct->refresh();
                $newHashPayload = $masterProduct->only($significantDataFields);
                $newHashPayload['media_signature'] = $masterProduct->getMedia(config('ijiproductcatalog.media_collections.master_product_base_images', 'master_product_base_images'))
                                                        ->map(fn($media) => $media->uuid)
                                                        ->sort()
                                                        ->implode(',');
                $newHash = md5(serialize($newHashPayload));

                $shopProductsToUpdateQuery = $masterProduct->shopProducts();

                $affectedShopProducts = $shopProductsToUpdateQuery->where(function ($query) use ($newHash) {
                    $query->where('master_version_hash', '!=', $newHash)
                          ->orWhereNull('master_version_hash');
                })->orWhere('needs_review_by_shop', false)
                  ->get();

                if ($affectedShopProducts->isNotEmpty()) {
                    Log::info('Admin MasterProductController@update: Master product changes require shop product review.', ['admin_user_id' => $adminUserId, 'master_product_id' => $masterProduct->id, 'affected_shop_product_count' => $affectedShopProducts->count()]);
                    foreach ($affectedShopProducts as $shopProduct) {
                        $shopProduct->update([
                            'master_version_hash' => $newHash,
                            'needs_review_by_shop' => true,
                            'is_visible_in_shop' => false,
                        ]);
                        Log::debug('Admin MasterProductController@update: Shop product marked for review.', ['admin_user_id' => $adminUserId, 'shop_product_id' => $shopProduct->id, 'master_product_id' => $masterProduct->id]);
                        // TODO: Dispatch event/notification to shop owner
                    }
                }
            }

            $masterProduct->refresh();
            $masterProduct->base_image_urls = $masterProduct->getMedia(config('ijiproductcatalog.media_collections.master_product_base_images', 'master_product_base_images'))->map(function ($media) {
                return ['id' => $media->id, 'original' => $media->getUrl(), 'thumb' => $media->getUrl('thumb'), 'showcase' => $media->getUrl('showcase')];
            });
            Log::info('Admin MasterProductController@update: Master product updated successfully.', ['admin_user_id' => $adminUserId, 'master_product_id' => $masterProduct->id]);
            return response()->json($masterProduct->load(['brand:id,name', 'category:id,name', 'productProposal:id,shop_id']));

        } catch (\Exception $e) {
            Log::error('Admin MasterProductController@update: Error updating master product.', [
                'admin_user_id' => $adminUserId,
                'master_product_id' => $masterProduct->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Error updating master product. Please try again.'], 500);
        }
    }

    /**
     * Remove the specified Master Product from storage.
     * Note: Media is expected to be handled by model events or cascade deletes if configured.
     * Consider implications for linked ShopProducts.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \IJIDeals\IJIProductCatalog\Models\MasterProduct  $masterProduct The MasterProduct instance via route model binding.
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, MasterProduct $masterProduct)
    {
        $adminUserId = Auth::id();
        Log::debug('Admin MasterProductController@destroy: Attempting to delete master product.', ['admin_user_id' => $adminUserId, 'master_product_id' => $masterProduct->id]);

        if ($request->user()->cannot('delete', $masterProduct)) {
            Log::warning('Admin MasterProductController@destroy: Authorization failed.', ['admin_user_id' => $adminUserId, 'master_product_id' => $masterProduct->id, 'action' => 'delete']);
            return response()->json(['message' => 'Unauthorized to delete this master product.'], 403);
        }

        // Consider implications for ShopProducts & Media
        // if ($masterProduct->shopProducts()->exists()) {
        //     Log::warning('Admin MasterProductController@destroy: Attempt to delete master product listed by shops.', ['admin_user_id' => $adminUserId, 'master_product_id' => $masterProduct->id]);
        //     return response()->json(['message' => 'Cannot delete: Master product is actively listed by shops. Archive it instead.'], 422);
        // }

        try {
            // Ensure media is handled (Spatie MediaLibrary usually handles this via model events if configured)
            // $masterProduct->clearMediaCollection(config('ijiproductcatalog.media_collections.master_product_base_images', 'master_product_base_images'));
            // Also clear media for variations if they exist and are deleted by cascade or explicitly. This needs careful handling.

            $masterProduct->delete(); // This should trigger deletion of related variations if cascade is set up.
            Log::info('Admin MasterProductController@destroy: Master product deleted successfully.', ['admin_user_id' => $adminUserId, 'master_product_id' => $masterProduct->id]);
            return response()->json(['message' => 'MasterProduct deleted successfully.'], 200);
        } catch (\Exception $e) {
            Log::error('Admin MasterProductController@destroy: Error deleting master product.', [
                'admin_user_id' => $adminUserId,
                'master_product_id' => $masterProduct->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString() // Caution in production
            ]);
            return response()->json(['message' => 'Error deleting master product. Please try again.'], 500);
        }
    }
}
