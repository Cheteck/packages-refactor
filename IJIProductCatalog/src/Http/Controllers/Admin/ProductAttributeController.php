<?php

namespace IJIDeals\IJIProductCatalog\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use IJIDeals\IJIProductCatalog\Models\ProductAttribute;
use IJIDeals\IJIProductCatalog\Models\ProductAttributeValue;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class ProductAttributeController extends Controller
{
    public function index(Request $request)
    {
        Log::info('Admin ProductAttributeController: Fetching all product attributes.');
        // TODO: Authorization check for platform admin
        $attributes = ProductAttribute::with('values')->orderBy('name')->paginate(20);
        Log::info('Admin ProductAttributeController: Product attributes fetched successfully.', ['count' => $attributes->count()]);
        return response()->json($attributes);
    }

    public function store(Request $request)
    {
        Log::info('Admin ProductAttributeController: Attempting to store a new product attribute.', ['request_data' => $request->all()]);
        // TODO: Authorization check for platform admin
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique((new ProductAttribute)->getTable(), 'name')],
            'type' => ['required', 'string', Rule::in(['select', 'radio', 'color_swatch', 'text'])],
        ]);

        $attribute = ProductAttribute::create($validated);
        Log::info('Admin ProductAttributeController: Product attribute stored successfully.', ['attribute_id' => $attribute->id]);
        return response()->json($attribute, 201);
    }

    public function show(Request $request, ProductAttribute $productAttribute)
    {
        Log::info('Admin ProductAttributeController: Showing product attribute details.', ['attribute_id' => $productAttribute->id]);
        // TODO: Authorization check for platform admin
        $productAttribute->load('values');
        return response()->json($productAttribute);
    }

    public function update(Request $request, ProductAttribute $productAttribute)
    {
        Log::info('Admin ProductAttributeController: Attempting to update product attribute.', ['attribute_id' => $productAttribute->id, 'request_data' => $request->all()]);
        // TODO: Authorization check for platform admin
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique((new ProductAttribute)->getTable(), 'name')->ignore($productAttribute->id)],
            'type' => ['sometimes', 'required', 'string', Rule::in(['select', 'radio', 'color_swatch', 'text'])],
        ]);

        $productAttribute->update($validated);
        Log::info('Admin ProductAttributeController: Product attribute updated successfully.', ['attribute_id' => $productAttribute->id]);
        return response()->json($productAttribute->load('values'));
    }

    public function destroy(Request $request, ProductAttribute $productAttribute)
    {
        Log::info('Admin ProductAttributeController: Attempting to delete product attribute.', ['attribute_id' => $productAttribute->id]);
        // TODO: Authorization check for platform admin
        if ($productAttribute->values()->exists()) {
             Log::warning('Admin ProductAttributeController: Cannot delete attribute with associated values.', ['attribute_id' => $productAttribute->id]);
             return response()->json(['message' => 'Cannot delete attribute as it has associated values or is used in product variations.'], 422);
        }
        $productAttribute->delete();
        Log::info('Admin ProductAttributeController: Product attribute deleted successfully.', ['attribute_id' => $productAttribute->id]);
        return response()->json(['message' => 'ProductAttribute deleted successfully.'], 200);
    }

    // --- Attribute Values specific to an Attribute ---

    public function storeValue(Request $request, ProductAttribute $productAttribute)
    {
        Log::info('Admin ProductAttributeController: Attempting to store new attribute value.', ['attribute_id' => $productAttribute->id, 'request_data' => $request->all()]);
        // TODO: Authorization check for platform admin
        $validated = $request->validate([
            'value' => ['required', 'string', 'max:255',
                Rule::unique(config('ijiproductcatalog.tables.product_attribute_values', 'product_attribute_values'), 'value')
                    ->where('product_attribute_id', $productAttribute->id)
            ],
            'meta' => 'nullable|array',
        ]);

        $value = $productAttribute->values()->create($validated);
        Log::info('Admin ProductAttributeController: Attribute value stored successfully.', ['value_id' => $value->id, 'attribute_id' => $productAttribute->id]);
        return response()->json($value, 201);
    }

    public function updateValue(Request $request, ProductAttribute $productAttribute, ProductAttributeValue $value)
    {
        Log::info('Admin ProductAttributeController: Attempting to update attribute value.', ['value_id' => $value->id, 'attribute_id' => $productAttribute->id, 'request_data' => $request->all()]);
        // TODO: Authorization check for platform admin
        if ($value->product_attribute_id !== $productAttribute->id) {
            Log::warning('Admin ProductAttributeController: Attribute value not found for product attribute during update.', ['value_id' => $value->id, 'attribute_id' => $productAttribute->id]);
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
        $value->update($validated);
        Log::info('Admin ProductAttributeController: Attribute value updated successfully.', ['value_id' => $value->id]);
        return response()->json($value);
    }

    public function destroyValue(Request $request, ProductAttribute $productAttribute, ProductAttributeValue $value)
    {
        Log::info('Admin ProductAttributeController: Attempting to delete attribute value.', ['value_id' => $value->id, 'attribute_id' => $productAttribute->id]);
        // TODO: Authorization check for platform admin
        if ($value->product_attribute_id !== $productAttribute->id) {
            Log::warning('Admin ProductAttributeController: Attribute value not found for product attribute during delete.', ['value_id' => $value->id, 'attribute_id' => $productAttribute->id]);
            return response()->json(['message' => 'Attribute value not found for this attribute.'], 404);
        }

        // Consider implications: if this value is used in any MasterProductVariation.
        // For now, simple delete. Add checks if needed.
        // if ($value->masterProductVariations()->exists()) {
        //    Log::warning('Admin ProductAttributeController: Cannot delete attribute value used in variations.', ['value_id' => $value->id]);
        //    return response()->json(['message' => 'Cannot delete value as it is used in product variations.'], 422);
        // }
        $value->delete();
        Log::info('Admin ProductAttributeController: Attribute value deleted successfully.', ['value_id' => $value->id]);
        return response()->json(['message' => 'Attribute value deleted successfully.'], 200);
    }
}
