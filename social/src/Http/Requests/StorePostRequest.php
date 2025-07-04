<?php

namespace IJIDeals\Social\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization will be handled by the PostPolicy in the controller
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'content' => 'required|string|max:5000', // Changed 'contenu' to 'content' and added max
            'type' => 'sometimes|string|in:text,image,video,link,product', // Standardized, added product
            'visibility' => 'sometimes|string|in:public,followers,private', // Standardized 'amis' to 'followers'
            // 'status' is not typically set by the user on creation, defaults in migration or controller

            'tagged_products' => 'nullable|array',
            'tagged_products.*.id' => 'required_with:tagged_products|integer|min:1',
            'tagged_products.*.type' => 'required_with:tagged_products|string|in:MasterProduct,ShopProduct',
        ];
    }
}
