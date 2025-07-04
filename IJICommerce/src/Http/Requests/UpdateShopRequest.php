<?php

namespace IJIDeals\IJICommerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use IJIDeals\IJICommerce\Models\Shop; // Required for policy check

class UpdateShopRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Ensure the user is authenticated before checking permission
        if (!$this->user()) {
            return false;
        }
        // $this->route('shop') will get the Shop model instance from the route
        $shop = $this->route('shop');
        return $shop && $this->user()->can('update', $shop);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $shopId = $this->route('shop') ? $this->route('shop')->id : null;

        return [
            'name' => 'sometimes|required|string|max:255',
            'slug' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                Rule::unique(config('ijicommerce.tables.shops', 'shops'), 'slug')->ignore($shopId)
            ],
            'description' => 'nullable|string',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'website_url' => 'nullable|url|max:255',
            'status' => ['sometimes', 'required', 'string', Rule::in(['active', 'inactive', 'pending_approval', 'suspended'])],
            'display_address' => 'nullable|string|max:1000',
            'logo_path' => 'nullable|string|max:2048',
            'cover_photo_path' => 'nullable|string|max:2048',
            'settings' => 'nullable|array',
            'approved_at' => 'nullable|date',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:500',
        ];
    }

    /**
     * @return array
     */
    public function bodyParameters(): array
    {
        return [
            'name' => [
                'description' => 'The name of the shop.',
                'type' => 'string',
                'required' => false,
            ],
            'slug' => [
                'description' => 'A unique slug for the shop.',
                'type' => 'string',
                'required' => false,
            ],
            'description' => [
                'description' => 'A brief description of the shop.',
                'type' => 'string',
                'required' => false,
            ],
            'contact_email' => [
                'description' => 'The contact email for the shop.',
                'type' => 'string',
                'format' => 'email',
                'required' => false,
            ],
            'contact_phone' => [
                'description' => 'The contact phone number for the shop.',
                'type' => 'string',
                'required' => false,
            ],
            'website_url' => [
                'description' => 'The website URL for the shop.',
                'type' => 'string',
                'format' => 'url',
                'required' => false,
            ],
            'status' => [
                'description' => 'The status of the shop.',
                'type' => 'string',
                'required' => false,
                'enum' => ['active', 'inactive', 'pending_approval', 'suspended'],
            ],
            'display_address' => [
                'description' => 'The physical address to display for the shop.',
                'type' => 'string',
                'required' => false,
            ],
            'logo_path' => [
                'description' => 'The path to the shop\'s logo image.',
                'type' => 'string',
                'required' => false,
            ],
            'cover_photo_path' => [
                'description' => 'The path to the shop\'s cover photo image.',
                'type' => 'string',
                'required' => false,
            ],
            'settings' => [
                'description' => 'An array of key-value settings for the shop.',
                'type' => 'object',
                'required' => false,
            ],
            'approved_at' => [
                'description' => 'The timestamp when the shop was approved.',
                'type' => 'string',
                'format' => 'date-time',
                'required' => false,
            ],
            'meta_title' => [
                'description' => 'SEO meta title for the shop.',
                'type' => 'string',
                'required' => false,
            ],
            'meta_description' => [
                'description' => 'SEO meta description for the shop.',
                'type' => 'string',
                'required' => false,
            ],
            'meta_keywords' => [
                'description' => 'SEO meta keywords for the shop (comma-separated).',
                'type' => 'string',
                'required' => false,
            ],
        ];
    }
}