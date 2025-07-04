<?php

namespace IJIDeals\Analytics\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use IJIDeals\Analytics\Models\TrackableStatsDaily;
use IJIDeals\Analytics\Models\ActivityLog;
use IJIDeals\Analytics\Http\Resources\TrackableStatsResource; // Import resource
use IJIDeals\Analytics\Http\Resources\PlatformSummaryStatsResource; // Import resource
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB; // Import DB Facade for raw queries if needed
use Illuminate\Support\Facades\Config; // Import Config Facade

class AnalyticsController extends Controller
{
    /**
     * Get statistics for a specific trackable model.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $trackableTypeAlias (e.g., 'product', 'post')
     * @param  mixed  $trackableId
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Http\JsonResponse
     */
    public function getTrackableModelStats(Request $request, string $trackableTypeAlias, $trackableId)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d|after_or_equal:start_date',
            'period' => ['nullable', 'string', Rule::in(['last_7_days', 'last_30_days', 'last_90_days', 'this_month', 'last_month', 'all_time'])],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $modelClass = $this->mapTrackableType($trackableTypeAlias);
        if (!$modelClass) {
            return response()->json(['message' => 'Invalid trackable type alias provided.'], 400);
        }

        // Basic check if the model instance itself exists - optional, as stats might exist even if model was deleted
        // if (!app($modelClass)->find($trackableId)) {
        //     return response()->json(['message' => "Trackable model with ID {$trackableId} of type {$trackableTypeAlias} not found."], 404);
        // }

        $query = TrackableStatsDaily::where('trackable_type', $modelClass)
                                     ->where('trackable_id', $trackableId);

        list($startDate, $endDate) = $this->parseDateRange($request->input('period'), $request->input('start_date'), $request->input('end_date'));

        if ($startDate && $endDate) {
           $query->whereBetween('date', [$startDate, $endDate]);
        }

        // Authorization: Who can see these stats?
        // Gate::authorize('viewAnalytics', [app($modelClass), $trackableId]); // Example policy check

        $stats = $query->orderBy('date', 'asc')->get();

        return TrackableStatsResource::collection($stats);
    }

    /**
     * Get platform summary statistics.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \IJIDeals\Analytics\Http\Resources\PlatformSummaryStatsResource|\Illuminate\Http\JsonResponse
     */
    public function getPlatformSummaryStats(Request $request)
    {
        // Gate::authorize('viewPlatformAnalytics', ActivityLog::class); // Example policy check

        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d|after_or_equal:start_date',
            'period' => ['nullable', 'string', Rule::in(['last_7_days', 'last_30_days', 'last_90_days', 'this_month', 'last_month', 'all_time'])],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        list($startDate, $endDate) = $this->parseDateRange($request->input('period'), $request->input('start_date'), $request->input('end_date'));

        $totalViewsQuery = ActivityLog::where('event', Config::get('analytics.event_types.view', 'viewed')); // Use configured event type
        $totalUniqueVisitorsQuery = ActivityLog::distinct('user_id'); // Simplified: needs better session/fingerprint logic for true uniques
        $frequentEventsQuery = ActivityLog::select('event', DB::raw('count(*) as total'))
                                       ->groupBy('event')
                                       ->orderBy('total', 'desc')
                                       ->limit(Config::get('analytics.summary_top_events_limit', 5));

        if ($startDate && $endDate) {
            $totalViewsQuery->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()]);
            $totalUniqueVisitorsQuery->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()]);
            $frequentEventsQuery->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()]);
        }

        $totalViews = $totalViewsQuery->count();
        $totalUniqueVisitors = $totalUniqueVisitorsQuery->count('user_id'); // Count distinct user_id
        $frequentEvents = $frequentEventsQuery->get();

        return new PlatformSummaryStatsResource([
            'total_views' => $totalViews,
            'total_unique_visitors' => $totalUniqueVisitors,
            'top_events' => $frequentEvents,
            'period_start_date' => $startDate ? $startDate->toDateString() : null,
            'period_end_date' => $endDate ? $endDate->toDateString() : null,
        ]);
    }

    /**
     * Maps a string alias to a fully qualified model class name.
     * This should be configurable, e.g., via config('analytics.trackable_types').
     *
     * @param string $alias
     * @return string|null
     */
    protected function mapTrackableType(string $alias): ?string
    {
        $map = Config::get('analytics.trackable_types', [
            // Example mapping:
            // 'product' => \IJIDeals\IJIProductCatalog\Models\MasterProduct::class,
            // 'post' => \IJIDeals\Social\Models\Post::class,
            // 'shop' => \IJIDeals\IJICommerce\Models\Shop::class,
        ]);
        // For safety, ensure alias exists and corresponds to a real class
        if (isset($map[strtolower($alias)]) && class_exists($map[strtolower($alias)])) {
            return $map[strtolower($alias)];
        }
        return null;
    }

    /**
     * Helper to parse date range from request parameters or period alias.
     *
     * @param string|null $period
     * @param string|null $startDateInput
     * @param string|null $endDateInput
     * @return array [Carbon|null, Carbon|null]
     */
    protected function parseDateRange(?string $period, ?string $startDateInput, ?string $endDateInput): array
    {
        $startDate = $startDateInput ? Carbon::parse($startDateInput)->startOfDay() : null;
        $endDate = $endDateInput ? Carbon::parse($endDateInput)->endOfDay() : null;

        if ($period && $period !== 'all_time') { // 'all_time' means no date filtering from period
            switch ($period) {
                case 'last_7_days':
                    $startDate = Carbon::now()->subDays(6)->startOfDay();
                    $endDate = Carbon::now()->endOfDay();
                    break;
                case 'last_30_days':
                    $startDate = Carbon::now()->subDays(29)->startOfDay();
                    $endDate = Carbon::now()->endOfDay();
                    break;
                case 'last_90_days':
                    $startDate = Carbon::now()->subDays(89)->startOfDay();
                    $endDate = Carbon::now()->endOfDay();
                    break;
                case 'this_month':
                    $startDate = Carbon::now()->startOfMonth();
                    $endDate = Carbon::now()->endOfMonth(); // Or ->endOfDay() if you want up to current time of current day
                    break;
                case 'last_month':
                    $startDate = Carbon::now()->subMonthNoOverflow()->startOfMonth();
                    $endDate = Carbon::now()->subMonthNoOverflow()->endOfMonth();
                    break;
            }
        }
        // If only one date is provided, adjust the range (e.g., for a single day, or from/to date indefinitely)
        // For simplicity, if period is not 'all_time' and specific dates are given, they override period.
        // If period is 'all_time', startDate and endDate will be null, resulting in no date filtering.
        if ($period === 'all_time') {
            return [null, null];
        }

        return [$startDate, $endDate];
    }
}
