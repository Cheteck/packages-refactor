<?php

namespace IJIDeals\IJIProductCatalog\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use IJIDeals\IJIProductCatalog\Models\Brand;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class BrandController extends Controller
{
    public function index(Request $request)
    {
        Log::info('Admin BrandController: Fetching all brands.');
        $brands = Brand::orderBy('name')->paginate(20);
        $brands->getCollection()->transform(function ($brand) {
            $brand->logo_url = $brand->getFirstMediaUrl(config('ijiproductcatalog.media_collections.brand_logo', 'brand_logo'), 'thumb');
            $brand->cover_photo_url = $brand->getFirstMediaUrl(config('ijiproductcatalog.media_collections.brand_cover', 'brand_covers'), 'cover_preview');
            return $brand;
        });
        return response()->json($brands);
    }

    public function store(Request $request)
    {
        Log::info('Admin BrandController: Attempting to store a new brand.', ['request_data' => $request->all()]);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => ['nullable', 'string', 'max:255', Rule::unique(config('ijiproductcatalog.tables.brands', 'brands'), 'slug')],
            'description' => 'nullable|string',
            'website_url' => 'nullable|url|max:255',
            'social_links' => 'nullable|array',
            'story' => 'nullable|string',
            'is_featured' => 'nullable|boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:1000',
            'meta_keywords' => 'nullable|string|max:1000',
            'status' => ['required', 'string', Rule::in(['active', 'inactive', 'pending_approval'])],
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:2048',
            'cover_photo' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:5120',
        ]);

        if (!isset($validated['is_featured'])) {
            $validated['is_featured'] = false;
        }
        if (empty($validated['status'])) {
            $validated['status'] = 'active';
        }

        $brandData = collect($validated)->except(['logo', 'cover_photo'])->toArray();
        $brand = Brand::create($brandData);

        if ($request->hasFile('logo') && $request->file('logo')->isValid()) {
            $brand->addMediaFromRequest('logo')->toMediaCollection(config('ijiproductcatalog.media_collections.brand_logo', 'brand_logo'));
            Log::info('Admin BrandController: Logo uploaded for brand.', ['brand_id' => $brand->id]);
        }
        if ($request->hasFile('cover_photo') && $request->file('cover_photo')->isValid()) {
            $brand->addMediaFromRequest('cover_photo')->toMediaCollection(config('ijiproductcatalog.media_collections.brand_cover', 'brand_covers'));
            Log::info('Admin BrandController: Cover photo uploaded for brand.', ['brand_id' => $brand->id]);
        }

        Log::info('Admin BrandController: Brand stored successfully.', ['brand_id' => $brand->id]);
        return response()->json($brand, 201);
    }

    public function show(Request $request, Brand $brand)
    {
        Log::info('Admin BrandController: Showing brand details.', ['brand_id' => $brand->id]);
        $brand->logo_url = $brand->getFirstMediaUrl(config('ijiproductcatalog.media_collections.brand_logo', 'brand_logo'), 'thumb');
        $brand->cover_photo_url = $brand->getFirstMediaUrl(config('ijiproductcatalog.media_collections.brand_cover', 'brand_covers'));
        return response()->json($brand);
    }

    public function update(Request $request, Brand $brand)
    {
        Log::info('Admin BrandController: Attempting to update brand.', ['brand_id' => $brand->id, 'request_data' => $request->all()]);
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'slug' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique(config('ijiproductcatalog.tables.brands', 'brands'), 'slug')->ignore($brand->id)],
            'description' => 'nullable|string',
            'website_url' => 'nullable|url|max:255',
            'social_links' => 'nullable|array',
            'story' => 'nullable|string',
            'is_featured' => 'nullable|boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:1000',
            'meta_keywords' => 'nullable|string|max:1000',
            'status' => ['sometimes', 'required', 'string', Rule::in(['active', 'inactive', 'pending_approval'])],
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:2048',
            'cover_photo' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:5120',
        ]);

        if ($request->has('is_featured') && ! array_key_exists('is_featured', $validated) ) {
             $validated['is_featured'] = (bool) $request->input('is_featured');
        } else if (!array_key_exists('is_featured', $validated)) {
            $validated['is_featured'] = $brand->is_featured;
        }

        $brandData = collect($validated)->except(['logo', 'cover_photo'])->toArray();
        $brand->update($brandData);

        if ($request->hasFile('logo') && $request->file('logo')->isValid()) {
            $brand->clearMediaCollection(config('ijiproductcatalog.media_collections.brand_logo', 'brand_logo'));
            $brand->addMediaFromRequest('logo')->toMediaCollection(config('ijiproductcatalog.media_collections.brand_logo', 'brand_logo'));
            Log::info('Admin BrandController: Logo updated for brand.', ['brand_id' => $brand->id]);
        }
        if ($request->hasFile('cover_photo') && $request->file('cover_photo')->isValid()) {
            $brand->clearMediaCollection(config('ijiproductcatalog.media_collections.brand_cover', 'brand_covers'));
            $brand->addMediaFromRequest('cover_photo')->toMediaCollection(config('ijiproductcatalog.media_collections.brand_cover', 'brand_covers'));
            Log::info('Admin BrandController: Cover photo updated for brand.', ['brand_id' => $brand->id]);
        }

        Log::info('Admin BrandController: Brand updated successfully.', ['brand_id' => $brand->id]);
        return response()->json($brand);
    }

    public function destroy(Request $request, Brand $brand)
    {
        Log::info('Admin BrandController: Attempting to delete brand.', ['brand_id' => $brand->id]);
        $brand->delete();
        Log::info('Admin BrandController: Brand deleted successfully.', ['brand_id' => $brand->id]);
        return response()->json(['message' => 'Brand deleted successfully.'], 200);
    }
}
