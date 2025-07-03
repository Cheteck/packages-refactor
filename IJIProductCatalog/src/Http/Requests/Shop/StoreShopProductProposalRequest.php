<?php

namespace IJIDeals\IJIProductCatalog\Http\Requests\Shop;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use IJIDeals\IJIProductCatalog\Models\ProductProposal;
use IJIDeals\IJICommerce\Models\Shop; // From IJICommerce package

class StoreShopProductProposalRequest extends FormRequest
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

        // The shop_id is part of the validated data to ensure it exists first.
        // However, for authorization, we might need to access it before full validation.
        // Let's assume shop_id is correctly passed and exists for the auth check.
        // If shop_id itself is invalid, the rules() will catch it.
        $shopId = $this->input('shop_id');
        if (!$shopId) {
            return false; // Cannot authorize without a shop_id
        }

        $shop = Shop::find($shopId);
        if (!$shop) {
            // If the shop doesn't exist, authorization should fail.
            // This will also be caught by validation rules, but good to be explicit.
            return false;
        }

        // Check if the authenticated user can create a proposal for this shop
        return $this->user()->can('createProposal', [ProductProposal::class, $shop]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'shop_id' => [
                'required',
                Rule::exists(config('ijicommerce.tables.shops', 'shops'), 'id') // Ensure shop_id exists in shops table
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'proposed_brand_name' => 'nullable|string|max:255',
            'proposed_category_name' => 'nullable|string|max:255',
            'proposed_specifications' => 'nullable|array',
            'proposed_images_payload' => 'nullable|array', // Consider how these images are actually handled (e.g., base64, temp IDs)
        ];
    }
}
