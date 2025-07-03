<?php

namespace IJIDeals\IJIProductCatalog\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use IJIDeals\IJIProductCatalog\Models\Brand;

class StoreBrandRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Assuming admin users are authorized or a specific policy is in place.
        // Replace with actual authorization logic, e.g., checking for an admin role or permission.
        // For example: return $this->user() && $this->user()->can('create', Brand::class);
        return $this->user() ? true : false; // Basic check: user must be authenticated. Add real policy/permission check.
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
            'slug' => ['nullable', 'string', 'max:255', Rule::unique(config('ijiproductcatalog.tables.brands', 'brands'), 'slug')],
            'description' => 'nullable|string',
            'website_url' => 'nullable|url|max:255',
            'social_links' => 'nullable|array',
            'story' => 'nullable|string',
            'is_featured' => 'nullable|boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:1000',
            'meta_keywords' => 'nullable|string|max:1000',
            'status' => ['required', 'string', Rule::in(['active', 'inactive', 'pending_approval'])],
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:2048', // Consider Spatie MediaLibraryPro specific validation if used
            'cover_photo' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:5120',
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        if (!$this->has('is_featured')) {
            $this->merge([
                'is_featured' => false,
            ]);
        } else {
            $this->merge([
                'is_featured' => filter_var($this->is_featured, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            ]);
        }

        if (empty($this->status)) {
            $this->merge([
                'status' => 'active', // Default status for new brands by admin
            ]);
        }
    }
}
