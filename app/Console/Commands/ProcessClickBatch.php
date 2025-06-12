<?php

namespace App\Console\Commands;

use App\Services\ClickTrackingService;
use Illuminate\Console\Command;

class ProcessClickBatch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clicks:process-batch 
                            {--limit=1000 : Maximum clicks to process}
                            {--dry-run : Show what would be processed without actually processing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process batched click data from Redis';

    /**
     * Execute the console command.
     */
    public function handle(ClickTrackingService $clickTracking): int
    {
        // Check if Redis tracking is enabled
        if (config('shortener.analytics.click_tracking_method') !== 'redis') {
            $this->warn('Redis click tracking is not enabled. Set CLICK_TRACKING_METHOD=redis in your .env file.');

            return Command::FAILURE;
        }

        $limit = (int) $this->option('limit');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('Running in dry-run mode. No clicks will be processed.');
        }

        $this->info('Processing click batch...');

        $totalProcessed = 0;
        $batchSize = config('shortener.analytics.redis.batch_size', 100);
        $iterations = ceil($limit / $batchSize);

        for ($i = 0; $i < $iterations; $i++) {
            if ($dryRun) {
                // Just show what would happen
                $this->line('Would process batch '.($i + 1)." of up to {$batchSize} clicks");

                continue;
            }

            $processed = $clickTracking->processBatch();

            if ($processed === 0) {
                // No more clicks to process
                break;
            }

            $totalProcessed += $processed;
            $this->line('Processed batch '.($i + 1).": {$processed} clicks");

            if ($totalProcessed >= $limit) {
                break;
            }
        }

        if (! $dryRun) {
            $this->info("Total clicks processed: {$totalProcessed}");
        }

        return Command::SUCCESS;
    }
}
