<?php

namespace IJIDeals\IJIProductCatalog\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use IJIDeals\IJIProductCatalog\Models\ProductAttribute;
use IJIDeals\IJIProductCatalog\Models\ProductAttributeValue;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

/**
 * Admin controller for managing Product Attributes and their associated Values.
 * Product Attributes define characteristics like 'Color', 'Size'.
 * Product Attribute Values are the specific options, e.g., 'Red', 'Large'.
 */
class ProductAttributeController extends Controller
{
    /**
     * Display a paginated listing of Product Attributes, including their values.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $adminUserId = Auth::id(); // Or $request->user()->id if auth middleware ensures $request->user()
        Log::debug('Admin ProductAttributeController@index: Fetching product attributes.', ['admin_user_id' => $adminUserId, 'filters' => $request->query()]);

        // if ($request->user()->cannot('viewAny', ProductAttribute::class)) {
        //     Log::warning('Admin ProductAttributeController@index: Authorization failed.', ['admin_user_id' => $adminUserId]);
        //     return response()->json(['message' => 'Unauthorized'], 403);
        // }

        $attributes = ProductAttribute::with('values')->orderBy('name')->paginate(config('ijiproductcatalog.pagination.admin_attributes', 20));
        Log::info('Admin ProductAttributeController@index: Product attributes fetched successfully.', ['admin_user_id' => $adminUserId, 'count' => $attributes->count(), 'total' => $attributes->total()]);
        return response()->json($attributes);
    }

    /**
     * Store a newly created Product Attribute in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $adminUserId = Auth::id(); // Or $request->user()->id
        Log::debug('Admin ProductAttributeController@store: Attempting to store new product attribute.', ['admin_user_id' => $adminUserId, 'request_data' => $request->all()]);

        // if ($request->user()->cannot('create', ProductAttribute::class)) {
        //     Log::warning('Admin ProductAttributeController@store: Authorization failed.', ['admin_user_id' => $adminUserId]);
        //     return response()->json(['message' => 'Unauthorized'], 403);
        // }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique((new ProductAttribute)->getTable(), 'name')],
            'type' => ['required', 'string', Rule::in(['select', 'radio', 'color_swatch', 'text'])],
        ]);
        Log::debug('Admin ProductAttributeController@store: Validation passed.', ['admin_user_id' => $adminUserId, 'validated_data' => $validated]);

        try {
            $attribute = ProductAttribute::create($validated);
            Log::info('Admin ProductAttributeController@store: Product attribute stored successfully.', ['admin_user_id' => $adminUserId, 'attribute_id' => $attribute->id]);
            return response()->json($attribute, 201);
        } catch (\Exception $e) {
            Log::error('Admin ProductAttributeController@store: Error storing product attribute.', [
                'admin_user_id' => $adminUserId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Error storing product attribute.'], 500);
        }
    }

    /**
     * Display the specified Product Attribute, including its values.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \IJIDeals\IJIProductCatalog\Models\ProductAttribute  $productAttribute The ProductAttribute instance via route model binding.
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, ProductAttribute $productAttribute)
    {
        $adminUserId = Auth::id(); // Or $request->user()->id
        Log::debug('Admin ProductAttributeController@show: Showing product attribute details.', ['admin_user_id' => $adminUserId, 'attribute_id' => $productAttribute->id]);

        // if ($request->user()->cannot('view', $productAttribute)) {
        //     Log::warning('Admin ProductAttributeController@show: Authorization failed.', ['admin_user_id' => $adminUserId, 'attribute_id' => $productAttribute->id]);
        //     return response()->json(['message' => 'Unauthorized'], 403);
        // }

        $productAttribute->load('values');
        Log::info('Admin ProductAttributeController@show: Product attribute details fetched.', ['admin_user_id' => $adminUserId, 'attribute_id' => $productAttribute->id]);
        return response()->json($productAttribute);
    }

    /**
     * Update the specified Product Attribute in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \IJIDeals\IJIProductCatalog\Models\ProductAttribute  $productAttribute The ProductAttribute instance via route model binding.
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, ProductAttribute $productAttribute)
    {
        $adminUserId = Auth::id(); // Or $request->user()->id
        Log::debug('Admin ProductAttributeController@update: Attempting to update product attribute.', ['admin_user_id' => $adminUserId, 'attribute_id' => $productAttribute->id, 'request_data' => $request->all()]);

        // if ($request->user()->cannot('update', $productAttribute)) {
        //     Log::warning('Admin ProductAttributeController@update: Authorization failed.', ['admin_user_id' => $adminUserId, 'attribute_id' => $productAttribute->id]);
        //     return response()->json(['message' => 'Unauthorized'], 403);
        // }

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique((new ProductAttribute)->getTable(), 'name')->ignore($productAttribute->id)],
            'type' => ['sometimes', 'required', 'string', Rule::in(['select', 'radio', 'color_swatch', 'text'])],
        ]);
        Log::debug('Admin ProductAttributeController@update: Validation passed.', ['admin_user_id' => $adminUserId, 'validated_data' => $validated]);

        try {
            $productAttribute->update($validated);
            Log::info('Admin ProductAttributeController@update: Product attribute updated successfully.', ['admin_user_id' => $adminUserId, 'attribute_id' => $productAttribute->id]);
            return response()->json($productAttribute->load('values'));
        } catch (\Exception $e) {
            Log::error('Admin ProductAttributeController@update: Error updating product attribute.', [
                'admin_user_id' => $adminUserId,
                'attribute_id' => $productAttribute->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Error updating product attribute.'], 500);
        }
    }

    /**
     * Remove the specified Product Attribute from storage.
     * Prevents deletion if the attribute has associated values.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \IJIDeals\IJIProductCatalog\Models\ProductAttribute  $productAttribute The ProductAttribute instance via route model binding.
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, ProductAttribute $productAttribute)
    {
        $adminUserId = Auth::id(); // Or $request->user()->id
        Log::debug('Admin ProductAttributeController@destroy: Attempting to delete product attribute.', ['admin_user_id' => $adminUserId, 'attribute_id' => $productAttribute->id]);

        // if ($request->user()->cannot('delete', $productAttribute)) {
        //     Log::warning('Admin ProductAttributeController@destroy: Authorization failed.', ['admin_user_id' => $adminUserId, 'attribute_id' => $productAttribute->id]);
        //     return response()->json(['message' => 'Unauthorized'], 403);
        // }

        try {
            if ($productAttribute->values()->exists()) { // Check if it has values
                Log::warning('Admin ProductAttributeController@destroy: Cannot delete attribute with associated values.', ['admin_user_id' => $adminUserId, 'attribute_id' => $productAttribute->id]);
                return response()->json(['message' => 'Cannot delete attribute as it has associated values. Please delete values first or ensure it is not used in product variations.'], 422);
            }
            // Additional check: is this attribute used by any product variations?
            // This might require a more complex query or a relationship on ProductAttribute model.
            // For example: if ($productAttribute->masterProductVariations()->exists()) { ... }

            $productAttribute->delete();
            Log::info('Admin ProductAttributeController@destroy: Product attribute deleted successfully.', ['admin_user_id' => $adminUserId, 'attribute_id' => $productAttribute->id]);
            return response()->json(['message' => 'ProductAttribute deleted successfully.'], 200);
        } catch (\Exception $e) {
            Log::error('Admin ProductAttributeController@destroy: Error deleting product attribute.', [
                'admin_user_id' => $adminUserId,
                'attribute_id' => $productAttribute->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Error deleting product attribute.'], 500);
        }
    }

    // --- Attribute Values specific to an Attribute ---

    /**
     * Store a new ProductAttributeValue for a given ProductAttribute.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \IJIDeals\IJIProductCatalog\Models\ProductAttribute  $productAttribute The parent ProductAttribute.
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeValue(Request $request, ProductAttribute $productAttribute)
    {
        $adminUserId = Auth::id(); // Or $request->user()->id
        Log::debug('Admin ProductAttributeController@storeValue: Attempting to store new attribute value.', ['admin_user_id' => $adminUserId, 'attribute_id' => $productAttribute->id, 'request_data' => $request->all()]);

        // if ($request->user()->cannot('manageValues', $productAttribute)) {
        //     Log::warning('Admin ProductAttributeController@storeValue: Authorization failed.', ['admin_user_id' => $adminUserId, 'attribute_id' => $productAttribute->id]);
        //     return response()->json(['message' => 'Unauthorized'], 403);
        // }

        $validated = $request->validate([
            'value' => ['required', 'string', 'max:255',
                Rule::unique(config('ijiproductcatalog.tables.product_attribute_values', 'product_attribute_values'), 'value')
                    ->where('product_attribute_id', $productAttribute->id)
            ],
            'meta' => 'nullable|array',
        ]);
        Log::debug('Admin ProductAttributeController@storeValue: Validation passed.', ['admin_user_id' => $adminUserId, 'validated_data' => $validated]);

        try {
            $value = $productAttribute->values()->create($validated);
            Log::info('Admin ProductAttributeController@storeValue: Attribute value stored successfully.', ['admin_user_id' => $adminUserId, 'value_id' => $value->id, 'attribute_id' => $productAttribute->id]);
            return response()->json($value, 201);
        } catch (\Exception $e) {
            Log::error('Admin ProductAttributeController@storeValue: Error storing attribute value.', [
                'admin_user_id' => $adminUserId,
                'attribute_id' => $productAttribute->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Error storing attribute value.'], 500);
        }
    }

    /**
     * Update the specified ProductAttributeValue.
     * Ensures the value belongs to the given ProductAttribute.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \IJIDeals\IJIProductCatalog\Models\ProductAttribute  $productAttribute The parent ProductAttribute.
     * @param  \IJIDeals\IJIProductCatalog\Models\ProductAttributeValue  $value The ProductAttributeValue instance via route model binding.
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateValue(Request $request, ProductAttribute $productAttribute, ProductAttributeValue $value)
    {
        $adminUserId = Auth::id(); // Or $request->user()->id
        Log::debug('Admin ProductAttributeController@updateValue: Attempting to update attribute value.', ['admin_user_id' => $adminUserId, 'value_id' => $value->id, 'attribute_id' => $productAttribute->id, 'request_data' => $request->all()]);

        // if ($request->user()->cannot('manageValues', $productAttribute)) { // Or can('update', $value)
        //     Log::warning('Admin ProductAttributeController@updateValue: Authorization failed.', ['admin_user_id' => $adminUserId, 'value_id' => $value->id]);
        //     return response()->json(['message' => 'Unauthorized'], 403);
        // }

        if ($value->product_attribute_id !== $productAttribute->id) {
            Log::warning('Admin ProductAttributeController@updateValue: Attribute value does not belong to the specified product attribute.', ['admin_user_id' => $adminUserId, 'value_id' => $value->id, 'attribute_id' => $productAttribute->id]);
            return response()->json(['message' => 'Attribute value not found for this attribute.'], 404);
        }

        $validated = $request->validate([
            'value' => ['sometimes', 'required', 'string', 'max:255',
                Rule::unique(config('ijiproductcatalog.tables.product_attribute_values', 'product_attribute_values'), 'value')
                    ->where('product_attribute_id', $productAttribute->id)
                    ->ignore($value->id)
            ],
            'meta' => 'nullable|array',
        ]);
        Log::debug('Admin ProductAttributeController@updateValue: Validation passed.', ['admin_user_id' => $adminUserId, 'validated_data' => $validated]);

        try {
            $value->update($validated);
            Log::info('Admin ProductAttributeController@updateValue: Attribute value updated successfully.', ['admin_user_id' => $adminUserId, 'value_id' => $value->id]);
            return response()->json($value);
        } catch (\Exception $e) {
            Log::error('Admin ProductAttributeController@updateValue: Error updating attribute value.', [
                'admin_user_id' => $adminUserId,
                'value_id' => $value->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Error updating attribute value.'], 500);
        }
    }

    /**
     * Remove the specified ProductAttributeValue from storage.
     * Ensures the value belongs to the given ProductAttribute.
     * Considers (but does not currently enforce) if the value is used in variations.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \IJIDeals\IJIProductCatalog\Models\ProductAttribute  $productAttribute The parent ProductAttribute.
     * @param  \IJIDeals\IJIProductCatalog\Models\ProductAttributeValue  $value The ProductAttributeValue instance via route model binding.
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroyValue(Request $request, ProductAttribute $productAttribute, ProductAttributeValue $value)
    {
        $adminUserId = Auth::id(); // Or $request->user()->id
        Log::debug('Admin ProductAttributeController@destroyValue: Attempting to delete attribute value.', ['admin_user_id' => $adminUserId, 'value_id' => $value->id, 'attribute_id' => $productAttribute->id]);

        // if ($request->user()->cannot('manageValues', $productAttribute)) { // Or can('delete', $value)
        //     Log::warning('Admin ProductAttributeController@destroyValue: Authorization failed.', ['admin_user_id' => $adminUserId, 'value_id' => $value->id]);
        //     return response()->json(['message' => 'Unauthorized'], 403);
        // }

        if ($value->product_attribute_id !== $productAttribute->id) {
            Log::warning('Admin ProductAttributeController@destroyValue: Attribute value does not belong to the specified product attribute.', ['admin_user_id' => $adminUserId, 'value_id' => $value->id, 'attribute_id' => $productAttribute->id]);
            return response()->json(['message' => 'Attribute value not found for this attribute.'], 404);
        }

        try {
            // Consider implications: if this value is used in any MasterProductVariation.
            // A more robust check would be: if ($value->masterProductVariations()->exists()) { ... }
            // This requires defining the relationship on ProductAttributeValue model.
            // For now, the log warning is good, actual prevention logic is commented.
            // if ($value->masterProductVariations()->exists()) { // Hypothetical relationship
            //    Log::warning('Admin ProductAttributeController@destroyValue: Cannot delete attribute value used in variations.', ['admin_user_id' => $adminUserId, 'value_id' => $value->id]);
            //    return response()->json(['message' => 'Cannot delete value as it is used in product variations.'], 422);
            // }
            $value->delete();
            Log::info('Admin ProductAttributeController@destroyValue: Attribute value deleted successfully.', ['admin_user_id' => $adminUserId, 'value_id' => $value->id]);
            return response()->json(['message' => 'Attribute value deleted successfully.'], 200);
        } catch (\Exception $e) {
            Log::error('Admin ProductAttributeController@destroyValue: Error deleting attribute value.', [
                'admin_user_id' => $adminUserId,
                'value_id' => $value->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Error deleting attribute value.'], 500);
        }
    }
}
