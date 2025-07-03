<?php

namespace IJIDeals\Social\Http\Controllers;

use App\Http\Controllers\Controller;
use IJIDeals\Social\Http\Resources\NotificationResource;
use IJIDeals\Social\Models\Notification;
use IJIDeals\UserManagement\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Needed for notifiable_type check
use OpenApi\Annotations as OA; // Import OpenApi namespace

/**
 * @OA\Tag(
 *     name="Notifications",
 *     description="API Endpoints for managing user notifications"
 * )
 */
class NotificationController extends Controller
{
    /**
     * Display a listing of the resource for the authenticated user.
     *
     * @OA\Get(
     *     path="/api/v1/social/notifications",
     *     operationId="listNotifications",
     *     summary="List all notifications for the authenticated user",
     *     tags={"Notifications"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *
     *         @OA\JsonContent(
     *             type="array",
     *
     *             @OA\Items(ref="#/components/schemas/Notification")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        // Assumes IJIDeals\UserManagement\Models\User uses Laravel's Notifiable trait
        $notifications = $user->notifications()->latest()->paginate();

        return NotificationResource::collection($notifications);
    }

    /**
     * Mark a specific notification as read.
     *
     * @OA\Patch(
     *     path="/api/v1/social/notifications/{notification}/read",
     *     operationId="markNotificationAsRead",
     *     summary="Mark a specific notification as read",
     *     tags={"Notifications"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="notification",
     *         in="path",
     *         required=true,
     *         description="ID of the notification to mark as read",
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Notification marked as read successfully",
     *
     *         @OA\JsonContent(ref="#/components/schemas/Notification")
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="This action is unauthorized.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Notification not found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="No query results for model [IJIDeals\\Social\\Models\\Notification] 1")
     *         )
     *     )
     * )
     */
    public function markAsRead(Request $request, Notification $notification)
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Check if the notification belongs to the authenticated user
        if ($notification->notifiable_id !== $user->id || $notification->notifiable_type !== User::class) {
            abort(403, 'This action is unauthorized.');
        }

        $notification->markAsRead(); // Standard Laravel method

        return new NotificationResource($notification);
    }

    /**
     * Mark all unread notifications for the authenticated user as read.
     *
     * @OA\Patch(
     *     path="/api/v1/social/notifications/mark-all-read",
     *     operationId="markAllNotificationsAsRead",
     *     summary="Mark all unread notifications for the authenticated user as read",
     *     tags={"Notifications"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="All notifications marked as read successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="All notifications marked as read.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function markAllAsRead(Request $request)
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user->unreadNotifications()->update(['read_at' => now()]); // Standard Laravel way

        return response()->json(['message' => 'All notifications marked as read.']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @OA\Delete(
     *     path="/api/v1/social/notifications/{notification}",
     *     operationId="deleteNotification",
     *     summary="Delete a specific notification",
     *     tags={"Notifications"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="notification",
     *         in="path",
     *         required=true,
     *         description="ID of the notification to delete",
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Response(
     *         response=204,
     *         description="Notification deleted successfully (No Content)"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="This action is unauthorized.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Notification not found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="No query results for model [IJIDeals\\Social\\Models\\Notification] 1")
     *         )
     *     )
     * )
     */
    public function destroy(Request $request, Notification $notification)
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Check if the notification belongs to the authenticated user
        if ($notification->notifiable_id !== $user->id || $notification->notifiable_type !== User::class) {
            abort(403, 'This action is unauthorized.');
        }

        $notification->delete();

        return response()->noContent();
    }
}
