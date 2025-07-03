<?php

namespace IJIDeals\IJIProductCatalog\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use IJIDeals\IJIProductCatalog\Models\MasterProductVariation;

class UpdateMasterProductVariationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if (!$this->user()) {
            return false;
        }
        // Assuming a policy exists on MasterProductVariation or MasterProduct
        // $variation = $this->route('variation'); // MasterProductVariation instance
        // $masterProduct = $this->route('masterProduct'); // MasterProduct instance
        // return $variation && $masterProduct && $this->user()->can('update', $variation);
        // Or: return $masterProduct && $this->user()->can('manageVariations', $masterProduct);
        return true; // Replace with actual authorization logic
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        $variationId = $this->route('variation') ? $this->route('variation')->id : null;

        return [
            'sku' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique(config('ijiproductcatalog.tables.master_product_variations', 'master_product_variations'), 'sku')->ignore($variationId)],
            'price_adjustment' => 'sometimes|nullable|numeric',
            'stock_override' => 'sometimes|nullable|integer|min:0',
            'variant_image' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:2048',
            'options' => 'sometimes|required|array|min:1',
            'options.*' => ['required', 'integer', Rule::exists(config('ijiproductcatalog.tables.product_attribute_values', 'product_attribute_values'), 'id')],
        ];
    }
}
