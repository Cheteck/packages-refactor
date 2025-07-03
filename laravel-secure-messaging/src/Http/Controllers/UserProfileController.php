<?php

namespace Acme\SecureMessaging\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash; // If password updates were allowed
use Illuminate\Validation\Rule;

class UserProfileController extends Controller
{
    protected $userModel;

    public function __construct()
    {
        $this->userModel = config('messaging.user_model');
        // Ensure the User model has a 'public_key' attribute or similar
        // This might be through a direct column, an accessor, or a related model
        // that the main User model is configured to handle transparently.
    }

    /**
     * Display the authenticated user's profile.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request)
    {
        $user = $request->user();
        // Assuming 'public_key' is an attribute on the user model or handled by an accessor
        // If it's a relationship that needs loading, the User model itself should handle that,
        // or documentation should instruct to add ->load('publicKeyRelation') if necessary.
        // For simplicity, we'll assume it's directly accessible.
        // $user->public_key will be part of the user object if it exists.

        return response()->json([
            'message' => 'User profile retrieved successfully.',
            'data' => $user
        ]);
    }

    /**
     * Update the authenticated user's profile.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        $user = $request->user();
        $userInstance = new $this->userModel();
        $userTable = $userInstance->getTable(); // Get table name dynamically

        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => [
                'sometimes',
                'string',
                'email',
                'max:255',
                Rule::unique($userTable, 'email')->ignore($user->id),
            ],
            // Password updates should ideally be a separate, more secure endpoint.
            // 'password' => 'sometimes|string|min:8|confirmed',
            'public_key' => 'nullable|string|max:2048', // Public key for E2EE
        ]);

        if (isset($validatedData['name'])) {
            $user->name = $validatedData['name'];
        }
        if (isset($validatedData['email'])) {
            $user->email = $validatedData['email'];
        }

        // The User model itself should handle how 'public_key' is stored.
        // This could be a direct attribute, or it might use a mutator.
        if (array_key_exists('public_key', $validatedData)) {
            // It's important that the User model has `public_key` in its `$fillable` array
            // or uses a specific method to update it if it's not a direct column.
            // We assume here it's a direct fillable attribute for simplicity.
            // If not, the application's User model needs to handle this.
            $user->public_key = $validatedData['public_key'];
        }

        // if (isset($validatedData['password'])) {
        // $user->password = Hash::make($validatedData['password']);
        // }

        $user->save();

        return response()->json([
            'message' => 'Profile updated successfully.',
            'data' => $user
        ]);
    }

    /**
     * Get the public key for a given user.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserPublicKey(Request $request, $userId)
    {
        // Find the user using the configured user model
        $keyOwner = call_user_func([$this->userModel, 'find'], $userId);


        if (!$keyOwner) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        // Assuming the User model has a 'public_key' attribute or an accessor for it.
        // The attribute name 'public_key' should be documented for the user to implement
        // in their User model.
        $publicKey = $keyOwner->public_key;

        if (is_null($publicKey)) { // Check for null explicitly, as an empty string might be valid in some contexts
            return response()->json(['message' => 'Public key not found for this user or user model does not expose it as "public_key".'], 404);
        }

        return response()->json([
            'message' => 'Public key retrieved successfully.',
            'data' => [
                'user_id' => (int)$userId,
                'public_key' => $publicKey,
            ]
        ]);
    }
}
