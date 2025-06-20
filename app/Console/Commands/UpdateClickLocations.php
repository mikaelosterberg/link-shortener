<?php

namespace App\Console\Commands;

use App\Models\Click;
use App\Services\GeolocationService;
use Illuminate\Console\Command;

class UpdateClickLocations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clicks:update-locations 
                            {--batch=100 : Number of clicks to process per batch}
                            {--dry-run : Show what would be updated without making changes}
                            {--all : Process all clicks, including those with existing location data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update location data for clicks that are missing country/city information';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $batchSize = (int) $this->option('batch');
        $isDryRun = $this->option('dry-run');
        $processAll = $this->option('all');

        if ($isDryRun) {
            $this->info('Running in dry-run mode. No changes will be made.');
        }

        // Initialize geolocation service
        $geoService = new GeolocationService;

        if (! $geoService->isAvailable()) {
            $this->error('Geolocation service is not available. Please ensure MaxMind database is installed.');
            $this->line('Run: php artisan geoip:update');

            return 1;
        }

        // Build query for clicks needing location update
        $query = Click::query()
            ->whereNotNull('ip_address')
            ->where('ip_address', '!=', '');

        if (! $processAll) {
            $query->where(function ($q) {
                $q->whereNull('country')
                    ->orWhereNull('city');
            });
        }

        $totalClicks = $query->count();

        if ($totalClicks === 0) {
            $this->info('No clicks found that need location updates.');

            return 0;
        }

        $this->info("Found {$totalClicks} clicks to process.");

        if ($isDryRun) {
            // Show sample of clicks that would be updated
            $sampleClicks = $query->limit(5)->get();
            $this->table(
                ['ID', 'Link ID', 'IP Address', 'Current Country', 'Current City', 'Clicked At'],
                $sampleClicks->map(function ($click) {
                    return [
                        $click->id,
                        $click->link_id,
                        $click->ip_address,
                        $click->country ?? 'NULL',
                        $click->city ?? 'NULL',
                        $click->clicked_at->format('Y-m-d H:i:s'),
                    ];
                })
            );

            return 0;
        }

        // Process in batches
        $processed = 0;
        $updated = 0;
        $failed = 0;

        $bar = $this->output->createProgressBar($totalClicks);
        $bar->start();

        $privateIps = 0;

        $query->chunk($batchSize, function ($clicks) use ($geoService, &$processed, &$updated, &$failed, &$privateIps, $bar) {
            foreach ($clicks as $click) {
                try {
                    // Skip private/local IP addresses
                    if ($this->isPrivateIp($click->ip_address)) {
                        $privateIps++;
                        $processed++;
                        $bar->advance();

                        continue;
                    }

                    $location = $geoService->getLocation($click->ip_address);

                    if ($location['country'] || $location['city']) {
                        $click->update([
                            'country' => $location['country'],
                            'city' => $location['city'],
                        ]);
                        $updated++;
                    }
                } catch (\Exception $e) {
                    $failed++;
                    if (! $this->option('quiet')) {
                        $this->warn("\nFailed to get location for IP {$click->ip_address}: ".$e->getMessage());
                    }
                }

                $processed++;
                $bar->advance();

                // Small delay to avoid overwhelming the geolocation service
                if ($processed % 100 === 0) {
                    usleep(10000); // 10ms delay every 100 records
                }
            }
        });

        $bar->finish();
        $this->newLine(2);

        // Summary
        $this->info('Update complete!');
        $this->line("Processed: {$processed} clicks");
        $this->line("Updated: {$updated} clicks");

        if ($privateIps > 0) {
            $this->line("Skipped: {$privateIps} clicks (private/local IPs)");
        }

        if ($failed > 0) {
            $this->line("Failed: {$failed} clicks");
        }

        // Show statistics
        $stats = Click::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('COUNT(CASE WHEN country IS NOT NULL THEN 1 END) as with_country')
            ->selectRaw('COUNT(CASE WHEN city IS NOT NULL THEN 1 END) as with_city')
            ->first();

        $this->newLine();
        $this->info('Overall Statistics:');
        $this->line("Total clicks: {$stats->total}");
        $this->line("Clicks with country: {$stats->with_country} (".round($stats->with_country / $stats->total * 100, 1).'%)');
        $this->line("Clicks with city: {$stats->with_city} (".round($stats->with_city / $stats->total * 100, 1).'%)');

        return 0;
    }

    /**
     * Check if an IP address is private or local
     */
    private function isPrivateIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }
}
