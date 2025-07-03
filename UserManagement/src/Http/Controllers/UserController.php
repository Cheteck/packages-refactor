<?php

namespace IJIDeals\UserManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use IJIDeals\UserManagement\Models\User; // Assuming your User model is in this namespace

class UserController extends Controller
{
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $user = User::find($id);
        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'User not found'], 404);
            }
            // For web, returning the view even if user is null might be desired if the view can handle it
            // or you might redirect/abort. For simplicity, we pass it as is.
            // Consider abort(404, 'User not found'); for web if user must exist.
            return view('user-management::users.show', compact('user'));
        }

        if ($request->expectsJson()) {
            return response()->json($user);
        }
        return view('user-management::users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response|\Illuminate\Contracts\View\View
     */
    public function edit(Request $request, $id) // Added Request $request
    {
        $user = User::find($id);
        if (!$user) {
            // API requests typically wouldn't hit an 'edit' endpoint that returns a form.
            // This endpoint is primarily for web.
            // Consider abort(404, 'User not found');
            return view('user-management::users.edit', compact('user'));
        }
        return view('user-management::users.edit', compact('user'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'User not found'], 404);
            }
            // For web, you might redirect back with an error, or abort
            return redirect()->back()->withErrors(['User not found.']);
        }

        // Basic validation - expand this as needed
        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
            'username' => 'sometimes|string|max:255|unique:users,username,' . $user->id . '|nullable',
            'profile_photo_path' => 'nullable|string|max:2048',
            'cover_photo_path' => 'nullable|string|max:2048',
            'bio' => 'nullable|string',
            'birthdate' => 'nullable|date',
            'gender' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:25',
            'preferred_language' => 'nullable|string|max:10',
            'location' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
        ]);

        $user->update($validatedData);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'User updated successfully', 'user' => $user]);
        }

        return redirect()->route('user-management.users.show', $user->id)
                         ->with('success', 'User updated successfully.');
    }
}
