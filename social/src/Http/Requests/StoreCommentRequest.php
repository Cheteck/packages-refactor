<?php

namespace IJIDeals\Social\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreCommentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Basic check: user must be authenticated to comment.
        // Finer-grained authorization (e.g., can the user comment on *this specific* post)
        // can be handled by a Policy (e.g., CommentPolicy or PostPolicy).
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'post_id' => 'required|exists:social_posts,id', // Assumes 'social_posts' is the table name for posts
            'contenu' => 'required|string|max:2000',
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
            'post_id.required' => 'L\'identifiant du post est requis pour ajouter un commentaire.',
            'post_id.exists' => 'Le post sur lequel vous essayez de commenter n\'existe pas ou n\'est plus disponible.',
            'contenu.required' => 'Le contenu du commentaire est requis.',
            'contenu.string' => 'Le contenu du commentaire doit être une chaîne de caractères.',
            'contenu.max' => 'Le contenu du commentaire ne peut pas dépasser 2000 caractères.',
        ];
    }
}
