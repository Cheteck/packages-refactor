<?php

namespace IJIDeals\Social\Http\Controllers;

use App\Http\Controllers\Controller;
use IJIDeals\Social\Http\Resources\UserResource;
use IJIDeals\Social\Models\Follow; // Added use for Follow model
use IJIDeals\UserManagement\Models\User; // Assuming UserResource is for displaying user listings
// FollowResource might be used if we were returning the Follow model instance itself.
// For now, as per spec, store/destroy don't return detailed follow info, and index returns UserResource.
// use IJIDeals\Social\Http\Resources\FollowResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use OpenApi\Annotations as OA; // Import OpenApi namespace

/**
 * @OA\Tag(
 *     name="Follows",
 *     description="API Endpoints for managing user follows"
 * )
 */
class FollowController extends Controller
{
    /**
     * List users who are followers of the given user.
     *
     * @OA\Get(
     *     path="/api/v1/social/users/{user}/followers",
     *     operationId="listUserFollowers",
     *     summary="List users who are followers of a specific user",
     *     tags={"Follows"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         required=true,
     *         description="ID of the user whose followers to list",
     *
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *
     *         @OA\JsonContent(
     *             type="array",
     *
     *             @OA\Items(ref="#/components/schemas/User")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="No query results for model [IJIDeals\\UserManagement\\Models\\User] 1")
     *         )
     *     )
     * )
     */
    public function followers(User $user)
    {
        // Authorization: Anyone can see who follows a user, unless profile is private.
        // Add Gate::authorize('viewFollowers', $user); if needed.
        $followerIds = Follow::where('followable_id', $user->id)
            ->where('followable_type', User::class) // Use User::class for morph type
            ->pluck('user_id');
        $followers = User::whereIn('id', $followerIds)->paginate();

        return UserResource::collection($followers);
    }

    /**
     * List users the given user is following.
     *
     * @OA\Get(
     *     path="/api/v1/social/users/{user}/following",
     *     operationId="listUserFollowing",
     *     summary="List users that a specific user is following",
     *     tags={"Follows"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         required=true,
     *         description="ID of the user whose followings to list",
     *
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *
     *         @OA\JsonContent(
     *             type="array",
     *
     *             @OA\Items(ref="#/components/schemas/User")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="No query results for model [IJIDeals\\UserManagement\\Models\\User] 1")
     *         )
     *     )
     * )
     */
    public function following(User $user)
    {
        // Authorization: Anyone can see who a user is following, unless profile is private.
        // Add Gate::authorize('viewFollowing', $user); if needed.
        $followedIds = Follow::where('user_id', $user->id)
            ->where('followable_type', User::class) // Use User::class for morph type
            ->pluck('followable_id');
        $followings = User::whereIn('id', $followedIds)->paginate();

        return UserResource::collection($followings);
    }

    /**
     * Store a newly created resource in storage.
     * Allows the authenticated user to follow $userToFollow.
     *
     * @OA\Post(
     *     path="/api/v1/social/users/{userToFollow}/follow",
     *     operationId="followUser",
     *     summary="Follow a user",
     *     tags={"Follows"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="userToFollow",
     *         in="path",
     *         required=true,
     *         description="ID of the user to follow",
     *
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Successfully followed user (new follow created)",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Successfully followed user.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Already following user (existing follow returned)",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Already following user.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Cannot follow self",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="You cannot follow yourself.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="User to follow not found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="No query results for model [IJIDeals\\UserManagement\\Models\\User] 1")
     *         )
     *     )
     * )
     */
    public function store(Request $request, User $userToFollow)
    {
        $currentUser = Auth::user();
        if (! $currentUser) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ($currentUser->id === $userToFollow->id) {
            return response()->json(['message' => 'You cannot follow yourself.'], 422);
        }

        // Gate::authorize('follow', $userToFollow); // If specific conditions to follow exist

        // Use firstOrCreate for the Follow model
        $follow = Follow::firstOrCreate([
            'user_id' => $currentUser->id,
            'followable_id' => $userToFollow->id,
            'followable_type' => User::class, // Use User::class for morph type
        ]);

        if ($follow->wasRecentlyCreated) {
            return response()->json(['message' => 'Successfully followed user.'], 201);
        }

        return response()->json(['message' => 'Already following user.'], 200);
    }

    /**
     * Remove the specified resource from storage.
     * Allows the authenticated user to unfollow $userToUnfollow.
     *
     * @OA\Delete(
     *     path="/api/v1/social/users/{userToUnfollow}/unfollow",
     *     operationId="unfollowUser",
     *     summary="Unfollow a user",
     *     tags={"Follows"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="userToUnfollow",
     *         in="path",
     *         required=true,
     *         description="ID of the user to unfollow",
     *
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *
     *     @OA\Response(
     *         response=204,
     *         description="User unfollowed successfully (No Content)"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Not following this user or user not found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Not following this user or already unfollowed.")
     *         )
     *     )
     * )
     */
    public function destroy(Request $request, User $userToUnfollow)
    {
        $currentUser = Auth::user();
        if (! $currentUser) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Gate::authorize('unfollow', $userToUnfollow); // If specific conditions to unfollow exist

        // Delete the Follow record
        $deletedCount = Follow::where('user_id', $currentUser->id)
            ->where('followable_id', $userToUnfollow->id)
            ->where('followable_type', User::class) // Use User::class for morph type
            ->delete();

        if ($deletedCount > 0) {
            return response()->noContent();
        }

        return response()->json(['message' => 'Not following this user or already unfollowed.'], 404);
    }
}
