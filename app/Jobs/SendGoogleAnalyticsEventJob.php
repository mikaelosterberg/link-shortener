<?php

namespace App\Jobs;

use App\Services\GoogleAnalyticsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendGoogleAnalyticsEventJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public $timeout = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $clickData
    ) {
        $this->onQueue('analytics');
    }

    /**
     * Execute the job.
     */
    public function handle(GoogleAnalyticsService $gaService): void
    {
        if (! $gaService->isEnabled()) {
            Log::debug('GA4 integration not enabled, skipping event');

            return;
        }

        $success = $gaService->sendClickEvent($this->clickData);

        if (! $success) {
            Log::warning('Failed to send GA4 event', [
                'click_data' => $this->clickData,
                'attempt' => $this->attempts(),
            ]);

            // If this is the final attempt, don't fail the job
            // GA tracking is not critical to the application
            if ($this->attempts() >= $this->tries) {
                Log::error('GA4 event failed after max attempts', [
                    'click_data' => $this->clickData,
                ]);
            } else {
                // Retry with exponential backoff
                $this->release(30 * $this->attempts());
            }
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('GA4 event job failed permanently', [
            'click_data' => $this->clickData,
            'error' => $exception->getMessage(),
        ]);
    }
}
