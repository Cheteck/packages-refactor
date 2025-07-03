<?php

namespace IJIDeals\Social\Http\Requests;

use IJIDeals\Social\Models\Comment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth; // Assuming this is the correct path to your Comment model

class UpdateCommentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $comment = $this->route('comment'); // Assumes route model binding for 'comment'

        // If $comment is not an instance of Comment, or if the authenticated user is not the author, deny access.
        // Ensure your Comment model has a 'user_id' attribute.
        if (! $comment instanceof Comment || $comment->user_id !== Auth::id()) {
            return false;
        }

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
            'contenu' => ['sometimes', 'required', 'string', 'max:2000'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'contenu.required' => 'Le contenu du commentaire est requis.',
            'contenu.string' => 'Le contenu du commentaire doit être une chaîne de caractères.',
            'contenu.max' => 'Le contenu du commentaire ne peut pas dépasser 2000 caractères.',
        ];
    }
}
