<?php

namespace IJIDeals\NotificationsManager\Http\Controllers;

use IJIDeals\NotificationsManager\Services\UserNotificationPreferenceServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;

/**
 * @OA\Schema(
 *     schema="NotificationPreferenceItem",
 *     type="object",
 *     description="Represents a single notification preference setting for a channel.",
 *     example={
 *         "email": true,
 *         "sms": false
 *     },
 *     additionalProperties={
 *         "type": "boolean",
 *         "description": "Whether the channel is enabled (true) or disabled (false)."
 *     }
 * )
 * @OA\Schema(
 *     schema="NotificationPreferences",
 *     type="object",
 *     description="User's notification preferences, grouped by notification type.",
 *     example={
 *         "new_product_alert": {
 *             "email": true,
 *             "sms": false
 *         },
 *         "order_status_update": {
 *             "email": true,
 *             "push": true
 *         }
 *     },
 *     additionalProperties={
 *         "$ref": "#/components/schemas/NotificationPreferenceItem"
 *     }
 * )
 */
class NotificationPreferenceController extends Controller
{
    protected UserNotificationPreferenceServiceInterface $preferenceService;

    public function __construct(UserNotificationPreferenceServiceInterface $preferenceService)
    {
        $this->preferenceService = $preferenceService;
    }

    /**
     * @OA\Get(
     *     path="/api/user/notification-preferences",
     *     summary="Get current user's notification preferences",
     *     tags={
     *         "User Notifications"
     *     },
     *     security={
     *         {"sanctum": {}}
     *     },
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *
     *         @OA\JsonContent(
     *             type="object",
     *             ref="#/components/schemas/NotificationPreferences"
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     *
     * Get the current user's notification preferences.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $preferences = $this->preferenceService->getFormattedPreferences($user);

        return response()->json($preferences);
    }

    /**
     * @OA\Put(
     *     path="/api/user/notification-preferences",
     *     summary="Update current user's notification preferences",
     *     tags={
     *         "User Notifications"
     *     },
     *     security={
     *         {"sanctum": {}}
     *     },
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Notification preferences to update",
     *
     *         @OA\JsonContent(
     *             type="object",
     *             ref="#/components/schemas/NotificationPreferences"
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Preferences updated successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Preferences updated successfully.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Invalid preference data",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Invalid preference data."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="An error occurred while updating preferences",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="An error occurred while updating preferences.")
     *         )
     *     )
     * )
     *
     * Update the current user's notification preferences.
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        // Basic validation for the overall structure
        $validated = $request->validate([
            'preferences' => 'required|array',
            'preferences.*' => 'required|array', // Each notification type must be an array of channels
            'preferences.*.*' => 'required|boolean', // Each channel setting must be a boolean
        ]);

        try {
            $this->preferenceService->updateMultiplePreferences($user, $validated['preferences']);

            return response()->json(['message' => 'Preferences updated successfully.']);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Invalid preference data.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            // Log::error('Error updating notification preferences: ' . $e->getMessage());
            return response()->json(['message' => 'An error occurred while updating preferences.'], 500);
        }
    }
}
