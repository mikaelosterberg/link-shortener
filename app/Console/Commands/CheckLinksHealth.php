<?php

namespace App\Console\Commands;

use App\Jobs\CheckLinkHealthJob;
use App\Models\Link;
use Illuminate\Console\Command;

class CheckLinksHealth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'links:check-health 
                            {--batch=50 : Number of links to check per batch}
                            {--all : Check all links regardless of last check time}
                            {--status= : Check only links with specific health status}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check the health status of links by making HTTP requests to their destinations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $batchSize = (int) $this->option('batch');
        $checkAll = $this->option('all');
        $statusFilter = $this->option('status');

        $this->info('Starting link health check...');

        // Build query - only check active, non-expired links
        $query = Link::query()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });

        // Apply filters
        if (! $checkAll) {
            $query->needsHealthCheck();
        }

        if ($statusFilter) {
            $query->where('health_status', $statusFilter);
        }

        // Get total count
        $totalLinks = $query->count();

        if ($totalLinks === 0) {
            $this->info('No links need checking at this time.');

            return Command::SUCCESS;
        }

        $this->info("Found {$totalLinks} links to check.");
        $bar = $this->output->createProgressBar($totalLinks);
        $bar->start();

        // Process in batches
        $processed = 0;
        $query->chunk($batchSize, function ($links) use (&$processed, $bar, $batchSize) {
            foreach ($links as $link) {
                CheckLinkHealthJob::dispatch($link);
                $processed++;
                $bar->advance();
            }

            // Add a small delay between batches to avoid overwhelming the queue
            if ($processed % $batchSize === 0) {
                sleep(1);
            }
        });

        $bar->finish();
        $this->newLine();

        $this->info("Dispatched {$processed} health check jobs to the queue.");
        $this->line('');
        $this->line('Run the queue worker to process these jobs:');
        $this->line('php artisan queue:work --queue=health-checks');
        $this->line('');
        $this->line('After health checks complete, send notifications with:');
        $this->line('php artisan notifications:send health');

        return Command::SUCCESS;
    }
}
