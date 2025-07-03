<?php

namespace IJIDeals\IJIProductCatalog\Policies;

use IJIDeals\IJIProductCatalog\Models\ProductProposal;
use IJIDeals\IJICommerce\Models\Shop; // Shop model remains in IJICommerce
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Log;

class ProductProposalPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     * (e.g., listing their own proposals)
     */
    public function viewAny(User $user): bool
    {
        $can = $user->exists();
        Log::info('ProductProposalPolicy: viewAny check.', ['user_id' => $user->id, 'can' => $can]);
        return $can;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ProductProposal $productProposal): bool
    {
        // User can view a proposal if they are an admin/owner of the shop that submitted it.
        $can = $user->hasRole(['Owner', 'Administrator'], $productProposal->shop);
        Log::info('ProductProposalPolicy: view check.', ['user_id' => $user->id, 'proposal_id' => $productProposal->id, 'can' => $can]);
        return $can;
    }

    /**
     * Determine whether the user can create models.
     * This policy method is slightly different as it's about creating a proposal *for* a shop.
     * So, the $shop instance is passed from the controller.
     */
    public function createProposal(User $user, Shop $shop): bool
    {
        // User can create a proposal for a shop if they are an admin/owner of that shop.
        $can = $user->hasRole(['Owner', 'Administrator'], $shop);
        Log::info('ProductProposalPolicy: createProposal check.', ['user_id' => $user->id, 'shop_id' => $shop->id, 'can' => $can]);
        return $can;
    }

    /**
     * Determine whether the user can update the model.
     * (Shops might only update proposals if they are still 'pending' or 'needs_revision')
     */
    public function update(User $user, ProductProposal $productProposal): bool
    {
        if (!in_array($productProposal->status, ['pending', 'needs_revision'])) {
            Log::info('ProductProposalPolicy: update denied due to status.', ['user_id' => $user->id, 'proposal_id' => $productProposal->id, 'status' => $productProposal->status]);
            return false;
        }
        $can = $user->hasRole(['Owner', 'Administrator'], $productProposal->shop);
        Log::info('ProductProposalPolicy: update check.', ['user_id' => $user->id, 'proposal_id' => $productProposal->id, 'can' => $can]);
        return $can;
    }

    /**
     * Determine whether the user can delete the model.
     * (Shops might only delete proposals if they are still 'pending')
     */
    public function delete(User $user, ProductProposal $productProposal): bool
    {
        if ($productProposal->status !== 'pending') {
            Log::info('ProductProposalPolicy: delete denied due to status.', ['user_id' => $user->id, 'proposal_id' => $productProposal->id, 'status' => $productProposal->status]);
            return false;
        }
        $can = $user->hasRole(['Owner', 'Administrator'], $productProposal->shop);
        Log::info('ProductProposalPolicy: delete check.', ['user_id' => $user->id, 'proposal_id' => $productProposal->id, 'can' => $can]);
        return $can;
    }
}
