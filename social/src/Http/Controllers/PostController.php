<?php

namespace IJIDeals\Social\Http\Controllers;

use App\Http\Controllers\Controller; // Assuming base controller is in App\Http\Controllers
use IJIDeals\Social\Http\Requests\StorePostRequest;
use IJIDeals\Social\Http\Requests\UpdatePostRequest;
use IJIDeals\IJIProductCatalog\Models\MasterProduct; // Added
use IJIDeals\IJIShopListings\Models\ShopProduct; // Added
use Illuminate\Support\Facades\Validator; // Added
use Illuminate\Validation\ValidationException; // Added
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

        $validatedData = $request->validated();
        $taggedProductsData = $this->validateAndPrepareTaggedProducts($validatedData['tagged_products'] ?? []);

        // Default 'author_type' and 'author_id' if not provided (e.g. for user posts)
        // For a system where other entities (like Shops) can post, this needs to be more robust.
        // Assuming auth()->id() is the author_id and User::class is the author_type for now.
        // This should ideally come from the request or be set by a service based on context.
        $author = auth()->user();
        $postData = array_merge($validatedData, [
            'author_id' => $author->id,
            'author_type' => get_class($author) // Or User::class if you have a specific user model
        ]);

        // Remove tagged_products from postData as it's handled by relation
        unset($postData['tagged_products']);

        $post = Post::create($postData);

        if (!empty($taggedProductsData)) {
            $post->taggedProducts()->sync($taggedProductsData);
        }

        // Manually trigger 'created' events if `create()` doesn't due to fillable or other reasons
        // $post->wasRecentlyCreated = true; // Ensure events fire if not already
        // event(new \Illuminate\Database\Events\ModelCreated($post));


        return new PostResource($post->load('taggedProducts'));
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

        $validatedData = $request->validated();
        $taggedProductsData = [];

        if (isset($validatedData['tagged_products'])) {
            $taggedProductsData = $this->validateAndPrepareTaggedProducts($validatedData['tagged_products']);
        }

        // Remove tagged_products from validatedData before updating the post model
        unset($validatedData['tagged_products']);

        $post->update($validatedData);

        // Sync tagged products only if the key was present in the request
        if ($request->has('tagged_products')) {
            $post->taggedProducts()->sync($taggedProductsData);
        }

        // Manually trigger 'updated' events if `update()` doesn't
        // event(new \Illuminate\Database\Events\ModelUpdated($post));


        return new PostResource($post->load('taggedProducts'));
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

    /**
     * Validate and prepare tagged products data for syncing.
     *
     * @param array $taggedProductsInput
     * @return array
     * @throws ValidationException
     */
    private function validateAndPrepareTaggedProducts(array $taggedProductsInput): array
    {
        if (empty($taggedProductsInput)) {
            return [];
        }

        $preparedData = [];
        $errors = [];

        foreach ($taggedProductsInput as $index => $productTag) {
            $validator = Validator::make($productTag, [
                'id' => 'required|integer|min:1',
                'type' => 'required|string|in:MasterProduct,ShopProduct',
            ]);

            if ($validator->fails()) {
                $errors["tagged_products.{$index}"] = $validator->errors()->all();
                continue;
            }

            $modelClass = null;
            if ($productTag['type'] === 'MasterProduct') {
                $modelClass = MasterProduct::class;
            } elseif ($productTag['type'] === 'ShopProduct') {
                $modelClass = ShopProduct::class;
            }

            if (!$modelClass || !app($modelClass)->find($productTag['id'])) {
                $errors["tagged_products.{$index}.id"] = "The selected {$productTag['type']} with ID {$productTag['id']} is invalid.";
                continue;
            }

            // The key for sync should be the ID of the product.
            // The value should be an array of pivot attributes.
            $preparedData[$productTag['id']] = ['taggable_type' => $modelClass];
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        return $preparedData;
    }
}
