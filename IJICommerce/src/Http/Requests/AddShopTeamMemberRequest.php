<?php

namespace IJIDeals\IJICommerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use IJIDeals\IJICommerce\Models\Shop;

class AddShopTeamMemberRequest extends FormRequest
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
        // Dynamically get the table name for users from the configured user model
        $userModelInstance = app(config('ijicommerce.user_model', \App\Models\User::class));
        $usersTable = $userModelInstance->getTable();

        return [
            'email' => ['required', 'email', Rule::exists($usersTable, 'email')],
            'role' => ['required', 'string', Rule::exists(config('permission.table_names.roles'), 'name')],
        ];
    }
}
