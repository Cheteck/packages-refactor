<?php

namespace IJIDeals\IJIShopListings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use IJIDeals\IJICommerce\Models\Shop;

class UpdateShopProductRequest extends FormRequest
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
        // We also need to ensure the shopProduct belongs to the shop, but that's usually done in controller.
        // The main authorization is if the user can manage products for this shop.
        return $shop && $this->user()->can('manageShopProducts', $shop);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        // Price and stock are required if no variations are provided or if the master product itself has no variations.
        // This logic is a bit complex for basic rules and might need after-validation or controller logic.
        // For simplicity in FormRequest, we make them conditionally required.
        // For updates, 'sometimes' is key.
        return [
            'price' => 'sometimes|required_without:variations|nullable|numeric|min:0',
            'stock_quantity' => 'sometimes|required_without:variations|nullable|integer|min:0',
            'is_visible_in_shop' => 'sometimes|boolean',
            'shop_specific_notes' => 'nullable|string|max:5000',
            'shop_images' => 'nullable|array',
            'shop_images.*' => 'image|mimes:jpg,jpeg,png,gif,webp|max:2048',
            'removed_shop_image_ids' => 'nullable|array',
            'removed_shop_image_ids.*' => 'integer|exists:media,id', // Ensure media IDs exist

            'sale_price' => 'nullable|numeric|min:0', // lt:price might be tricky
            'sale_start_date' => 'nullable|date|required_with:sale_price',
            'sale_end_date' => 'nullable|date|after_or_equal:sale_start_date',

            // Note: Updating variations themselves (their attributes, options) is typically handled by a separate
            // ShopProductVariationController. This request would focus on the ShopProduct's own fields.
            // If variations array is passed here, it implies changing which variations are sold or their shop-specific overrides,
            // which is generally handled by ShopProductVariationController.
            // For this UpdateShopProductRequest, we'll assume it does NOT handle direct updates to variation items,
            // only the ShopProduct's own fields. If variation-level updates were intended here, the rules
            // would be similar to StoreShopProductRequest but with 'sometimes'.
        ];
    }
     /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        if ($this->has('is_visible_in_shop')) {
             $this->merge([
                'is_visible_in_shop' => filter_var($this->is_visible_in_shop, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            ]);
        }
    }
}
