<?php

namespace IJIDeals\IJIProductCatalog\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use IJIDeals\IJIProductCatalog\Models\ProductProposal;
use IJIDeals\IJIProductCatalog\Models\Brand;
use IJIDeals\IJIProductCatalog\Models\Category;

class ApproveProductProposalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // $productProposal = $this->route('productProposal');
        // return $this->user() && $productProposal && $this->user()->can('approve', $productProposal);
        return $this->user() ? true : false; // Basic check, implement proper policy
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'name' => 'required|string|max:255', // Name for the MasterProduct
            'description' => 'nullable|string',
            'brand_id' => ['nullable', 'integer', Rule::exists((new Brand())->getTable(), 'id')],
            'category_id' => ['nullable', 'integer', Rule::exists((new Category())->getTable(), 'id')],
            'specifications' => 'nullable|array',
            'status' => ['required', 'string', Rule::in(['active', 'draft_by_admin', 'archived'])], // Status for the new MasterProduct
            'admin_notes' => 'nullable|string|max:5000', // Notes for the proposal itself
        ];
    }
}
