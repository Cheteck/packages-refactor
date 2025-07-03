<?php

namespace IJIDeals\Analytics\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RecordViewJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $trackable;

    protected $data;

    /**
     * Create a new job instance.
     *
     * @param  mixed  $trackable
     * @return void
     */
    public function __construct($trackable, array $data)
    {
        $this->trackable = $trackable;
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            // Ensure $this->trackable is an Eloquent model
            if (! method_exists($this->trackable, 'views')) {
                Log::error('Trackable model does not have a views relationship.', [
                    'trackable_type' => get_class($this->trackable),
                    'trackable_id' => $this->trackable->id ?? null,
                ]);

                return;
            }

            // Data expected for TrackableView model based on its fillable fields:
            // 'user_id', 'source', 'session_id', 'device_type', 'ip_address', 'referrer'
            // These should be present in $this->data

            // Basic validation of required data fields can be added here if necessary
            // For example, ensuring ip_address is present if it's non-nullable in your logic

            $this->trackable->views()->create($this->data);

            Log::info('TrackableView recorded successfully.', [
                'trackable_type' => get_class($this->trackable),
                'trackable_id' => $this->trackable->id,
                'data' => $this->data,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to record TrackableView.', [
                'trackable_type' => get_class($this->trackable),
                'trackable_id' => $this->trackable->id ?? 'unknown',
                'data' => $this->data,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(), // Optional: for detailed debugging
            ]);
            // Optionally re-throw or handle specific exceptions if needed
        }
    }
}
