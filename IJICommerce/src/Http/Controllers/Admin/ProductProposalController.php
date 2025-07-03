<?php

namespace IJIDeals\IJICommerce\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use IJIDeals\IJICommerce\Models\ProductProposal;
use IJIDeals\IJICommerce\Models\MasterProduct; // Needed for creating MasterProduct
use IJIDeals\IJICommerce\Models\Brand;       // For finding/creating brand
use IJIDeals\IJICommerce\Models\Category;   // For finding/creating category
use Illuminate\Support\Facades\DB;          // For transactions
use Illuminate\Support\Facades\Auth;       // For getting current admin user (if needed)

class ProductProposalController extends Controller // Changed name
{
    // Assuming platform admin authorization is handled via route middleware or a general admin policy

    /**
     * Display a listing of product proposals for admin review.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // TODO: Authorization check for platform admin
        // if ($request->user()->cannot('viewAllProposals', ProductProposal::class)) { abort(403); }

        $status = $request->query('status', 'pending'); // Default to pending proposals

        $proposals = ProductProposal::with('shop:id,name')
            ->where('status', $status)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($proposals);
    }

    /**
     * Display the specified product proposal for admin review.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \IJIDeals\IJICommerce\Models\ProductProposal  $productProposal
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, ProductProposal $productProposal)
    {
        // TODO: Authorization check for platform admin
        // if ($request->user()->cannot('manageProposal', $productProposal)) { abort(403); }

        $productProposal->load('shop:id,name');
        return response()->json($productProposal);
    }

    /**
     * Approve a product proposal and create a MasterProduct.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \IJIDeals\IJICommerce\Models\ProductProposal  $productProposal
     * @return \Illuminate\Http\JsonResponse
     */
    public function approve(Request $request, ProductProposal $productProposal)
    {
        // TODO: Authorization check for platform admin
        // if ($request->user()->cannot('manageProposal', $productProposal)) { abort(403); }

        if ($productProposal->status !== 'pending' && $productProposal->status !== 'needs_revision') {
            return response()->json(['message' => 'This proposal cannot be approved as it is not pending or needing revision.'], 422);
        }

        // Admin can override proposal data before creating MasterProduct
        $validatedMasterProductData = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:'.(new MasterProduct())->getTable().',slug',
            'description' => 'nullable|string',
            'brand_name' => 'nullable|string|max:255', // Admin can create/select brand
            'category_name' => 'nullable|string|max:255', // Admin can create/select category
            // 'brand_id' => 'nullable|exists:brands,id', // Or provide IDs directly
            // 'category_id' => 'nullable|exists:categories,id',
            'specifications' => 'nullable|array',
            'images_payload' => 'nullable|array',
            'status' => 'required|string|in:active,draft_by_admin', // Status for the new MasterProduct
            'admin_notes_for_proposal' => 'nullable|string', // Notes on the proposal itself
            // Validation for proposed variations payload
            'proposed_variations_payload' => 'nullable|array',
            'proposed_variations_payload.*.sku' => ['nullable', 'string', 'max:255', Rule::unique(config('ijicommerce.tables.master_product_variations', 'master_product_variations'), 'sku')],
            'proposed_variations_payload.*.price_adjustment' => 'nullable|numeric',
            'proposed_variations_payload.*.stock_override' => 'nullable|integer|min:0',
            'proposed_variations_payload.*.images_payload_variation' => 'nullable|array',
            'proposed_variations_payload.*.options' => 'required_with:proposed_variations_payload|array|min:1', // If variations are proposed, options are required
            'proposed_variations_payload.*.options.*' => ['required_with:proposed_variations_payload', 'integer', Rule::exists(config('ijicommerce.tables.product_attribute_values', 'product_attribute_values'), 'id')],
        ]);

        $masterProduct = null;

        DB::beginTransaction();
        try {
            // Find or create Brand
            $brand = null;
            if (!empty($validatedMasterProductData['brand_name'])) {
                $brand = Brand::firstOrCreate(
                    ['name' => $validatedMasterProductData['brand_name']],
                    ['slug' => \Illuminate\Support\Str::slug($validatedMasterProductData['brand_name'])]
                );
            }

            // Find or create Category
            $category = null;
            if (!empty($validatedMasterProductData['category_name'])) {
                // Simplified category creation; real app might need parent_id selection
                $category = Category::firstOrCreate(
                    ['name' => $validatedMasterProductData['category_name']],
                    ['slug' => \Illuminate\Support\Str::slug($validatedMasterProductData['category_name'])]
                );
            }

            $masterProduct = MasterProduct::create([
                'name' => $validatedMasterProductData['name'],
                'slug' => $validatedMasterProductData['slug'] ?? null, // Model will auto-generate if null
                'description' => $validatedMasterProductData['description'] ?? $productProposal->description,
                'brand_id' => $brand ? $brand->id : null,
                'category_id' => $category ? $category->id : null,
                'specifications' => $validatedMasterProductData['specifications'] ?? $productProposal->proposed_specifications,
                'images_payload' => $validatedMasterProductData['images_payload'] ?? $productProposal->proposed_images_payload,
                'status' => $validatedMasterProductData['status'],
                'created_by_proposal_id' => $productProposal->id,
                // 'created_by_admin_id' => Auth::id(), // If tracking which admin approved
            ]);

            // Handle proposed variations if present and MasterProduct created successfully
            if ($masterProduct && !empty($validatedMasterProductData['proposed_variations_payload'])) {
                foreach ($validatedMasterProductData['proposed_variations_payload'] as $variationData) {
                    // Ensure options are valid and unique for this master product before creating variation
                    // (Simplified: AdminProductVariationController::findVariationByOptions could be reused/adapted)
                    // For now, assuming options are valid as per request validation.
                    $mpVariation = $masterProduct->variations()->create([
                        'sku' => $variationData['sku'] ?? null,
                        'price_adjustment' => $variationData['price_adjustment'] ?? 0.00,
                        'stock_override' => $variationData['stock_override'] ?? null,
                        'images_payload_variation' => $variationData['images_payload_variation'] ?? [],
                    ]);
                    $mpVariation->attributeOptions()->sync($variationData['options']);
                }
            }

            $productProposal->status = 'approved';
            $productProposal->admin_notes = $validatedMasterProductData['admin_notes_for_proposal'] ?? $productProposal->admin_notes;
            $productProposal->approved_master_product_id = $masterProduct->id; // Link to the created MasterProduct
            $productProposal->save();

            DB::commit();

            // TODO: Notify the shop that their proposal was approved.
            // event(new ProductProposalApproved($productProposal, $masterProduct));

            return response()->json([
                'message' => 'Product proposal approved and MasterProduct created.',
                'master_product' => $masterProduct,
                'proposal' => $productProposal->fresh(),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to approve proposal: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Reject a product proposal.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \IJIDeals\IJICommerce\Models\ProductProposal  $productProposal
     * @return \Illuminate\Http\JsonResponse
     */
    public function reject(Request $request, ProductProposal $productProposal)
    {
        // TODO: Authorization check for platform admin
        // if ($request->user()->cannot('manageProposal', $productProposal)) { abort(403); }

        if ($productProposal->status !== 'pending' && $productProposal->status !== 'needs_revision') {
            return response()->json(['message' => 'This proposal cannot be rejected as it is not pending or needing revision.'], 422);
        }

        $validated = $request->validate([
            'admin_notes' => 'required|string|max:2000', // Reason for rejection
        ]);

        $productProposal->status = 'rejected';
        $productProposal->admin_notes = $validated['admin_notes'];
        $productProposal->save();

        // TODO: Notify the shop that their proposal was rejected.
        // event(new ProductProposalRejected($productProposal));

        return response()->json([
            'message' => 'Product proposal rejected.',
            'proposal' => $productProposal,
        ]);
    }
}
