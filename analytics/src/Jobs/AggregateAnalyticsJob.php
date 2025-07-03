<?php

namespace IJIDeals\Analytics\Jobs;

use Carbon\Carbon;
use IJIDeals\Analytics\Models\TrackableInteraction;
use IJIDeals\Analytics\Models\TrackableStatsDaily;
use IJIDeals\Analytics\Models\TrackableView;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AggregateAnalyticsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Carbon $dateToProcess;

    public int $chunkSize;

    /**
     * Create a new job instance.
     *
     * @param  Carbon|null  $dateToProcess  The specific date to process. If null, processes yesterday's data.
     * @param  int  $chunkSize  The number of records to process in each chunk.
     */
    public function __construct(?Carbon $dateToProcess = null, int $chunkSize = 500)
    {
        $this->dateToProcess = $dateToProcess ?? Carbon::yesterday();
        $this->chunkSize = $chunkSize;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info('Starting AggregateAnalyticsJob for date: '.$this->dateToProcess->toDateString());

        $this->aggregateViews();
        $this->aggregateInteractions();
        // Potentially, a third step to combine view/interaction data if engagement_score calculation is complex
        // or if TrackableStatsDaily directly sums up different interaction types from TrackableInteraction.

        Log::info('Finished AggregateAnalyticsJob for date: '.$this->dateToProcess->toDateString());
    }

    protected function aggregateViews()
    {
        Log::info('Aggregating views for '.$this->dateToProcess->toDateString());
        TrackableView::whereDate('created_at', $this->dateToProcess)
            ->select([
                'trackable_type',
                'trackable_id',
                DB::raw('DATE(created_at) as aggregation_date'),
                DB::raw('COUNT(*) as views_count'),
                DB::raw('COUNT(DISTINCT user_id) as unique_views_count'), // Count distinct users if user_id is reliably present
            ])
            ->groupBy('trackable_type', 'trackable_id', 'aggregation_date')
            ->chunkById($this->chunkSize, function ($views) {
                foreach ($views as $viewStat) {
                    TrackableStatsDaily::updateOrCreate(
                        [
                            'trackable_type' => $viewStat->trackable_type,
                            'trackable_id' => $viewStat->trackable_id,
                            'date' => $viewStat->aggregation_date,
                        ],
                        [
                            'views_count' => DB::raw("views_count + {$viewStat->views_count}"),
                            'unique_views_count' => DB::raw("unique_views_count + {$viewStat->unique_views_count}"),
                            // Other fields will be updated by aggregateInteractions or later logic
                        ]
                    );
                }
                Log::info('Processed a chunk of '.count($views).' view aggregations.');
            });
        Log::info('Finished aggregating views for '.$this->dateToProcess->toDateString());
    }

    protected function aggregateInteractions()
    {
        Log::info('Aggregating interactions for '.$this->dateToProcess->toDateString());
        TrackableInteraction::whereDate('created_at', $this->dateToProcess)
            ->select([
                'trackable_type',
                'trackable_id',
                'interaction_type',
                DB::raw('DATE(created_at) as aggregation_date'),
                DB::raw('COUNT(*) as interaction_count'),
            ])
            ->groupBy('trackable_type', 'trackable_id', 'interaction_type', 'aggregation_date')
            ->chunkById($this->chunkSize, function ($interactions) {
                foreach ($interactions as $interactionStat) {
                    $statsDaily = TrackableStatsDaily::firstOrNew(
                        [
                            'trackable_type' => $interactionStat->trackable_type,
                            'trackable_id' => $interactionStat->trackable_id,
                            'date' => $interactionStat->aggregation_date,
                        ]
                    );

                    // Update specific interaction count columns if they exist (e.g., likes_count)
                    // Or update a JSON column 'interaction_summary'
                    $interactionSummary = $statsDaily->interaction_summary ?? [];
                    $currentTypeCount = $interactionSummary[$interactionStat->interaction_type] ?? 0;
                    $interactionSummary[$interactionStat->interaction_type] = $currentTypeCount + $interactionStat->interaction_count;

                    // Example for specific columns if they exist on TrackableStatsDaily
                    // if (Schema::hasColumn('trackable_stats_daily', $interactionStat->interaction_type . '_count')) {
                    //    $statsDaily->{$interactionStat->interaction_type . '_count'} =
                    //        ($statsDaily->{$interactionStat->interaction_type . '_count'} ?? 0) + $interactionStat->interaction_count;
                    // }

                    $statsDaily->interaction_summary = $interactionSummary;

                    // Recalculate engagement score or other derived metrics if necessary
                    // This might be better done after all interactions for a day are processed for an item
                    // $statsDaily->calculateEngagementScore(); // If such a method exists and considers interaction_summary

                    $statsDaily->save();
                }
                Log::info('Processed a chunk of '.count($interactions).' interaction aggregations.');
            });
        Log::info('Finished aggregating interactions for '.$this->dateToProcess->toDateString());
    }
}
