<?php

namespace App\Jobs;

use App\Models\Link;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
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
            // Make HTTP request with redirects disabled to check the direct response
            $response = Http::timeout(20)
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
            $this->link->update([
                'last_checked_at' => now(),
                'health_status' => $healthStatus,
                'http_status_code' => $statusCode,
                'health_check_message' => $message,
                'final_url' => $finalUrl !== $this->link->original_url ? $finalUrl : null,
            ]);

        } catch (\Exception $e) {
            // Handle connection errors, timeouts, etc.
            $this->link->update([
                'last_checked_at' => now(),
                'health_status' => 'error',
                'http_status_code' => null,
                'health_check_message' => 'Failed to connect: ' . $e->getMessage(),
                'final_url' => null,
            ]);

            Log::warning('Link health check failed', [
                'link_id' => $this->link->id,
                'url' => $this->link->original_url,
                'error' => $e->getMessage(),
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

        // 4xx and 5xx are errors
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
            403 => 'Access forbidden',
            404 => 'Page not found',
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
