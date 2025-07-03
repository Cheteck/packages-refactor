<?php

namespace IJIDeals\IJIProductCatalog\Http\Controllers\Admin;

use Illuminate\Http\Request; // Keep for index, show
use Illuminate\Routing\Controller;
use IJIDeals\IJIProductCatalog\Models\ProductProposal;
use IJIDeals\IJIProductCatalog\Models\MasterProduct;
use Illuminate\Validation\Rule; // No longer needed here
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use IJIDeals\IJIProductCatalog\Http\Requests\Admin\ApproveProductProposalRequest;
use IJIDeals\IJIProductCatalog\Http\Requests\Admin\RejectProductProposalRequest;
use IJIDeals\IJIProductCatalog\Http\Requests\Admin\NeedsRevisionProductProposalRequest;

/**
 * Admin controller for managing Product Proposals submitted by shops.
 * Allows admins to review, approve (creating a MasterProduct), reject, or request revisions.
 */
class ProductProposalController extends Controller
{
    /**
     * Display a paginated listing of Product Proposals for admin review.
     * Supports filtering by status and shop_id.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $adminUserId = $request->user() ? $request->user()->id : (Auth::id() ?? null);
        Log::debug('Admin ProductProposalController@index: Fetching product proposals.', ['admin_user_id' => $adminUserId, 'filters' => $request->query()]);

        // Authorization placeholder
        // if ($request->user() && $request->user()->cannot('viewAny', ProductProposal::class)) { ... }

        $query = ProductProposal::with('shop:id,name', 'masterProduct:id,name');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('shop_id')) {
            $query->where('shop_id', $request->input('shop_id'));
        }

        $proposals = $query->orderByDesc('created_at')->paginate(config('ijiproductcatalog.pagination.admin_proposals', 20));
        Log::info('Admin ProductProposalController@index: Product proposals fetched successfully.', ['admin_user_id' => $adminUserId, 'count' => $proposals->count(), 'total' => $proposals->total()]);
        return response()->json($proposals);
    }

    /**
     * Display the specified Product Proposal.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \IJIDeals\IJIProductCatalog\Models\ProductProposal  $productProposal The ProductProposal instance via route model binding.
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, ProductProposal $productProposal)
    {
        $adminUserId = $request->user() ? $request->user()->id : (Auth::id() ?? null);
        Log::debug('Admin ProductProposalController@show: Showing product proposal details.', ['admin_user_id' => $adminUserId, 'proposal_id' => $productProposal->id]);

        // Authorization placeholder
        // if ($request->user() && $request->user()->cannot('view', $productProposal)) { ... }

        $productProposal->load('shop:id,name', 'masterProduct:id,name');
        Log::info('Admin ProductProposalController@show: Product proposal details fetched.', ['admin_user_id' => $adminUserId, 'proposal_id' => $productProposal->id]);
        return response()->json($productProposal);
    }

    /**
     * Approve a Product Proposal.
     * This action creates a new MasterProduct based on the proposal data and updates the proposal's status.
     *
     * @param  \IJIDeals\IJIProductCatalog\Http\Requests\Admin\ApproveProductProposalRequest  $request
     * @param  \IJIDeals\IJIProductCatalog\Models\ProductProposal  $productProposal The ProductProposal to approve.
     * @return \Illuminate\Http\JsonResponse
     */
    public function approve(ApproveProductProposalRequest $request, ProductProposal $productProposal)
    {
        $adminUserId = $request->user() ? $request->user()->id : (Auth::id() ?? null);
        Log::debug('Admin ProductProposalController@approve: Attempting to approve product proposal.', ['admin_user_id' => $adminUserId, 'proposal_id' => $productProposal->id, 'request_data' => $request->all()]);

        // Authorization handled by ApproveProductProposalRequest->authorize()

        if ($productProposal->status !== 'pending' && $productProposal->status !== 'needs_revision') {
            Log::warning('Admin ProductProposalController@approve: Attempted to approve non-pending/non-revision proposal.', ['admin_user_id' => $adminUserId, 'proposal_id' => $productProposal->id, 'status' => $productProposal->status]);
            return response()->json(['message' => 'Proposal cannot be approved in its current status: ' . $productProposal->status . '.'], 400);
        }

        $validatedData = $request->validated();
        Log::debug('Admin ProductProposalController@approve: Validation passed via FormRequest.', ['admin_user_id' => $adminUserId, 'validated_data' => $validatedData]);

        DB::beginTransaction();
        Log::debug('Admin ProductProposalController@approve: Transaction started.', ['admin_user_id' => $adminUserId, 'proposal_id' => $productProposal->id]);
        try {
            $masterProduct = MasterProduct::create([
                'name' => $validatedData['name'],
                'slug' => \Illuminate\Support\Str::slug($validatedData['name']),
                'description' => $validatedData['description'],
                'brand_id' => $validatedData['brand_id'],
                'category_id' => $validatedData['category_id'],
                'specifications' => $validatedData['specifications'],
                'status' => $validatedData['status'],
                'created_by_proposal_id' => $productProposal->id,
            ]);
            Log::info('Admin ProductProposalController@approve: MasterProduct created from proposal.', ['admin_user_id' => $adminUserId, 'master_product_id' => $masterProduct->id, 'proposal_id' => $productProposal->id]);

            $productProposal->update([
                'status' => 'approved',
                'admin_notes' => $validatedData['admin_notes'],
                'approved_master_product_id' => $masterProduct->id,
            ]);
            Log::info('Admin ProductProposalController@approve: ProductProposal status updated to approved.', ['admin_user_id' => $adminUserId, 'proposal_id' => $productProposal->id]);

            DB::commit();
            Log::info('Admin ProductProposalController@approve: Transaction committed.', ['admin_user_id' => $adminUserId, 'proposal_id' => $productProposal->id, 'master_product_id' => $masterProduct->id]);

            // TODO: Notify shop owner of approval

            return response()->json($masterProduct->fresh()->load('brand:id,name', 'category:id,name'), 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Admin ProductProposalController@approve: Failed to approve product proposal, transaction rolled back.', [
                'admin_user_id' => $adminUserId,
                'proposal_id' => $productProposal->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Failed to approve proposal: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Reject a Product Proposal.
     * Updates the proposal's status to 'rejected' and records admin notes.
     *
     * @param  \IJIDeals\IJIProductCatalog\Http\Requests\Admin\RejectProductProposalRequest  $request
     * @param  \IJIDeals\IJIProductCatalog\Models\ProductProposal  $productProposal The ProductProposal to reject.
     * @return \Illuminate\Http\JsonResponse
     */
    public function reject(RejectProductProposalRequest $request, ProductProposal $productProposal)
    {
        $adminUserId = $request->user() ? $request->user()->id : (Auth::id() ?? null);
        Log::debug('Admin ProductProposalController@reject: Attempting to reject product proposal.', ['admin_user_id' => $adminUserId, 'proposal_id' => $productProposal->id, 'request_data' => $request->all()]);

        // Authorization handled by RejectProductProposalRequest->authorize()

        if ($productProposal->status !== 'pending' && $productProposal->status !== 'needs_revision') {
            Log::warning('Admin ProductProposalController@reject: Attempted to reject non-pending/non-revision proposal.', ['admin_user_id' => $adminUserId, 'proposal_id' => $productProposal->id, 'status' => $productProposal->status]);
            return response()->json(['message' => 'Proposal cannot be rejected in its current status: ' . $productProposal->status . '.'], 400);
        }

        $validatedData = $request->validated();
        Log::debug('Admin ProductProposalController@reject: Validation passed via FormRequest.', ['admin_user_id' => $adminUserId, 'validated_data' => $validatedData]);

        try {
            $productProposal->update([
                'status' => 'rejected',
                'admin_notes' => $validatedData['admin_notes'],
            ]);
            Log::info('Admin ProductProposalController@reject: Product proposal rejected.', ['admin_user_id' => $adminUserId, 'proposal_id' => $productProposal->id]);

            // TODO: Notify shop owner of rejection

            return response()->json(['message' => 'Proposal rejected successfully.'], 200);
        } catch (\Exception $e) {
            Log::error('Admin ProductProposalController@reject: Error rejecting product proposal.', [
                'admin_user_id' => $adminUserId,
                'proposal_id' => $productProposal->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Error rejecting proposal.'], 500);
        }
    }

    /**
     * Mark a Product Proposal as needing revision by the shop.
     * Updates the proposal's status to 'needs_revision' and records admin notes.
     *
     * @param  \IJIDeals\IJIProductCatalog\Http\Requests\Admin\NeedsRevisionProductProposalRequest  $request
     * @param  \IJIDeals\IJIProductCatalog\Models\ProductProposal  $productProposal The ProductProposal to mark.
     * @return \Illuminate\Http\JsonResponse
     */
    public function needsRevision(NeedsRevisionProductProposalRequest $request, ProductProposal $productProposal)
    {
        $adminUserId = $request->user() ? $request->user()->id : (Auth::id() ?? null);
        Log::debug('Admin ProductProposalController@needsRevision: Marking product proposal as needs revision.', ['admin_user_id' => $adminUserId, 'proposal_id' => $productProposal->id, 'request_data' => $request->all()]);

        // Authorization handled by NeedsRevisionProductProposalRequest->authorize()

        if ($productProposal->status !== 'pending') {
            Log::warning('Admin ProductProposalController@needsRevision: Attempted to mark non-pending proposal as needs revision.', ['admin_user_id' => $adminUserId, 'proposal_id' => $productProposal->id, 'status' => $productProposal->status]);
            return response()->json(['message' => 'Proposal can only be marked as "needs revision" if it is currently pending.'], 400);
        }

        $validatedData = $request->validated();
        Log::debug('Admin ProductProposalController@needsRevision: Validation passed via FormRequest.', ['admin_user_id' => $adminUserId, 'validated_data' => $validatedData]);

        try {
            $productProposal->update([
                'status' => 'needs_revision',
                'admin_notes' => $validatedData['admin_notes'],
            ]);
            Log::info('Admin ProductProposalController@needsRevision: Product proposal marked as needs revision.', ['admin_user_id' => $adminUserId, 'proposal_id' => $productProposal->id]);

            // TODO: Notify shop owner that revisions are needed

            return response()->json(['message' => 'Proposal marked as needs revision successfully.'], 200);
        } catch (\Exception $e) {
            Log::error('Admin ProductProposalController@needsRevision: Error marking proposal as needs revision.', [
                'admin_user_id' => $adminUserId,
                'proposal_id' => $productProposal->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Error marking proposal as needs revision.'], 500);
        }
    }
}
