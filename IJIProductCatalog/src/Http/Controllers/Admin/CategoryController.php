<?php

namespace IJIDeals\IJIProductCatalog\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use IJIDeals\IJIProductCatalog\Models\Category;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        Log::info('Admin CategoryController: Fetching all categories.');
        $categories = Category::orderBy('name')->whereNull('parent_id')->with('children')->paginate(20);
        return response()->json($categories);
    }

    public function store(Request $request)
    {
        Log::info('Admin CategoryController: Attempting to store a new category.', ['request_data' => $request->all()]);
        $tableName = (new Category())->getTable();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => ['nullable', 'string', 'max:255', Rule::unique($tableName, 'slug')],
            'description' => 'nullable|string',
            'parent_id' => ['nullable', 'integer', Rule::exists($tableName, 'id')],
            'image' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:2048',
        ]);

        $categoryData = collect($validated)->except(['image'])->toArray();
        $category = Category::create($categoryData);

        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $category->addMediaFromRequest('image')->toMediaCollection(config('ijiproductcatalog.media_collections.category_image', 'category_images'));
            Log::info('Admin CategoryController: Image uploaded for category.', ['category_id' => $category->id]);
        }

        Log::info('Admin CategoryController: Category stored successfully.', ['category_id' => $category->id]);
        return response()->json($category, 201);
    }

    public function show(Request $request, Category $category)
    {
        Log::info('Admin CategoryController: Showing category details.', ['category_id' => $category->id]);
        $category->load(['parent', 'children']);
        $category->image_url = $category->getFirstMediaUrl(config('ijiproductcatalog.media_collections.category_image', 'category_images'), 'thumb');
        return response()->json($category);
    }

    public function update(Request $request, Category $category)
    {
        Log::info('Admin CategoryController: Attempting to update category.', ['category_id' => $category->id, 'request_data' => $request->all()]);
        $tableName = (new Category())->getTable();

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'slug' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique($tableName, 'slug')->ignore($category->id)],
            'description' => 'nullable|string',
            'parent_id' => ['nullable', 'integer', Rule::exists($tableName, 'id')->whereNot('id', $category->id)],
            'image' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:2048',
        ]);

        if (isset($validated['parent_id']) && $validated['parent_id']) {
            $potentialParent = Category::find($validated['parent_id']);
            $node = $potentialParent;
            $isDescendant = false;
            while($node) {
                if ($node->parent_id == $category->id) {
                    $isDescendant = true;
                    break;
                }
                $node = $node->parent;
            }
            if ($isDescendant) {
                Log::warning('Admin CategoryController: Attempted to set parent to a descendant.', ['category_id' => $category->id, 'parent_id' => $validated['parent_id']]);
                return response()->json(['errors' => ['parent_id' => ['Cannot set parent to one of its own descendants.']]], 422);
            }
        }

        $categoryData = collect($validated)->except(['image'])->toArray();
        $category->update($categoryData);

        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $category->clearMediaCollection(config('ijiproductcatalog.media_collections.category_image', 'category_images'));
            $category->addMediaFromRequest('image')->toMediaCollection(config('ijiproductcatalog.media_collections.category_image', 'category_images'));
            Log::info('Admin CategoryController: Image updated for category.', ['category_id' => $category->id]);
        }

        $category->load(['parent', 'children']);
        $category->image_url = $category->getFirstMediaUrl(config('ijiproductcatalog.media_collections.category_image', 'category_images'), 'thumb');
        Log::info('Admin CategoryController: Category updated successfully.', ['category_id' => $category->id]);
        return response()->json($category);
    }

    public function destroy(Request $request, Category $category)
    {
        Log::info('Admin CategoryController: Attempting to delete category.', ['category_id' => $category->id]);
        if ($category->children()->count() > 0) {
            Log::warning('Admin CategoryController: Attempted to delete category with children.', ['category_id' => $category->id]);
        }
        $category->delete();
        Log::info('Admin CategoryController: Category deleted successfully.', ['category_id' => $category->id]);
        return response()->json(['message' => 'Category deleted successfully.'], 200);
    }
}
