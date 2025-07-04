<?php

namespace IJIDeals\IJIProductCatalog\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use IJIDeals\IJIProductCatalog\Models\ProductProposal;

class RejectProductProposalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // $productProposal = $this->route('productProposal');
        // return $this->user() && $productProposal && $this->user()->can('reject', $productProposal);
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
            'admin_notes' => 'required|string|max:5000',
        ];
    }

    /**
     * @return array
     */
    public function bodyParameters(): array
    {
        return [
            'admin_notes' => [
                'description' => 'Notes from the admin explaining the rejection.',
                'type' => 'string',
                'required' => true,
            ],
        ];
    }
}