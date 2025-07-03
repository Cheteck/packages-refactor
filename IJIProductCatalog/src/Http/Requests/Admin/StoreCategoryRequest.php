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
    public function rules()
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
}
