<?php

namespace IJIDeals\Subscriptions\Http\Controllers;

use IJIDeals\Payments\Contracts\PaymentServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller; // From Soulbscription
use LucasDotVin\Soulbscription\Models\Plan; // For processing payments

class SubscriptionController extends Controller
{
    protected ?PaymentServiceInterface $paymentService;

    public function __construct()
    {
        // Conditionally inject PaymentService if ijideals/payments is available
        if (interface_exists(PaymentServiceInterface::class)) {
            $this->paymentService = resolve(PaymentServiceInterface::class);
        } else {
            $this->paymentService = null;
        }
    }

    /**
     * List available subscription plans.
     */
    public function indexPlans(): JsonResponse
    {
        // Assuming Plans are seeded or managed via Soulbscription's mechanisms
        $plans = Plan::where('is_active', true) // Example: only active plans
            ->orderBy('price') // Soulbscription Plan model has price, currency
            ->get()
            ->map(function (Plan $plan) {
                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'description' => $plan->description, // If you add this to Soulbscription's Plan
                    'price' => $plan->price,
                    'currency' => $plan->currency,
                    'periodicity_type' => $plan->periodicity_type,
                    'periodicity' => $plan->periodicity,
                    'features' => $plan->features->map(function ($feature) {
                        return [
                            'name' => $feature->name,
                            'code' => $feature->code,
                            'value' => $feature->pivot->value, // Value of the feature for this plan
                        ];
                    }),
                    // Add any other relevant plan details
                ];
            });

        return response()->json(['data' => $plans]);
    }

    /**
     * Get the current user's active subscription.
     */
    public function showUserSubscription(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user || ! method_exists($user, 'subscription')) {
            return response()->json(['message' => 'User not authenticated or model not subscribable.'], 403);
        }

        $activeSubscription = $user->subscription(); // Soulbscription's method for current active one

        if (! $activeSubscription) {
            return response()->json(['message' => 'No active subscription found.'], 404);
        }

        $plan = $activeSubscription->plan;

        return response()->json([
            'data' => [
                'plan_name' => $plan->name,
                'status' => $activeSubscription->getRawStatus(), // 'active', 'expired', 'cancelled'
                'ends_at' => $activeSubscription->ends_at ? $activeSubscription->ends_at->toIso8601String() : null,
                'started_at' => $activeSubscription->started_at ? $activeSubscription->started_at->toIso8601String() : null,
                'grace_days_ended_at' => $activeSubscription->grace_days_ended_at ? $activeSubscription->grace_days_ended_at->toIso8601String() : null,
                'features' => $user->activeFeatures()->map(fn ($feature) => [
                    'name' => $feature->name, 'code' => $feature->code, 'value' => $feature->pivot->value,
                ]),
            ],
        ]);
    }

    /**
     * Subscribe the user to a new plan.
     * This is a simplified example. Real implementation needs payment processing.
     */
    public function subscribe(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! method_exists($user, 'subscribeTo')) {
            return response()->json(['message' => 'User not authenticated or model not subscribable.'], 403);
        }

        $validated = $request->validate([
            'plan_id' => 'required|exists:'.(new Plan)->getTable().',id',
            'payment_method_token' => 'sometimes|string', // e.g., Stripe token, PayPal nonce
            'payment_method_id' => 'sometimes|string', // e.g., existing Stripe pm_xxx
        ]);

        $plan = Plan::find($validated['plan_id']);
        if (! $plan) {
            return response()->json(['message' => 'Plan not found.'], 404);
        }

        // --- Payment Processing Step (Simplified) ---
        // In a real scenario, you would:
        // 1. Create a customer on the payment gateway if not exists (via ijideals/payments)
        // 2. Add payment method to customer (if new token/details provided)
        // 3. Charge the initial amount for the subscription OR set up recurring payment profile
        // This step heavily depends on the payment gateway and ijideals/payments capabilities.

        if ($this->paymentService && $plan->price > 0) {
            // This is a placeholder for actual payment logic
            // You'd need to determine payment details (new token or stored method)
            // and pass them to $this->paymentService->processPayment or a specific
            // method for setting up recurring payments.
            // For now, we'll assume payment is handled externally or is free.
            if (empty($validated['payment_method_token']) && empty($validated['payment_method_id'])) {
                // return response()->json(['message' => 'Payment method required for paid plans.'], 422);
                // For now, let's bypass for testing structure
            }
            // $transaction = $this->paymentService->processPayment($plan, $plan->price, $plan->currency, $paymentDetails, $user);
            // if ($transaction->status !== 'succeeded') {
            //     return response()->json(['message' => 'Payment failed.', 'details' => $transaction->error_message], 402);
            // }
        }
        // --- End Payment Processing Step ---

        try {
            // Soulbscription's subscribeTo method
            // It might take start_date, charge_id etc. as parameters depending on Soulbscription version and setup
            $subscription = $user->subscribeTo($plan);

            // If payment was processed, you might link $transaction->id to the subscription metadata
            // $subscription->update(['gateway_charge_id' => $transaction->gateway_transaction_id]);

            return response()->json([
                'message' => 'Successfully subscribed to '.$plan->name,
                'data' => [ // Return some details about the new subscription
                    'plan_name' => $plan->name,
                    'ends_at' => $subscription->ends_at ? $subscription->ends_at->toIso8601String() : null,
                ],
            ], 201);

        } catch (\Exception $e) {
            // Log::error("Subscription error for user {$user->id} to plan {$plan->id}: " . $e->getMessage());
            return response()->json(['message' => 'Could not subscribe to the plan.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Cancel the user's current active subscription.
     */
    public function cancelSubscription(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! method_exists($user, 'subscription')) {
            return response()->json(['message' => 'User not authenticated or model not subscribable.'], 403);
        }

        $subscription = $user->subscription();

        if (! $subscription) {
            return response()->json(['message' => 'No active subscription to cancel.'], 404);
        }

        try {
            // Soulbscription's cancel method
            // It can take `now` (cancel immediately) or `at_period_end` (default)
            $subscription->cancel($request->input('immediately', false) ? 'now' : 'at_period_end');

            return response()->json(['message' => 'Subscription cancellation processed.']);
        } catch (\Exception $e) {
            // Log::error("Cancellation error for user {$user->id} subscription {$subscription->id}: " . $e->getMessage());
            return response()->json(['message' => 'Could not cancel the subscription.', 'error' => $e->getMessage()], 500);
        }
    }

    // Other methods could include:
    // - switchPlan()
    // - resumeSubscription()
    // - updatePaymentMethodForSubscription() (integrates with ijideals/payments)
}
