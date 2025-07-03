<?php

namespace IJIDeals\IJIProductCatalog\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use IJIDeals\IJIProductCatalog\Models\MasterProduct;
use IJIDeals\IJIProductCatalog\Models\Brand;
use IJIDeals\IJIProductCatalog\Models\Category;
use IJIDeals\IJIProductCatalog\Models\ProductProposal;

class StoreMasterProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // return $this->user() && $this->user()->can('create', MasterProduct::class);
        return $this->user() ? true : false; // Basic check: user must be authenticated. Implement proper policy.
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => ['nullable', 'string', 'max:255', Rule::unique((new MasterProduct())->getTable(), 'slug')],
            'description' => 'nullable|string',
            'brand_id' => ['nullable', 'integer', Rule::exists((new Brand())->getTable(), 'id')],
            'category_id' => ['nullable', 'integer', Rule::exists((new Category())->getTable(), 'id')],
            'specifications' => 'nullable|array',
            'status' => ['required', 'string', Rule::in(['active', 'draft_by_admin', 'archived'])],
            'created_by_proposal_id' => ['nullable', 'integer', Rule::exists((new ProductProposal())->getTable(), 'id')],
            'base_images' => 'nullable|array',
            'base_images.*' => 'image|mimes:jpg,jpeg,png,gif,webp|max:2048', // Adjust max size as needed
        ];
    }
}
