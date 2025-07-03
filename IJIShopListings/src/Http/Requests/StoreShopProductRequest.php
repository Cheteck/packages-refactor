<?php

namespace IJIDeals\IJIShopListings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use IJIDeals\IJICommerce\Models\Shop;
use IJIDeals\IJIProductCatalog\Models\MasterProduct;
use IJIDeals\IJIShopListings\Models\ShopProduct;

class StoreShopProductRequest extends FormRequest
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
        /** @var Shop $shop */
        $shop = $this->route('shop');
        return $shop && $this->user()->can('manageShopProducts', $shop);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        $shopId = $this->route('shop') ? $this->route('shop')->id : null;

        return [
            'master_product_id' => [
                'required',
                Rule::exists((new MasterProduct())->getTable(), 'id')->where('status', 'active'),
                Rule::unique((new ShopProduct())->getTable())->where(function ($query) use ($shopId) {
                    return $query->where('shop_id', $shopId);
                })->setMessage('This product is already listed in your shop.')
            ],
            // Price and stock are required if no variations are provided or if the master product itself has no variations.
            // This logic is a bit complex for basic rules and might need after-validation or controller logic.
            // For simplicity in FormRequest, we make them conditionally required.
            'price' => 'required_without:variations|nullable|numeric|min:0',
            'stock_quantity' => 'required_without:variations|nullable|integer|min:0',

            'is_visible_in_shop' => 'sometimes|boolean',
            'shop_specific_notes' => 'nullable|string|max:5000',
            'shop_images' => 'nullable|array',
            'shop_images.*' => 'image|mimes:jpg,jpeg,png,gif,webp|max:2048', // Adjust as needed

            'sale_price' => 'nullable|numeric|min:0|lt:price', // lt:price might be tricky if price is part of variations
            'sale_start_date' => 'nullable|date|required_with:sale_price',
            'sale_end_date' => 'nullable|date|after_or_equal:sale_start_date',

            'variations' => 'nullable|array',
            // Conditionally require variation fields if variations array is present
            'variations.*.master_product_variation_id' => ['required_with:variations', 'integer', Rule::exists(config('ijiproductcatalog.tables.master_product_variations','master_product_variations'), 'id')],
            'variations.*.price' => 'required_with:variations|numeric|min:0',
            'variations.*.stock_quantity' => 'required_with:variations|integer|min:0',
            'variations.*.shop_sku_variant' => 'nullable|string|max:255',
            'variations.*.sale_price' => 'nullable|numeric|min:0', // Add 'lt:variations.*.price' if possible/needed
            'variations.*.sale_start_date' => 'nullable|date|required_with:variations.*.sale_price',
            'variations.*.sale_end_date' => 'nullable|date|after_or_equal:variations.*.sale_start_date',
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        if (!$this->has('is_visible_in_shop') && $this->isMethod('POST')) { // Default for store only
            $this->merge([
                'is_visible_in_shop' => true,
            ]);
        }
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'variations.*.price.required_with' => 'The price for each variation is required when variations are provided.',
            'variations.*.stock_quantity.required_with' => 'The stock quantity for each variation is required when variations are provided.',
            'variations.*.master_product_variation_id.required_with' => 'The master variation ID for each variation is required.',
        ];
    }
}
