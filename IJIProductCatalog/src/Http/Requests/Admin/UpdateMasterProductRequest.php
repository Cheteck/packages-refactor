<?php

namespace IJIDeals\IJIProductCatalog\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use IJIDeals\IJIProductCatalog\Models\MasterProduct;
use IJIDeals\IJIProductCatalog\Models\Brand;
use IJIDeals\IJIProductCatalog\Models\Category;

class UpdateMasterProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // $masterProduct = $this->route('masterProduct');
        // return $this->user() && $masterProduct && $this->user()->can('update', $masterProduct);
        return $this->user() ? true : false; // Basic check: user must be authenticated. Implement proper policy.
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        $masterProductId = $this->route('masterProduct') ? $this->route('masterProduct')->id : null;

        return [
            'name' => 'sometimes|required|string|max:255',
            'slug' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique((new MasterProduct())->getTable(), 'slug')->ignore($masterProductId)],
            'description' => 'nullable|string',
            'brand_id' => ['nullable', 'integer', Rule::exists((new Brand())->getTable(), 'id')],
            'category_id' => ['nullable', 'integer', Rule::exists((new Category())->getTable(), 'id')],
            'specifications' => 'nullable|array',
            'status' => ['sometimes','required', 'string', Rule::in(['active', 'draft_by_admin', 'archived'])],
            'base_images' => 'nullable|array', // For new uploads
            'base_images.*' => 'image|mimes:jpg,jpeg,png,gif,webp|max:2048',
            'removed_media_ids' => 'nullable|array', // IDs of media to be removed
            'removed_media_ids.*' => 'integer|exists:media,id', // Ensure media IDs exist
        ];
    }
}
