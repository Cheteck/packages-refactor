<?php

namespace IJIDeals\Social\Http\Controllers;

use App\Http\Controllers\Controller;
use IJIDeals\Social\Http\Resources\LikeResource;
use IJIDeals\Social\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class LikeController extends Controller
{
    /**
     * Display a listing of the resource.
     * Lists users who liked a specific post.
     */
    public function index(Post $post)
    {
        Gate::authorize('view', $post);

        // Assuming Post model has a 'reactions' relationship, and we filter by type 'like'
        return LikeResource::collection($post->reactions()->where('type', 'like')->with('user')->latest()->paginate());
    }

    /**
     * Store a newly created resource in storage.
     * Likes a post.
     */
    public function store(Request $request, Post $post)
    {
        Gate::authorize('like', $post);
        // The explicit check for Auth::user() is redundant here.
        // If the 'like' gate requires an authenticated user (which is typical),
        // Laravel will automatically throw an AuthenticationException if the user is not logged in,
        // before this line is reached.
        $user = Auth::user();

        // Assuming Post model has a 'reactions' relationship
        $reaction = $post->reactions()->firstOrCreate(
            [
                'user_id' => $user->id,
                'type' => 'like',
            ]
        );

        $wasRecentlyCreated = $reaction->wasRecentlyCreated;

        return (new LikeResource($reaction->load('user')))
            ->additional(['meta' => ['created' => $wasRecentlyCreated]])
            ->response()
            ->setStatusCode($wasRecentlyCreated ? 201 : 200);
    }

    /**
     * Remove the specified resource from storage.
     * Unlikes a post.
     *
     * @OA\Delete(
     *     path="/api/v1/social/posts/{post}/likes",
     *     operationId="unlikePost",
     *     summary="Unlike a social post",
     *     tags={"Likes"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="post",
     *         in="path",
     *         required=true,
     *         description="ID of the post to unlike",
     *
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *
     *     @OA\Response(
     *         response=204,
     *         description="Post unliked successfully (No Content)"
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
     *         response=403,
     *         description="Forbidden",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="This action is unauthorized.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Like not found or already unliked",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Like not found or already unliked.")
     *         )
     *     )
     * )
     */
    public function destroy(Request $request, Post $post)
    {
        // Add authorization for unliking the post.
        // Assuming an 'unlike' ability is defined in the PostPolicy.
        Gate::authorize('unlike', $post);

        // The explicit check for Auth::user() is redundant here for the same reason as in store().
        $user = Auth::user();

        // Assuming Post model has a 'reactions' relationship
        $deletedCount = $post->reactions()
            ->where('user_id', $user->id)
            ->where('type', 'like')
            ->delete();

        if ($deletedCount > 0) {
            return response()->noContent();
        }

        return response()->json(['message' => 'Like not found or already unliked.'], 404);
    }
}
