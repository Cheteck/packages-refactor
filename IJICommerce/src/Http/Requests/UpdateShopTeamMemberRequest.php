<?php

namespace IJIDeals\IJICommerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use IJIDeals\IJICommerce\Models\Shop;

class UpdateShopTeamMemberRequest extends FormRequest
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
        return $shop && $this->user()->can('manageTeam', $shop);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'role' => ['required', 'string', Rule::exists(config('permission.table_names.roles'), 'name')],
        ];
    }
}
