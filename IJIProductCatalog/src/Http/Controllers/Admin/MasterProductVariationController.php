<?php

namespace IJIDeals\IJIProductCatalog\Http\Controllers\Admin;

use Illuminate\Http\Request; // Keep for index, show, destroy
use Illuminate\Routing\Controller;
use IJIDeals\IJIProductCatalog\Models\MasterProduct;
use IJIDeals\IJIProductCatalog\Models\MasterProductVariation;
// use IJIDeals\IJIProductCatalog\Models\ProductAttributeValue; // Not directly used, remove
// use Illuminate\Validation\Rule; // No longer needed here
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use IJIDeals\IJIProductCatalog\Http\Requests\Admin\StoreMasterProductVariationRequest;
use IJIDeals\IJIProductCatalog\Http\Requests\Admin\UpdateMasterProductVariationRequest;

/**
 * Admin controller for managing Master Product Variations.
 * These are the specific variants of a MasterProduct (e.g., based on color, size).
 */
class MasterProductVariationController extends Controller
{
    /**
     * Display a paginated listing of variations for a given Master Product.
     * Includes attribute options and media URLs.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \IJIDeals\IJIProductCatalog\Models\MasterProduct  $masterProduct The parent MasterProduct.
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request, MasterProduct $masterProduct)
    {
        $adminUserId = $request->user() ? $request->user()->id : (Auth::id() ?? null);
        Log::debug('Admin MasterProductVariationController@index: Fetching variations.', ['admin_user_id' => $adminUserId, 'master_product_id' => $masterProduct->id]);

        // Authorization placeholder
        // if ($request->user() && $request->user()->cannot('viewVariations', $masterProduct)) { ... }

        $variations = $masterProduct->variations()
            ->with(['attributeOptions.attribute', 'media'])
            ->paginate(config('ijiproductcatalog.pagination.admin_variations', 20));

        $variations->getCollection()->transform(function ($variation) {
            $variation->variant_image_url = $variation->getFirstMediaUrl(config('ijiproductcatalog.media_collections.master_product_variant_image', 'master_product_variant_images'), 'thumb');
            return $variation;
        });

        Log::info('Admin MasterProductVariationController@index: Variations fetched successfully.', ['admin_user_id' => $adminUserId, 'master_product_id' => $masterProduct->id, 'count' => $variations->count(), 'total' => $variations->total()]);
        return response()->json($variations);
    }

    /**
     * Store a new Master Product Variation for a given Master Product.
     * Prevents creation of duplicate variations based on attribute options.
     * Handles variant image upload.
     *
     * @param  \IJIDeals\IJIProductCatalog\Http\Requests\Admin\StoreMasterProductVariationRequest  $request
     * @param  \IJIDeals\IJIProductCatalog\Models\MasterProduct  $masterProduct The parent MasterProduct.
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreMasterProductVariationRequest $request, MasterProduct $masterProduct)
    {
        $adminUserId = $request->user() ? $request->user()->id : (Auth::id() ?? null);
        Log::debug('Admin MasterProductVariationController@store: Attempting to store new variation.', ['admin_user_id' => $adminUserId, 'master_product_id' => $masterProduct->id, 'request_data' => $request->all()]);

        // Authorization handled by StoreMasterProductVariationRequest->authorize()
        $validatedData = $request->validated();
        Log::debug('Admin MasterProductVariationController@store: Validation passed via FormRequest.', ['admin_user_id' => $adminUserId, 'validated_data' => $validatedData]);

        $existingVariation = $this->findVariationByOptions($masterProduct, $validatedData['options']);
        if ($existingVariation) {
            Log::warning('Admin MasterProductVariationController@store: Duplicate variation attempt.', ['admin_user_id' => $adminUserId, 'master_product_id' => $masterProduct->id, 'options' => $validatedData['options']]);
            return response()->json(['message' => 'A variation with these exact options already exists for this product.'], 422);
        }

        DB::beginTransaction();
        Log::debug('Admin MasterProductVariationController@store: Transaction started.', ['admin_user_id' => $adminUserId, 'master_product_id' => $masterProduct->id]);
        try {
            $variationData = collect($validatedData)->except(['variant_image', 'options'])->toArray();
            $variation = $masterProduct->variations()->create($variationData);
            Log::info('Admin MasterProductVariationController@store: Variation record created.', ['admin_user_id' => $adminUserId, 'variation_id' => $variation->id]);

            if ($request->hasFile('variant_image') && $request->file('variant_image')->isValid()) {
                $variation->addMediaFromRequest('variant_image')->toMediaCollection(config('ijiproductcatalog.media_collections.master_product_variant_image', 'master_product_variant_images'));
                Log::info('Admin MasterProductVariationController@store: Variant image uploaded.', ['admin_user_id' => $adminUserId, 'variation_id' => $variation->id]);
            }

            $variation->attributeOptions()->sync($validatedData['options']);
            Log::info('Admin MasterProductVariationController@store: Variation options synced.', ['admin_user_id' => $adminUserId, 'variation_id' => $variation->id, 'options_count' => count($validatedData['options'])]);

            DB::commit();
            Log::info('Admin MasterProductVariationController@store: Transaction committed.', ['admin_user_id' => $adminUserId, 'variation_id' => $variation->id]);

            $variation->load('attributeOptions.attribute', 'media');
            $variation->variant_image_url = $variation->getFirstMediaUrl(config('ijiproductcatalog.media_collections.master_product_variant_image', 'master_product_variant_images'), 'thumb');

            Log::info('Admin MasterProductVariationController@store: Variation stored successfully.', ['admin_user_id' => $adminUserId, 'variation_id' => $variation->id]);
            return response()->json($variation->fresh()->load('attributeOptions.attribute', 'media'), 201); // Return fresh

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Admin MasterProductVariationController@store: Failed to create variation, transaction rolled back.', [
                'admin_user_id' => $adminUserId,
                'master_product_id' => $masterProduct->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Failed to create variation: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified Master Product Variation.
     * Ensures the variation belongs to the given Master Product.
     * Includes attribute options and media URL.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \IJIDeals\IJIProductCatalog\Models\MasterProduct  $masterProduct The parent MasterProduct.
     * @param  \IJIDeals\IJIProductCatalog\Models\MasterProductVariation  $variation The MasterProductVariation instance via route model binding.
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, MasterProduct $masterProduct, MasterProductVariation $variation)
    {
        $adminUserId = $request->user() ? $request->user()->id : (Auth::id() ?? null);
        Log::debug('Admin MasterProductVariationController@show: Showing variation details.', ['admin_user_id' => $adminUserId, 'variation_id' => $variation->id, 'master_product_id' => $masterProduct->id]);

        // Authorization placeholder
        // if ($request->user() && $request->user()->cannot('view', $variation)) { ... }

        if ($variation->master_product_id !== $masterProduct->id) {
            Log::warning('Admin MasterProductVariationController@show: Variation does not belong to the specified master product.', ['admin_user_id' => $adminUserId, 'variation_id' => $variation->id, 'master_product_id' => $masterProduct->id]);
            return response()->json(['message' => 'Variation not found for this product.'], 404);
        }
        $variation->load('attributeOptions.attribute', 'media');
        $variation->variant_image_url = $variation->getFirstMediaUrl(config('ijiproductcatalog.media_collections.master_product_variant_image', 'master_product_variant_images'), 'thumb');
        Log::info('Admin MasterProductVariationController@show: Variation details fetched successfully.', ['admin_user_id' => $adminUserId, 'variation_id' => $variation->id]);
        return response()->json($variation);
    }

    /**
     * Update the specified Master Product Variation.
     * Ensures the variation belongs to the given Master Product.
     * Prevents updates that would result in duplicate variations (based on options).
     * Handles variant image updates and option syncing.
     *
     * @param  \IJIDeals\IJIProductCatalog\Http\Requests\Admin\UpdateMasterProductVariationRequest  $request
     * @param  \IJIDeals\IJIProductCatalog\Models\MasterProduct  $masterProduct The parent MasterProduct.
     * @param  \IJIDeals\IJIProductCatalog\Models\MasterProductVariation  $variation The MasterProductVariation instance via route model binding.
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateMasterProductVariationRequest $request, MasterProduct $masterProduct, MasterProductVariation $variation)
    {
        $adminUserId = $request->user() ? $request->user()->id : (Auth::id() ?? null);
        Log::debug('Admin MasterProductVariationController@update: Attempting to update variation.', ['admin_user_id' => $adminUserId, 'variation_id' => $variation->id, 'master_product_id' => $masterProduct->id, 'request_data' => $request->all()]);

        // Authorization handled by UpdateMasterProductVariationRequest->authorize()

        if ($variation->master_product_id !== $masterProduct->id) {
            Log::warning('Admin MasterProductVariationController@update: Variation does not belong to the specified master product.', ['admin_user_id' => $adminUserId, 'variation_id' => $variation->id, 'master_product_id' => $masterProduct->id]);
            return response()->json(['message' => 'Variation not found for this product.'], 404);
        }

        $validatedData = $request->validated();
        Log::debug('Admin MasterProductVariationController@update: Validation passed via FormRequest.', ['admin_user_id' => $adminUserId, 'validated_data' => $validatedData]);

        if (isset($validatedData['options'])) {
            $existingVariation = $this->findVariationByOptions($masterProduct, $validatedData['options'], $variation->id);
            if ($existingVariation) {
                Log::warning('Admin MasterProductVariationController@update: Duplicate variation options attempt.', ['admin_user_id' => $adminUserId, 'variation_id' => $variation->id, 'options' => $validatedData['options']]);
                return response()->json(['message' => 'Another variation with these exact options already exists for this product.'], 422);
            }
        }

        DB::beginTransaction();
        Log::debug('Admin MasterProductVariationController@update: Transaction started.', ['admin_user_id' => $adminUserId, 'variation_id' => $variation->id]);
        try {
            $variationDataToUpdate = collect($validatedData)->except(['variant_image', 'options'])->toArray();
            $variation->update($variationDataToUpdate);
            Log::info('Admin MasterProductVariationController@update: Variation record updated.', ['admin_user_id' => $adminUserId, 'variation_id' => $variation->id]);

            if ($request->hasFile('variant_image') && $request->file('variant_image')->isValid()) {
                $variation->clearMediaCollection(config('ijiproductcatalog.media_collections.master_product_variant_image', 'master_product_variant_images'));
                $variation->addMediaFromRequest('variant_image')->toMediaCollection(config('ijiproductcatalog.media_collections.master_product_variant_image', 'master_product_variant_images'));
                Log::info('Admin MasterProductVariationController@update: Variant image updated.', ['admin_user_id' => $adminUserId, 'variation_id' => $variation->id]);
            }

            if (isset($validatedData['options'])) {
                $variation->attributeOptions()->sync($validatedData['options']);
                Log::info('Admin MasterProductVariationController@update: Variation options synced.', ['admin_user_id' => $adminUserId, 'variation_id' => $variation->id, 'options_count' => count($validatedData['options'])]);
            }
            DB::commit();
            Log::info('Admin MasterProductVariationController@update: Transaction committed.', ['admin_user_id' => $adminUserId, 'variation_id' => $variation->id]);

            $variation->refresh()->load('attributeOptions.attribute', 'media');
            $variation->variant_image_url = $variation->getFirstMediaUrl(config('ijiproductcatalog.media_collections.master_product_variant_image', 'master_product_variant_images'), 'thumb');

            Log::info('Admin MasterProductVariationController@update: Variation updated successfully.', ['admin_user_id' => $adminUserId, 'variation_id' => $variation->id]);
            return response()->json($variation->fresh()->load('attributeOptions.attribute', 'media'));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Admin MasterProductVariationController@update: Failed to update variation, transaction rolled back.', [
                'admin_user_id' => $adminUserId,
                'variation_id' => $variation->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Failed to update variation: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified Master Product Variation from storage.
     * Ensures the variation belongs to the given Master Product.
     * Detaches attribute options before deletion.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \IJIDeals\IJIProductCatalog\Models\MasterProduct  $masterProduct The parent MasterProduct.
     * @param  \IJIDeals\IJIProductCatalog\Models\MasterProductVariation  $variation The MasterProductVariation instance via route model binding.
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, MasterProduct $masterProduct, MasterProductVariation $variation)
    {
        $adminUserId = Auth::id();
        Log::debug('Admin MasterProductVariationController@destroy: Attempting to delete variation.', ['admin_user_id' => $adminUserId, 'variation_id' => $variation->id, 'master_product_id' => $masterProduct->id]);

        // if ($request->user()->cannot('manageVariations', $masterProduct)) { // Or can('delete', $variation)
        //     Log::warning('Admin MasterProductVariationController@destroy: Authorization failed.', ['admin_user_id' => $adminUserId, 'variation_id' => $variation->id]);
        //     return response()->json(['message' => 'Unauthorized to delete this variation.'], 403);
        // }

        if ($variation->master_product_id !== $masterProduct->id) {
            Log::warning('Admin MasterProductVariationController@destroy: Variation does not belong to the specified master product.', ['admin_user_id' => $adminUserId, 'variation_id' => $variation->id, 'master_product_id' => $masterProduct->id]);
            return response()->json(['message' => 'Variation not found for this product.'], 404);
        }

        DB::beginTransaction();
        Log::debug('Admin MasterProductVariationController@destroy: Transaction started.', ['admin_user_id' => $adminUserId, 'variation_id' => $variation->id]);
        try {
            $variation->attributeOptions()->detach();
            Log::info('Admin MasterProductVariationController@destroy: Detached attribute options.', ['admin_user_id' => $adminUserId, 'variation_id' => $variation->id]);

            // Spatie MediaLibrary should handle media deletion on model delete if configured with cascades.
            // If not, clear manually: $variation->clearMediaCollection(...);
            $variation->delete();
            Log::info('Admin MasterProductVariationController@destroy: Variation record deleted.', ['admin_user_id' => $adminUserId, 'variation_id' => $variation->id]);

            DB::commit();
            Log::info('Admin MasterProductVariationController@destroy: Transaction committed.', ['admin_user_id' => $adminUserId, 'variation_id' => $variation->id]);
            Log::info('Admin MasterProductVariationController@destroy: Variation deleted successfully.', ['admin_user_id' => $adminUserId, 'variation_id' => $variation->id]);
            return response()->json(['message' => 'Variation deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Admin MasterProductVariationController@destroy: Failed to delete variation, transaction rolled back.', [
                'admin_user_id' => $adminUserId,
                'variation_id' => $variation->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString() // Caution in production
            ]);
            return response()->json(['message' => 'Failed to delete variation: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Helper to find an existing variation by a set of option IDs for a given Master Product.
     * Used to prevent creation/update of duplicate variations (same set of attribute options).
     *
     * @param  \IJIDeals\IJIProductCatalog\Models\MasterProduct  $masterProduct
     * @param  array  $optionIds Array of ProductAttributeValue IDs.
     * @param  int|null  $excludeVariationId  ID of a variation to exclude from the check (used during updates).
     * @return \IJIDeals\IJIProductCatalog\Models\MasterProductVariation|null
     */
    protected function findVariationByOptions(MasterProduct $masterProduct, array $optionIds, $excludeVariationId = null)
    {
        // This helper is internal and logging within it might be too verbose unless debugging specific issues.
        // Log::debug('findVariationByOptions called', ['master_product_id' => $masterProduct->id, 'options' => $optionIds, 'exclude_id' => $excludeVariationId]);
        sort($optionIds); // Ensure consistent order for comparison if the query relies on it.

        return $masterProduct->variations()
            ->where(function ($query) use ($optionIds) {
                // This query structure ensures that a variation has ALL the specified options
                // AND ONLY the specified options (by count).
                foreach ($optionIds as $optionId) {
                    $query->whereHas('attributeOptions', function ($q) use ($optionId) {
                        $q->where(config('ijiproductcatalog.tables.product_attribute_values', 'product_attribute_values').'.id', $optionId);
                    });
                }
                $query->has('attributeOptions', '=', count($optionIds));
            })
            ->when($excludeVariationId, function ($query) use ($excludeVariationId) {
                $query->where('id', '!=', $excludeVariationId);
            })
            ->first();
    }
}
