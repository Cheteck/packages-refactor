<?php

namespace IJIDeals\IJIProductCatalog\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use IJIDeals\IJIProductCatalog\Models\Category;

class UpdateCategoryRequest extends FormRequest
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
        // $category = $this->route('category');
        // return $this->user() && $category && $this->user()->can('update', $category);
        return $this->user() ? true : false; // Basic check: user must be authenticated.
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        $categoryId = $this->route('category') ? $this->route('category')->id : null;
        $tableName = (new Category())->getTable();

        return [
            'name' => 'sometimes|required|string|max:255',
            'slug' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique($tableName, 'slug')->ignore($categoryId)],
            'description' => 'nullable|string',
            // The complex descendant check for parent_id will remain in the controller for now.
            // Basic validation for parent_id:
            'parent_id' => ['nullable', 'integer', Rule::exists($tableName, 'id')->whereNot('id', $categoryId)], // Prevent self-parenting
            'image' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:2048',
        ];
    }
}
