<?php

namespace IJIDeals\IJIProductCatalog\Http\Controllers\Admin;

use Illuminate\Http\Request; // Keep for index, show, destroy if not using specific FormRequests
use Illuminate\Routing\Controller;
use IJIDeals\IJIProductCatalog\Models\Brand;
// use Illuminate\Validation\Rule; // No longer needed here
use Illuminate\Support\Facades\Log;
use IJIDeals\IJIProductCatalog\Http\Requests\Admin\StoreBrandRequest;
use IJIDeals\IJIProductCatalog\Http\Requests\Admin\UpdateBrandRequest;

/**
 * Admin controller for managing product Brands.
 * Handles CRUD operations for brands within the product catalog.
 */
class BrandController extends Controller
{
    /**
     * Display a paginated listing of Brands.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $adminUser = $request->user();
        Log::debug('Admin BrandController@index: Fetching all brands.', ['admin_user_id' => $adminUser ? $adminUser->id : null]);

        // Authorization should be handled by a middleware or a policy's viewAny method
        // if ($adminUser && $adminUser->cannot('viewAny', Brand::class)) { ... }

        $brands = Brand::orderBy('name')->paginate(config('ijiproductcatalog.pagination.admin_brands', 20));
        $brands->getCollection()->transform(function ($brand) {
            $brand->logo_url = $brand->getFirstMediaUrl(config('ijiproductcatalog.media_collections.brand_logo', 'brand_logo'), 'thumb');
            $brand->cover_photo_url = $brand->getFirstMediaUrl(config('ijiproductcatalog.media_collections.brand_cover', 'brand_covers'), 'cover_preview');
            return $brand;
        });
        Log::info('Admin BrandController@index: Successfully fetched brands.', ['admin_user_id' => $adminUser ? $adminUser->id : null, 'count' => $brands->count(), 'total' => $brands->total()]);
        return response()->json($brands);
    }

    /**
     * Store a newly created Brand in storage.
     * Handles logo and cover photo uploads.
     *
     * @param  \IJIDeals\IJIProductCatalog\Http\Requests\Admin\StoreBrandRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreBrandRequest $request)
    {
        $adminUser = $request->user();
        Log::debug('Admin BrandController@store: Attempting to store new brand.', ['admin_user_id' => $adminUser ? $adminUser->id : null, 'request_data' => $request->all()]);

        // Authorization handled by StoreBrandRequest->authorize()
        // Validation handled by StoreBrandRequest->rules() & prepareForValidation()
        $validatedData = $request->validated();
        Log::debug('Admin BrandController@store: Validation passed via FormRequest.', ['admin_user_id' => $adminUser ? $adminUser->id : null, 'validated_data' => $validatedData]);

        // Default status and is_featured are handled in StoreBrandRequest's prepareForValidation

        $brandData = collect($validatedData)->except(['logo', 'cover_photo'])->toArray();

        try {
            $brand = Brand::create($brandData);
            Log::info('Admin BrandController@store: Brand created in database.', ['admin_user_id' => $adminUser ? $adminUser->id : null, 'brand_id' => $brand->id]);

            if ($request->hasFile('logo') && $request->file('logo')->isValid()) {
                $brand->addMediaFromRequest('logo')->toMediaCollection(config('ijiproductcatalog.media_collections.brand_logo', 'brand_logo'));
                Log::info('Admin BrandController@store: Logo uploaded for brand.', ['admin_user_id' => $adminUser ? $adminUser->id : null, 'brand_id' => $brand->id]);
            }
            if ($request->hasFile('cover_photo') && $request->file('cover_photo')->isValid()) {
                $brand->addMediaFromRequest('cover_photo')->toMediaCollection(config('ijiproductcatalog.media_collections.brand_cover', 'brand_covers'));
                Log::info('Admin BrandController@store: Cover photo uploaded for brand.', ['admin_user_id' => $adminUser ? $adminUser->id : null, 'brand_id' => $brand->id]);
            }

            Log::info('Admin BrandController@store: Brand stored successfully.', ['admin_user_id' => $adminUser ? $adminUser->id : null, 'brand_id' => $brand->id]);
            return response()->json($brand->fresh(), 201); // Return fresh model
        } catch (\Exception $e) {
            Log::error('Admin BrandController@store: Error storing brand.', [
                'admin_user_id' => $adminUser ? $adminUser->id : null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Error storing brand. Please try again.'], 500);
        }
    }

    /**
     * Display the specified Brand.
     * Includes URLs for logo and cover photo.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \IJIDeals\IJIProductCatalog\Models\Brand  $brand The Brand instance via route model binding.
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, Brand $brand)
    {
        $adminUser = $request->user();
        Log::debug('Admin BrandController@show: Showing brand details.', ['admin_user_id' => $adminUser ? $adminUser->id : null, 'brand_id' => $brand->id]);

        // Authorization should be handled by a policy's view method
        // if ($adminUser && $adminUser->cannot('view', $brand)) { ... }

        $brand->logo_url = $brand->getFirstMediaUrl(config('ijiproductcatalog.media_collections.brand_logo', 'brand_logo'), 'thumb');
        $brand->cover_photo_url = $brand->getFirstMediaUrl(config('ijiproductcatalog.media_collections.brand_cover', 'brand_covers'));
        Log::info('Admin BrandController@show: Successfully fetched brand details.', ['admin_user_id' => $adminUser ? $adminUser->id : null, 'brand_id' => $brand->id]);
        return response()->json($brand);
    }

    /**
     * Update the specified Brand in storage.
     * Handles logo and cover photo updates.
     *
     * @param  \IJIDeals\IJIProductCatalog\Http\Requests\Admin\UpdateBrandRequest  $request
     * @param  \IJIDeals\IJIProductCatalog\Models\Brand  $brand The Brand instance via route model binding.
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateBrandRequest $request, Brand $brand)
    {
        $adminUser = $request->user();
        Log::debug('Admin BrandController@update: Attempting to update brand.', ['admin_user_id' => $adminUser ? $adminUser->id : null, 'brand_id' => $brand->id, 'request_data' => $request->all()]);

        // Authorization handled by UpdateBrandRequest->authorize()
        // Validation handled by UpdateBrandRequest->rules() & prepareForValidation()
        $validatedData = $request->validated();
        Log::debug('Admin BrandController@update: Validation passed via FormRequest.', ['admin_user_id' => $adminUser ? $adminUser->id : null, 'validated_data' => $validatedData]);

        // Logic for preserving 'is_featured' if not in request needs to be handled carefully.
        // If 'is_featured' is nullable and not in validatedData, it means it wasn't sent or was null.
        // To update 'is_featured' to false, it must be explicitly sent as false.
        // If the field is truly optional for update (i.e., not changing it if not sent),
        // then we might only pass $request->safe()->only([...updatable_fields...]) to $brand->update().
        // For now, $validatedData will contain 'is_featured' if it was in the request.
        // If it was not, and it was nullable, it won't be in $validatedData, and $brand->update
        // will not change it. This is generally the desired behavior for partial updates.
        // The previous explicit handling of `is_featured` in the controller is now mostly covered by
        // the FormRequest's prepareForValidation and the rules.

        $brandData = collect($validatedData)->except(['logo', 'cover_photo'])->toArray();

        try {
            $brand->update($brandData);
            Log::info('Admin BrandController@update: Brand updated in database.', ['admin_user_id' => $adminUser ? $adminUser->id : null, 'brand_id' => $brand->id]);

            if ($request->hasFile('logo') && $request->file('logo')->isValid()) {
                $brand->clearMediaCollection(config('ijiproductcatalog.media_collections.brand_logo', 'brand_logo'));
                $brand->addMediaFromRequest('logo')->toMediaCollection(config('ijiproductcatalog.media_collections.brand_logo', 'brand_logo'));
                Log::info('Admin BrandController@update: Logo updated for brand.', ['admin_user_id' => $adminUser ? $adminUser->id : null, 'brand_id' => $brand->id]);
            }
            if ($request->hasFile('cover_photo') && $request->file('cover_photo')->isValid()) {
                $brand->clearMediaCollection(config('ijiproductcatalog.media_collections.brand_cover', 'brand_covers'));
                $brand->addMediaFromRequest('cover_photo')->toMediaCollection(config('ijiproductcatalog.media_collections.brand_cover', 'brand_covers'));
                Log::info('Admin BrandController@update: Cover photo updated for brand.', ['admin_user_id' => $adminUser ? $adminUser->id : null, 'brand_id' => $brand->id]);
            }

            Log::info('Admin BrandController@update: Brand updated successfully.', ['admin_user_id' => $adminUser ? $adminUser->id : null, 'brand_id' => $brand->id]);
            return response()->json($brand->fresh());
        } catch (\Exception $e) {
            Log::error('Admin BrandController@update: Error updating brand.', [
                'admin_user_id' => $adminUser ? $adminUser->id : null,
                'brand_id' => $brand->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Error updating brand. Please try again.'], 500);
        }
    }

    /**
     * Remove the specified Brand from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \IJIDeals\IJIProductCatalog\Models\Brand  $brand The Brand instance via route model binding.
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, Brand $brand)
    {
        $adminUser = $request->user();
        Log::debug('Admin BrandController@destroy: Attempting to delete brand.', ['admin_user_id' => $adminUser ? $adminUser->id : null, 'brand_id' => $brand->id]);

        // if ($adminUser && $adminUser->cannot('delete', $brand)) {
        //     Log::warning('Admin BrandController@destroy: Authorization failed.', ['admin_user_id' => $adminUser->id, 'brand_id' => $brand->id]);
        //     return response()->json(['message' => 'Unauthorized'], 403);
        // }

        try {
            $brand->delete(); // This will also delete related media if configured in the Brand model
            Log::info('Admin BrandController@destroy: Brand deleted successfully.', ['admin_user_id' => $adminUser ? $adminUser->id : null, 'brand_id' => $brand->id]);
            return response()->json(['message' => 'Brand deleted successfully.'], 200);
        } catch (\Exception $e) {
            Log::error('Admin BrandController@destroy: Error deleting brand.', [
                'admin_user_id' => $adminUser ? $adminUser->id : null,
                'brand_id' => $brand->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString() // Be cautious with trace in production logs
            ]);
            return response()->json(['message' => 'Error deleting brand. Please try again.'], 500);
        }
    }
}
