<?php

namespace App\Console\Commands;

use App\Jobs\ProcessRedisBatchJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class ProcessPendingClicks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clicks:process-pending 
                            {--force : Process even if below threshold}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process any pending clicks in Redis (scheduled task)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Check if Redis tracking is enabled
        if (config('shortener.analytics.click_tracking_method') !== 'redis') {
            return Command::SUCCESS; // Silently exit if not using Redis tracking
        }

        $prefix = config('shortener.analytics.redis.prefix', 'clicks:');
        $key = $prefix.'pending';
        $pendingCount = Redis::llen($key);

        if ($pendingCount === 0) {
            $this->info('No pending clicks to process.');

            return Command::SUCCESS;
        }

        $threshold = config('shortener.analytics.redis.trigger_threshold', 100);
        $force = $this->option('force');

        // Only process if we have pending clicks and either:
        // 1. Force flag is set, OR
        // 2. We're below threshold (cleanup stragglers)
        if ($force || $pendingCount < $threshold) {
            $this->info("Processing {$pendingCount} pending clicks...");
            ProcessRedisBatchJob::dispatch()->onQueue('clicks');

            return Command::SUCCESS;
        }

        $this->info("Pending clicks ({$pendingCount}) above threshold ({$threshold}). Will be processed automatically.");

        return Command::SUCCESS;
    }
}
