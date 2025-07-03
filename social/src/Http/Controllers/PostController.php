<?php

namespace IJIDeals\Social\Http\Controllers;

use App\Http\Controllers\Controller;
use IJIDeals\Social\Http\Requests\StorePostRequest;
use IJIDeals\Social\Http\Requests\UpdatePostRequest;
use IJIDeals\Social\Http\Resources\PostResource;
// Will be created later
use IJIDeals\Social\Models\Post;
use Illuminate\Support\Facades\Gate; // Assuming base controller is in App\Http\Controllers
use OpenApi\Annotations as OA; // Import OpenApi namespace

/**
 * @OA\Tag(
 *     name="Posts",
 *     description="API Endpoints for managing social posts"
 * )
 */
class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *     path="/api/v1/social/posts",
     *     operationId="listPosts",
     *     summary="List all social posts",
     *     tags={"Posts"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *
     *         @OA\JsonContent(
     *             type="array",
     *
     *             @OA\Items(ref="#/components/schemas/Post")
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
     *         response=403,
     *         description="Forbidden",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="This action is unauthorized.")
     *         )
     *     )
     * )
     */
    public function index()
    {
        Gate::authorize('viewAny', Post::class);

        return PostResource::collection(Post::latest()->paginate());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @OA\Post(
     *     path="/api/v1/social/posts",
     *     operationId="createPost",
     *     summary="Create a new social post",
     *     tags={"Posts"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Post data to store",
     *
     *         @OA\JsonContent(
     *             required={"content"},
     *
     *             @OA\Property(property="content", type="string", example="This is my first post!"),
     *             @OA\Property(property="type", type="string", enum={"text", "image", "video", "link"}, default="text", example="text"),
     *             @OA\Property(property="media_url", type="string", format="url", nullable=true, example="http://example.com/image.jpg", description="URL of attached media"),
     *             @OA\Property(property="visibility", type="string", enum={"public", "friends", "private"}, default="public", example="public"),
     *             @OA\Property(property="metadata", type="object", nullable=true, description="Additional metadata (JSON)")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Post created successfully",
     *
     *         @OA\JsonContent(ref="#/components/schemas/Post")
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
    public function store(StorePostRequest $request)
    {
        Gate::authorize('create', Post::class);
        $post = Post::create(array_merge($request->validated(), ['user_id' => auth()->id()]));

        return new PostResource($post);
    }

    /**
     * Display the specified resource.
     *
     * @OA\Get(
     *     path="/api/v1/social/posts/{post}",
     *     operationId="getPostById",
     *     summary="Get details of a specific social post",
     *     tags={"Posts"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="post",
     *         in="path",
     *         required=true,
     *         description="ID of the post to retrieve",
     *
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *
     *         @OA\JsonContent(ref="#/components/schemas/Post")
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
     *             @OA\Property(property="message", type="string", example="Post not found.")
     *         )
     *     )
     * )
     */
    public function show(Post $post)
    {
        Gate::authorize('view', $post);

        return new PostResource($post);
    }

    /**
     * Update the specified resource in storage.
     *
     * @OA\Put(
     *     path="/api/v1/social/posts/{post}",
     *     operationId="updatePost",
     *     summary="Update an existing social post (full replacement)",
     *     tags={"Posts"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="post",
     *         in="path",
     *         required=true,
     *         description="ID of the post to update",
     *
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Post data to update (all fields will be replaced)",
     *
     *         @OA\JsonContent(
     *             required={"content"},
     *
     *             @OA\Property(property="content", type="string", example="Updated post content!"),
     *             @OA\Property(property="type", type="string", enum={"text", "image", "video", "link"}, example="text"),
     *             @OA\Property(property="media_url", type="string", format="url", nullable=true, example="http://example.com/updated_image.jpg"),
     *             @OA\Property(property="visibility", type="string", enum={"public", "friends", "private"}, example="friends"),
     *             @OA\Property(property="metadata", type="object", nullable=true, example={"mood": "happy"})
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Post updated successfully",
     *
     *         @OA\JsonContent(ref="#/components/schemas/Post")
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
     *             @OA\Property(property="message", type="string", example="Post not found.")
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
     * ),
     *
     * @OA\Patch(
     *     path="/api/v1/social/posts/{post}",
     *     operationId="patchPost",
     *     summary="Partially update an existing social post",
     *     tags={"Posts"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="post",
     *         in="path",
     *         required=true,
     *         description="ID of the post to update",
     *
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *
     *     @OA\RequestBody(
     *         description="Partial post data to update",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="content", type="string", example="Only updating content."),
     *             @OA\Property(property="visibility", type="string", enum={"public", "friends", "private"}, example="private")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Post updated successfully",
     *
     *         @OA\JsonContent(ref="#/components/schemas/Post")
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
     *         description="Post not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors"
     *     )
     * )
     */
    public function update(UpdatePostRequest $request, Post $post)
    {
        Gate::authorize('update', $post);
        $post->update($request->validated());

        return new PostResource($post);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @OA\Delete(
     *     path="/api/v1/social/posts/{post}",
     *     operationId="deletePost",
     *     summary="Delete a social post",
     *     tags={"Posts"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="post",
     *         in="path",
     *         required=true,
     *         description="ID of the post to delete",
     *
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *
     *     @OA\Response(
     *         response=204,
     *         description="Post deleted successfully (No Content)"
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
     *         description="Post not found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Post not found.")
     *         )
     *     )
     * )
     */
    public function destroy(Post $post)
    {
        Gate::authorize('delete', $post);
        $post->delete();

        return response()->noContent();
    }
}
