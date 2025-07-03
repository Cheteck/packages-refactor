<?php

namespace IJIDeals\IJIProductCatalog\Http\Controllers\Admin;

use Illuminate\Http\Request; // Keep for index, show, destroy
use Illuminate\Routing\Controller;
use IJIDeals\IJIProductCatalog\Models\Category;
// use Illuminate\Validation\Rule; // No longer needed here
use Illuminate\Support\Facades\Log;
use IJIDeals\IJIProductCatalog\Http\Requests\Admin\StoreCategoryRequest;
use IJIDeals\IJIProductCatalog\Http\Requests\Admin\UpdateCategoryRequest;

/**
 * Admin controller for managing product Categories.
 * Handles CRUD operations for categories, including hierarchical management.
 */
class CategoryController extends Controller
{
    /**
     * Display a paginated listing of root Categories, with their children loaded recursively.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $adminUser = $request->user();
        Log::debug('Admin CategoryController@index: Fetching all categories.', ['admin_user_id' => $adminUser ? $adminUser->id : null]);

        // Authorization placeholder
        // if ($adminUser && $adminUser->cannot('viewAny', Category::class)) { ... }

        $categories = Category::orderBy('name')
            ->whereNull('parent_id')
            ->with('childrenRecursive')
            ->paginate(config('ijiproductcatalog.pagination.admin_categories', 20));

        $categories->getCollection()->transform(function ($category) {
            $category->image_url = $category->getFirstMediaUrl(config('ijiproductcatalog.media_collections.category_image', 'category_images'), 'thumb');
            return $category;
        });

        Log::info('Admin CategoryController@index: Successfully fetched categories.', ['admin_user_id' => $adminUser ? $adminUser->id : null, 'count' => $categories->count(), 'total' => $categories->total()]);
        return response()->json($categories);
    }

    /**
     * Store a newly created Category in storage.
     * Handles image upload for the category.
     *
     * @param  \IJIDeals\IJIProductCatalog\Http\Requests\Admin\StoreCategoryRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreCategoryRequest $request)
    {
        $adminUser = $request->user();
        Log::debug('Admin CategoryController@store: Attempting to store new category.', ['admin_user_id' => $adminUser ? $adminUser->id : null, 'request_data' => $request->all()]);

        // Authorization handled by StoreCategoryRequest->authorize()
        // Validation handled by StoreCategoryRequest->rules()
        $validatedData = $request->validated();
        Log::debug('Admin CategoryController@store: Validation passed via FormRequest.', ['admin_user_id' => $adminUser ? $adminUser->id : null, 'validated_data' => $validatedData]);

        $categoryData = collect($validatedData)->except(['image'])->toArray();

        try {
            $category = Category::create($categoryData);
            Log::info('Admin CategoryController@store: Category created in database.', ['admin_user_id' => $adminUser ? $adminUser->id : null, 'category_id' => $category->id]);

            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                $category->addMediaFromRequest('image')->toMediaCollection(config('ijiproductcatalog.media_collections.category_image', 'category_images'));
                Log::info('Admin CategoryController@store: Image uploaded for category.', ['admin_user_id' => $adminUser ? $adminUser->id : null, 'category_id' => $category->id]);
            }

            Log::info('Admin CategoryController@store: Category stored successfully.', ['admin_user_id' => $adminUser ? $adminUser->id : null, 'category_id' => $category->id]);
            return response()->json($category->fresh()->load('parent', 'childrenRecursive'), 201);
        } catch (\Exception $e) {
            Log::error('Admin CategoryController@store: Error storing category.', [
                'admin_user_id' => $adminUser ? $adminUser->id : null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Error storing category. Please try again.'], 500);
        }
    }

    /**
     * Display the specified Category.
     * Includes parent, children (recursive), and image URL.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \IJIDeals\IJIProductCatalog\Models\Category  $category The Category instance via route model binding.
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, Category $category)
    {
        $adminUser = $request->user();
        Log::debug('Admin CategoryController@show: Showing category details.', ['admin_user_id' => $adminUser ? $adminUser->id : null, 'category_id' => $category->id]);

        // Authorization placeholder
        // if ($adminUser && $adminUser->cannot('view', $category)) { ... }

        $category->load(['parent', 'childrenRecursive']);
        $category->image_url = $category->getFirstMediaUrl(config('ijiproductcatalog.media_collections.category_image', 'category_images'), 'thumb');
        Log::info('Admin CategoryController@show: Successfully fetched category details.', ['admin_user_id' => $adminUser ? $adminUser->id : null, 'category_id' => $category->id]);
        return response()->json($category);
    }

    /**
     * Update the specified Category in storage.
     * Handles image updates and parent assignment (with descendant check).
     *
     * @param  \IJIDeals\IJIProductCatalog\Http\Requests\Admin\UpdateCategoryRequest  $request
     * @param  \IJIDeals\IJIProductCatalog\Models\Category  $category The Category instance via route model binding.
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $adminUser = $request->user();
        Log::debug('Admin CategoryController@update: Attempting to update category.', ['admin_user_id' => $adminUser ? $adminUser->id : null, 'category_id' => $category->id, 'request_data' => $request->all()]);

        // Authorization handled by UpdateCategoryRequest->authorize()
        // Validation handled by UpdateCategoryRequest->rules()
        $validatedData = $request->validated();
        Log::debug('Admin CategoryController@update: Validation passed via FormRequest.', ['admin_user_id' => $adminUser ? $adminUser->id : null, 'validated_data' => $validatedData]);

        // Descendant check remains in controller due to its complexity involving the existing model state.
        if (isset($validatedData['parent_id']) && $validatedData['parent_id']) {
            // Ensure the Category model has an `isDescendantOf` method.
            // This method should check if the current category ($this) is a descendant of the given $parentId.
            // A simple implementation might involve traversing up the tree from the potential new parent.
            // For this example, we assume $category->isDescendantOf($potentialParentId) exists.
            // A more robust check might involve loading the potential parent and checking its lineage against $category->id.
            $potentialParent = Category::find($validatedData['parent_id']);
            if ($potentialParent && $potentialParent->isDescendantOf($category->id)) {
                 Log::warning('Admin CategoryController@update: Attempted to set parent to a descendant of itself.', [
                    'admin_user_id' => $adminUser ? $adminUser->id : null,
                    'category_id' => $category->id,
                    'new_parent_id' => $validatedData['parent_id']
                ]);
                return response()->json(['errors' => ['parent_id' => ['Cannot set parent to one of its own descendants.']]], 422);
            }
        }

        $categoryData = collect($validatedData)->except(['image'])->toArray();

        try {
            $category->update($categoryData);
            Log::info('Admin CategoryController@update: Category updated in database.', ['admin_user_id' => $adminUser ? $adminUser->id : null, 'category_id' => $category->id]);

            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                $category->clearMediaCollection(config('ijiproductcatalog.media_collections.category_image', 'category_images'));
                $category->addMediaFromRequest('image')->toMediaCollection(config('ijiproductcatalog.media_collections.category_image', 'category_images'));
                Log::info('Admin CategoryController@update: Image updated for category.', ['admin_user_id' => $adminUser ? $adminUser->id : null, 'category_id' => $category->id]);
            }

            $category->load(['parent', 'childrenRecursive']);
            $category->image_url = $category->getFirstMediaUrl(config('ijiproductcatalog.media_collections.category_image', 'category_images'), 'thumb');
            Log::info('Admin CategoryController@update: Category updated successfully.', ['admin_user_id' => $adminUser ? $adminUser->id : null, 'category_id' => $category->id]);
            return response()->json($category->fresh()->load('parent', 'childrenRecursive'));
        } catch (\Exception $e) {
            Log::error('Admin CategoryController@update: Error updating category.', [
                'admin_user_id' => $adminUser ? $adminUser->id : null,
                'category_id' => $category->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Error updating category. Please try again.'], 500);
        }
    }

    /**
     * Remove the specified Category from storage.
     * Logs a warning if the category has children but proceeds with deletion.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \IJIDeals\IJIProductCatalog\Models\Category  $category The Category instance via route model binding.
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, Category $category)
    {
        $adminUser = $request->user();
        Log::debug('Admin CategoryController@destroy: Attempting to delete category.', ['admin_user_id' => $adminUser ? $adminUser->id : null, 'category_id' => $category->id]);

        // Authorization check
        // if ($adminUser && $adminUser->cannot('delete', $category)) {
        //     Log::warning('Admin CategoryController@destroy: Authorization failed.', ['admin_user_id' => $adminUser->id, 'category_id' => $category->id]);
        //     return response()->json(['message' => 'Unauthorized'], 403);
        // }

        if ($category->children()->count() > 0) {
            Log::warning('Admin CategoryController@destroy: Attempted to delete category with children. Deletion will proceed.', ['admin_user_id' => $adminUser ? $adminUser->id : null, 'category_id' => $category->id, 'children_count' => $category->children()->count()]);
            // Depending on policy, you might want to prevent this:
            // return response()->json(['message' => 'Cannot delete category with children. Please remove or reassign children first.'], 422);
        }

        try {
            $category->delete(); // This will also delete related media if configured in the Category model
            Log::info('Admin CategoryController@destroy: Category deleted successfully.', ['admin_user_id' => $adminUser ? $adminUser->id : null, 'category_id' => $category->id]);
            return response()->json(['message' => 'Category deleted successfully.'], 200);
        } catch (\Exception $e) {
            Log::error('Admin CategoryController@destroy: Error deleting category.', [
                'admin_user_id' => $adminUser ? $adminUser->id : null,
                'category_id' => $category->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString() // Caution in production
            ]);
            return response()->json(['message' => 'Error deleting category. Please try again.'], 500);
        }
    }
}
