<?php

namespace IJIDeals\Social\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization will be handled by the PostPolicy in the controller
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'contenu' => 'required|string',
            'type' => 'sometimes|string|in:texte,image,vidéo,lien',
            'visibilite' => 'sometimes|string|in:public,amis,prive',
            // 'statut' is not typically set by the user on creation, defaults in migration
        ];
    }
}
