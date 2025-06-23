<?php

namespace App\Services;

use App\Jobs\LogClickJob;
use App\Jobs\ProcessRedisBatchJob;
use App\Jobs\SendGoogleAnalyticsEventJob;
use App\Models\Click;
use App\Models\Link;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class ClickTrackingService
{
    /**
     * Track a click based on configured method
     */
    public function trackClick(Link $link, array $clickData): void
    {
        $method = config('shortener.analytics.click_tracking_method', 'queue');

        // Always send to Google Analytics if enabled (regardless of local tracking method)
        $this->sendToGoogleAnalytics($link, $clickData);

        switch ($method) {
            case 'redis':
                $this->trackViaRedis($link, $clickData);
                break;

            case 'none':
                // Only increment count, skip detailed tracking
                if (! $link->click_limit) {
                    DB::table('links')->where('id', $link->id)->increment('click_count');
                }
                break;

            case 'queue':
            default:
                $this->trackViaQueue($link, $clickData);
                break;
        }
    }

    /**
     * Track click using Redis for batch processing
     */
    private function trackViaRedis(Link $link, array $clickData): void
    {
        try {
            // Check if Redis is available
            if (! $this->isRedisAvailable()) {
                // Fallback to queue method
                $this->trackViaQueue($link, $clickData);

                return;
            }

            $prefix = config('shortener.analytics.redis.prefix', 'clicks:');
            $ttl = config('shortener.analytics.redis.ttl', 86400);

            // Store click data in Redis list
            $key = $prefix.'pending';
            $data = json_encode($clickData);

            Redis::rpush($key, $data);
            Redis::expire($key, $ttl);

            // Increment click count in Redis (for real-time stats)
            $countKey = $prefix.'count:'.$link->id;
            Redis::incr($countKey);
            Redis::expire($countKey, $ttl);

            // Dispatch batch processing job if queue is available
            $queueLength = Redis::llen($key);
            $triggerThreshold = config('shortener.analytics.redis.trigger_threshold', 100);

            // Trigger batch processing when we reach the threshold
            if ($queueLength >= $triggerThreshold) {
                ProcessRedisBatchJob::dispatch()->onQueue('clicks');

                // Log for debugging
                Log::info('Redis batch job dispatched', [
                    'queue_length' => $queueLength,
                    'threshold' => $triggerThreshold,
                    'link_id' => $link->id,
                ]);
            }

            // If link doesn't have click limit, we can skip DB increment
            // The batch processor will handle it
            if ($link->click_limit) {
                // Must increment in DB for accurate limit enforcement
                DB::table('links')->where('id', $link->id)->increment('click_count');
            }

        } catch (\Exception $e) {
            Log::warning('Redis click tracking failed, falling back to queue', [
                'error' => $e->getMessage(),
                'link_id' => $link->id,
            ]);

            // Fallback to queue method
            $this->trackViaQueue($link, $clickData);
        }
    }

    /**
     * Track click using Laravel queue (default method)
     */
    private function trackViaQueue(Link $link, array $clickData): void
    {
        if (config('shortener.analytics.async_tracking', true)) {
            LogClickJob::dispatch($clickData)->onQueue('clicks');
        } else {
            // Process synchronously when async tracking is disabled
            $job = new LogClickJob($clickData);
            $job->handle();
        }
    }

    /**
     * Process batch of clicks from Redis
     */
    public function processBatch(): int
    {
        if (! $this->isRedisAvailable()) {
            return 0;
        }

        $prefix = config('shortener.analytics.redis.prefix', 'clicks:');
        $batchSize = config('shortener.analytics.redis.batch_size', 100);
        $key = $prefix.'pending';

        $processed = 0;
        $clicks = [];
        $linkCounts = [];

        // Initialize geolocation service
        $geoService = null;
        if (class_exists(GeolocationService::class)) {
            $geoService = new GeolocationService;
        }

        // Get batch of clicks from Redis
        for ($i = 0; $i < $batchSize; $i++) {
            $data = Redis::lpop($key);
            if (! $data) {
                break;
            }

            $clickData = json_decode($data, true);
            if (! $clickData) {
                continue;
            }

            // Add geolocation data if not already present
            if ($geoService && ! empty($clickData['ip_address']) && empty($clickData['country'])) {
                $location = $geoService->getLocation($clickData['ip_address']);
                $clickData['country'] = $location['country'] ?? null;
                $clickData['city'] = $location['city'] ?? null;
                $clickData['region'] = $location['region'] ?? null;
            }

            // GA events are sent immediately during redirect, not during batch processing

            // Prepare for bulk insert
            unset($clickData['increment_click_count']); // Remove non-database field
            $clicks[] = $clickData;

            // Track which links need count increments
            if (! empty($clickData['link_id'])) {
                $linkId = $clickData['link_id'];
                $linkCounts[$linkId] = ($linkCounts[$linkId] ?? 0) + 1;
            }

            $processed++;
        }

        // Bulk insert clicks (with MySQL-compatible datetime format)
        if (! empty($clicks)) {
            // Convert datetime formats for MySQL compatibility
            foreach ($clicks as &$click) {
                if (isset($click['clicked_at'])) {
                    // Convert to MySQL datetime format (no microseconds)
                    $click['clicked_at'] = \Carbon\Carbon::parse($click['clicked_at'])->format('Y-m-d H:i:s');
                }
            }

            Click::insert($clicks);
        }

        // Update link counts in bulk
        foreach ($linkCounts as $linkId => $count) {
            // Check if this link has a click limit
            $link = Link::find($linkId);
            if ($link && ! $link->click_limit) {
                // Safe to increment since no limit
                DB::table('links')
                    ->where('id', $linkId)
                    ->increment('click_count', $count);
            }
        }

        return $processed;
    }

    /**
     * Get current click count from Redis or database
     */
    public function getClickCount(Link $link): int
    {
        if (! $this->isRedisAvailable()) {
            return $link->click_count;
        }

        $prefix = config('shortener.analytics.redis.prefix', 'clicks:');
        $countKey = $prefix.'count:'.$link->id;

        // Try Redis first
        $redisCount = Redis::get($countKey);
        if ($redisCount !== null) {
            return (int) $redisCount;
        }

        // Fall back to database
        return $link->click_count;
    }


    /**
     * Send click data to Google Analytics if enabled (original method for immediate sending)
     */
    private function sendToGoogleAnalytics(Link $link, array $clickData): void
    {
        // Prepare GA-specific click data
        $gaClickData = array_merge($clickData, [
            'link_slug' => $link->short_code,
            'destination_url' => $link->original_url,
            'click_id' => uniqid('click_', true),
            'session_id' => session()->getId(),
            // Add any additional fields that GA service expects
        ]);

        // Dispatch GA job asynchronously (non-blocking)
        try {
            SendGoogleAnalyticsEventJob::dispatch($gaClickData);
        } catch (\Exception $e) {
            Log::warning('Failed to dispatch GA event job', [
                'error' => $e->getMessage(),
                'link_id' => $link->id,
            ]);
        }
    }

    /**
     * Check if Redis is available
     */
    private function isRedisAvailable(): bool
    {
        try {
            Redis::ping();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
