<?php

namespace IJIDeals\IJIOrderManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use IJIDeals\IJICommerce\Models\Shop; // Assuming Shop model is in IJICommerce

class StoreOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Ensures the user is authenticated.
        // More complex authorization (e.g., can user order from this specific shop, item availability)
        // would typically be handled in a service layer or post-validation in the controller.
        return $this->user() ? true : false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'shop_id' => ['required', Rule::exists(config('ijicommerce.tables.shops', 'shops'), 'id')],
            'items' => 'required|array|min:1',
            'items.*.type' => ['required', Rule::in(['shopproduct', 'shopproductvariation'])],
            'items.*.id' => 'required|integer|min:1', // Ensure IDs are positive integers
            'items.*.quantity' => 'required|integer|min:1',

            'billing_address' => 'required|array',
            'billing_address.first_name' => 'required|string|max:255',
            'billing_address.last_name' => 'required|string|max:255',
            'billing_address.address_line_1' => 'required|string|max:255',
            'billing_address.address_line_2' => 'nullable|string|max:255',
            'billing_address.city' => 'required|string|max:255',
            'billing_address.state' => 'required|string|max:255',
            'billing_address.postal_code' => 'required|string|max:20',
            'billing_address.country_code' => 'required|string|max:2', // ISO 3166-1 alpha-2
            'billing_address.phone' => 'nullable|string|max:30',

            'shipping_address' => 'required|array',
            'shipping_address.first_name' => 'required|string|max:255',
            'shipping_address.last_name' => 'required|string|max:255',
            'shipping_address.address_line_1' => 'required|string|max:255',
            'shipping_address.address_line_2' => 'nullable|string|max:255',
            'shipping_address.city' => 'required|string|max:255',
            'shipping_address.state' => 'required|string|max:255',
            'shipping_address.postal_code' => 'required|string|max:20',
            'shipping_address.country_code' => 'required|string|max:2', // ISO 3166-1 alpha-2
            'shipping_address.phone' => 'nullable|string|max:30',

            // This is a placeholder; actual payment integration would be more complex.
            'payment_method_token' => 'required|string|max:255',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'items.*.id.required' => 'The item ID is required.',
            'items.*.id.integer' => 'The item ID must be an integer.',
            'items.*.id.min' => 'The item ID must be at least 1.',
            'items.*.quantity.required' => 'The item quantity is required.',
            'items.*.quantity.integer' => 'The item quantity must be an integer.',
            'items.*.quantity.min' => 'The item quantity must be at least 1.',
            'items.*.type.required' => 'The item type is required.',
            'items.*.type.in' => 'The selected item type is invalid.',
        ];
    }
}
