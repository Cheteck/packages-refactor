<?php

namespace IJIDeals\Social\Http\Controllers;

use App\Http\Controllers\Controller; // Assuming base controller
use IJIDeals\Social\Http\Requests\StoreCommentRequest; // Changed to new Comment model namespace
use IJIDeals\Social\Http\Requests\UpdateCommentRequest; // Changed to new Post model namespace
use IJIDeals\Social\Http\Resources\CommentResource;
use IJIDeals\Social\Models\Comment;
use IJIDeals\Social\Models\Post;
// CommentPolicy will be created later
// use IJIDeals\Social\Policies\CommentPolicy;
use Illuminate\Support\Facades\Gate;
use OpenApi\Annotations as OA; // Import OpenApi namespace

/**
 * @OA\Tag(
 *     name="Comments",
 *     description="API Endpoints for managing comments on social posts"
 * )
 */
class CommentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *     path="/api/v1/social/posts/{post}/comments",
     *     operationId="listCommentsForPost",
     *     summary="List all comments for a specific social post",
     *     tags={"Comments"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="post",
     *         in="path",
     *         required=true,
     *         description="ID of the post to retrieve comments for",
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
     *             @OA\Items(ref="#/components/schemas/Comment")
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
     *         description="Post not found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="No query results for model [IJIDeals\\Social\\Models\\Post] 1")
     *         )
     *     )
     * )
     */
    public function index(Post $post)
    {
        // No specific gate for viewing all comments on a post for now,
        // Post viewability should handle this.
        return CommentResource::collection($post->comments()->latest()->paginate());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @OA\Post(
     *     path="/api/v1/social/posts/{post}/comments",
     *     operationId="createComment",
     *     summary="Create a new comment on a social post",
     *     tags={"Comments"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="post",
     *         in="path",
     *         required=true,
     *         description="ID of the post to comment on",
     *
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Comment data to store",
     *
     *         @OA\JsonContent(
     *             required={"content"},
     *
     *             @OA\Property(property="content", type="string", example="Great post!"),
     *             @OA\Property(property="parent_id", type="integer", format="int64", nullable=true, example=1, description="ID of the parent comment if this is a reply")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Comment created successfully",
     *
     *         @OA\JsonContent(ref="#/components/schemas/Comment")
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
     *         description="Post not found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="No query results for model [IJIDeals\\Social\\Models\\Post] 1")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function store(StoreCommentRequest $request, Post $post)
    {
        Gate::authorize('create', Comment::class);
        $comment = $post->comments()->create(array_merge(
            $request->validated(),
            ['user_id' => auth()->id()]
        ));

        return new CommentResource($comment);
    }

    /**
     * Display the specified resource.
     *
     * @OA\Get(
     *     path="/api/v1/social/comments/{comment}",
     *     operationId="getCommentById",
     *     summary="Get details of a specific comment",
     *     tags={"Comments"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="comment",
     *         in="path",
     *         required=true,
     *         description="ID of the comment to retrieve",
     *
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *
     *         @OA\JsonContent(ref="#/components/schemas/Comment")
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
     *         description="Comment not found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="No query results for model [IJIDeals\\Social\\Models\\Comment] 1")
     *         )
     *     )
     * )
     */
    public function show(Comment $comment)
    {
        Gate::authorize('view', $comment);

        return new CommentResource($comment);
    }

    /**
     * Update the specified resource in storage.
     *
     * @OA\Put(
     *     path="/api/v1/social/comments/{comment}",
     *     operationId="updateComment",
     *     summary="Update an existing comment (full replacement)",
     *     tags={"Comments"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="comment",
     *         in="path",
     *         required=true,
     *         description="ID of the comment to update",
     *
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Comment data to update",
     *
     *         @OA\JsonContent(
     *             required={"content"},
     *
     *             @OA\Property(property="content", type="string", example="Updated comment content!")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Comment updated successfully",
     *
     *         @OA\JsonContent(ref="#/components/schemas/Comment")
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Comment not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors"
     *     )
     * ),
     *
     * @OA\Patch(
     *     path="/api/v1/social/comments/{comment}",
     *     operationId="patchComment",
     *     summary="Partially update an existing comment",
     *     tags={"Comments"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="comment",
     *         in="path",
     *         required=true,
     *         description="ID of the comment to update",
     *
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *
     *     @OA\RequestBody(
     *         description="Partial comment data to update",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="content", type="string", example="Partially updated content.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Comment updated successfully",
     *
     *         @OA\JsonContent(ref="#/components/schemas/Comment")
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Comment not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors"
     *     )
     * )
     */
    public function update(UpdateCommentRequest $request, Comment $comment)
    {
        Gate::authorize('update', $comment);
        $comment->update($request->validated());

        return new CommentResource($comment);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @OA\Delete(
     *     path="/api/v1/social/comments/{comment}",
     *     operationId="deleteComment",
     *     summary="Delete a comment",
     *     tags={"Comments"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="comment",
     *         in="path",
     *         required=true,
     *         description="ID of the comment to delete",
     *
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *
     *     @OA\Response(
     *         response=204,
     *         description="Comment deleted successfully (No Content)"
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
     *         description="Comment not found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="No query results for model [IJIDeals\\Social\\Models\\Comment] 1")
     *         )
     *     )
     * )
     */
    public function destroy(Comment $comment)
    {
        Gate::authorize('delete', $comment);
        $comment->delete();

        return response()->noContent();
    }
}
