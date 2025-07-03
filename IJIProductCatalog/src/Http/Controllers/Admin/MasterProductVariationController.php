<?php

namespace IJIDeals\IJIProductCatalog\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use IJIDeals\IJIProductCatalog\Models\MasterProduct;
use IJIDeals\IJIProductCatalog\Models\MasterProductVariation;
use IJIDeals\IJIProductCatalog\Models\ProductAttributeValue;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class MasterProductVariationController extends Controller
{
    /**
     * List variations for a master product.
     */
    public function index(Request $request, MasterProduct $masterProduct)
    {
        Log::info('Admin MasterProductVariationController: Fetching variations for master product.', ['master_product_id' => $masterProduct->id]);
        // TODO: Authorize: $request->user()->can('viewVariations', $masterProduct) or similar
        $variations = $masterProduct->variations()->with('attributeOptions.attribute')->paginate(20);
        Log::info('Admin MasterProductVariationController: Variations fetched successfully.', ['master_product_id' => $masterProduct->id, 'count' => $variations->count()]);
        return response()->json($variations);
    }

    /**
     * Store a new variation for a master product.
     */
    public function store(Request $request, MasterProduct $masterProduct)
    {
        Log::info('Admin MasterProductVariationController: Attempting to store new variation.', ['master_product_id' => $masterProduct->id, 'request_data' => $request->all()]);
        // TODO: Authorize: $request->user()->can('manageVariations', $masterProduct)

        $validated = $request->validate([
            'sku' => ['nullable', 'string', 'max:255', Rule::unique(config('ijiproductcatalog.tables.master_product_variations', 'master_product_variations'), 'sku')],
            'price_adjustment' => 'nullable|numeric',
            'stock_override' => 'nullable|integer|min:0',
            'variant_image' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:2048',
            'options' => 'required|array|min:1',
            'options.*' => ['required', 'integer', Rule::exists(config('ijiproductcatalog.tables.product_attribute_values', 'product_attribute_values'), 'id')],
        ]);

        // Check if a variation with the exact same set of options already exists for this master product
        $existingVariation = $this->findVariationByOptions($masterProduct, $validated['options']);
        if ($existingVariation) {
            Log::warning('Admin MasterProductVariationController: Duplicate variation attempt.', ['master_product_id' => $masterProduct->id, 'options' => $validated['options']]);
            return response()->json(['message' => 'A variation with these exact options already exists for this product.'], 422);
        }

        $variation = null;
        DB::beginTransaction();
        try {
            $variationData = collect($validated)->except(['variant_image', 'options'])->toArray();
            $variation = $masterProduct->variations()->create($variationData);

            if ($request->hasFile('variant_image') && $request->file('variant_image')->isValid()) {
                $variation->addMediaFromRequest('variant_image')->toMediaCollection(config('ijiproductcatalog.media_collections.master_product_variant_image', 'master_product_variant_images'));
                Log::info('Admin MasterProductVariationController: Variant image uploaded.', ['variation_id' => $variation->id]);
            }

            $variation->attributeOptions()->sync($validated['options']);
            DB::commit();

            $variation->load('attributeOptions.attribute', 'media');
            $variation->variant_image_url = $variation->getFirstMediaUrl(config('ijiproductcatalog.media_collections.master_product_variant_image', 'master_product_variant_images'), 'thumb');

            Log::info('Admin MasterProductVariationController: Variation stored successfully.', ['variation_id' => $variation->id]);
            return response()->json($variation, 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Admin MasterProductVariationController: Failed to create variation.', ['master_product_id' => $masterProduct->id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to create variation: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Show a specific variation.
     */
    public function show(Request $request, MasterProduct $masterProduct, MasterProductVariation $variation)
    {
        Log::info('Admin MasterProductVariationController: Showing variation details.', ['variation_id' => $variation->id, 'master_product_id' => $masterProduct->id]);
        // TODO: Authorize
        if ($variation->master_product_id !== $masterProduct->id) {
            Log::warning('Admin MasterProductVariationController: Variation not found for product.', ['variation_id' => $variation->id, 'master_product_id' => $masterProduct->id]);
            return response()->json(['message' => 'Variation not found for this product.'], 404);
        }
        $variation->load('attributeOptions.attribute', 'media');
        $variation->variant_image_url = $variation->getFirstMediaUrl(config('ijiproductcatalog.media_collections.master_product_variant_image', 'master_product_variant_images'), 'thumb');
        return response()->json($variation);
    }

    /**
     * Update an existing variation.
     */
    public function update(Request $request, MasterProduct $masterProduct, MasterProductVariation $variation)
    {
        Log::info('Admin MasterProductVariationController: Attempting to update variation.', ['variation_id' => $variation->id, 'master_product_id' => $masterProduct->id, 'request_data' => $request->all()]);
        // TODO: Authorize
        if ($variation->master_product_id !== $masterProduct->id) {
            Log::warning('Admin MasterProductVariationController: Variation not found for product during update.', ['variation_id' => $variation->id, 'master_product_id' => $masterProduct->id]);
            return response()->json(['message' => 'Variation not found for this product.'], 404);
        }

        $validated = $request->validate([
            'sku' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique(config('ijiproductcatalog.tables.master_product_variations', 'master_product_variations'), 'sku')->ignore($variation->id)],
            'price_adjustment' => 'sometimes|nullable|numeric',
            'stock_override' => 'sometimes|nullable|integer|min:0',
            'variant_image' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:2048',
            'options' => 'sometimes|required|array|min:1',
            'options.*' => ['required', 'integer', Rule::exists(config('ijiproductcatalog.tables.product_attribute_values', 'product_attribute_values'), 'id')],
        ]);

        // If options are being updated, check for uniqueness against other variations of the same master product
        if (isset($validated['options'])) {
            $existingVariation = $this->findVariationByOptions($masterProduct, $validated['options'], $variation->id);
            if ($existingVariation) {
                Log::warning('Admin MasterProductVariationController: Duplicate variation options attempt during update.', ['variation_id' => $variation->id, 'options' => $validated['options']]);
                return response()->json(['message' => 'Another variation with these exact options already exists for this product.'], 422);
            }
        }

        DB::beginTransaction();
        try {
            $variationData = collect($validated)->except(['variant_image', 'options'])->toArray();
            $variation->update($variationData);

            if ($request->hasFile('variant_image') && $request->file('variant_image')->isValid()) {
                $variation->clearMediaCollection(config('ijiproductcatalog.media_collections.master_product_variant_image', 'master_product_variant_images'));
                $variation->addMediaFromRequest('variant_image')->toMediaCollection(config('ijiproductcatalog.media_collections.master_product_variant_image', 'master_product_variant_images'));
                Log::info('Admin MasterProductVariationController: Variant image updated.', ['variation_id' => $variation->id]);
            }

            if (isset($validated['options'])) {
                $variation->attributeOptions()->sync($validated['options']);
                Log::info('Admin MasterProductVariationController: Variation options synced.', ['variation_id' => $variation->id, 'options' => $validated['options']]);
            }
            DB::commit();

            $variation->refresh()->load('attributeOptions.attribute', 'media');
            $variation->variant_image_url = $variation->getFirstMediaUrl(config('ijiproductcatalog.media_collections.master_product_variant_image', 'master_product_variant_images'), 'thumb');

            Log::info('Admin MasterProductVariationController: Variation updated successfully.', ['variation_id' => $variation->id]);
            return response()->json($variation);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Admin MasterProductVariationController: Failed to update variation.', ['variation_id' => $variation->id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to update variation: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Delete a variation.
     */
    public function destroy(Request $request, MasterProduct $masterProduct, MasterProductVariation $variation)
    {
        Log::info('Admin MasterProductVariationController: Attempting to delete variation.', ['variation_id' => $variation->id, 'master_product_id' => $masterProduct->id]);
        // TODO: Authorize
        if ($variation->master_product_id !== $masterProduct->id) {
            Log::warning('Admin MasterProductVariationController: Variation not found for product during delete.', ['variation_id' => $variation->id, 'master_product_id' => $masterProduct->id]);
            return response()->json(['message' => 'Variation not found for this product.'], 404);
        }

        $variation->attributeOptions()->detach();
        $variation->delete();

        Log::info('Admin MasterProductVariationController: Variation deleted successfully.', ['variation_id' => $variation->id]);
        return response()->json(['message' => 'Variation deleted successfully.'], 200);
    }

    /**
     * Helper to find an existing variation by a set of option IDs for a given master product.
     * Used to prevent duplicate variations.
     */
    protected function findVariationByOptions(MasterProduct $masterProduct, array $optionIds, $excludeVariationId = null)
    {
        sort($optionIds);

        return $masterProduct->variations()
            ->where(function ($query) use ($optionIds) {
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
