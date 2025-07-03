<?php

namespace IJIDeals\IJIProductCatalog\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use IJIDeals\IJIProductCatalog\Models\ProductProposal;
use IJIDeals\IJIProductCatalog\Models\MasterProduct;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class ProductProposalController extends Controller
{
    /**
     * Display a listing of product proposals for admin review.
     */
    public function index(Request $request)
    {
        Log::info('Admin ProductProposalController: Fetching all product proposals.');
        // TODO: Authorization check for platform admin
        $query = ProductProposal::with('shop:id,name', 'masterProduct:id,name');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('shop_id')) {
            $query->where('shop_id', $request->input('shop_id'));
        }

        $proposals = $query->orderBy('created_at', 'desc')->paginate(20);
        Log::info('Admin ProductProposalController: Product proposals fetched successfully.', ['count' => $proposals->count()]);
        return response()->json($proposals);
    }

    /**
     * Display the specified product proposal.
     */
    public function show(Request $request, ProductProposal $productProposal)
    {
        Log::info('Admin ProductProposalController: Showing product proposal details.', ['proposal_id' => $productProposal->id]);
        // TODO: Authorization check for platform admin
        $productProposal->load('shop:id,name', 'masterProduct:id,name');
        return response()->json($productProposal);
    }

    /**
     * Approve a product proposal and create a MasterProduct.
     */
    public function approve(Request $request, ProductProposal $productProposal)
    {
        Log::info('Admin ProductProposalController: Attempting to approve product proposal.', ['proposal_id' => $productProposal->id]);
        // TODO: Authorization check for platform admin
        if ($productProposal->status !== 'pending' && $productProposal->status !== 'needs_revision') {
            Log::warning('Admin ProductProposalController: Attempted to approve non-pending/non-revision proposal.', ['proposal_id' => $productProposal->id, 'status' => $productProposal->status]);
            return response()->json(['message' => 'Proposal cannot be approved in its current status.'], 400);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'brand_id' => ['nullable', 'integer', Rule::exists(config('ijiproductcatalog.tables.brands', 'brands'), 'id')],
            'category_id' => ['nullable', 'integer', Rule::exists(config('ijiproductcatalog.tables.categories', 'categories'), 'id')],
            'specifications' => 'nullable|array',
            'status' => ['required', 'string', Rule::in(['active', 'draft_by_admin', 'archived'])],
            // No direct image upload here, images are handled via MasterProductController after creation
        ]);

        DB::beginTransaction();
        try {
            $masterProduct = MasterProduct::create(array_merge($validated, [
                'created_by_proposal_id' => $productProposal->id,
                'slug' => \Illuminate\Support\Str::slug($validated['name']),
            ]));

            $productProposal->update([
                'status' => 'approved',
                'admin_notes' => $request->input('admin_notes'),
                'approved_master_product_id' => $masterProduct->id,
            ]);

            DB::commit();
            Log::info('Admin ProductProposalController: Product proposal approved and MasterProduct created.', ['proposal_id' => $productProposal->id, 'master_product_id' => $masterProduct->id]);
            return response()->json($masterProduct, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Admin ProductProposalController: Failed to approve product proposal.', ['proposal_id' => $productProposal->id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to approve proposal: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Reject a product proposal.
     */
    public function reject(Request $request, ProductProposal $productProposal)
    {
        Log::info('Admin ProductProposalController: Attempting to reject product proposal.', ['proposal_id' => $productProposal->id]);
        // TODO: Authorization check for platform admin
        if ($productProposal->status !== 'pending' && $productProposal->status !== 'needs_revision') {
            Log::warning('Admin ProductProposalController: Attempted to reject non-pending/non-revision proposal.', ['proposal_id' => $productProposal->id, 'status' => $productProposal->status]);
            return response()->json(['message' => 'Proposal cannot be rejected in its current status.'], 400);
        }

        $validated = $request->validate([
            'admin_notes' => 'required|string|max:5000',
        ]);

        $productProposal->update([
            'status' => 'rejected',
            'admin_notes' => $validated['admin_notes'],
        ]);

        Log::info('Admin ProductProposalController: Product proposal rejected.', ['proposal_id' => $productProposal->id]);
        return response()->json(['message' => 'Proposal rejected successfully.'], 200);
    }

    /**
     * Mark a product proposal as needing revision.
     */
    public function needsRevision(Request $request, ProductProposal $productProposal)
    {
        Log::info('Admin ProductProposalController: Marking product proposal as needs revision.', ['proposal_id' => $productProposal->id]);
        // TODO: Authorization check for platform admin
        if ($productProposal->status !== 'pending') {
            Log::warning('Admin ProductProposalController: Attempted to mark non-pending proposal as needs revision.', ['proposal_id' => $productProposal->id, 'status' => $productProposal->status]);
            return response()->json(['message' => 'Proposal can only be marked as needs revision if it is pending.'], 400);
        }

        $validated = $request->validate([
            'admin_notes' => 'required|string|max:5000',
        ]);

        $productProposal->update([
            'status' => 'needs_revision',
            'admin_notes' => $validated['admin_notes'],
        ]);

        Log::info('Admin ProductProposalController: Product proposal marked as needs revision.', ['proposal_id' => $productProposal->id]);
        return response()->json(['message' => 'Proposal marked as needs revision successfully.'], 200);
    }
}
