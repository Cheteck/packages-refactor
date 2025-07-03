<?php

namespace IJIDeals\Social\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePostRequest extends FormRequest
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
            'contenu' => 'sometimes|string',
            'type' => 'sometimes|string|in:texte,image,vidéo,lien',
            'visibilite' => 'sometimes|string|in:public,amis,prive',
            'statut' => 'sometimes|string|in:publie,brouillon,archive', // Added for updates
        ];
    }
}
