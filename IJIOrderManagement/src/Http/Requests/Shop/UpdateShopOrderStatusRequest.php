<?php

namespace IJIDeals\IJIOrderManagement\Http\Requests\Shop;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use IJIDeals\IJICommerce\Models\Shop;
use IJIDeals\IJIOrderManagement\Models\Order;

class UpdateShopOrderStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if (!$this->user()) {
            return false;
        }

        /** @var Shop $shop */
        $shop = $this->route('shop');
        /** @var Order $order */
        $order = $this->route('order');

        // Ensure the order actually belongs to the shop, in addition to policy check
        if (!$shop || !$order || $order->shop_id !== $shop->id) {
            return false;
        }

        return $this->user()->can('updateShopOrderStatus', [$order, $shop]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // These statuses should ideally come from a config or an Enum
        $allowedStatuses = config('ijiordermanagement.shop_updatable_statuses', [
            'processing',
            'shipped',
            'completed',
            'on_hold',
            'cancelled_by_shop'
        ]);

        return [
            'status' => ['required', 'string', Rule::in($allowedStatuses)],
            'tracking_number' => 'nullable|string|max:255|required_if:status,shipped', // Tracking number required if status is 'shipped'
            'notes_for_customer' => 'nullable|string|max:1000',
        ];
    }

    /**
     * @return array
     */
    public function bodyParameters(): array
    {
        return [
            'status' => [
                'description' => 'The new status of the order.',
                'type' => 'string',
                'required' => true,
                'enum' => ['processing', 'shipped', 'completed', 'on_hold', 'cancelled_by_shop'],
            ],
            'tracking_number' => [
                'description' => 'The tracking number for the order. Required if status is "shipped".',
                'type' => 'string',
                'required' => false,
            ],
            'notes_for_customer' => [
                'description' => 'Optional notes for the customer regarding the order status update.',
                'type' => 'string',
                'required' => false,
            ],
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
            'tracking_number.required_if' => 'The tracking number is required when marking the order as shipped.',
        ];
    }
}
