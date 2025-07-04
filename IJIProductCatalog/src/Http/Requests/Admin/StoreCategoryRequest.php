<?php

namespace IJIDeals\IJIProductCatalog\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use IJIDeals\IJIProductCatalog\Models\Category;

class StoreCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Assuming admin users are authorized or a specific policy is in place.
        // Replace with actual authorization logic, e.g.:
        // return $this->user() && $this->user()->can('create', Category::class);
        return $this->user() ? true : false; // Basic check: user must be authenticated.
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tableName = (new Category())->getTable();
        return [
            'name' => 'required|string|max:255',
            'slug' => ['nullable', 'string', 'max:255', Rule::unique($tableName, 'slug')],
            'description' => 'nullable|string',
            'parent_id' => ['nullable', 'integer', Rule::exists($tableName, 'id')],
            'image' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:2048',
        ];
    }

    /**
     * @return array
     */
    public function bodyParameters(): array
    {
        return [
            'name' => [
                'description' => 'The name of the category.',
                'type' => 'string',
                'required' => true,
            ],
            'slug' => [
                'description' => 'A unique slug for the category. If not provided, one will be generated from the name.',
                'type' => 'string',
                'required' => false,
            ],
            'description' => [
                'description' => 'A brief description of the category.',
                'type' => 'string',
                'required' => false,
            ],
            'parent_id' => [
                'description' => 'The ID of the parent category, if this is a subcategory.',
                'type' => 'integer',
                'required' => false,
            ],
            'image' => [
                'description' => 'The image file for the category.',
                'type' => 'file',
                'required' => false,
            ],
        ];
    }
}
