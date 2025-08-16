<?php

namespace App\Jobs;

use App\Models\Link;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CheckLinkHealthJob implements ShouldQueue
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
        public Link $link
    ) {
        $this->onQueue('health-checks');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Get timeout setting from cache (default 10 seconds)
            $timeout = Cache::get('health_check.timeout_seconds', 10);

            // Make HTTP request with redirects disabled to check the direct response
            $response = Http::timeout($timeout)
                ->withOptions([
                    'allow_redirects' => [
                        'max' => 10,
                        'track_redirects' => true,
                    ],
                    'http_errors' => false,
                ])
                ->withUserAgent('Mozilla/5.0 (compatible; LinkHealthChecker/1.0)')
                ->get($this->link->original_url);

            $statusCode = $response->status();
            $finalUrl = $response->header('X-Guzzle-Redirect-History')
                ? last(explode(', ', $response->header('X-Guzzle-Redirect-History')))
                : $this->link->original_url;

            // Determine health status based on response
            $healthStatus = $this->determineHealthStatus($statusCode, $finalUrl);
            $message = $this->generateHealthMessage($statusCode, $finalUrl);

            // Update link with health check results
            $updateData = [
                'last_checked_at' => now(),
                'health_status' => $healthStatus,
                'http_status_code' => $statusCode,
                'health_check_message' => $message,
                'final_url' => $finalUrl !== $this->link->original_url ? $finalUrl : null,
            ];

            // Track first failure if this is a new failure
            if ($healthStatus === 'error' && $this->link->health_status !== 'error' && ! $this->link->first_failure_detected_at) {
                $updateData['first_failure_detected_at'] = now();
            }

            // Reset failure tracking if link is now healthy
            if ($healthStatus === 'healthy' && $this->link->health_status !== 'healthy') {
                $updateData['first_failure_detected_at'] = null;
                $updateData['notification_count'] = 0;
                $updateData['notification_paused'] = false;
            }

            $this->link->update($updateData);

        } catch (\Exception $e) {
            // Handle connection errors, timeouts, etc.
            $errorMessage = $e->getMessage();

            // Check if this is a timeout
            $isTimeout = str_contains($errorMessage, 'cURL error 28') ||
                        str_contains($errorMessage, 'timed out') ||
                        str_contains($errorMessage, 'timeout');

            // Check if this is a redirect loop/limit issue
            $isRedirectIssue = str_contains($errorMessage, 'Will not follow more than') ||
                               str_contains($errorMessage, 'redirect') ||
                               str_contains($errorMessage, 'Too many redirects');

            // Determine status based on error type
            $healthStatus = $isTimeout ? 'timeout' : ($isRedirectIssue ? 'warning' : 'error');
            $message = $isTimeout ? 'Connection timeout' : 'Failed to connect: '.$errorMessage;

            $updateData = [
                'last_checked_at' => now(),
                'health_status' => $healthStatus,
                'http_status_code' => null,
                'health_check_message' => $message,
                'final_url' => null,
            ];

            // Track first failure if this is a new failure
            if (in_array($healthStatus, ['error', 'timeout']) &&
                ! in_array($this->link->health_status, ['error', 'timeout']) &&
                ! $this->link->first_failure_detected_at) {
                $updateData['first_failure_detected_at'] = now();
            }

            $this->link->update($updateData);

            Log::warning('Link health check failed', [
                'link_id' => $this->link->id,
                'url' => $this->link->original_url,
                'error' => $errorMessage,
                'is_redirect_issue' => $isRedirectIssue,
            ]);
        }
    }

    /**
     * Determine health status based on HTTP status code and final URL
     */
    private function determineHealthStatus(int $statusCode, string $finalUrl): string
    {
        // 2xx responses are healthy
        if ($statusCode >= 200 && $statusCode < 300) {
            // Check if URL redirected to a different domain
            if ($this->hasDifferentDomain($this->link->original_url, $finalUrl)) {
                return 'warning';
            }

            return 'healthy';
        }

        // 3xx redirects are warnings (shouldn't happen with our redirect following)
        if ($statusCode >= 300 && $statusCode < 400) {
            return 'warning';
        }

        // 401 Unauthorized and 403 Forbidden - often datacenter IP blocks or access restrictions
        if ($statusCode === 401 || $statusCode === 403) {
            return 'blocked';
        }

        // 404 Not Found and 410 Gone are definite errors
        if ($statusCode === 404 || $statusCode === 410) {
            return 'error';
        }

        // Other 4xx and 5xx are errors
        if ($statusCode >= 400) {
            return 'error';
        }

        // Default to error for unexpected cases
        return 'error';
    }

    /**
     * Generate a human-readable health check message
     */
    private function generateHealthMessage(int $statusCode, string $finalUrl): string
    {
        $messages = [
            200 => 'OK',
            301 => 'Permanent redirect',
            302 => 'Temporary redirect',
            401 => 'Unauthorized access',
            403 => 'Access blocked',
            404 => 'Page not found',
            410 => 'Page permanently removed',
            500 => 'Server error',
            502 => 'Bad gateway',
            503 => 'Service unavailable',
        ];

        $message = $messages[$statusCode] ?? "HTTP $statusCode";

        // Add redirect information if URL changed
        if ($finalUrl !== $this->link->original_url) {
            $message .= ' (redirected)';
        }

        return $message;
    }

    /**
     * Check if two URLs have different domains
     */
    private function hasDifferentDomain(string $url1, string $url2): bool
    {
        $domain1 = parse_url($url1, PHP_URL_HOST);
        $domain2 = parse_url($url2, PHP_URL_HOST);

        return $domain1 !== $domain2;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $this->link->update([
            'last_checked_at' => now(),
            'health_status' => 'error',
            'http_status_code' => null,
            'health_check_message' => 'Health check job failed',
            'final_url' => null,
        ]);
    }
}
