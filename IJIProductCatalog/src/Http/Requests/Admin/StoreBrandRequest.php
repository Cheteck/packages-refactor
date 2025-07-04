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
    public function rules(): array
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
     * @return array
     */
    public function bodyParameters(): array
    {
        return [
            'name' => [
                'description' => 'The name of the brand.',
                'type' => 'string',
                'required' => true,
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
                'default' => false,
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
                'required' => true,
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
