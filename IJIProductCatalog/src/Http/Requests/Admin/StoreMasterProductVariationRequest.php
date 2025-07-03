<?php

namespace IJIDeals\IJIProductCatalog\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use IJIDeals\IJIProductCatalog\Models\MasterProduct;

class StoreMasterProductVariationRequest extends FormRequest
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
        // Assuming a policy exists on MasterProduct to manage its variations
        // $masterProduct = $this->route('masterProduct');
        // return $masterProduct && $this->user()->can('manageVariations', $masterProduct);
        return true; // Replace with actual authorization logic
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'sku' => ['nullable', 'string', 'max:255', Rule::unique(config('ijiproductcatalog.tables.master_product_variations', 'master_product_variations'), 'sku')],
            'price_adjustment' => 'nullable|numeric',
            'stock_override' => 'nullable|integer|min:0',
            'variant_image' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:2048',
            'options' => 'required|array|min:1',
            'options.*' => ['required', 'integer', Rule::exists(config('ijiproductcatalog.tables.product_attribute_values', 'product_attribute_values'), 'id')],
        ];
    }
}
