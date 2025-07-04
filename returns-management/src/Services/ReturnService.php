<?php

namespace IJIDeals\ReturnsManagement\Services;

use IJIDeals\ReturnsManagement\Models\ReturnRequest;
use IJIDeals\ReturnsManagement\Models\ReturnItem;
use IJIDeals\IJIOrderManagement\Models\Order;
use IJIDeals\IJIOrderManagement\Models\OrderItem;
use IJIDeals\Inventory\Services\InventoryService;
use IJIDeals\Inventory\Models\InventoryLocation; // Assuming a default location for returns
use IJIDeals\NotificationsManager\Services\NotificationService; // Assuming a NotificationService
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;
use Exception;
use IJIDeals\ReturnsManagement\Events\ReturnRequested;
use IJIDeals\ReturnsManagement\Events\ReturnStatusUpdated;
use IJIDeals\ReturnsManagement\Events\RefundProcessed;

class ReturnService
{
    protected $inventoryService;
    protected $notificationService;

    public function __construct(InventoryService $inventoryService, NotificationService $notificationService)
    {
        $this->inventoryService = $inventoryService;
        $this->notificationService = $notificationService;
    }

    /**
     * Create a new return request.
     *
     * @param array $data
     * @return ReturnRequest
     * @throws Exception
     */
    public function createReturnRequest(array $data): ReturnRequest
    {
        return DB::transaction(function () use ($data) {
            $order = Order::findOrFail($data['order_id']);

            // Basic validation: ensure items belong to the order
            foreach ($data['items'] as $itemData) {
                $orderItem = OrderItem::where('id', $itemData['order_item_id'])
                                      ->where('order_id', $order->id)
                                      ->firstOrFail();
                if ($itemData['quantity'] <= 0 || $itemData['quantity'] > $orderItem->quantity) {
                    throw new Exception("Invalid quantity for order item #{$orderItem->id}.");
                }
            }

            $returnRequest = ReturnRequest::create([
                'user_id' => $order->user_id,
                'order_id' => $order->id,
                'reason' => $data['reason'] ?? null,
                'customer_notes' => $data['customer_notes'] ?? null,
                'status' => 'pending',
            ]);

            foreach ($data['items'] as $itemData) {
                $returnRequest->items()->create([
                    'order_item_id' => $itemData['order_item_id'],
                    'quantity' => $itemData['quantity'],
                    'refund_amount' => $itemData['refund_amount'] ?? null, // Can be set later
                    'condition' => $itemData['condition'] ?? null,
                    'reason' => $itemData['item_reason'] ?? null,
                ]);
            }

            // TODO: Integrate with file-management to handle uploaded proof images (e.g., $data['proof_images'])

            event(new ReturnRequested($returnRequest));
            // $this->notificationService->sendNotification($returnRequest->user, 'return_requested', $returnRequest);

            return $returnRequest;
        });
    }

    /**
     * Update the status of a return request.
     *
     * @param ReturnRequest $returnRequest
     * @param string $newStatus
     * @param string|null $adminNotes
     * @return ReturnRequest
     * @throws Exception
     */
    public function updateReturnStatus(ReturnRequest $returnRequest, string $newStatus, ?string $adminNotes = null): ReturnRequest
    {
        // Basic state machine for status transitions
        $allowedTransitions = [
            'pending' => ['approved', 'rejected'],
            'approved' => ['received', 'cancelled'],
            'received' => ['refunded', 'closed'],
            'refunded' => ['closed'],
            'rejected' => ['closed'],
            'cancelled' => ['closed'],
        ];

        if (!isset($allowedTransitions[$returnRequest->status]) || !in_array($newStatus, $allowedTransitions[$returnRequest->status])) {
            throw new Exception("Invalid status transition from {$returnRequest->status} to {$newStatus}.");
        }

        $returnRequest->admin_notes = $adminNotes;
        $returnRequest->status = $newStatus;

        // Update timestamps based on status
        if ($newStatus === 'approved' && is_null($returnRequest->approved_at)) {
            $returnRequest->approved_at = now();
        } elseif ($newStatus === 'received' && is_null($returnRequest->received_at)) {
            $returnRequest->received_at = now();
            $this->handleReceivedReturnItems($returnRequest); // Handle inventory
        } elseif ($newStatus === 'refunded' && is_null($returnRequest->refunded_at)) {
            $returnRequest->refunded_at = now();
            $this->processRefund($returnRequest); // Process refund
        }

        $returnRequest->save();

        event(new ReturnStatusUpdated($returnRequest, $oldStatus, $newStatus));
        // $this->notificationService->sendNotification($returnRequest->user, 'return_status_updated', $returnRequest);

        return $returnRequest;
    }

    /**
     * Handle inventory adjustments for received return items.
     *
     * @param ReturnRequest $returnRequest
     * @return void
     */
    protected function handleReceivedReturnItems(ReturnRequest $returnRequest): void
    {
        // Assuming a default location for returned items. This should be configurable.
        $returnLocation = InventoryLocation::firstOrCreate(['name' => 'Returns Warehouse']);

        foreach ($returnRequest->items as $returnItem) {
            $orderItem = $returnItem->orderItem;
            $stockable = $orderItem->masterProductVariation ?? $orderItem->masterProduct; // Or ShopProduct/ShopProductVariation

            if (!$stockable) {
                Log::warning("Stockable item not found for return item #{$returnItem->id}.");
                continue;
            }

            // Decide whether to restock or scrap based on condition or other rules
            if ($returnItem->condition === 'new' || $returnItem->condition === 'used') {
                // Restock the item
                $this->inventoryService->adjustStock(
                    $stockable,
                    $returnLocation,
                    $returnItem->quantity,
                    'return_restock',
                    "Returned item #{$returnItem->id} restocked.",
                    $returnRequest,
                    $returnRequest->user // User who initiated the return
                );
            } else {
                // Scrap the item (remove from inventory without adding to available stock)
                $this->inventoryService->adjustStock(
                    $stockable,
                    $returnLocation,
                    $returnItem->quantity,
                    'return_scrapped',
                    "Returned item #{$returnItem->id} scrapped due to condition: {$returnItem->condition}.",
                    $returnRequest,
                    $returnRequest->user
                );
            }
        }
    }

    /**
     * Process the refund for a return request.
     *
     * @param ReturnRequest $returnRequest
     * @return void
     */
    protected function processRefund(ReturnRequest $returnRequest): void
    {
        // This is a placeholder for actual payment gateway integration.
        // It would involve calling the payment service (e.g., from IJICommerce or a dedicated payment package)
        // to issue a refund for the total refund amount of the return request.

        $totalRefundAmount = $returnRequest->items->sum('refund_amount');

        if ($totalRefundAmount > 0) {
            Log::info("Processing refund for Return Request #{$returnRequest->id}. Amount: {$totalRefundAmount}");
            // Example: Call a payment service
            // $paymentService->issueRefund($returnRequest->order->payment_transaction_id, $totalRefundAmount);
        }

        event(new RefundProcessed($returnRequest, $totalRefundAmount));
    }
}
