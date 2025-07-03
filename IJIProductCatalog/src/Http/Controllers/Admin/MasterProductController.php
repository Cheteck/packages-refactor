<?php

namespace IJIDeals\IJIProductCatalog\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use IJIDeals\IJIProductCatalog\Models\MasterProduct;
use IJIDeals\IJIProductCatalog\Models\Brand;
use IJIDeals\IJIProductCatalog\Models\Category;
use IJIDeals\IJIProductCatalog\Models\ProductProposal;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class MasterProductController extends Controller
{
    public function __construct()
    {
        // Apply a general platform admin check middleware here if desired,
        // or rely on policies for each method.
        // e.g., $this->middleware('platform.admin');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Log::info('Admin MasterProductController: Fetching all master products.');
        // $this->authorize('viewAny', MasterProduct::class); // Using policy
        if ($request->user()->cannot('viewAny', MasterProduct::class)) {
            Log::warning('Admin MasterProductController: Unauthorized attempt to list master products.', ['user_id' => Auth::id()]);
            return response()->json(['message' => 'Unauthorized to list master products.'], 403);
        }

        $query = MasterProduct::with(['brand:id,name', 'category:id,name']);

        if ($request->filled('name')) {
            $query->where('name', 'LIKE', '%' . $request->input('name') . '%');
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->input('brand_id'));
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        $masterProducts = $query->orderBy('name')->paginate($request->input('per_page', 20));

        $masterProducts->getCollection()->transform(function ($product) {
            $product->base_image_urls = $product->getMedia(config('ijiproductcatalog.media_collections.master_product_base_images', 'master_product_base_images'))->map(function ($media) {
                return ['id' => $media->id, 'original' => $media->getUrl(), 'thumb' => $media->getUrl('thumb'), 'showcase' => $media->getUrl('showcase')];
            });
            return $product;
        });
        Log::info('Admin MasterProductController: Master products fetched successfully.', ['count' => $masterProducts->count()]);
        return response()->json($masterProducts);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Log::info('Admin MasterProductController: Attempting to store a new master product.', ['request_data' => $request->all()]);
        if ($request->user()->cannot('create', MasterProduct::class)) {
            Log::warning('Admin MasterProductController: Unauthorized attempt to create master product.', ['user_id' => Auth::id()]);
            return response()->json(['message' => 'Unauthorized to create master products.'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => ['nullable', 'string', 'max:255', Rule::unique((new MasterProduct())->getTable(), 'slug')],
            'description' => 'nullable|string',
            'brand_id' => ['nullable', 'integer', Rule::exists((new Brand())->getTable(), 'id')],
            'category_id' => ['nullable', 'integer', Rule::exists((new Category())->getTable(), 'id')],
            'specifications' => 'nullable|array',
            'status' => ['required', 'string', Rule::in(['active', 'draft_by_admin', 'archived'])],
            'created_by_proposal_id' => ['nullable', 'integer', Rule::exists((new ProductProposal())->getTable(), 'id')],
            'base_images' => 'nullable|array',
            'base_images.*' => 'image|mimes:jpg,jpeg,png,gif,webp|max:2048',
        ]);

        $masterProductData = collect($validated)->except(['base_images'])->toArray();
        $masterProduct = MasterProduct::create($masterProductData);

        if ($request->hasFile('base_images')) {
            foreach ($request->file('base_images') as $file) {
                if ($file->isValid()) {
                    $masterProduct->addMedia($file)->toMediaCollection(config('ijiproductcatalog.media_collections.master_product_base_images', 'master_product_base_images'));
                }
            }
            Log::info('Admin MasterProductController: Base images uploaded for master product.', ['master_product_id' => $masterProduct->id]);
        }

        Log::info('Admin MasterProductController: Master product stored successfully.', ['master_product_id' => $masterProduct->id]);
        return response()->json($masterProduct, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, MasterProduct $masterProduct)
    {
        Log::info('Admin MasterProductController: Showing master product details.', ['master_product_id' => $masterProduct->id]);
        if ($request->user()->cannot('view', $masterProduct)) {
            Log::warning('Admin MasterProductController: Unauthorized attempt to view master product.', ['user_id' => Auth::id(), 'master_product_id' => $masterProduct->id]);
            return response()->json(['message' => 'Unauthorized to view this master product.'], 403);
        }
        $masterProduct->load(['brand:id,name', 'category:id,name', 'productProposal:id,shop_id']);
        $masterProduct->base_image_urls = $masterProduct->getMedia(config('ijiproductcatalog.media_collections.master_product_base_images', 'master_product_base_images'))->map(function ($media) {
            return ['id' => $media->id, 'original' => $media->getUrl(), 'thumb' => $media->getUrl('thumb'), 'showcase' => $media->getUrl('showcase')];
        });
        return response()->json($masterProduct);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MasterProduct $masterProduct)
    {
        Log::info('Admin MasterProductController: Attempting to update master product.', ['master_product_id' => $masterProduct->id, 'request_data' => $request->all()]);
        if ($request->user()->cannot('update', $masterProduct)) {
            Log::warning('Admin MasterProductController: Unauthorized attempt to update master product.', ['user_id' => Auth::id(), 'master_product_id' => $masterProduct->id]);
            return response()->json(['message' => 'Unauthorized to update this master product.'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'slug' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique((new MasterProduct())->getTable(), 'slug')->ignore($masterProduct->id)],
            'description' => 'nullable|string',
            'brand_id' => ['nullable', 'integer', Rule::exists((new Brand())->getTable(), 'id')],
            'category_id' => ['nullable', 'integer', Rule::exists((new Category())->getTable(), 'id')],
            'specifications' => 'nullable|array',
            'status' => ['sometimes','required', 'string', Rule::in(['active', 'draft_by_admin', 'archived'])],
            'base_images' => 'nullable|array',
            'base_images.*' => 'image|mimes:jpg,jpeg,png,gif,webp|max:2048',
            'removed_media_ids' => 'nullable|array',
            'removed_media_ids.*' => 'integer',
        ]);

        $masterProductData = collect($validated)->except(['base_images', 'removed_media_ids'])->toArray();

        $significantDataFields = ['name', 'description', 'specifications'];
        $originalSignificantData = $masterProduct->only($significantDataFields);

        $masterProduct->update($masterProductData);

        $mediaChanged = false;
        if ($request->hasFile('base_images')) {
            foreach ($request->file('base_images') as $file) {
                if ($file->isValid()) {
                    $masterProduct->addMedia($file)->toMediaCollection(config('ijiproductcatalog.media_collections.master_product_base_images', 'master_product_base_images'));
                    $mediaChanged = true;
                }
            }
            Log::info('Admin MasterProductController: New base images uploaded for master product.', ['master_product_id' => $masterProduct->id]);
        }

        if ($request->filled('removed_media_ids')) {
            foreach ($validated['removed_media_ids'] as $mediaId) {
                $mediaItem = $masterProduct->getMedia(config('ijiproductcatalog.media_collections.master_product_base_images', 'master_product_base_images'))->find($mediaId);
                if ($mediaItem) {
                    $mediaItem->delete();
                    $mediaChanged = true;
                    Log::info('Admin MasterProductController: Removed media item from master product.', ['master_product_id' => $masterProduct->id, 'media_id' => $mediaId]);
                }
            }
        }

        $wasSignificantlyDataChanged = false;
        if ($masterProduct->wasChanged($significantDataFields)) {
            $wasSignificantlyDataChanged = true;
        }

        if ($masterProduct->status === 'active' && ($wasSignificantlyDataChanged || $mediaChanged)) {
            $masterProduct->refresh();
            $newHashPayload = $masterProduct->only($significantDataFields);
            $newHashPayload['media_signature'] = $masterProduct->getMedia(config('ijiproductcatalog.media_collections.master_product_base_images', 'master_product_base_images'))
                                                    ->map(fn($media) => $media->uuid)
                                                    ->sort()
                                                    ->implode(',');
            $newHash = md5(serialize($newHashPayload));

            // Reference to ShopProduct in IJIShopListings package
            $shopProductsToUpdateQuery = $masterProduct->shopProducts();
            $shopProductsToUpdateQuery->where(function ($query) use ($newHash) {
                $query->where('master_version_hash', '!=', $newHash)
                      ->orWhereNull('master_version_hash')
                      ->orWhere('needs_review_by_shop', false);
            });

            foreach ($shopProductsToUpdateQuery->get() as $shopProduct) {
                $shopProduct->update([
                    'master_version_hash' => $newHash,
                    'needs_review_by_shop' => true,
                    'is_visible_in_shop' => false,
                ]);
                Log::info('Admin MasterProductController: Shop product marked for review due to master product update.', ['shop_product_id' => $shopProduct->id, 'master_product_id' => $masterProduct->id]);
                // TODO: Dispatch event/notification
            }
        }

        $masterProduct->refresh();
        $masterProduct->base_image_urls = $masterProduct->getMedia(config('ijiproductcatalog.media_collections.master_product_base_images', 'master_product_base_images'))->map(function ($media) {
            return ['id' => $media->id, 'original' => $media->getUrl(), 'thumb' => $media->getUrl('thumb'), 'showcase' => $media->getUrl('showcase')];
        });
        Log::info('Admin MasterProductController: Master product updated successfully.', ['master_product_id' => $masterProduct->id]);
        return response()->json($masterProduct->load(['brand:id,name', 'category:id,name', 'productProposal:id,shop_id']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, MasterProduct $masterProduct)
    {
        Log::info('Admin MasterProductController: Attempting to delete master product.', ['master_product_id' => $masterProduct->id]);
        if ($request->user()->cannot('delete', $masterProduct)) {
            Log::warning('Admin MasterProductController: Unauthorized attempt to delete master product.', ['user_id' => Auth::id(), 'master_product_id' => $masterProduct->id]);
            return response()->json(['message' => 'Unauthorized to delete this master product.'], 403);
        }

        // Consider implications for ShopProducts & Media
        // if ($masterProduct->shopProducts()->exists()) {
        //     return response()->json(['message' => 'Cannot delete: Master product is actively listed by shops.'], 422);
        // }
        $masterProduct->clearMediaCollection(config('ijiproductcatalog.media_collections.master_product_base_images', 'master_product_base_images'));
        // Also clear media for variations if they exist and are deleted by cascade or explicitly
        $masterProduct->delete();
        Log::info('Admin MasterProductController: Master product deleted successfully.', ['master_product_id' => $masterProduct->id]);
        return response()->json(['message' => 'MasterProduct deleted successfully.'], 200);
    }
}
