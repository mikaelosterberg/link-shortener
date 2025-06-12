<?php

namespace App\Jobs;

use App\Services\ClickTrackingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessRedisBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(ClickTrackingService $clickTracking): void
    {
        \Log::info('ProcessRedisBatchJob started');
        
        // Process a batch of clicks from Redis
        $processed = $clickTracking->processBatch();
        
        \Log::info('ProcessRedisBatchJob completed', ['processed' => $processed]);

        // If we processed clicks and there might be more, dispatch another job
        if ($processed > 0) {
            $batchSize = config('shortener.analytics.redis.batch_size', 100);

            // If we processed a full batch, there might be more pending
            if ($processed >= $batchSize) {
                // Dispatch another job to process more (with a small delay)
                self::dispatch()->onQueue('clicks')->delay(now()->addSeconds(1));
            }
        }
    }
}
