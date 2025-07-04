<?php

namespace IJIDeals\IJIProductCatalog\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use IJIDeals\IJIProductCatalog\Models\Brand;

class UpdateBrandRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Assuming admin users are authorized or a specific policy is in place.
        // Replace with actual authorization logic.
        // For example:
        // $brand = $this->route('brand');
        // return $this->user() && $brand && $this->user()->can('update', $brand);
        return $this->user() ? true : false; // Basic check: user must be authenticated. Add real policy/permission check.
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $brandId = $this->route('brand') ? $this->route('brand')->id : null;

        return [
            'name' => 'sometimes|required|string|max:255',
            'slug' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique(config('ijiproductcatalog.tables.brands', 'brands'), 'slug')->ignore($brandId)],
            'description' => 'nullable|string',
            'website_url' => 'nullable|url|max:255',
            'social_links' => 'nullable|array',
            'story' => 'nullable|string',
            'is_featured' => 'nullable|boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:1000',
            'meta_keywords' => 'nullable|string|max:1000',
            'status' => ['sometimes', 'required', 'string', Rule::in(['active', 'inactive', 'pending_approval'])],
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:2048',
            'cover_photo' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:5120',
        ];
    }

    /**
     * @return array
     */
    public function bodyParameters(): array
    {
        return [
            'name' => [
                'description' => 'The name of the brand.',
                'type' => 'string',
                'required' => false,
            ],
            'slug' => [
                'description' => 'A unique slug for the brand. If not provided, one will be generated from the name.',
                'type' => 'string',
                'required' => false,
            ],
            'description' => [
                'description' => 'A brief description of the brand.',
                'type' => 'string',
                'required' => false,
            ],
            'website_url' => [
                'description' => 'The official website URL of the brand.',
                'type' => 'string',
                'format' => 'url',
                'required' => false,
            ],
            'social_links' => [
                'description' => 'An array of social media links for the brand.',
                'type' => 'array',
                'required' => false,
                'example' => [['platform' => 'facebook', 'url' => 'https://facebook.com/brand']],
            ],
            'story' => [
                'description' => 'A detailed story or history of the brand.',
                'type' => 'string',
                'required' => false,
            ],
            'is_featured' => [
                'description' => 'Whether the brand should be featured on the platform.',
                'type' => 'boolean',
                'required' => false,
            ],
            'meta_title' => [
                'description' => 'SEO meta title for the brand.',
                'type' => 'string',
                'required' => false,
            ],
            'meta_description' => [
                'description' => 'SEO meta description for the brand.',
                'type' => 'string',
                'required' => false,
            ],
            'meta_keywords' => [
                'description' => 'SEO meta keywords for the brand (comma-separated).',
                'type' => 'string',
                'required' => false,
            ],
            'status' => [
                'description' => 'The status of the brand.',
                'type' => 'string',
                'required' => false,
                'enum' => ['active', 'inactive', 'pending_approval'],
            ],
            'logo' => [
                'description' => 'The logo image file for the brand.',
                'type' => 'file',
                'required' => false,
            ],
            'cover_photo' => [
                'description' => 'The cover photo image file for the brand.',
                'type' => 'file',
                'required' => false,
            ],
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        if ($this->has('is_featured')) {
             $this->merge([
                'is_featured' => filter_var($this->is_featured, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            ]);
        }
        // No default for status on update, let it be what it is or what's provided.
        // If 'is_featured' is not present, it will be handled by the controller or model to keep its existing value if not part of validated data.
        // The 'nullable|boolean' rule means if it's not present, it won't be validated,
        // and $request->validated() won't include it.
        // The controller logic that was:
        //  } else if (!array_key_exists('is_featured', $validated)) {
        //      $validated['is_featured'] = $brand->is_featured;
        //  }
        // should be handled post-validation if `is_featured` is not in `validated()` data.
        // A better approach is to ensure the key is always present if it's meant to be updatable to false.
        // For now, FormRequest only prepares what's explicitly sent or sets defaults for creation.
        // The controller's update logic should handle merging $validatedData with existing data carefully.
    }
}
