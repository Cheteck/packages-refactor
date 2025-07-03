<?php

namespace IJIDeals\FileManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule; // If using Rule::exists for attachable_type

class StoreFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check(); // Only authenticated users can upload
    }

    public function rules(): array
    {
        // Fetch max_size_kb and allowed_extensions from config
        // Using the correct config key 'ijideals-file-management'
        $maxSizeKb = config('ijideals-file-management.validation.max_size_kb', 10240); // Default 10MB
        $allowedExtensions = config('ijideals-file-management.validation.allowed_extensions', []);

        $rules = [
            'file' => [
                'required',
                'file',
                // The 'mimes' rule checks extensions. Make sure $allowedExtensions is an array of strings.
                'mimes:'.(is_array($allowedExtensions) ? implode(',', $allowedExtensions) : ''),
                'max:'.$maxSizeKb,
            ],
            // 'attachable_type' is a string identifier like 'user', 'post', 'product_image' etc.
            // It's used by the controller to map to an actual model class.
            'attachable_type' => 'nullable|string|max:255|alpha_dash', // alpha_dash allows a-z, 0-9, _ and -

            // 'attachable_id' can be integer or UUID depending on your related models' primary key types.
            // For this basic implementation, we assume integer. If UUIDs are used, change 'integer' to 'uuid'.
            'attachable_id' => 'nullable|integer|min:1',
        ];

        // Conditional validation for attachable_id if attachable_type is present
        // If attachable_type is given, then attachable_id must also be given.
        if ($this->input('attachable_type')) {
            $rules['attachable_id'] = 'required|integer|min:1'; // Or 'required|uuid'

            // Advanced: Validate that attachable_id exists for the given attachable_type.
            // This requires a mapping from 'attachable_type' string to a table name.
            // Example (if you have a reliable mapping function/config):
            // $modelClass = $this->mapAttachableTypeToModelClass($this->input('attachable_type'));
            // if ($modelClass && class_exists($modelClass)) {
            //     $tableName = (new $modelClass())->getTable();
            //     $rules['attachable_id'] .= "|exists:{$tableName},id"; // Assuming 'id' is the PK
            // } else {
            //     // If type is provided but not mappable, add an error.
            //     // This logic might be better placed in a custom FormRequest rule or controller.
            // }
        } elseif ($this->input('attachable_id')) {
            // If attachable_id is given, then attachable_type must also be given.
            $rules['attachable_type'] = 'required|string|max:255|alpha_dash';
        }

        // Optional: Add other fields like 'disk', 'directory', 'type' (category) from request
        $rules['disk'] = 'nullable|string|max:50'; // e.g., 'public', 's3_private'
        $rules['directory'] = 'nullable|string|max:255'; // e.g., 'user_avatars', 'product_manuals'
        // 'type' here refers to the user-defined category for the attachment,
        // not the MIME type, which is inferred by UploadService.
        $rules['custom_type'] = 'nullable|string|max:50';

        return $rules;
    }

    // Example helper (could be moved to a service or trait if used elsewhere)
    // protected function mapAttachableTypeToModelClass(string $type): ?string
    // {
    //     $map = config('ijideals-file-management.attachable_map', [
    //         // 'user' => \IJIDeals\UserManagement\Models\User::class,
    //     ]);
    //     return $map[strtolower($type)] ?? null;
    // }

    public function messages(): array
    {
        return [
            'file.required' => 'A file is required for upload.',
            'file.mimes' => 'The uploaded file type is not allowed.',
            'file.max' => 'The uploaded file exceeds the maximum allowed size.',
            'attachable_type.required_with' => 'The attachable type is required when an attachable ID is provided.',
            'attachable_id.required_with' => 'The attachable ID is required when an attachable type is provided.',
        ];
    }
}
