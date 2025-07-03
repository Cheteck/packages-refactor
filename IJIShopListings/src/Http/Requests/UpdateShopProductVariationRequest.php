<?php

namespace IJIDeals\IJIShopListings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use IJIDeals\IJICommerce\Models\Shop;
use IJIDeals\IJIShopListings\Models\ShopProductVariation;

class UpdateShopProductVariationRequest extends FormRequest
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
        return [
            'price' => 'sometimes|required|numeric|min:0',
            'stock_quantity' => 'sometimes|required|integer|min:0',
            'shop_sku_variant' => 'nullable|string|max:255',
            'sale_price' => 'nullable|numeric|min:0',
            'sale_start_date' => 'nullable|date|required_with:sale_price',
            'sale_end_date' => 'nullable|date|after_or_equal:sale_start_date',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $price = $this->input('price');
            $salePrice = $this->input('sale_price');

            // If price is not being updated, get it from the existing variation model
            if ($price === null && $this->route('variation')) {
                /** @var ShopProductVariation $variation */
                $variation = $this->route('variation');
                $price = $variation->price;
            }

            if ($salePrice !== null && $price !== null && $salePrice >= $price) {
                $validator->errors()->add('sale_price', 'Sale price must be less than the regular price.');
            }
        });
    }
}
