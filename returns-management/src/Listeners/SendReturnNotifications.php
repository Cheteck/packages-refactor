<?php

namespace IJIDeals\ReturnsManagement\Listeners;

use IJIDeals\ReturnsManagement\Events\ReturnRequested;
use IJIDeals\ReturnsManagement\Events\ReturnStatusUpdated;
use IJIDeals\ReturnsManagement\Events\RefundProcessed;
use IJIDeals\NotificationsManager\Services\NotificationService; // Assuming this service exists
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendReturnNotifications implements ShouldQueue
{
    use InteractsWithQueue;

    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function handleReturnRequested(ReturnRequested $event)
    {
        Log::info('Return requested event received.', ['return_id' => $event->returnRequest->id]);
        $this->notificationService->sendNotification(
            $event->returnRequest->user,
            'return_requested',
            ['returnRequest' => $event->returnRequest->toArray()]
        );
        // Notify admin/shop owner as well
    }

    public function handleReturnStatusUpdated(ReturnStatusUpdated $event)
    {
        Log::info('Return status updated event received.', [
            'return_id' => $event->returnRequest->id,
            'old_status' => $event->oldStatus,
            'new_status' => $event->newStatus,
        ]);
        $this->notificationService->sendNotification(
            $event->returnRequest->user,
            'return_status_updated',
            [
                'returnRequest' => $event->returnRequest->toArray(),
                'oldStatus' => $event->oldStatus,
                'newStatus' => $event->newStatus,
            ]
        );
    }

    public function handleRefundProcessed(RefundProcessed $event)
    {
        Log::info('Refund processed event received.', [
            'return_id' => $event->returnRequest->id,
            'amount' => $event->amount,
        ]);
        $this->notificationService->sendNotification(
            $event->returnRequest->user,
            'refund_processed',
            [
                'returnRequest' => $event->returnRequest->toArray(),
                'amount' => $event->amount,
            ]
        );
    }
}
